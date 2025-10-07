<?php

namespace KatalysisProAi;

use NeuronAI\Chat\History\AbstractChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\Messages\Message;
use Concrete\Core\Support\Facade\Application;
use KatalysisProAi\Entity\Chat;

/**
 * Database-based chat history implementation that uses the Chat entity's
 * chatHistory field to store Neuron AI conversation history in the database
 * instead of files. Uses Neuron AI compatible format (role, content, usage).
 */
class DatabaseChatHistory extends AbstractChatHistory
{
    private $app;
    private $entityManager;
    private ?int $chatId = null;
    private ?Chat $chat = null;

    public function __construct(int $contextWindow = 50000, ?int $chatId = null)
    {
        parent::__construct($contextWindow);
        
        $this->app = Application::getFacadeApplication();
        $this->entityManager = $this->app->make('Doctrine\ORM\EntityManager');
        $this->chatId = $chatId;
        
        // Load existing messages if chat ID is provided
        if ($this->chatId) {
            $this->loadExistingMessages();
        }
    }

    /**
     * Set the chat ID for this history instance
     */
    public function setChatId(int $chatId): self
    {
        $this->chatId = $chatId;
        $this->loadExistingMessages();
        return $this;
    }

    /**
     * Load existing messages from the database
     */
    private function loadExistingMessages(): void
    {
        if (!$this->chatId) {
            return;
        }

        try {
            $this->chat = $this->entityManager->find(Chat::class, $this->chatId);
            if ($this->chat && $this->chat->getChatHistory()) {
                $historyData = json_decode($this->chat->getChatHistory(), true);
                if (is_array($historyData) && !empty($historyData)) {
                    $this->history = $this->deserializeMessages($historyData);
                }
            }
        } catch (\Exception $e) {
            // Continue with empty history if loading fails
            $this->history = [];
        }
    }

    /**
     * Store a single message to the database
     */
    protected function storeMessage(Message $message): ChatHistoryInterface
    {
        if (!$this->chatId) {
            return $this;
        }

        try {
            // Ensure we have the chat entity
            if (!$this->chat) {
                $this->chat = $this->entityManager->find(Chat::class, $this->chatId);
            }

            if ($this->chat) {
                // Convert all messages to JSON for storage
                $messagesData = array_map(function (Message $msg) {
                    return $this->serializeMessage($msg);
                }, $this->history);

                $this->chat->setChatHistory(json_encode($messagesData, JSON_PRETTY_PRINT));
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            // Don't throw exception - continue without persistence
        }

        return $this;
    }

    /**
     * Remove old message from database storage
     */
    public function removeOldMessage(int $index): ChatHistoryInterface
    {
        // The parent class already handles removing from the in-memory $history array
        // We just need to update the database with the current state
        if ($this->chatId && $this->chat) {
            try {
                $messagesData = array_map(function (Message $msg) {
                    return $this->serializeMessage($msg);
                }, $this->history);

                $this->chat->setChatHistory(json_encode($messagesData, JSON_PRETTY_PRINT));
                $this->entityManager->flush();
            } catch (\Exception $e) {
                error_log('DatabaseChatHistory: Failed to remove old message: ' . $e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Clear all messages from database
     */
    protected function clear(): ChatHistoryInterface
    {
        if ($this->chatId && $this->chat) {
            try {
                $this->chat->setChatHistory('');
                $this->entityManager->flush();
            } catch (\Exception $e) {
                error_log('DatabaseChatHistory: Failed to clear messages: ' . $e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Serialize a message for database storage
     * Uses the exact same format as Neuron AI's FileChatHistory for compatibility
     */
    private function serializeMessage(Message $message): array
    {
        // Use Neuron AI's standard jsonSerialize method to maintain compatibility
        return $message->jsonSerialize();
    }



    /**
     * Get the current chat ID
     */
    public function getChatId(): ?int
    {
        return $this->chatId;
    }

    /**
     * Static factory method for easier instantiation
     */
    public static function forChat(int $chatId, int $contextWindow = 50000): self
    {
        return new self($contextWindow, $chatId);
    }
}
