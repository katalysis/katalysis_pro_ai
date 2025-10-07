<?php

namespace KatalysisProAi;

use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\Messages\AbstractMessage;
use KatalysisProAi\Entity\Chat;
use Concrete\Core\Support\Facade\Database;

/**
 * Database-based chat history for Neuron AI compatibility
 * Stores chat history in the Chat entity's chatHistory field
 */
class DatabaseChatHistory extends AbstractChatHistory
{
    private ?Chat $chatEntity = null;
    private string $sessionId;

    public function __construct(string $sessionId, int $maxMessages = 2000)
    {
        $this->sessionId = $sessionId;
        $this->maxMessages = $maxMessages;
        $this->loadChatEntity();
    }

    /**
     * Load or create the Chat entity for this session
     */
    private function loadChatEntity(): void
    {
        $db = Database::get();
        $entityManager = $db->getEntityManager();
        
        // Try to find existing chat by session ID
        $chat = $entityManager->getRepository(Chat::class)
            ->findOneBy(['sessionId' => $this->sessionId]);
        
        if (!$chat) {
            // Create new chat entity
            $chat = new Chat();
            $chat->setSessionId($this->sessionId);
            $chat->setStarted(new \DateTime());
            $chat->setCreatedDate(new \DateTime());
            $chat->setChatHistory('');
            $entityManager->persist($chat);
            $entityManager->flush();
        }
        
        $this->chatEntity = $chat;
        
        // Load existing messages from chatHistory field
        if (!empty($chat->getChatHistory())) {
            $historyData = json_decode($chat->getChatHistory(), true);
            if (is_array($historyData)) {
                foreach ($historyData as $messageData) {
                    $this->messages[] = $this->deserializeMessage($messageData);
                }
            }
        }
    }

    /**
     * Add a message to the chat history
     */
    public function addMessage(AbstractMessage $message): void
    {
        $this->messages[] = $message;
        $this->save();
    }

    /**
     * Get all messages in the chat history
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Clear all messages from the chat history
     */
    public function clear(): void
    {
        $this->messages = [];
        $this->save();
    }

    /**
     * Save the current messages to the database
     */
    private function save(): void
    {
        if (!$this->chatEntity) {
            return;
        }

        // Serialize messages to JSON
        $historyData = array_map(function($message) {
            return $this->serializeMessage($message);
        }, $this->messages);

        // Limit the number of messages
        if (count($historyData) > $this->maxMessages) {
            $historyData = array_slice($historyData, -$this->maxMessages);
        }

        $db = Database::get();
        $entityManager = $db->getEntityManager();
        
        $this->chatEntity->setChatHistory(json_encode($historyData));
        $entityManager->persist($this->chatEntity);
        $entityManager->flush();
    }

    /**
     * Serialize a message to an array
     */
    private function serializeMessage(AbstractMessage $message): array
    {
        return [
            'role' => $message->role,
            'content' => $message->content,
            'type' => get_class($message)
        ];
    }

    /**
     * Deserialize a message from an array
     */
    private function deserializeMessage(array $data): AbstractMessage
    {
        $className = $data['type'] ?? null;
        
        if ($className && class_exists($className)) {
            return new $className($data['content']);
        }
        
        // Fallback to basic message types
        if ($data['role'] === 'user') {
            return new \NeuronAI\Chat\Messages\UserMessage($data['content']);
        } else {
            return new \NeuronAI\Chat\Messages\AssistantMessage($data['content']);
        }
    }
}
