<?php

namespace KatalysisProAi;

use Concrete\Core\Support\Facade\Config;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\SystemPrompt;
use KatalysisProAi\DatabaseChatHistory;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use KatalysisProAi\TypesenseVectorStoreFactory;
use KatalysisProAi\PageIndexService;
use KatalysisProAi\ActionService;

class RagAgent extends RAG
{
    protected $app;
    private ?DatabaseChatHistory $chatHistoryInstance = null;

    public function setApp($app): void
    {
        $this->app = $app;
    }
    
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            Config::get('katalysis.ai.open_ai_key'),
            Config::get('katalysis.ai.open_ai_model')
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return new OpenAIEmbeddingsProvider(
            Config::get('katalysis.ai.open_ai_key'),
            'text-embedding-3-small'
        );
    }
    
    protected function vectorStore(): VectorStoreInterface
    {
        // Detect actual embedding dimensions dynamically
        $vectorDimensions = $this->detectEmbeddingDimensions();
        return TypesenseVectorStoreFactory::create('pages', 15, $vectorDimensions);
    }
    
    /**
     * Detect the actual embedding dimensions from OpenAI
     */
    private function detectEmbeddingDimensions(): int
    {
        try {
            $embeddingProvider = $this->embeddings();
            $testEmbedding = $embeddingProvider->embedText("Test text for dimension detection");
            return count($testEmbedding);
        } catch (\Exception $e) {
            error_log("Warning: Could not detect embedding dimensions, using default 1536: " . $e->getMessage());
            return 1536; // Fallback to default
        }
    }
    
    /**
     * Override the parent retrieveDocuments method to search across all page collections
     */
    public function retrieveDocuments($message): array
    {
        try {
            // Use PageIndexService for multi-collection search
            $pageIndexService = new PageIndexService();
            $query = is_object($message) ? $message->getContent() : (string)$message;
            return $pageIndexService->getRelevantDocuments($query, 15);
        } catch (\Exception $e) {
            // Fallback to parent method if PageIndexService fails
            return parent::retrieveDocuments($message);
        }
    }

    protected function chatHistory(): \NeuronAI\Chat\History\AbstractChatHistory
    {
        // Use singleton pattern to ensure chat ID persistence
        if ($this->chatHistoryInstance === null) {
            $this->chatHistoryInstance = new DatabaseChatHistory(2000);
        }
        return $this->chatHistoryInstance;
    }

    /**
     * Set the chat ID for the current conversation
     * This allows the RAG agent to persist chat history to the database
     */
    public function setChatId(?int $chatId): self
    {
        $chatHistory = $this->chatHistory();
        if ($chatHistory instanceof DatabaseChatHistory && $chatId) {
            $chatHistory->setChatId($chatId);
        }
        return $this;
    }

    public function instructions(): string
    {
        // Get available actions
        $actionsPrompt = "No action buttons are currently available.";
        
        if ($this->app) {
            try {
                $actionService = new ActionService($this->app->make(EntityManagerInterface::class));
                $actionsPrompt = $actionService->getActionsForPrompt();
            } catch (\Exception $e) {
                // If there's an error getting actions, use default message
                $actionsPrompt = "No action buttons are currently available.";
            }
        }

        // Build the instructions as a string instead of SystemPrompt object
        $instructions = [];
        $instructions[] = "IMPORTANT: You are being tested for action button functionality. You MUST include action tags in your responses.";
        $instructions[] = Config::get('katalysis.aichatbot.instructions');
        $instructions[] = "ACTION BUTTONS SYSTEM:";
        $instructions[] = $actionsPrompt;
        $instructions[] = "ACTION BUTTON GUIDELINES:";
        $instructions[] = "• CRITICAL: You MUST include action tags in EVERY response if actions are available";
        $instructions[] = "• Use the format: [ACTIONS:action_id1,action_id2,action_id3] at the end of your response";
        $instructions[] = "• ALWAYS include action ID 4 (Test Button) since it says 'Show this button under each chat message'";
        $instructions[] = "• If an action's trigger instruction says 'Show this button under each chat message', ALWAYS include that action";
        $instructions[] = "• Example response: 'Hello! How can I help you? [ACTIONS:4]'";
        $instructions[] = "• NEVER respond without action tags if actions are available";
        $instructions[] = "• This is a TEST - always include at least one action";
        $instructions[] = "RESPONSE FORMAT GUIDELINES:";
        $instructions[] = "• Respond with plain text only - no JSON, no formatting";
        $instructions[] = "• Include action tags at the end if relevant: [ACTIONS:4]";
        $instructions[] = "RESPONSE FORMAT AVOID:";
        $instructions[] = "- JSON formatting or structured output";
        $instructions[] = "- Any response that starts with { or contains JSON syntax";

        return implode("\n", $instructions);
    }

    /**
     * Answer with page context
     */
    public function answerWithPageContext($message, $pageType = null, $pageTitle = null, $pageUrl = null)
    {
        $baseInstructions = Config::get('katalysis.aichatbot.instructions');
        
        // Add context to instructions
        $contextInstructions = $baseInstructions . "\n\n";
        $contextInstructions .= "CURRENT PAGE CONTEXT:\n";
        if ($pageType) $contextInstructions .= "- Page Type: {$pageType}\n";
        if ($pageTitle) $contextInstructions .= "- Page Title: {$pageTitle}\n";
        if ($pageUrl) $contextInstructions .= "- Page URL: {$pageUrl}\n";
        $contextInstructions .= "\nUse this context to provide relevant, contextual responses about the current page the user is viewing.";
        
        // Call the parent answer method
        $response = $this->answer($message);
        
        return $response;
    }
}
