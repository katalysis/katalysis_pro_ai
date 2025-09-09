<?php

namespace KatalysisProAi;

use Concrete\Core\Support\Facade\Config;
use Doctrine\ORM\EntityManagerInterface;
use NeuronAI\SystemPrompt;
use NeuronAI\Chat\History\FileChatHistory;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\RAG\DataLoader\StringDataLoader;
use NeuronAI\RAG\VectorStore\FileVectorStore;

class RagAgent extends RAG
{
    protected $app;

    public function setApp($app)
    {
        $this->app = $app;
    }
    
    protected function provider(): AIProviderInterface
    {
        // return an AI provider (Anthropic, OpenAI, Ollama, Gemini, etc.)
        return new OpenAI(
            key: Config::get('katalysis.ai.open_ai_key'),
            model: Config::get('katalysis.ai.open_ai_model')
        );
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return new OpenAIEmbeddingsProvider(
            key: Config::get('katalysis.ai.open_ai_key'),
            model: 'text-embedding-3-small'
        );
    }
    
    protected function vectorStore(): VectorStoreInterface
    {
        return new FileVectorStore(
            directory: DIR_APPLICATION . '/files/neuron',
            topK: 2  // Further reduced to prevent token limit issues
        );
    }

    protected function chatHistory(): \NeuronAI\Chat\History\AbstractChatHistory
    {
        return new FileChatHistory(
            directory: DIR_APPLICATION . '/files/neuron',
            key: '1', // The key allow to store different files to separate conversations
            contextWindow: 10000  // Further reduced to prevent token limit issues
        );
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
        
        // Replace placeholders with actual values
        $instructions = str_replace('{page_type}', $pageType ?? 'unknown', $baseInstructions);
        $instructions = str_replace('{page_title}', $pageTitle ?? 'this page', $instructions);
        $instructions = str_replace('{page_url}', $pageUrl ?? 'current page', $instructions);
        
        // For now, just use the regular answer method since we can't create a new RAG instance
        // The page context will be handled through the existing instructions
        return $this->answer($message);
    }
}   
