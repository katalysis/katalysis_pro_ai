<?php

namespace KatalysisProAi;

use KatalysisProAi\AiAgent;
use NeuronAI\Chat\Messages\UserMessage;

/**
 * AI-driven form flow control engine
 * Determines which form steps to show based on collected data and context
 */
class AiFormFlowEngine
{
    private $aiAgent;
    
    public function __construct($app = null)
    {
        // Try to create AiAgent, but don't fail if it doesn't work
        try {
            // Set a timeout for the constructor to prevent hanging
            set_time_limit(30); // 30 second timeout
            
            // Set memory limit to prevent memory issues
            $currentMemoryLimit = ini_get('memory_limit');
            if ($currentMemoryLimit !== '-1') {
                $memoryLimitBytes = $this->parseMemoryLimit($currentMemoryLimit);
                if ($memoryLimitBytes < 256 * 1024 * 1024) { // Less than 256MB
                    ini_set('memory_limit', '256M');
                }
            }
            
            $this->aiAgent = new AiAgent($app);
            error_log('AiFormFlowEngine - AiAgent created successfully');
        } catch (\Error $e) {
            error_log('AiFormFlowEngine - Failed to create AiAgent (Error): ' . $e->getMessage());
            error_log('AiFormFlowEngine - Error file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiAgent = null;
        } catch (\Exception $e) {
            error_log('AiFormFlowEngine - Failed to create AiAgent (Exception): ' . $e->getMessage());
            error_log('AiFormFlowEngine - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiAgent = null;
        } catch (\Throwable $e) {
            error_log('AiFormFlowEngine - Failed to create AiAgent (Throwable): ' . $e->getMessage());
            error_log('AiFormFlowEngine - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiAgent = null;
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit($memoryLimit)
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }
    
    /**
     * Determine if a form step should be shown based on AI decision
     */
    public function shouldShowStep($step, $collectedData, $chatContext)
    {
        // Debug logging
        error_log('shouldShowStep - Step key: ' . ($step['stepKey'] ?? 'unknown'));
        error_log('shouldShowStep - Step conditional logic: ' . json_encode($step['conditionalLogic'] ?? 'none'));
        
        // Handle static conditions first
        if (!isset($step['conditionalLogic']['ai_decides']) || !$step['conditionalLogic']['ai_decides']) {
            error_log('shouldShowStep - Using static evaluation for step: ' . ($step['stepKey'] ?? 'unknown'));
            return $this->evaluateStaticConditions($step, $collectedData);
        }
        
        // Check if aiAgent is available
        if (!$this->aiAgent) {
            error_log('shouldShowStep - aiAgent not available, using static evaluation');
            return $this->evaluateStaticConditions($step, $collectedData);
        }
        
        $decisionPrompt = $this->buildDecisionPrompt($step, $collectedData, $chatContext);
        error_log('shouldShowStep - Decision prompt: ' . $decisionPrompt);
        
        try {
            // Set timeout for AI calls
            set_time_limit(60); // 60 second timeout for AI calls
            
            // Force garbage collection before AI call
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            $userMessage = new UserMessage($decisionPrompt);
            $aiResponse = $this->aiAgent->chat($userMessage);
            
            // Validate AI response
            if (!$aiResponse) {
                error_log('shouldShowStep - AI response is null or empty');
                return $this->evaluateStaticConditions($step, $collectedData);
            }
            
            $decision = $this->parseAiDecision($aiResponse);
            error_log('shouldShowStep - AI decision: ' . json_encode($decision));
            
            // Force garbage collection after AI call
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            $result = $decision['show_step'] ?? false;
            error_log('shouldShowStep - Final result: ' . ($result ? 'true' : 'false'));
            return $result;
        } catch (\Exception $e) {
            error_log('shouldShowStep - AI call failed: ' . $e->getMessage());
            error_log('shouldShowStep - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            // Fallback to static evaluation on AI failure
            return $this->evaluateStaticConditions($step, $collectedData);
        } catch (\Error $e) {
            error_log('shouldShowStep - AI call error: ' . $e->getMessage());
            error_log('shouldShowStep - Error file: ' . $e->getFile() . ':' . $e->getLine());
            // Fallback to static evaluation on AI failure
            return $this->evaluateStaticConditions($step, $collectedData);
        }
    }
    
    /**
     * Generate a dynamic form step using AI
     */
    public function generateDynamicStep($step, $collectedData, $chatContext)
    {
        // Check if aiAgent is available
        if (!$this->aiAgent) {
            error_log('generateDynamicStep - aiAgent not available, using fallback step');
            return $this->createFallbackStep($step, $collectedData);
        }
        
        $generationPrompt = $this->buildGenerationPrompt($step, $collectedData, $chatContext);
        
        try {
            // Set timeout for AI calls
            set_time_limit(60); // 60 second timeout for AI calls
            
            $userMessage = new UserMessage($generationPrompt);
            $aiResponse = $this->aiAgent->chat($userMessage);
            
            // Validate AI response
            if (!$aiResponse) {
                error_log('generateDynamicStep - AI response is null or empty');
                return $this->createFallbackStep($step, $collectedData);
            }
            
            return $this->parseAiGeneratedStep($aiResponse, $step, $collectedData);
        } catch (\Exception $e) {
            error_log('generateDynamicStep - AI call failed: ' . $e->getMessage());
            error_log('generateDynamicStep - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            // Return a fallback generic question
            return $this->createFallbackStep($step, $collectedData);
        } catch (\Error $e) {
            error_log('generateDynamicStep - AI call error: ' . $e->getMessage());
            error_log('generateDynamicStep - Error file: ' . $e->getFile() . ':' . $e->getLine());
            // Return a fallback generic question
            return $this->createFallbackStep($step, $collectedData);
        }
    }
    
    /**
     * Determine what action to take after form completion
     */
    public function determineCompletionAction($action, $formData, $chatContext)
    {
        // AI completion is only used for dynamic_form types
        if ($action['actionType'] !== 'dynamic_form') {
            return $this->getDefaultCompletionAction($action);
        }
        
        // Check if aiAgent is available
        if (!$this->aiAgent) {
            error_log('determineCompletionAction - aiAgent not available, using default completion action');
            return $this->getDefaultCompletionAction($action);
        }
        
        $completionPrompt = $this->buildCompletionPrompt($action, $formData, $chatContext);
        
        try {
            // Set timeout for AI calls
            set_time_limit(60); // 60 second timeout for AI calls
            
            $userMessage = new UserMessage($completionPrompt);
            $aiResponse = $this->aiAgent->chat($userMessage);
            
            // Validate AI response
            if (!$aiResponse) {
                error_log('determineCompletionAction - AI response is null or empty');
                return $this->getDefaultCompletionAction($action);
            }
            
            return $this->parseCompletionDecision($aiResponse);
        } catch (\Exception $e) {
            error_log('determineCompletionAction - AI call failed: ' . $e->getMessage());
            error_log('determineCompletionAction - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            return $this->getDefaultCompletionAction($action);
        } catch (\Error $e) {
            error_log('determineCompletionAction - AI call error: ' . $e->getMessage());
            error_log('determineCompletionAction - Error file: ' . $e->getFile() . ':' . $e->getLine());
            return $this->getDefaultCompletionAction($action);
        }
    }
    
    /**
     * Build decision prompt for step visibility
     */
    private function buildDecisionPrompt($step, $collectedData, $chatContext)
    {
        $prompt = "You are helping qualify a potential lead through a conversational form.\n\n";
        $prompt .= "COLLECTED DATA SO FAR:\n";
        
        foreach ($collectedData as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        $prompt .= "\nCONTEXT:\n";
        $prompt .= "- Page: {$chatContext['page_title']}\n";
        $prompt .= "- Conversation summary: {$chatContext['conversation_summary']}\n\n";
        
        $prompt .= "DECISION NEEDED:\n";
        $prompt .= $step['conditionalLogic']['decision_prompt'] . "\n\n";
        
        $prompt .= "Should we ask this question: \"{$step['question']}\"?\n\n";
        $prompt .= "Respond with ONLY a JSON object: {\"show_step\": true/false, \"reason\": \"brief explanation\"}";
        
        return $prompt;
    }
    
    /**
     * Build prompt for generating dynamic questions
     */
    private function buildGenerationPrompt($step, $collectedData, $chatContext)
    {
        $prompt = "You are creating a conversational form question based on collected lead data.\n\n";
        $prompt .= "COLLECTED DATA:\n";
        
        foreach ($collectedData as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        $prompt .= "\nCONTEXT:\n";
        $prompt .= "- Page: {$chatContext['page_title']}\n";
        $prompt .= "- Conversation: {$chatContext['conversation_summary']}\n\n";
        
        $prompt .= "GENERATION INSTRUCTION:\n";
        $prompt .= $step['aiGenerationPrompt'] . "\n\n";
        
        $prompt .= "Create 1-2 relevant follow-up questions that would help qualify this lead better.\n\n";
        $prompt .= "Respond with JSON array: [{\"question\": \"What you would ask?\", \"field_type\": \"text|email|select|textarea\", \"options\": [\"if select type\"], \"validation\": {\"required\": true}}]";
        
        return $prompt;
    }
    
    /**
     * Build prompt for completion action decision
     */
    private function buildCompletionPrompt($action, $formData, $chatContext)
    {
        $prompt = "You are deciding the best next action after a lead completes a form.\n\n";
        $prompt .= "FORM DATA:\n";
        
        foreach ($formData as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        $prompt .= "\nCONTEXT:\n";
        $prompt .= "- Page: {$chatContext['page_title']}\n";
        $prompt .= "- Form: {$action['name']}\n\n";
        
        // Use the responseInstruction as guidance for dynamic forms
        if (!empty($action['responseInstruction'])) {
            $prompt .= "DECISION GUIDANCE:\n";
            $prompt .= $action['responseInstruction'] . "\n\n";
        }
        
        $prompt .= "Based on the collected information, what's the best next action?\n\n";
        $prompt .= "Options:\n";
        $prompt .= "- schedule_demo: High-value lead ready for sales demo\n";
        $prompt .= "- send_pricing: Qualified lead wanting pricing information\n";
        $prompt .= "- send_resources: Interested but needs more information\n";
        $prompt .= "- qualify_further: Need more information to assess fit\n";
        $prompt .= "- mark_unqualified: Not a good fit for our solution\n\n";
        
        $prompt .= "Respond with JSON: {\"action\": \"chosen_action\", \"reason\": \"explanation\", \"followup_message\": \"message to show user\", \"urgency\": \"high|medium|low\"}";
        
        return $prompt;
    }
    
    /**
     * Safely extract content from AI response (handles both AssistantMessage objects and arrays)
     */
    private function extractResponseContent($aiResponse)
    {
        try {
            // Handle AssistantMessage object
            if (is_object($aiResponse) && method_exists($aiResponse, 'getContent')) {
                return $aiResponse->getContent();
            }
            
            // Handle array format
            if (is_array($aiResponse)) {
                return $aiResponse['content'] ?? $aiResponse;
            }
            
            // Handle string format
            if (is_string($aiResponse)) {
                return $aiResponse;
            }
            
            // Fallback: try to convert to string
            return (string) $aiResponse;
        } catch (\Exception $e) {
            error_log('extractResponseContent - Exception: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Parse AI decision response
     */
    private function parseAiDecision($aiResponse)
    {
        try {
            $content = $this->extractResponseContent($aiResponse);
            
            // Look for JSON in the response
            if (preg_match('/\{[^}]*"show_step"[^}]*\}/', $content, $matches)) {
                return json_decode($matches[0], true);
            }
            
            // Fallback: look for true/false indicators
            if (stripos($content, 'true') !== false || stripos($content, 'yes') !== false) {
                return ['show_step' => true, 'reason' => 'AI indicated positive'];
            }
            
            return ['show_step' => false, 'reason' => 'AI indicated negative or unclear'];
        } catch (\Exception $e) {
            error_log('parseAiDecision - Exception: ' . $e->getMessage());
            return ['show_step' => false, 'reason' => 'Error parsing AI response'];
        }
    }
    
    /**
     * Parse AI generated step response
     */
    private function parseAiGeneratedStep($aiResponse, $originalStep, $collectedData)
    {
        try {
            $content = $this->extractResponseContent($aiResponse);
            
            // Try to extract JSON array
            if (preg_match('/\[.*\]/', $content, $matches)) {
                $questions = json_decode($matches[0], true);
                if ($questions && is_array($questions) && count($questions) > 0) {
                    $question = $questions[0]; // Take first question
                    
                    return [
                        'stepKey' => 'ai_generated_' . uniqid(),
                        'fieldType' => $question['field_type'] ?? 'text',
                        'question' => $this->replacePlaceholders($question['question'], $collectedData),
                        'options' => $question['options'] ?? null,
                        'validation' => $question['validation'] ?? ['required' => false],
                        'sortOrder' => $originalStep['sortOrder'],
                        'isAiGenerated' => true
                    ];
                }
            }
            
            return $this->createFallbackStep($originalStep, $collectedData);
        } catch (\Exception $e) {
            error_log('parseAiGeneratedStep - Exception: ' . $e->getMessage());
            return $this->createFallbackStep($originalStep, $collectedData);
        }
    }
    
    /**
     * Parse completion decision response
     */
    private function parseCompletionDecision($aiResponse)
    {
        try {
            $content = $this->extractResponseContent($aiResponse);
            
            if (preg_match('/\{[^}]*"action"[^}]*\}/', $content, $matches)) {
                $decision = json_decode($matches[0], true);
                if ($decision && isset($decision['action'])) {
                    return $decision;
                }
            }
            
            // Fallback based on keywords in response
            if (stripos($content, 'demo') !== false) {
                return ['action' => 'schedule_demo', 'reason' => 'AI suggested demo', 'followup_message' => 'Let\'s schedule a demo!'];
            } elseif (stripos($content, 'pricing') !== false) {
                return ['action' => 'send_pricing', 'reason' => 'AI suggested pricing', 'followup_message' => 'I\'ll send you pricing information.'];
            }
            
            return ['action' => 'send_resources', 'reason' => 'Default action', 'followup_message' => 'Thanks for your interest! I\'ll send you some helpful resources.'];
        } catch (\Exception $e) {
            error_log('parseCompletionDecision - Exception: ' . $e->getMessage());
            return ['action' => 'send_resources', 'reason' => 'Error parsing', 'followup_message' => 'Thanks for completing the form!'];
        }
    }
    
    /**
     * Evaluate static conditional logic
     */
    private function evaluateStaticConditions($step, $collectedData)
    {
        if (!isset($step['conditionalLogic'])) {
            return true; // No conditions, show step
        }
        
        $conditions = $step['conditionalLogic'];
        
        // Handle show_if conditions
        if (isset($conditions['show_if'])) {
            $showIf = $conditions['show_if'];
            
            if (isset($showIf['field']) && isset($showIf['equals'])) {
                $fieldValue = $collectedData[$showIf['field']] ?? null;
                return $fieldValue === $showIf['equals'];
            }
            
            if (isset($showIf['field']) && isset($showIf['not_empty'])) {
                $fieldValue = $collectedData[$showIf['field']] ?? null;
                return !empty($fieldValue);
            }
        }
        
        return true;
    }
    
    /**
     * Replace placeholders in text with collected data
     */
    private function replacePlaceholders($text, $collectedData)
    {
        foreach ($collectedData as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Create fallback step when AI generation fails
     */
    private function createFallbackStep($originalStep, $collectedData)
    {
        return [
            'stepKey' => 'additional_info',
            'fieldType' => 'textarea',
            'question' => 'Is there anything else you\'d like us to know?',
            'validation' => ['required' => false],
            'sortOrder' => $originalStep['sortOrder'],
            'isAiGenerated' => true,
            'isFallback' => true
        ];
    }
    
    /**
     * Get default completion action when AI is not used
     */
    private function getDefaultCompletionAction($action)
    {
        // Instead of using a static message, indicate that AI generation should be attempted
        // This allows the calling code to try AI generation even when the flow engine's AI agent failed
        return [
            'action' => 'send_resources',
            'reason' => 'Default completion',
            'followup_message' => null, // Signal that AI generation should be attempted
            'response_instruction' => $action['responseInstruction'], // Preserve the original instruction for AI
            'urgency' => 'medium'
        ];
    }
}