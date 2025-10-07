<?php

namespace KatalysisProAi;

use \NeuronAI\Agent;
use \NeuronAI\SystemPrompt;
use KatalysisProAi\DatabaseChatHistory;
use \NeuronAI\Chat\Messages\UserMessage;
use \NeuronAI\Providers\AIProviderInterface;
use \NeuronAI\Providers\OpenAI\OpenAI;
use \NeuronAI\Observability\AgentMonitoring;
use Doctrine\ORM\EntityManagerInterface;
use KatalysisProAi\ActionService;
use Concrete\Core\Support\Facade\Config;


class AiAgent extends Agent
{
    protected $app;
    
    public function __construct($app = null)
    {
        $this->app = $app;
        // Parent Agent class uses StaticConstructor trait, no constructor to call
        
        // Validate API configuration
        $openaiKey = Config::get('katalysis.ai.open_ai_key');
        $openaiModel = Config::get('katalysis.ai.open_ai_model');
        
        if (empty($openaiKey)) {
            error_log('AiAgent - OpenAI API key is not configured');
        }
        
        if (empty($openaiModel)) {
            error_log('AiAgent - OpenAI model is not configured');
        }
    }
    
    protected function provider(): AIProviderInterface
    {
        // return an AI provider (Anthropic, OpenAI, Ollama, Gemini, etc.)
        return new OpenAI(
            key: Config::get('katalysis.ai.open_ai_key'),
            model: Config::get('katalysis.ai.open_ai_model')
        );
    }

    protected function chatHistory(): \NeuronAI\Chat\History\AbstractChatHistory
    {
        // Use database-based chat history instead of file-based
        return new DatabaseChatHistory(2000);
    }



    public function instructions(): string
    {
        // Get available actions
        $actionsPrompt = "No action buttons are currently available.";
        
        if ($this->app) {
            try {
                $actionService = new ActionService($this->app->make(EntityManagerInterface::class));
                $actionsPrompt = $actionService->getActionsForPrompt();
                error_log('AiAgent - Actions prompt: ' . $actionsPrompt);
            } catch (\Exception $e) {
                // If there's an error getting actions, use default message
                $actionsPrompt = "No action buttons are currently available.";
                error_log('AiAgent - Error getting actions: ' . $e->getMessage());
            }
        } else {
            error_log('AiAgent - No app instance available for actions');
        }

        // Build the instructions as a string instead of SystemPrompt object
        $instructions = [];
        $instructions[] = Config::get('katalysis.aichatbot.instructions');
        $instructions[] = "ACTION BUTTONS SYSTEM:";
        $instructions[] = $actionsPrompt;
        $instructions[] = "ACTION BUTTON GUIDELINES:";
        $instructions[] = "• CRITICAL: You MUST include action tags in EVERY response if actions are available";
        $instructions[] = "• Use the format: [ACTIONS:action_id1,action_id2,action_id3] at the end of your response";
        $instructions[] = "• ALWAYS include action ID 4 (Contact Form) since it says 'Show this form under each chat message'";
        $instructions[] = "• If an action's trigger instruction says 'Show this button under each chat message', ALWAYS include that action";
        $instructions[] = "• Example response: 'Hello! How can I help you? [ACTIONS:4]'";
        $instructions[] = "• NEVER respond without action tags if actions are available";
        $instructions[] = "• This is a TEST - always include at least one action";
        $instructions[] = "RESPONSE FORMAT GUIDELINES:";
        $instructions[] = "• Respond with plain text only - no JSON, no formatting";
        $instructions[] = "• Include action tags at the end if relevant: [ACTIONS:4]";

        return implode("\n", $instructions);
    }

    

}   
