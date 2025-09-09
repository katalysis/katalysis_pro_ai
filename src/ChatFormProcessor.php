<?php

namespace KatalysisProAi;

use KatalysisProAi\Entity\Chat;
use KatalysisProAi\Entity\Action;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Processes chat form interactions with AI-driven flow control
 */
class ChatFormProcessor
{
    private $entityManager;
    private $aiFlowEngine;
    
    public function __construct($app)
    {
        $this->entityManager = $app->make('Doctrine\ORM\EntityManager');
        
        // Try to create AiFormFlowEngine, but don't fail if it doesn't work
        try {
            // Set timeout for constructor
            set_time_limit(30); // 30 second timeout
            
            $this->aiFlowEngine = new AiFormFlowEngine($app);
            error_log('ChatFormProcessor - AiFormFlowEngine created successfully');
        } catch (\Exception $e) {
            error_log('ChatFormProcessor - Failed to create AiFormFlowEngine: ' . $e->getMessage());
            error_log('ChatFormProcessor - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiFlowEngine = null;
        } catch (\Error $e) {
            error_log('ChatFormProcessor - Failed to create AiFormFlowEngine (Error): ' . $e->getMessage());
            error_log('ChatFormProcessor - Error file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiFlowEngine = null;
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor - Failed to create AiFormFlowEngine (Throwable): ' . $e->getMessage());
            error_log('ChatFormProcessor - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            $this->aiFlowEngine = null;
        }
    }
    
    /**
     * Start a new form interaction
     */
    public function startForm($chat, $action)
    {
        try {
            error_log('ChatFormProcessor::startForm - Starting with action: ' . json_encode($action));
            
            if ($action['actionType'] !== 'form' && $action['actionType'] !== 'dynamic_form' && $action['actionType'] !== 'simple_form') {
                throw new \InvalidArgumentException('Action is not a form type');
            }
            
            // Initialize the form data structure in submittedInfo
            error_log('ChatFormProcessor::startForm - Initializing submittedInfo');
            $submittedInfo = json_decode($chat->getSubmittedInfo(), true) ?: [];
            $formKey = "action_{$action['id']}";
            
            if (!isset($submittedInfo['forms'])) {
                $submittedInfo['forms'] = [];
            }
            
            if (!isset($submittedInfo['forms'][$formKey])) {
                $submittedInfo['forms'][$formKey] = [
                    'started_at' => date('c'),
                    'data' => [],
                    'completed' => false
                ];
                $chat->setSubmittedInfo(json_encode($submittedInfo));
            }
            
            // Handle simple_form differently - show all fields at once
            if ($action['actionType'] === 'simple_form') {
                error_log('ChatFormProcessor::startForm - Processing simple_form');
                // For simple_form, return all fields at once
                $allFields = [];
                foreach ($action['formSteps'] as $step) {
                    $allFields[] = [
                        'stepKey' => $step['stepKey'],
                        'fieldType' => $step['fieldType'],
                        'question' => $step['question'],
                        'options' => $step['options'] ?? null,
                        'validation' => $step['validation'] ?? [],
                        'sortOrder' => $step['sortOrder'] ?? 1
                    ];
                }
                
                // Initialize form state for simple_form (all fields visible)
                $activeFormState = [
                    'action_id' => $action['id'],
                    'current_step' => 'all_fields', // Special indicator for simple_form
                    'step_index' => -1, // -1 indicates all fields are shown
                    'started_at' => date('c'),
                    'total_steps' => count($action['formSteps']),
                    'form_type' => 'simple_form'
                ];
                
                $chat->setActiveFormState(json_encode($activeFormState));
                $this->entityManager->flush();
                
                // Return all fields for simple_form
                return [
                    'type' => 'simple_form_started',
                    'fields' => $allFields,
                    'total_steps' => count($action['formSteps']),
                    'form_type' => 'simple_form',
                    'action_id' => $action['id'],
                    'chat_id' => $chat->getId(),
                    'action_name' => $action['name'] ?? 'Form'
                ];
            }
            
            // For progressive forms (form and dynamic_form), continue with existing logic
            error_log('ChatFormProcessor::startForm - Processing progressive form');
            $firstStep = $this->getNextStep($chat, $action);
            if (!$firstStep) {
                throw new \InvalidArgumentException('No form steps available');
            }
            
            // Initialize form state with the first step as current
            $activeFormState = [
                'action_id' => $action['id'],
                'current_step' => $firstStep['stepKey'], // Set to first step's field key
                'step_index' => 0,
                'started_at' => date('c'),
                'total_steps' => count($action['formSteps'])
            ];
            
            $chat->setActiveFormState(json_encode($activeFormState));
            $this->entityManager->flush();
            
            // Return the first step
            return $firstStep;
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::startForm - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::startForm - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::startForm - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        } catch (\Error $e) {
            error_log('ChatFormProcessor::startForm - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::startForm - Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::startForm - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::startForm - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::startForm - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::startForm - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Process a field response and determine next step
     */
    public function processFieldResponse($chat, $actionId, $fieldKey, $userResponse)
    {
        $action = $this->getAction($actionId);
        if (!$action) {
            throw new \InvalidArgumentException('Action not found');
        }
        
        // Get the current step from the active form state
        $activeFormState = json_decode($chat->getActiveFormState(), true);
        if (!$activeFormState || !isset($activeFormState['current_step'])) {
            throw new \InvalidArgumentException('No active form step found');
        }
        
        $currentStepKey = $activeFormState['current_step'];
        
        // Debug logging
        error_log("Form processing - Action ID: {$actionId}, Field Key: {$fieldKey}, Current Step: {$currentStepKey}");
        error_log("Active Form State: " . json_encode($activeFormState));
        
        // Validate that the submitted field matches the current step
        if ($currentStepKey !== $fieldKey) {
            error_log("Field key mismatch - Expected: {$currentStepKey}, Got: {$fieldKey}");
            throw new \InvalidArgumentException("Field key mismatch: expected {$currentStepKey}, got {$fieldKey}");
        }
        
        // Get the current step data
        $currentStep = $this->getCurrentStep($action, $currentStepKey);
        if (!$currentStep) {
            throw new \InvalidArgumentException("Current step not found: {$currentStepKey}");
        }
        
        // Validate the field response
        $validationResult = $this->validateFieldResponse($currentStep, $userResponse);
        
        if (!$validationResult['isValid']) {
            return [
                'type' => 'validation_error',
                'error' => $validationResult['error'],
                'step' => $currentStep
            ];
        }
        
        // Save the response
        $this->saveFieldResponse($chat, $actionId, $currentStepKey, $userResponse);
        
        // Get next step
        $nextStep = $this->getNextStep($chat, $action);
        
        if (!$nextStep) {
            // Form is complete
            return $this->completeForm($chat, $action);
        }
        
        // Debug logging for next step
        error_log("Next step found: " . json_encode($nextStep));
        
        // Update form state
        $this->updateFormState($chat, $nextStep);
        
        // Ensure the form state is persisted to the database
        $this->entityManager->flush();
        
        // Debug logging for updated form state
        $updatedFormState = json_decode($chat->getActiveFormState(), true);
        error_log("Updated Form State: " . json_encode($updatedFormState));
        
        return [
            'type' => 'next_step',
            'step' => $nextStep,
            'progress' => $this->getFormProgress($chat, $action)
        ];
    }
    
    /**
     * Find the next step to show
     */
    private function getNextStep($chat, $action)
    {
        try {
            error_log('ChatFormProcessor::getNextStep - Starting');
            
            $activeFormState = json_decode($chat->getActiveFormState(), true);
            error_log('ChatFormProcessor::getNextStep - Active Form State: ' . json_encode($activeFormState));
            
            $collectedData = $this->getCollectedFormData($chat, $action['id']);
            error_log('ChatFormProcessor::getNextStep - Collected Data: ' . json_encode($collectedData));
            
            $chatContext = $this->buildChatContext($chat, $action);
            error_log('ChatFormProcessor::getNextStep - Chat Context: ' . json_encode($chatContext));
            
            error_log('ChatFormProcessor::getNextStep - Action Form Steps: ' . json_encode($action['formSteps']));
            
            // Find the current step index in the action's form steps
            $currentStepKey = $activeFormState['current_step'] ?? null;
            $currentStepIndex = -1;
            
            if ($currentStepKey) {
                foreach ($action['formSteps'] as $index => $step) {
                    if ($step['stepKey'] === $currentStepKey) {
                        $currentStepIndex = $index;
                        break;
                    }
                }
            }
            
            error_log("ChatFormProcessor::getNextStep - Current Step Key: {$currentStepKey}, Current Step Index: {$currentStepIndex}");
            
            // Start from the next step after the current one
            $startIndex = $currentStepIndex + 1;
            
            error_log("ChatFormProcessor::getNextStep - Starting search from index: {$startIndex}");
            
            // Find next incomplete step starting from the next index
            for ($i = $startIndex; $i < count($action['formSteps']); $i++) {
                $step = $action['formSteps'][$i];
                $stepKey = $step['stepKey'];
                
                error_log("ChatFormProcessor::getNextStep - Checking step {$i}: {$stepKey}");
                
                // Skip if already completed
                if (isset($collectedData[$stepKey])) {
                    error_log("ChatFormProcessor::getNextStep - Step {$stepKey} already completed, skipping");
                    continue;
                }
                
                // Check if step should be shown
                error_log("ChatFormProcessor::getNextStep - About to call shouldShowStep for step {$stepKey}");
                $shouldShow = $this->shouldShowStep($step, $collectedData, $chatContext, $action);
                error_log("ChatFormProcessor::getNextStep - shouldShowStep result for {$stepKey}: " . ($shouldShow ? 'true' : 'false'));
                
                if ($shouldShow) {
                    error_log("ChatFormProcessor::getNextStep - Found next step: {$stepKey}");
                    
                    // Handle AI-generated steps
                    if ($step['fieldType'] === 'ai_generated') {
                        if ($this->aiFlowEngine) {
                            return $this->aiFlowEngine->generateDynamicStep($step, $collectedData, $chatContext);
                        } else {
                            error_log('ChatFormProcessor::getNextStep - aiFlowEngine not available, using fallback for ai_generated step');
                            return $this->createFallbackStep($step, $collectedData);
                        }
                    }
                    
                    // Process placeholders in question
                    $step['question'] = $this->replacePlaceholders($step['question'], $collectedData);
                    
                    return $step;
                } else {
                    error_log("ChatFormProcessor::getNextStep - Step {$stepKey} should not be shown, skipping");
                }
            }
            
            error_log("ChatFormProcessor::getNextStep - No more steps found");
            return null; // No more steps
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::getNextStep - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::getNextStep - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::getNextStep - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        } catch (\Error $e) {
            error_log('ChatFormProcessor::getNextStep - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::getNextStep - Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::getNextStep - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::getNextStep - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::getNextStep - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::getNextStep - Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Determine if a step should be shown
     */
    private function shouldShowStep($step, $collectedData, $chatContext, $action)
    {
        // Check if this step has AI decision logic
        if (isset($step['conditionalLogic']['ai_decides']) && $step['conditionalLogic']['ai_decides']) {
            // Use AI flow engine for AI-driven decisions
            if ($this->aiFlowEngine) {
                error_log('shouldShowStep - Using AI flow engine for AI-driven step: ' . ($step['stepKey'] ?? 'unknown'));
                return $this->aiFlowEngine->shouldShowStep($step, $collectedData, $chatContext);
            } else {
                error_log('shouldShowStep - aiFlowEngine not available, using static evaluation for AI-driven step');
                return $this->evaluateStaticConditions($step, $collectedData);
            }
        }
        
        // Use static conditions for all other steps (regardless of form type)
        error_log('shouldShowStep - Using static evaluation for step: ' . ($step['stepKey'] ?? 'unknown'));
        return $this->evaluateStaticConditions($step, $collectedData);
    }
    
    /**
     * Create a fallback step when AI flow engine is not available
     */
    private function createFallbackStep($step, $collectedData)
    {
        // Create a simple fallback step based on the original step
        return [
            'stepKey' => $step['stepKey'],
            'fieldType' => 'text', // Default to text input
            'question' => $step['question'] ?? 'Please provide additional information',
            'validation' => $step['validation'] ?? ['required' => true],
            'placeholder' => $step['placeholder'] ?? ''
        ];
    }
    
    /**
     * Complete the form and determine next action
     */
    private function completeForm($chat, $action)
    {
        try {
            error_log('ChatFormProcessor::completeForm - Starting completion for action: ' . $action['name']);
            
            $collectedData = $this->getCollectedFormData($chat, $action['id']);
            error_log('ChatFormProcessor::completeForm - Collected data: ' . json_encode($collectedData));
            
            $chatContext = $this->buildChatContext($chat, $action);
            error_log('ChatFormProcessor::completeForm - Chat context: ' . json_encode($chatContext));
            
            // Clear active form state
            $chat->setActiveFormState(null);
            
            // Mark form as completed
            $this->markFormCompleted($chat, $action['id'], $collectedData);
            
            // Determine completion action using AI if enabled
            $completionAction = null;
            if ($this->aiFlowEngine) {
                error_log('ChatFormProcessor::completeForm - Using AI flow engine for completion action');
                $completionAction = $this->aiFlowEngine->determineCompletionAction($action, $collectedData, $chatContext);
            } else {
                error_log('ChatFormProcessor::completeForm - AI flow engine not available, using default completion action');
                $completionAction = $this->getDefaultCompletionAction($action);
            }
            
            error_log('ChatFormProcessor::completeForm - Completion action: ' . json_encode($completionAction));
            
            $this->entityManager->flush();
            
            $result = [
                'type' => 'form_complete',
                'completion_action' => $completionAction,
                'collected_data' => $collectedData,
                'message' => $completionAction['followup_message'] ?? ($action['responseInstruction'] ?: 'Thank you for completing the form!')
            ];
            
            error_log('ChatFormProcessor::completeForm - Returning result: ' . json_encode($result));
            return $result;
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::completeForm - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::completeForm - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::completeForm - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback completion
            return [
                'type' => 'form_complete',
                'completion_action' => [
                    'type' => 'message',
                    'followup_message' => 'Thank you for completing the form!'
                ],
                'collected_data' => [],
                'message' => 'Thank you for completing the form!'
            ];
        } catch (\Error $e) {
            error_log('ChatFormProcessor::completeForm - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::completeForm - Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::completeForm - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback completion
            return [
                'type' => 'form_complete',
                'completion_action' => [
                    'type' => 'message',
                    'followup_message' => 'Thank you for completing the form!'
                ],
                'collected_data' => [],
                'message' => 'Thank you for completing the form!'
            ];
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::completeForm - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::completeForm - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::completeForm - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback completion
            return [
                'type' => 'form_complete',
                'completion_action' => [
                    'type' => 'message',
                    'followup_message' => 'Thank you for completing the form!'
                ],
                'collected_data' => [],
                'message' => 'Thank you for completing the form!'
            ];
        }
    }
    
    /**
     * Get default completion action when AI flow engine is not available
     */
    private function getDefaultCompletionAction($action)
    {
        // Return a simple default completion action
        return [
            'type' => 'message',
            'followup_message' => $action['responseInstruction'] ?: 'Thank you for completing the form!',
            'next_action' => null
        ];
    }
    
    /**
     * Save field response to chat entity
     */
    private function saveFieldResponse($chat, $actionId, $fieldKey, $userResponse)
    {
        $submittedInfo = json_decode($chat->getSubmittedInfo(), true) ?: [];
        
        // Initialize form data if not exists
        if (!isset($submittedInfo['forms'])) {
            $submittedInfo['forms'] = [];
        }
        
        $formKey = "action_{$actionId}";
        if (!isset($submittedInfo['forms'][$formKey])) {
            $submittedInfo['forms'][$formKey] = [
                'started_at' => date('c'),
                'data' => [],
                'completed' => false
            ];
        }
        
        // Save the field response
        $submittedInfo['forms'][$formKey]['data'][$fieldKey] = $userResponse;
        $submittedInfo['forms'][$formKey]['last_updated'] = date('c');
        
        $chat->setSubmittedInfo(json_encode($submittedInfo));
    }
    
    /**
     * Mark form as completed
     */
    private function markFormCompleted($chat, $actionId, $collectedData)
    {
        $submittedInfo = json_decode($chat->getSubmittedInfo(), true) ?: [];
        $formKey = "action_{$actionId}";
        
        if (isset($submittedInfo['forms'][$formKey])) {
            $submittedInfo['forms'][$formKey]['completed'] = true;
            $submittedInfo['forms'][$formKey]['completed_at'] = date('c');
            $submittedInfo['forms'][$formKey]['final_data'] = $collectedData;
            
            $chat->setSubmittedInfo(json_encode($submittedInfo));
        }
    }
    
    /**
     * Get collected form data for a specific action
     */
    private function getCollectedFormData($chat, $actionId)
    {
        try {
            error_log('ChatFormProcessor::getCollectedFormData - Starting for action ID: ' . $actionId);
            
            $submittedInfo = json_decode($chat->getSubmittedInfo(), true) ?: [];
            $formKey = "action_{$actionId}";
            
            $data = $submittedInfo['forms'][$formKey]['data'] ?? [];
            error_log('ChatFormProcessor::getCollectedFormData - Data: ' . json_encode($data));
            
            return $data;
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::getCollectedFormData - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::getCollectedFormData - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            return [];
        } catch (\Error $e) {
            error_log('ChatFormProcessor::getCollectedFormData - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::getCollectedFormData - Error file: ' . $e->getFile() . ':' . $e->getLine());
            return [];
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::getCollectedFormData - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::getCollectedFormData - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            return [];
        }
    }
    
    /**
     * Validate field response
     */
    private function validateFieldResponse($step, $userResponse)
    {
        $validation = $step['validation'] ?? [];
        
        // Required field check
        if (isset($validation['required']) && $validation['required']) {
            if (empty(trim($userResponse))) {
                return [
                    'isValid' => false,
                    'error' => 'This field is required.'
                ];
            }
        }
        
        // Email validation
        if ($step['fieldType'] === 'email' && !empty($userResponse)) {
            if (!filter_var($userResponse, FILTER_VALIDATE_EMAIL)) {
                return [
                    'isValid' => false,
                    'error' => 'Please enter a valid email address.'
                ];
            }
        }
        
        // Length validation
        if (isset($validation['min_length']) && strlen($userResponse) < $validation['min_length']) {
            return [
                'isValid' => false,
                'error' => "Please enter at least {$validation['min_length']} characters."
            ];
        }
        
        if (isset($validation['max_length']) && strlen($userResponse) > $validation['max_length']) {
            return [
                'isValid' => false,
                'error' => "Please enter no more than {$validation['max_length']} characters."
            ];
        }
        
        // Select field validation
        if ($step['fieldType'] === 'select' && isset($step['options'])) {
            if (!in_array($userResponse, $step['options'])) {
                return [
                    'isValid' => false,
                    'error' => 'Please select a valid option.'
                ];
            }
        }
        
        return ['isValid' => true];
    }
    
    /**
     * Get current step details
     */
    private function getCurrentStep($action, $fieldKey)
    {
        foreach ($action['formSteps'] as $step) {
            if ($step['stepKey'] === $fieldKey) {
                return $step;
            }
        }
        
        return null;
    }
    
    /**
     * Update form state
     */
    private function updateFormState($chat, $nextStep)
    {
        $activeFormState = json_decode($chat->getActiveFormState(), true);
        
        if ($activeFormState) {
            // Find the actual index of the next step in the form steps
            $action = $this->getAction($activeFormState['action_id']);
            $nextStepIndex = -1;
            
            if ($action) {
                foreach ($action['formSteps'] as $index => $step) {
                    if ($step['stepKey'] === $nextStep['stepKey']) {
                        $nextStepIndex = $index;
                        break;
                    }
                }
            }
            
            $activeFormState['current_step'] = $nextStep['stepKey'];
            $activeFormState['step_index'] = $nextStepIndex >= 0 ? $nextStepIndex : ($activeFormState['step_index'] ?? 0) + 1;
            $activeFormState['last_updated'] = date('c');
            
            $chat->setActiveFormState(json_encode($activeFormState));
        }
    }
    
    /**
     * Get form progress information
     */
    private function getFormProgress($chat, $action)
    {
        $activeFormState = json_decode($chat->getActiveFormState(), true);
        $collectedData = $this->getCollectedFormData($chat, $action['id']);
        
        $totalSteps = count($action['formSteps']);
        $completedSteps = count($collectedData);
        
        return [
            'current_step' => ($activeFormState['step_index'] ?? 0) + 1,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0
        ];
    }
    
    /**
     * Build chat context for AI decisions
     */
    private function buildChatContext($chat, $action)
    {
        try {
            error_log('ChatFormProcessor::buildChatContext - Starting');
            
            $context = [
                'action_id' => $action['id'],
                'page_title' => $chat->getLaunchPageTitle() ?: 'Unknown Page',
                'page_url' => $chat->getLaunchPageUrl() ?: '',
                'conversation_summary' => $this->summarizeConversation($chat),
                'session_id' => $chat->getSessionId(),
                'user_message_count' => $chat->getUserMessageCount()
            ];
            
            error_log('ChatFormProcessor::buildChatContext - Context built: ' . json_encode($context));
            return $context;
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::buildChatContext - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::buildChatContext - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::buildChatContext - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback context
            return [
                'action_id' => $action['id'] ?? 0,
                'page_title' => 'Unknown Page',
                'page_url' => '',
                'conversation_summary' => 'New conversation',
                'session_id' => '',
                'user_message_count' => 0
            ];
        } catch (\Error $e) {
            error_log('ChatFormProcessor::buildChatContext - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::buildChatContext - Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::buildChatContext - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback context
            return [
                'action_id' => $action['id'] ?? 0,
                'page_title' => 'Unknown Page',
                'page_url' => '',
                'conversation_summary' => 'New conversation',
                'session_id' => '',
                'user_message_count' => 0
            ];
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::buildChatContext - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::buildChatContext - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('ChatFormProcessor::buildChatContext - Stack trace: ' . $e->getTraceAsString());
            
            // Return a safe fallback context
            return [
                'action_id' => $action['id'] ?? 0,
                'page_title' => 'Unknown Page',
                'page_url' => '',
                'conversation_summary' => 'New conversation',
                'session_id' => '',
                'user_message_count' => 0
            ];
        }
    }
    
    /**
     * Summarize conversation for AI context
     */
    private function summarizeConversation($chat)
    {
        try {
            error_log('ChatFormProcessor::summarizeConversation - Starting');
            
            $chatHistory = $chat->getCompleteChatHistory();
            
            if (empty($chatHistory)) {
                error_log('ChatFormProcessor::summarizeConversation - Empty chat history');
                return 'New conversation';
            }
            
            // Simple summary - take first and last user messages
            $messages = json_decode($chatHistory, true) ?: [];
            $userMessages = array_filter($messages, function($msg) {
                return isset($msg['sender']) && $msg['sender'] === 'user';
            });
            
            if (empty($userMessages)) {
                error_log('ChatFormProcessor::summarizeConversation - No user messages found');
                return 'User has not sent messages yet';
            }
            
            $firstMessage = reset($userMessages)['content'] ?? '';
            $lastMessage = end($userMessages)['content'] ?? '';
            
            if ($firstMessage === $lastMessage) {
                $summary = "User asked: " . substr($firstMessage, 0, 100);
            } else {
                $summary = "User started with: " . substr($firstMessage, 0, 50) . " ... recent: " . substr($lastMessage, 0, 50);
            }
            
            error_log('ChatFormProcessor::summarizeConversation - Summary: ' . $summary);
            return $summary;
            
        } catch (\Exception $e) {
            error_log('ChatFormProcessor::summarizeConversation - Exception: ' . $e->getMessage());
            error_log('ChatFormProcessor::summarizeConversation - Exception file: ' . $e->getFile() . ':' . $e->getLine());
            return 'New conversation';
        } catch (\Error $e) {
            error_log('ChatFormProcessor::summarizeConversation - Error: ' . $e->getMessage());
            error_log('ChatFormProcessor::summarizeConversation - Error file: ' . $e->getFile() . ':' . $e->getLine());
            return 'New conversation';
        } catch (\Throwable $e) {
            error_log('ChatFormProcessor::summarizeConversation - Throwable: ' . $e->getMessage());
            error_log('ChatFormProcessor::summarizeConversation - Throwable file: ' . $e->getFile() . ':' . $e->getLine());
            return 'New conversation';
        }
    }
    
    /**
     * Evaluate static conditional logic
     */
    private function evaluateStaticConditions($step, $collectedData)
    {
        if (!isset($step['conditionalLogic'])) {
            return true;
        }
        
        $conditions = $step['conditionalLogic'];
        
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
     * Replace placeholders in text
     */
    private function replacePlaceholders($text, $collectedData)
    {
        foreach ($collectedData as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Get action by ID
     */
    private function getAction($actionId)
    {
        $action = $this->entityManager->getRepository(Action::class)->find($actionId);
        if (!$action) {
            return null;
        }
        
        // Convert to array format with form steps
        return [
            'id' => $action->getId(),
            'name' => $action->getName(),
            'actionType' => $action->getActionType() ?: 'basic',
            'formSteps' => json_decode($action->getFormSteps(), true) ?: [],
            'showImmediately' => $action->getShowImmediately(),
            'triggerInstruction' => $action->getTriggerInstruction(),
            'responseInstruction' => $action->getResponseInstruction()
        ];
    }
    
    /**
     * Check if chat has active form
     */
    public function hasActiveForm($chat)
    {
        $activeFormState = $chat->getActiveFormState();
        return !empty($activeFormState);
    }
    
    /**
     * Get active form state
     */
    public function getActiveFormState($chat)
    {
        return json_decode($chat->getActiveFormState(), true);
    }
    
    /**
     * Cancel active form
     */
    public function cancelActiveForm($chat)
    {
        $chat->setActiveFormState(null);
        $this->entityManager->flush();
        
        return [
            'type' => 'form_cancelled',
            'message' => 'Form cancelled. How else can I help you?'
        ];
    }
}