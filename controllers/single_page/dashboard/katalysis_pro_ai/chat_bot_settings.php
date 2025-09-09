<?php
namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Core;
use Config;
use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\User\User;
use KatalysisProAi\AiAgent;
use \NeuronAI\Chat\Messages\UserMessage;

use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\Http\Request;
use KatalysisProAi\RagAgent;
use KatalysisProAi\ChatFormProcessor;
use KatalysisProAi\Entity\Chat;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChatBotSettings extends DashboardPageController
{

    public function view()
    {

        $this->requireAsset('css', 'katalysis-ai');
        $this->requireAsset('javascript', 'katalysis-ai');

        $this->set('token', $this->app->make('token'));
        $this->set('form', $this->app->make('helper/form'));

        $config = $this->app->make('config');
        $this->set('instructions', $config->get('katalysis.aichatbot.instructions'));
        $this->set('linkSelectionRules', $config->get('katalysis.aichatbot.link_selection_rules', $this->getDefaultLinkSelectionRules()));
        $this->set('defaultLinkSelectionRules', $this->getDefaultLinkSelectionRules());
        $this->set('defaultInstructions', $this->getDefaultInstructions());
        $this->set('welcomeMessagePrompt', $config->get('katalysis.aichatbot.welcome_message_prompt', $this->getDefaultWelcomeMessagePrompt()));
        $this->set('defaultWelcomeMessagePrompt', $this->getDefaultWelcomeMessagePrompt());
        $this->set('essentialWelcomeMessageInstructions', $this->getEssentialWelcomeMessageInstructions());
        $this->set('contactPageID', $config->get('katalysis.aichatbot.contact_page_id', null));
        
        // Get the contact page URL if a page is selected
        $contactPageUrl = '/contact-us'; // Default fallback
        if ($config->get('katalysis.aichatbot.contact_page_id')) {
            try {
                $contactPage = \Page::getByID($config->get('katalysis.aichatbot.contact_page_id'));
                if ($contactPage && !$contactPage->isError()) {
                    $contactPageUrl = $contactPage->getCollectionLink();
                }
            } catch (\Exception $e) {
                // Keep default URL if there's an error
            }
        }
        $this->set('contactPageUrl', $contactPageUrl);
        
        $this->set('debugMode', (bool) $config->get('katalysis.aichatbot.debug_mode', false));
        $this->set('debugPageTitle', $config->get('katalysis.aichatbot.debug_page_title', ''));
        $this->set('debugPageType', $config->get('katalysis.aichatbot.debug_page_type', ''));
        $this->set('debugPageUrl', $config->get('katalysis.aichatbot.debug_page_url', ''));
        
        // Email configuration settings
        $this->set('email_from_email', $config->get('katalysis.aichatbot.sender_from_email', ''));
        $this->set('email_from_name', $config->get('katalysis.aichatbot.sender_from_name', ''));
        $this->set('recipient_emails', $config->get('katalysis.aichatbot.recipient_emails', ''));

        // Debug: Test actions if debug mode is enabled
        $debugActions = [];
        if ($config->get('katalysis.aichatbot.debug_mode', false)) {
            try {
                $actionService = new \KatalysisProAi\ActionService($this->app->make('Doctrine\ORM\EntityManager'));
                $actions = $actionService->getAllActions();
                
                foreach ($actions as $action) {
                    $debugActions[] = [
                        'id' => $action->getId(),
                        'name' => $action->getName(),
                        'icon' => $action->getIcon(),
                        'triggerInstruction' => $action->getTriggerInstruction(),
                        'responseInstruction' => $action->getResponseInstruction()
                    ];
                }
                
                // Also get the formatted prompt for debugging
                $this->set('debugActionsPrompt', $actionService->getActionsForPrompt());
                
            } catch (\Exception $e) {
                $this->set('debugActionsError', $e->getMessage());
            }
        }
        $this->set('debugActions', $debugActions);

        // Get available page types
        $pageTypes = \PageType::getList(false);
        $pageTypesList = [];
        foreach ($pageTypes as $pageType) {
            $pageTypesList[] = [
                'id' => $pageType->getPageTypeID(),
                'handle' => $pageType->getPageTypeHandle(),
                'name' => $pageType->getPageTypeDisplayName(),
                'isInternal' => $pageType->isPageTypeInternal(),
                'isFrequentlyAdded' => $pageType->isPageTypeFrequentlyAdded()
            ];
        }
        $this->set('pageTypes', $pageTypesList);

        $this->set('results', []);
    }

    public function save()
    {
        if (!$this->token->validate('ai.settings')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$this->error->has()) {
            $config = $this->app->make('config');
            $config->save('katalysis.aichatbot.instructions', (string) $this->post('instructions'));
            $config->save('katalysis.aichatbot.link_selection_rules', (string) $this->post('link_selection_rules'));
            $config->save('katalysis.aichatbot.welcome_message_prompt', (string) $this->post('welcome_message_prompt'));
            $config->save('katalysis.aichatbot.contact_page_id', (int) $this->post('contact_page_id'));
            $config->save('katalysis.aichatbot.debug_mode', (bool) $this->post('debug_mode'));
            $config->save('katalysis.aichatbot.debug_page_title', (string) $this->post('debug_page_title'));
            $config->save('katalysis.aichatbot.debug_page_type', (string) $this->post('debug_page_type'));
            $config->save('katalysis.aichatbot.debug_page_url', (string) $this->post('debug_page_url'));
            
            // Save email configuration settings
            $config->save('katalysis.aichatbot.sender_from_email', (string) $this->post('sender_from_email'));
            $config->save('katalysis.aichatbot.sender_from_name', (string) $this->post('sender_from_name'));
            $config->save('katalysis.aichatbot.recipient_emails', (string) $this->post('recipient_emails'));
            
            $this->flash('success', t('Chat bot settings have been updated.'));
        }
        return $this->buildRedirect($this->action());
    }

    /**
     * Send chat notification email via AJAX
     */
    public function send_chat_email()
    {
        $response = new JsonResponse();
        
        if (!$this->token->validate('send_chat_email')) {
            return $response->setData([
                'success' => false,
                'message' => $this->token->getErrorMessage()
            ]);
        }

        $chatId = (int) $this->post('chat_id');
        if (!$chatId) {
            return $response->setData([
                'success' => false,
                'message' => t('Invalid chat ID')
            ]);
        }

        try {
            // Get the chat entity
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(Chat::class, $chatId);
            
            if (!$chat) {
                return $response->setData([
                    'success' => false,
                    'message' => t('Chat not found')
                ]);
            }

            // Get email configuration
            $config = $this->app->make('config');
            $senderEmail = $config->get('katalysis.aichatbot.sender_from_email');
            $senderName = $config->get('katalysis.aichatbot.sender_from_name');
            $recipientEmails = $config->get('katalysis.aichatbot.recipient_emails');

            if (!$senderEmail || !$recipientEmails) {
                return $response->setData([
                    'success' => false,
                    'message' => t('Email configuration is incomplete. Please check sender email and recipient emails in settings.')
                ]);
            }

            // Parse recipient emails (support comma-separated list)
            $recipients = array_map('trim', explode(',', $recipientEmails));
            $recipients = array_filter($recipients); // Remove empty values

            if (empty($recipients)) {
                return $response->setData([
                    'success' => false,
                    'message' => t('No valid recipient emails found')
                ]);
            }

            // Format chat content similar to view page
            $chatContent = $this->formatChatContentForEmail($chat);

            // Send email using Concrete CMS mail system
            $emailsSent = 0;
            foreach ($recipients as $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    try {
                        // Use the mail helper (Loader approach)
                        $mailService = \Loader::helper('mail');
                        $mailService->from($senderEmail, $senderName ?: 'Chat Bot Notification');
                        $mailService->to($recipient);
                        $mailService->setSubject(t('Chat Notification - Chat #%s', $chat->getId()));
                        
                        // Add template parameters
                        $mailService->addParameter('chat', $chat);
                        $mailService->addParameter('chatContent', $chatContent);
                        $mailService->addParameter('chatUrl', $this->app->make('url/resolver/path')->resolve(['/dashboard/katalysis_pro_ai/chats/view_chat', $chat->getId()]));
                        
                        // Load template from the package
                        $mailService->load('chat_notification', 'katalysis_pro_ai');
                        
                        if ($mailService->sendMail()) {
                            $emailsSent++;
                        }
                        
                        // Clean up for next iteration
                        $mailService->reset();
                    } catch (\Exception $e) {
                        // Log individual send failures but continue trying other recipients
                        error_log('Failed to send email to ' . $recipient . ': ' . $e->getMessage());
                    }
                }
            }

            if ($emailsSent > 0) {
                return $response->setData([
                    'success' => true,
                    'message' => t('Email sent successfully to %s recipient(s)', $emailsSent)
                ]);
            } else {
                return $response->setData([
                    'success' => false,
                    'message' => t('Failed to send email to any recipients')
                ]);
            }

        } catch (\Exception $e) {
            return $response->setData([
                'success' => false,
                'message' => t('Error: %s', $e->getMessage())
            ]);
        }
    }

    /**
     * Format chat content for email template (similar to view page formatting)
     */
    private function formatChatContentForEmail($chat)
    {
        $formattedContent = [];
        
        // Add welcome message if exists
        if ($chat->getWelcomeMessage() && trim($chat->getWelcomeMessage()) !== '') {
            $formattedContent[] = [
                'sender' => 'ai',
                'type' => 'welcome',
                'content' => $chat->getWelcomeMessage(),
                'timestamp' => null
            ];
        }
        
        // Add chat history
        if ($chat->getCompleteChatHistory()) {
            $chatHistory = json_decode($chat->getCompleteChatHistory(), true);
            if (is_array($chatHistory) && !empty($chatHistory)) {
                foreach ($chatHistory as $message) {
                    $formattedContent[] = [
                        'sender' => $message['sender'] ?? 'unknown',
                        'type' => 'message',
                        'content' => $message['content'] ?? '',
                        'timestamp' => isset($message['timestamp']) ? date('H:i:s', $message['timestamp'] / 1000) : null
                    ];
                }
            }
        }
        
        return $formattedContent;
    }

    /**
     * Get default instructions
     */
    private function getDefaultInstructions(): string
    {
        return "You are an expert AI sales assistant for Katalysis, a UK-based web design and development company.
You have access to indexed content from the Katalysis website and should use this information to provide accurate, contextual responses.

RESPONSE GUIDELINES:
• Keep responses concise and to the point (preferably one sentence)
• Use UK spelling: specialise, organisation, customise, optimise
• Include a call to action encouraging contact
• Include a link to the contact page when suggesting users contact us
• Be helpful and professional

EXAMPLES OF GOOD RESPONSES:
- 'Yes, we specialise in Concrete CMS hosting and would be happy to discuss your requirements.'
- 'We offer customised web development services - get in touch to learn more.'
- 'Our team can design websites for law firms - contact us for a consultation.'

AVOID:
- Long explanations or detailed feature lists
- US spelling (specialize, organization, customize, optimize)
- Responses without a call to action";
    }

    /**
     * Get default link selection rules
     */
    private function getDefaultLinkSelectionRules(): string
    {
        return "You are selecting the most relevant links for a user's question. Be very selective and only choose links that directly address the user's specific needs.

Selection Criteria (in order of priority):
1. **Direct Relevance**: Choose documents whose titles/content directly answer the user's question
2. **Specific Information**: Prefer documents that provide specific, actionable information over general pages
3. **Service Matching**: If the user asks about a specific service, prioritize pages about that service
4. **Location Context**: Only include location pages if the user specifically mentions a location
5. **Quality Over Quantity**: It's better to select 1-2 highly relevant links than 4 mediocre ones

Selection Rules:
- Select 1-3 links (prefer fewer, more relevant links)
- Avoid generic pages unless they're the only relevant option
- Don't select location pages unless location is mentioned in the question
- Prioritize pages with higher relevance scores when relevance is similar
- If no documents are truly relevant, return 'none'";
    }

    /**
     * Get essential link selection instructions that are always appended
     */
    private function getEssentialLinkSelectionInstructions(): string
    {
        return "

RESPONSE FORMAT REQUIREMENTS:
• You must respond with ONLY numbers separated by commas (e.g., '1,3,4') or 'none' if no links are relevant
• Do not include any other text, explanations, or formatting
• Do not use bullet points, dashes, or any other characters
• Maximum 4 numbers total
• Numbers must correspond to the document numbers listed above

EXAMPLES OF CORRECT RESPONSES:
- '1,3' (selects documents 1 and 3)
- '2' (selects only document 2)
- 'none' (no documents are relevant)
- '1,2,4' (selects documents 1, 2, and 4)

EXAMPLES OF INCORRECT RESPONSES:
- 'I think documents 1 and 3 would be helpful'
- '1, 3' (with spaces)
- 'Documents 1 and 3'
- '1. and 3.' (with periods)";
    }

    /**
     * Get default welcome message prompt
     */
    private function getDefaultWelcomeMessagePrompt(): string
    {
        return "Generate a friendly welcome message for Katalysis, a UK-based web design and development company. 

Context:
- Time of day: {time_of_day}
- Current page: {page_title}
- Page URL: {page_url}

Requirements:
- Include time-based greeting (Good morning/afternoon/evening)
- Keep it concise but complete (1-2 sentences)
- Be welcoming, appreciative and professional
- End with \"How can we help?\" or similar invitation
- Keep under 40 words for readability";
    }

    /**
     * Get essential welcome message formatting instructions that are always appended
     */
    private function getEssentialWelcomeMessageInstructions(): string
    {
        return "

RESPONSE FORMAT REQUIREMENTS:
• Respond with ONLY the welcome message text - no additional formatting, quotes, or explanations
• Do not include phrases like \"Here's a welcome message:\" or similar introductions
• Do not use markdown formatting, bullet points, or special characters
• Keep the response as plain text only
• Do not include any meta-commentary about the message

EXAMPLES OF CORRECT RESPONSES:
- \"Good morning! Welcome to our website. How can we help?\"
- \"Good afternoon and welcome to our site. How can we assist you today?\"
- \"Good evening! Thank you for visiting us. How can we help?\"

EXAMPLES OF INCORRECT RESPONSES:
- \"Here's a welcome message: Good morning! How can we help?\"
- \"*Good morning! Welcome to our site. How can we help?*\"
- \"I'll generate a welcome message: Good morning! How can we help?\"
- \"Good morning! Welcome to our site. How can we help?\" (with quotes)";
    }

    /**
     * Get default link selection rules via AJAX
     */
    public function get_default_link_rules()
    {
        if (!$this->token->validate('ai.settings')) {
            return new JsonResponse(['error' => $this->token->getErrorMessage()], 400);
        }
        
        return new JsonResponse([
            'rules' => $this->getDefaultLinkSelectionRules()
        ]);
    }

    /**
     * AJAX method to get default welcome message prompt
     */
    public function get_default_welcome_prompt()
    {
        if (!$this->token->validate('ai.settings')) {
            return new JsonResponse(['error' => $this->token->getErrorMessage()], 400);
        }
        
        return new JsonResponse([
            'prompt' => $this->getDefaultWelcomeMessagePrompt()
        ]);
    }

    /**
     * Clear chat history files from the server
     */
    public function clear_chat_history()
    {
        try {
            $chatDirectory = DIR_APPLICATION . '/files/neuron';

            // Clear RAG chat history (key '1')
            $ragChatFile = $chatDirectory . '/1.json';
            if (file_exists($ragChatFile)) {
                unlink($ragChatFile);
            }

            // Clear basic AI chat history (key '2')
            $basicChatFile = $chatDirectory . '/2.json';
            if (file_exists($basicChatFile)) {
                unlink($basicChatFile);
            }

            // Also clear any other chat files that might exist
            $chatFiles = glob($chatDirectory . '/*.json');
            foreach ($chatFiles as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            return new JsonResponse(['success' => true, 'message' => 'Chat history cleared successfully']);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    public function ask_ai()
    {
        // Initialize variables
        $message = null;
        $mode = 'rag'; // Default to RAG mode
        $isNewChat = false; // Track if this is a new chat session

        // Get the request object
        $request = $this->app->make('request');
        
        // Debug log the raw request
        error_log('ASK_AI DEBUG - Raw request content: ' . $request->getContent());

        // Check if this is a JSON request
        $contentType = $request->headers->get('Content-Type');
        $rawContent = $request->getContent();

        // Check if content looks like JSON (starts with { or [)
        if (
            strpos($contentType, 'application/json') !== false ||
            (trim($rawContent) && (strpos(trim($rawContent), '{') === 0 || strpos(trim($rawContent), '[') === 0))
        ) {
            // Handle JSON request
            $jsonData = json_decode($rawContent, true);
            $message = $jsonData['message'] ?? null;
            $mode = $jsonData['mode'] ?? 'rag';
            $isNewChat = $jsonData['new_chat'] ?? false;
            $pageType = $jsonData['page_type'] ?? null;
            $pageTitle = $jsonData['page_title'] ?? null;
            $pageUrl = $jsonData['page_url'] ?? null;
            $welcomeMessage = $jsonData['welcome_message'] ?? null;
            $isWelcomeGeneration = $jsonData['is_welcome_generation'] ?? false;
            $chatId = $jsonData['chat_id'] ?? null;
            $sessionId = $jsonData['session_id'] ?? null;
            
            // Debug log for welcome generation detection
            error_log('ASK_AI DEBUG - isWelcomeGeneration flag value: ' . ($isWelcomeGeneration ? 'true' : 'false'));
            error_log('ASK_AI DEBUG - isNewChat flag value: ' . ($isNewChat ? 'true' : 'false'));
            error_log('ASK_AI DEBUG - mode value: ' . $mode);
            if ($isWelcomeGeneration) {
                error_log('WELCOME GENERATION - Detected welcome message generation request, will skip chat creation');
            }
        } else {
            // Handle form data request
            $data = $request->request->all();
            $message = $data['message'] ?? null;
            $mode = $data['mode'] ?? 'rag';
            $isNewChat = $data['new_chat'] ?? false;
            $pageType = $data['page_type'] ?? null;
            $pageTitle = $data['page_title'] ?? null;
            $pageUrl = $data['page_url'] ?? null;
            $welcomeMessage = $data['welcome_message'] ?? null;
            $isWelcomeGeneration = $data['is_welcome_generation'] ?? false;
            $chatId = $data['chat_id'] ?? null;
            $sessionId = $data['session_id'] ?? null;
        }

        // Create new chat record if this is a new chat session
        if ($isNewChat) {
            $chatId = $this->createNewChatRecord($mode, $pageType, $pageTitle, $pageUrl, $sessionId);
            
            // Save the welcome message if provided
            if ($chatId && $welcomeMessage) {
                try {
                    $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
                    $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
                    if ($chat) {
                        $chat->setWelcomeMessage($welcomeMessage);
                        $entityManager->flush();
                    }
                } catch (\Exception $e) {
                    \Log::addError('Failed to save welcome message to new chat record: ' . $e->getMessage());
                }
            }
        }

        // Update chat record with message information
        if ($chatId) {
            if ($isNewChat) {
                // For new chats, this user message becomes the first message
                $this->updateChatWithFirstMessage($chatId, $message);
            } else {
                // For existing chats, this user message becomes the last message
                $this->updateChatWithLastMessage($chatId, $message);
            }
        }

        // Check for active form processing
        $formChatId = $chatId; // Use the chat_id from the request or newly created above
        $isFormField = $jsonData['is_form_field'] ?? false;
        $fieldKey = $jsonData['field_key'] ?? null;
        
        error_log('SESSION TRACKING - sessionId: ' . ($sessionId ?? 'NULL'));
        error_log('SESSION TRACKING - formChatId: ' . ($formChatId ?? 'NULL'));
        error_log('SESSION TRACKING - chatId: ' . ($chatId ?? 'NULL'));
        error_log('SESSION TRACKING - isFormField: ' . ($isFormField ? 'true' : 'false'));
        
        if ($sessionId && $formChatId && $isFormField && $fieldKey) {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->getRepository(Chat::class)->find($formChatId);
            
            if ($chat) {
                $formProcessor = new ChatFormProcessor($this->app);
                
                // Check if there's an active form
                if ($formProcessor->hasActiveForm($chat)) {
                    $activeFormState = $formProcessor->getActiveFormState($chat);
                    
                    // Process the form field response using the field key from the request
                    $formResult = $formProcessor->processFieldResponse(
                        $chat, 
                        $activeFormState['action_id'], 
                        $fieldKey,  // Use the field key from the user's response
                        $message
                    );
                    
                    // Return form-specific response
                    return $this->handleFormResponse($formResult);
                }
            }
        }

        // Get AI configuration
        $config = $this->app->make('config');
        $openaiKey = $config->get('katalysis.ai.open_ai_key');
        $openaiModel = $config->get('katalysis.ai.open_ai_model');
        $maxLinksPerResponse = (int) $config->get('katalysis.ai.max_links_per_response', 3);
        $linkSelectionRules = $config->get('katalysis.aichatbot.link_selection_rules', $this->getDefaultLinkSelectionRules());

        if (!isset($message) || empty($message)) {
            $message = 'Please apologise for not understanding the question';
        }

        try {
            // Test if configuration is valid
            if (empty($openaiKey) || empty($openaiModel)) {
                return new JsonResponse(
                    ['error' => 'AI configuration is incomplete. Please check your OpenAI API key and model settings.'],
                    400
                );
            }

            if ($mode === 'rag') {
                // Check if this is a welcome message request (backup check for RAG mode)
                $isWelcomeRequest = $isWelcomeGeneration || 
                    strpos($message, 'Generate a friendly welcome message') !== false || 
                    strpos($message, 'Generate a short, friendly welcome message') !== false ||
                    strpos($message, '{time_of_day}') !== false ||
                    strpos($message, '{page_title}') !== false;
                
                error_log('ASK_AI DEBUG - RAG mode isWelcomeRequest check result: ' . ($isWelcomeRequest ? 'true' : 'false'));
                
                // RAG Mode: Use RagAgent with its instructions
                $ragAgent = new RagAgent();
                $ragAgent->setApp($this->app);

                try {
                    // Get the response using page context if available
                    if ($pageType || $pageTitle || $pageUrl) {
                        $response = $ragAgent->answerWithPageContext(new UserMessage($message), $pageType, $pageTitle, $pageUrl);
                    } else {
                        $response = $ragAgent->answer(new UserMessage($message));
                    }
                    $responseContent = $response->getContent();
                } catch (\Exception $e) {
                    \Log::addError('RAG agent failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                    throw new \Exception('RAG processing failed: ' . $e->getMessage());
                }


                // Handle case where AI returns JSON instead of plain text
                if (strpos($responseContent, '{') === 0 && strpos($responseContent, '}') !== false) {
                    $jsonData = json_decode($responseContent, true);
                    if ($jsonData && isset($jsonData['response'])) {
                        $responseContent = $jsonData['response'];
                    } elseif ($jsonData && isset($jsonData['content'])) {
                        $responseContent = $jsonData['content'];
                    } elseif ($jsonData && isset($jsonData['message'])) {
                        $responseContent = $jsonData['message'];
                    }
                }

                // Extract action IDs from response
                $actionIds = [];
                if (preg_match('/\[ACTIONS:([^\]]+)\]/', $responseContent, $matches)) {
                    $actionIds = array_map('intval', explode(',', $matches[1]));
                    // Remove the action tag from the response content
                    $responseContent = preg_replace('/\[ACTIONS:[^\]]+\]/', '', $responseContent);
                    $responseContent = trim($responseContent);
                }

                // Get relevant documents for metadata links using the parent class method
                try {
                    $relevantDocs = $ragAgent->retrieveDocuments(new UserMessage($message));
                } catch (\Exception $e) {
                    \Log::addError('Document retrieval failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                    // Continue without documents rather than failing completely
                    $relevantDocs = [];
                }

                // Track page types used in the response
                $pageTypesUsed = [];
                $pageTypesUsed[] = $pageType; // Add the current page type if available

                // Extract page types from relevant documents
                foreach ($relevantDocs as $doc) {
                    if (isset($doc->metadata['pagetype']) && !empty($doc->metadata['pagetype'])) {
                        $docPageType = $doc->metadata['pagetype'];
                        if (!in_array($docPageType, $pageTypesUsed)) {
                            $pageTypesUsed[] = $docPageType;
                        }
                    }
                }

                // AI-based link selection - let the AI choose the most relevant links
                $metadata = [];
                $seenUrls = []; // Track seen URLs to avoid duplicates

                // Prepare candidate documents for AI selection
                $candidateDocs = [];
                foreach ($relevantDocs as $doc) {
                    if (isset($doc->metadata['url']) && !empty($doc->metadata['url'])) {
                        $url = $doc->metadata['url'];

                        // Skip if we've already seen this URL
                        if (in_array($url, $seenUrls)) {
                            continue;
                        }

                        $title = $doc->sourceName ?? '';
                        $content = $doc->content ?? '';
                        $score = $doc->score ?? 0;
                        $pageType = $doc->metadata['pagetype'] ?? '';

                        // Only include documents with reasonable relevance scores
                        if ($score >= 0.3) {
                            $candidateDocs[] = [
                                'title' => $title,
                                'url' => $url,
                                'content' => $content,
                                'score' => $score,
                                'page_type' => $pageType
                            ];
                            $seenUrls[] = $url;
                        }
                    }
                }

                // If we have candidate documents, let AI select the best ones
                if (!empty($candidateDocs)) {
                    // Limit candidates to prevent token overflow
                    $maxCandidates = min(count($candidateDocs), 15);
                    $candidateDocs = array_slice($candidateDocs, 0, $maxCandidates);

                    // Create a prompt for AI to select the most relevant links
                    $linkSelectionPrompt = "You are helping to select the most relevant links for a user's question. 

User Question: \"{$message}\"

Available documents (with titles and URLs):
";

                    foreach ($candidateDocs as $index => $doc) {
                        $linkSelectionPrompt .= ($index + 1) . ". Title: \"{$doc['title']}\" | URL: {$doc['url']} | Page Type: {$doc['page_type']} | Relevance Score: " . number_format($doc['score'], 3) . "\n";
                    }
                    
                    $linkSelectionPrompt .= "\n" . $linkSelectionRules;
                    $linkSelectionPrompt .= "\n" . $this->getEssentialLinkSelectionInstructions();

                    try {
                        // Use the same AI provider to select links
                        $aiProvider = new \NeuronAI\Providers\OpenAI\OpenAI(
                            key: $openaiKey,
                            model: $openaiModel
                        );

                        $linkSelectionResponse = $aiProvider->chat([
                            new \NeuronAI\Chat\Messages\UserMessage($linkSelectionPrompt)
                        ]);

                        $selectedNumbers = $linkSelectionResponse->getContent();

                        // Parse the AI's selection
                        $selectedIndices = [];

                        // Check if AI returned 'none' (no relevant documents)
                        if (strtolower(trim($selectedNumbers)) === 'none') {
                            $selectedIndices = []; // No documents selected
                        } else {
                            // Parse numbers from AI response
                            if (preg_match_all('/\d+/', $selectedNumbers, $matches)) {
                                foreach ($matches[0] as $number) {
                                    $index = (int) $number - 1; // Convert to 0-based index
                                    if ($index >= 0 && $index < count($candidateDocs)) {
                                        $selectedIndices[] = $index;
                                    }
                                }
                            }
                        }

                        // If AI selection failed or returned invalid numbers, fall back to top-scoring documents
                        if (empty($selectedIndices)) {
                            // Sort by score and take top documents
                            usort($candidateDocs, function ($a, $b) {
                                return $b['score'] <=> $a['score'];
                            });
                            $selectedIndices = array_keys(array_slice($candidateDocs, 0, $maxLinksPerResponse));
                        }

                        // Build metadata from AI-selected documents
                        foreach ($selectedIndices as $index) {
                            if (isset($candidateDocs[$index])) {
                                $doc = $candidateDocs[$index];
                                $metadata[] = [
                                    'title' => $doc['title'],
                                    'url' => $doc['url'],
                                    'score' => $doc['score'],
                                    'original_score' => $doc['score'],
                                    'ai_selected' => true,
                                    'selection_reason' => 'AI chose this as most relevant to the user\'s question'
                                ];
                            }
                        }

                    } catch (\Exception $e) {
                        // Fallback to top-scoring documents if AI selection fails
                        usort($candidateDocs, function ($a, $b) {
                            return $b['score'] <=> $a['score'];
                        });

                        $topDocs = array_slice($candidateDocs, 0, $maxLinksPerResponse);
                        foreach ($topDocs as $doc) {
                            $metadata[] = [
                                'title' => $doc['title'],
                                'url' => $doc['url'],
                                'score' => $doc['score'],
                                'original_score' => $doc['score'],
                                'ai_selected' => false,
                                'selection_reason' => 'Fallback to top-scoring documents (AI selection failed)'
                            ];
                        }
                    }
                }

                // Return response with metadata and page types used
                $responseData = [
                    'content' => $responseContent,
                    'metadata' => $metadata,
                    'page_types_used' => $pageTypesUsed,
                    'current_page_type' => $pageType,
                    'context_info' => [
                        'current_page_title' => $pageTitle,
                        'current_page_url' => $pageUrl,
                        'total_documents_retrieved' => count($relevantDocs),
                        'page_types_from_documents' => array_unique(array_filter(array_map(function ($doc) {
                            return $doc->metadata['pagetype'] ?? null;
                        }, $relevantDocs)))
                    ],
                    'debug_info' => [
                        'link_selection' => [
                            'total_documents_processed' => count($relevantDocs),
                            'documents_with_urls' => count(array_filter($relevantDocs, function ($doc) {
                                return isset($doc->metadata['url']) && !empty($doc->metadata['url']);
                            })),
                            'candidate_documents' => count($candidateDocs ?? []),
                            'ai_selected_links' => count($metadata)
                        ],
                        'scoring_details' => array_map(function ($link) {
                            return [
                                'title' => $link['title'],
                                'url' => $link['url'],
                                'final_score' => $link['score'],
                                'original_score' => $link['original_score'] ?? $link['score'],
                                'selection_reason' => $link['selection_reason'] ?? null
                            ];
                        }, $metadata)
                    ]
                ];

                // Add actions if any were suggested by the AI
                $suggestedActions = []; // Initialize empty array
                $immediateFormAction = null;
                $entityManager = $this->app->make('Doctrine\ORM\EntityManager'); // Add missing entityManager for RAG mode
                
                if (!empty($actionIds)) {
                    error_log('AI RESPONSE - Found action IDs: ' . implode(',', $actionIds));
                    $actionService = new \KatalysisProAi\ActionService($this->app->make('Doctrine\ORM\EntityManager'));
                    
                    foreach ($actionIds as $actionId) {
                        error_log('AI RESPONSE - Processing action ID: ' . $actionId);
                        $action = $actionService->getActionById($actionId);
                        if ($action) {
                            error_log('AI RESPONSE - Found action: ' . $action->getName() . ' (ID: ' . $action->getId() . ')');
                            $actionData = [
                                'id' => $action->getId(),
                                'name' => $action->getName(),
                                'icon' => $action->getIcon(),
                                'triggerInstruction' => $action->getTriggerInstruction(),
                                'responseInstruction' => $action->getResponseInstruction(),
                                'actionType' => $action->getActionType(),
                                'showImmediately' => $action->getShowImmediately(),
                                'formSteps' => $action->getFormSteps() ? json_decode($action->getFormSteps(), true) : []
                            ];
                            
                            // Check if this is a form action with show_immediately enabled
                            if (in_array($action->getActionType(), ['form', 'dynamic_form', 'simple_form'])) {
                                error_log('ACTION CHECK - Action: ' . $action->getName() . ' (ID: ' . $action->getId() . ') - Type: ' . $action->getActionType());
                                error_log('ACTION CHECK - Show immediately: ' . ($action->getShowImmediately() ? 'true' : 'false'));
                                
                                if ($action->getShowImmediately()) {
                                    error_log('IMMEDIATE FORM FOUND - Action: ' . $action->getName() . ' has show_immediately: true');
                                    // This action should be shown immediately - store the first one found
                                    if (!$immediateFormAction) {
                                        $immediateFormAction = $actionData;
                                        error_log('IMMEDIATE FORM SET - Setting immediateFormAction to: ' . $action->getName());
                                    }
                                } else {
                                    error_log('ACTION CHECK - Action: ' . $action->getName() . ' does not have show_immediately: true');
                                }
                            } else {
                                error_log('ACTION CHECK - Action: ' . $action->getName() . ' is not a form type: ' . $action->getActionType());
                            }
                            
                            $suggestedActions[] = $actionData;
                        }
                    }
                    
                    error_log('AFTER ACTION LOOP - immediateFormAction: ' . ($immediateFormAction ? 'SET' : 'NULL'));
                    error_log('AFTER ACTION LOOP - suggestedActions count: ' . count($suggestedActions));
                    
                    // If we have an immediate form action, start it instead of showing buttons
                    error_log('BEFORE IMMEDIATE FORM CHECK - About to check if immediateFormAction exists');
                    if ($immediateFormAction) {
                        error_log('IMMEDIATE FORM PROCESSING - About to start form processing...');
                        error_log('IMMEDIATE FORM DETECTED - Action: ' . $immediateFormAction['name'] . ' (ID: ' . $immediateFormAction['id'] . ')');
                        error_log('IMMEDIATE FORM - Form config: ' . json_encode($immediateFormAction['formConfig']));
                        error_log('IMMEDIATE FORM - Form steps: ' . json_encode($immediateFormAction['formSteps']));
                        
                        // Start the form immediately
                        error_log('IMMEDIATE FORM - About to create ChatFormProcessor...');
                        try {
                            $chatFormProcessor = new \KatalysisProAi\ChatFormProcessor($this->app);
                            error_log('IMMEDIATE FORM - ChatFormProcessor created successfully');
                        } catch (\Error $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Error): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        } catch (\Exception $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Exception): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        } catch (\Throwable $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Throwable): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        }
                        
                        try {
                            // Use formChatId if chatId is empty
                            $effectiveChatId = $chatId ?: $formChatId;
                            
                            // Validate chat ID before trying to find the entity
                            if (empty($effectiveChatId)) {
                                error_log('IMMEDIATE FORM ERROR - Chat ID is empty, looking for existing chat by session ID');
                                
                                // First try to find existing chat by session ID
                                if ($sessionId) {
                                    $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
                                    $existingChat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)
                                        ->findOneBy(['sessionId' => $sessionId]);
                                    if ($existingChat) {
                                        $effectiveChatId = $existingChat->getId();
                                        error_log('IMMEDIATE FORM - Found existing chat with ID: ' . $effectiveChatId . ' for session: ' . $sessionId);
                                    }
                                }
                                
                                // Only create new chat if no existing one found
                                if (empty($effectiveChatId)) {
                                    $effectiveChatId = $this->createNewChatRecord($mode, $pageType, $pageTitle, $pageUrl, $sessionId);
                                    if (!$effectiveChatId) {
                                        error_log('IMMEDIATE FORM ERROR - Failed to create new chat record');
                                        throw new \Exception('Failed to create new chat record');
                                    }
                                    error_log('IMMEDIATE FORM - Created new chat with ID: ' . $effectiveChatId);
                                }
                            }
                            
                            // Get the chat entity first
                            try {
                                $chat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)->find($effectiveChatId);
                                error_log('IMMEDIATE FORM - Repository query completed');
                            } catch (\Exception $e) {
                                error_log('IMMEDIATE FORM ERROR - Repository query failed: ' . $e->getMessage());
                                error_log('IMMEDIATE FORM ERROR - Repository error file: ' . $e->getFile() . ':' . $e->getLine());
                                throw $e;
                            }
                            
                            if (!$chat) {
                                error_log('IMMEDIATE FORM ERROR - Chat not found for ID: ' . $effectiveChatId);
                                throw new \Exception('Chat not found');
                            }
                            error_log('IMMEDIATE FORM - Chat entity found: ' . $chat->getId());
                            
                            // Pass the full action array (not just the ID) to match the existing form system
                            error_log('IMMEDIATE FORM - Calling startForm with action: ' . json_encode($immediateFormAction));
                            $formStartResult = $chatFormProcessor->startForm($chat, $immediateFormAction);
                            error_log('IMMEDIATE FORM - startForm result: ' . json_encode($formStartResult));
                            
                            if ($formStartResult && !isset($formStartResult['error'])) {
                                error_log('IMMEDIATE FORM SUCCESS - Returning form start result');
                                
                                // Check if this is a simple_form that should show all fields at once
                                if (isset($formStartResult['type']) && $formStartResult['type'] === 'simple_form_started') {
                                    error_log('IMMEDIATE FORM - Simple form detected, returning simple_form_started response');
                                    // For simple_form, return the result as-is
                                    $response = $formStartResult;
                                    $response['chat_id'] = $effectiveChatId; // Ensure chat_id is included
                                } else {
                                    error_log('IMMEDIATE FORM - Progressive form detected, returning form_started response');
                                    // For progressive forms, format as form_started
                                    $response = [
                                        'type' => 'form_started',
                                        'content' => $formStartResult['question'],
                                        'chat_id' => $effectiveChatId,
                                        'step_data' => [
                                            'field_key' => $formStartResult['stepKey'],
                                            'field_type' => $formStartResult['fieldType'],
                                            'options' => $formStartResult['options'] ?? null,
                                            'validation' => $formStartResult['validation'] ?? [],
                                            'placeholder' => $formStartResult['placeholder'] ?? null
                                        ],
                                        'progress' => [
                                            'current_step' => 1,
                                            'total_steps' => count($immediateFormAction['formSteps']),
                                            'percentage' => 0
                                        ],
                                        'is_form_active' => true
                                    ];
                                }
                                
                                error_log('IMMEDIATE FORM SUCCESS - Response data: ' . json_encode($response));
                                
                                // Update chatId for any later use in the main method
                                $chatId = $effectiveChatId;
                                
                                error_log('IMMEDIATE FORM SUCCESS - About to return JsonResponse');
                                return new JsonResponse($response);
                            } else {
                                error_log('IMMEDIATE FORM ERROR - startForm returned invalid result: ' . json_encode($formStartResult));
                            }
                        } catch (\Exception $e) {
                            error_log('IMMEDIATE FORM EXCEPTION - Error starting immediate form: ' . $e->getMessage());
                            error_log('IMMEDIATE FORM EXCEPTION - Stack trace: ' . $e->getTraceAsString());
                            // Fall through to show regular response with buttons
                            error_log('IMMEDIATE FORM EXCEPTION - Falling through to show regular response');
                        }
                    } else {
                        error_log('NO IMMEDIATE FORM - No action with show_immediately: true found');
                    }
                    
                    error_log('AFTER IMMEDIATE FORM CHECK - About to set responseData actions');
                    $responseData['actions'] = $suggestedActions;
                }

                // Add chat ID if a new chat was created (but not for welcome generation)
                if ($chatId && !$isWelcomeRequest) {
                    $responseData['chat_id'] = $chatId;
                }

                error_log('FINAL RESPONSE - Returning response with ' . count($suggestedActions) . ' actions');
                error_log('FINAL RESPONSE - Response data: ' . json_encode($responseData));

                return new JsonResponse($responseData);

            } else {
                // Basic Mode: Use regular AiAgent or direct AI call for welcome messages
                if (strpos($message, 'Generate a friendly welcome message') !== false || strpos($message, 'Generate a short, friendly welcome message') !== false) {
                    // This is a welcome message request - use direct AI call without action instructions
                    // Don't create chat records or save welcome messages here - only when user actually engages
                    
                    $aiProvider = new \NeuronAI\Providers\OpenAI\OpenAI(
                        key: Config::get('katalysis.ai.open_ai_key'),
                        model: Config::get('katalysis.ai.open_ai_model')
                    );

                    $response = $aiProvider->chat([
                        new \NeuronAI\Chat\Messages\UserMessage($message)
                    ]);

                    $responseContent = $response->getContent();
                } else {
                    // Regular basic mode - use AiAgent
                    $agent = new AiAgent($this->app);
                    $response = $agent->chat(
                        new UserMessage($message)
                    );

                    $responseContent = $response->getContent();
                }

                // Extract action IDs from response (same logic as complex mode)
                $actionIds = [];
                error_log('AiAgent Response Content: ' . $responseContent);
                if (preg_match('/\[ACTIONS:([^\]]+)\]/', $responseContent, $matches)) {
                    $actionIds = array_map('intval', explode(',', $matches[1]));
                    error_log('Found action IDs: ' . implode(',', $actionIds));
                    // Remove the action tag from the response content
                    $responseContent = preg_replace('/\[ACTIONS:[^\]]+\]/', '', $responseContent);
                    $responseContent = trim($responseContent);
                } else {
                    error_log('No ACTIONS tags found in response');
                }

                $responseData = [
                    'content' => $responseContent,
                    'metadata' => [],
                    'page_types_used' => [],
                    'current_page_type' => $pageType ?? '',
                    'context_info' => [],
                    'debug_info' => []
                ];

                // Process actions for basic mode (same logic as complex mode)
                $suggestedActions = []; // Initialize empty array
                $immediateFormAction = null;
                $entityManager = $this->app->make('Doctrine\ORM\EntityManager'); // Add missing entityManager
                
                // Check if this is a welcome message request (backup check)
                $isWelcomeRequest = $isWelcomeGeneration || 
                    strpos($message, 'Generate a friendly welcome message') !== false || 
                    strpos($message, 'Generate a short, friendly welcome message') !== false ||
                    strpos($message, '{time_of_day}') !== false ||
                    strpos($message, '{page_title}') !== false;
                
                error_log('ASK_AI DEBUG - isWelcomeRequest check result: ' . ($isWelcomeRequest ? 'true' : 'false'));
                
                // Ensure we have a chatId for form processing (but not for welcome generation)
                // Check if formChatId is already available from frontend data
                $formChatId = $jsonData['chat_id'] ?? null;
                if (!$chatId && !$isWelcomeRequest) {
                    // First try to use the chat_id sent from frontend
                    if ($formChatId) {
                        $chatId = $formChatId;
                        error_log('RAG MODE - Using formChatId from frontend: ' . $chatId);
                    } else if ($sessionId) {
                        // Fallback to finding existing chat by session ID
                        error_log('RAG MODE - Looking for existing chat with session ID: ' . $sessionId);
                        $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
                        $existingChat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)
                            ->findOneBy(['sessionId' => $sessionId]);
                        if ($existingChat) {
                            $chatId = $existingChat->getId();
                            error_log('RAG MODE - Found existing chat with ID: ' . $chatId . ' for session: ' . $sessionId);
                        } else {
                            error_log('RAG MODE - No existing chat found for session ID: ' . $sessionId);
                        }
                    } else {
                        error_log('RAG MODE - No chat_id or session ID provided, cannot look for existing chat');
                    }
                    
                    // Only create new chat if no existing one found
                    if (!$chatId) {
                        $chatId = $this->createNewChatRecord($mode, $pageType, $pageTitle, $pageUrl, $sessionId);
                        error_log('RAG MODE - Created new chat with ID: ' . $chatId);
                    }
                }
                error_log('RAG MODE - chatId value: ' . ($chatId ?? 'NULL')); // Debug chatId
                
                // Skip action processing and chat updates for welcome generation
                if (!$isWelcomeRequest && !empty($actionIds)) {
                    $actionService = new \KatalysisProAi\ActionService($this->app->make('Doctrine\ORM\EntityManager'));
                    
                    foreach ($actionIds as $actionId) {
                        error_log('AI RESPONSE - Processing action ID: ' . $actionId);
                        $action = $actionService->getActionById($actionId);
                        if ($action) {
                            error_log('AI RESPONSE - Found action: ' . $action->getName() . ' (ID: ' . $action->getId() . ')');
                            $actionData = [
                                'id' => $action->getId(),
                                'name' => $action->getName(),
                                'icon' => $action->getIcon(),
                                'triggerInstruction' => $action->getTriggerInstruction(),
                                'responseInstruction' => $action->getResponseInstruction(),
                                'actionType' => $action->getActionType(),
                                'showImmediately' => $action->getShowImmediately(),
                                'formSteps' => $action->getFormSteps() ? json_decode($action->getFormSteps(), true) : []
                            ];
                            
                            // Check if this is a form action with show_immediately enabled
                            if (in_array($action->getActionType(), ['form', 'dynamic_form', 'simple_form'])) {
                                error_log('ACTION CHECK - Action: ' . $action->getName() . ' (ID: ' . $action->getId() . ') - Type: ' . $action->getActionType());
                                error_log('ACTION CHECK - Show immediately: ' . ($action->getShowImmediately() ? 'true' : 'false'));
                                
                                if ($action->getShowImmediately()) {
                                    error_log('IMMEDIATE FORM FOUND - Action: ' . $action->getName() . ' has show_immediately: true');
                                    // This action should be shown immediately - store the first one found
                                    if (!$immediateFormAction) {
                                        $immediateFormAction = $actionData;
                                        error_log('IMMEDIATE FORM SET - Setting immediateFormAction to: ' . $action->getName());
                                    }
                                } else {
                                    error_log('ACTION CHECK - Action: ' . $action->getName() . ' does not have show_immediately: true');
                                }
                            } else {
                                error_log('ACTION CHECK - Action: ' . $action->getName() . ' is not a form type: ' . $action->getActionType());
                            }
                            
                            $suggestedActions[] = $actionData;
                        }
                    }
                    
                    error_log('AFTER ACTION LOOP - immediateFormAction: ' . ($immediateFormAction ? 'SET' : 'NULL'));
                    error_log('AFTER ACTION LOOP - suggestedActions count: ' . count($suggestedActions));
                    
                    // If we have an immediate form action, start it instead of showing buttons
                    error_log('BEFORE IMMEDIATE FORM CHECK - About to check if immediateFormAction exists');
                    if ($immediateFormAction) {
                        error_log('IMMEDIATE FORM PROCESSING - About to start form processing...');
                        error_log('IMMEDIATE FORM DETECTED - Action: ' . $immediateFormAction['name'] . ' (ID: ' . $immediateFormAction['id'] . ')');
                        error_log('IMMEDIATE FORM - Form config: ' . json_encode($immediateFormAction['formConfig']));
                        error_log('IMMEDIATE FORM - Form steps: ' . json_encode($immediateFormAction['formSteps']));
                        
                        // Start the form immediately
                        error_log('IMMEDIATE FORM - About to create ChatFormProcessor...');
                        try {
                            $chatFormProcessor = new \KatalysisProAi\ChatFormProcessor($this->app);
                            error_log('IMMEDIATE FORM - ChatFormProcessor created successfully');
                        } catch (\Error $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Error): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        } catch (\Exception $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Exception): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        } catch (\Throwable $e) {
                            error_log('IMMEDIATE FORM ERROR - Failed to create ChatFormProcessor (Throwable): ' . $e->getMessage());
                            error_log('IMMEDIATE FORM ERROR - Stack trace: ' . $e->getTraceAsString());
                            throw $e; // Re-throw to be caught by outer try-catch
                        }
                        
                        try {
                            // Use formChatId if chatId is empty
                            $effectiveChatId = $chatId ?: $formChatId;
                            
                            // Validate chat ID before trying to find the entity
                            if (empty($effectiveChatId)) {
                                error_log('IMMEDIATE FORM ERROR - Chat ID is empty, looking for existing chat by session ID');
                                
                                // First try to find existing chat by session ID
                                if ($sessionId) {
                                    $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
                                    $existingChat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)
                                        ->findOneBy(['sessionId' => $sessionId]);
                                    if ($existingChat) {
                                        $effectiveChatId = $existingChat->getId();
                                        error_log('IMMEDIATE FORM - Found existing chat with ID: ' . $effectiveChatId . ' for session: ' . $sessionId);
                                    }
                                }
                                
                                // Only create new chat if no existing one found
                                if (empty($effectiveChatId)) {
                                    $effectiveChatId = $this->createNewChatRecord($mode, $pageType, $pageTitle, $pageUrl, $sessionId);
                                    if (!$effectiveChatId) {
                                        error_log('IMMEDIATE FORM ERROR - Failed to create new chat record');
                                        throw new \Exception('Failed to create new chat record');
                                    }
                                    error_log('IMMEDIATE FORM - Created new chat with ID: ' . $effectiveChatId);
                                }
                            }
                            
                            // Get the chat entity first
                            try {
                                $chat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)->find($effectiveChatId);
                                error_log('IMMEDIATE FORM - Repository query completed');
                            } catch (\Exception $e) {
                                error_log('IMMEDIATE FORM ERROR - Repository query failed: ' . $e->getMessage());
                                error_log('IMMEDIATE FORM ERROR - Repository error file: ' . $e->getFile() . ':' . $e->getLine());
                                throw $e;
                            }
                            
                            if (!$chat) {
                                error_log('IMMEDIATE FORM ERROR - Chat not found for ID: ' . $effectiveChatId);
                                throw new \Exception('Chat not found');
                            }
                            error_log('IMMEDIATE FORM - Chat entity found: ' . $chat->getId());
                            
                            // Pass the full action array (not just the ID) to match the existing form system
                            error_log('IMMEDIATE FORM - Calling startForm with action: ' . json_encode($immediateFormAction));
                            $formStartResult = $chatFormProcessor->startForm($chat, $immediateFormAction);
                            error_log('IMMEDIATE FORM - startForm result: ' . json_encode($formStartResult));
                            
                            if ($formStartResult && !isset($formStartResult['error'])) {
                                error_log('IMMEDIATE FORM SUCCESS - Returning form start result');
                                
                                // Check if this is a simple_form that should show all fields at once
                                if (isset($formStartResult['type']) && $formStartResult['type'] === 'simple_form_started') {
                                    error_log('IMMEDIATE FORM - Simple form detected, returning simple_form_started response');
                                    // For simple_form, return the result as-is
                                    $response = $formStartResult;
                                    $response['chat_id'] = $effectiveChatId; // Ensure chat_id is included
                                } else {
                                    error_log('IMMEDIATE FORM - Progressive form detected, returning form_started response');
                                    // For progressive forms, format as form_started
                                    $response = [
                                        'type' => 'form_started',
                                        'content' => $formStartResult['question'],
                                        'chat_id' => $effectiveChatId,
                                        'step_data' => [
                                            'field_key' => $formStartResult['stepKey'],
                                            'field_type' => $formStartResult['fieldType'],
                                            'options' => $formStartResult['options'] ?? null,
                                            'validation' => $formStartResult['validation'] ?? [],
                                            'placeholder' => $formStartResult['placeholder'] ?? null
                                        ],
                                        'progress' => [
                                            'current_step' => 1,
                                            'total_steps' => count($immediateFormAction['formSteps']),
                                            'percentage' => 0
                                        ],
                                        'is_form_active' => true
                                    ];
                                }
                                
                                error_log('IMMEDIATE FORM SUCCESS - Response data: ' . json_encode($response));
                                
                                // Update chatId for any later use in the main method
                                $chatId = $effectiveChatId;
                                
                                error_log('IMMEDIATE FORM SUCCESS - About to return JsonResponse');
                                return new JsonResponse($response);
                            } else {
                                error_log('IMMEDIATE FORM ERROR - startForm returned invalid result: ' . json_encode($formStartResult));
                            }
                        } catch (\Exception $e) {
                            error_log('IMMEDIATE FORM EXCEPTION - Error starting immediate form: ' . $e->getMessage());
                            error_log('IMMEDIATE FORM EXCEPTION - Stack trace: ' . $e->getTraceAsString());
                            // Fall through to show regular response with buttons
                            error_log('IMMEDIATE FORM EXCEPTION - Falling through to show regular response');
                        }
                    } else {
                        error_log('NO IMMEDIATE FORM - No action with show_immediately: true found');
                    }
                    
                    error_log('AFTER IMMEDIATE FORM CHECK - About to set responseData actions');
                    $responseData['actions'] = $suggestedActions;
                }

                // Add chat ID if a new chat was created (but not for welcome generation)
                if ($chatId && !$isWelcomeRequest) {
                    $responseData['chat_id'] = $chatId;
                }

                error_log('FINAL RESPONSE - Returning response with ' . count($suggestedActions) . ' actions');
                error_log('FINAL RESPONSE - Response data: ' . json_encode($responseData));

                return new JsonResponse($responseData);
            }

        } catch (\Exception $e) {
            // Log the specific error for debugging
            \Log::addError('AI request failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            
            // Return more specific error information
            return new JsonResponse([
                'error' => 'AI processing failed',
                'details' => $e->getMessage(),
                'type' => get_class($e)
            ], 500);
        }
    }

    /**
     * Handle action button clicks
     */
    public function execute_action()
    {
        // No token validation needed (like ask_ai method)

        // Get request data
        $request = $this->app->make('request');
        
        // Parse JSON request body
        $rawContent = $request->getContent();
        $jsonData = json_decode($rawContent, true);
        
        $actionId = $jsonData['action_id'] ?? null;
        $conversationContext = $jsonData['conversation_context'] ?? '';

        if (!$actionId) {
            return new JsonResponse(['error' => 'Action ID is required'], 400);
        }

        try {
            // Get the action
            $actionService = new \KatalysisProAi\ActionService($this->app->make('Doctrine\ORM\EntityManager'));
            $action = $actionService->getActionById($actionId);

            if (!$action) {
                return new JsonResponse(['error' => 'Action not found'], 404);
            }

            // Get AI configuration
            $config = $this->app->make('config');
            $openaiKey = $config->get('katalysis.ai.open_ai_key');
            $openaiModel = $config->get('katalysis.ai.open_ai_model');

            if (empty($openaiKey) || empty($openaiModel)) {
                return new JsonResponse(['error' => 'AI configuration is incomplete'], 400);
            }

            // Create a prompt for the AI to execute the action
            $prompt = "The user has clicked the '{$action->getName()}' action button. ";
            $prompt .= "Here is the instruction for what to do: {$action->getResponseInstruction()}";
            
            if (!empty($conversationContext)) {
                $prompt .= "\n\nConversation context: {$conversationContext}";
            }

            // Execute the action using AI
            $aiProvider = new \NeuronAI\Providers\OpenAI\OpenAI(
                key: $openaiKey,
                model: $openaiModel
            );

            $response = $aiProvider->chat([
                new \NeuronAI\Chat\Messages\UserMessage($prompt)
            ]);

            $responseContent = $response->getContent();

            return new JsonResponse([
                'content' => $responseContent,
                'action_name' => $action->getName(),
                'action_icon' => $action->getIcon()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to execute action: ' . $e->getMessage()], 500);
        }
    }







    /**
     * Create a new chat record in the database
     */
    private function createNewChatRecord(string $mode, ?string $pageType, ?string $pageTitle, ?string $pageUrl, ?string $sessionId = null): ?int
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            
            $chat = new \KatalysisProAi\Entity\Chat();
            
            // Set chat properties
            $chat->setStarted(new \DateTime());
            $chat->setCreatedDate(new \DateTime());
            
            // Get user's geographical location from IP address
            $location = $this->getUserGeographicalLocation();
            $chat->setLocation($location);
            
            // Get the actual chat model being used
            $config = $this->app->make('config');
            $chatModel = $config->get('katalysis.ai.open_ai_model', 'gpt-4');
            $chat->setLlm($chatModel);
            
            // Get current page information from Concrete CMS
            $currentPage = \Page::getCurrentPage();
            if ($currentPage && !$currentPage->isError()) {
                $chat->setLaunchPageUrl($currentPage->getCollectionLink());
                $chat->setLaunchPageType($currentPage->getPageTypeHandle());
                $chat->setLaunchPageTitle($currentPage->getCollectionName());
            } else {
                // Fallback to provided values if current page is not available
                $chat->setLaunchPageUrl($pageUrl ?: '');
                $chat->setLaunchPageType($pageType ?: '');
                $chat->setLaunchPageTitle($pageTitle ?: '');
            }
            
            // Set UTM parameters from request
            $request = $this->app->make('request');
            $chat->setUtmId($request->get('utm_id', ''));
            $chat->setUtmSource($request->get('utm_source', ''));
            $chat->setUtmMedium($request->get('utm_medium', ''));
            $chat->setUtmCampaign($request->get('utm_campaign', ''));
            $chat->setUtmTerm($request->get('utm_term', ''));
            $chat->setUtmContent($request->get('utm_content', ''));
            
            // Set created by (current user or null if not logged in)
            $user = new \Concrete\Core\User\User();
            if ($user && $user->isRegistered()) {
                $chat->setCreatedBy($user->getUserID());
            }
            
            // Set session ID if provided
            if ($sessionId) {
                $chat->setSessionId($sessionId);
                error_log('CREATE CHAT - Setting session ID: ' . $sessionId . ' for new chat');
            } else {
                error_log('CREATE CHAT - No session ID provided for new chat');
            }
            
            // Persist the chat record
            $entityManager->persist($chat);
            $entityManager->flush();
            
            return $chat->getId();
            
        } catch (\Exception $e) {
            // Log the error but don't fail the chat request
            \Log::addError('Failed to create chat record: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user's geographical location from IP address
     */
    private function getUserGeographicalLocation(): string
    {
        try {
            $request = $this->app->make('request');
            $ip = $request->getClientIp();
            
            // Skip local/private IPs
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return 'Local/Private IP';
            }
            
            // Use a free IP geolocation service
            $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city";
            $response = file_get_contents($url);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['status']) && $data['status'] === 'success') {
                    $location = [];
                    
                    if (!empty($data['city'])) {
                        $location[] = $data['city'];
                    }
                    if (!empty($data['regionName'])) {
                        $location[] = $data['regionName'];
                    }
                    if (!empty($data['country'])) {
                        $location[] = $data['country'];
                    }
                    
                    return !empty($location) ? implode(', ', $location) : 'Unknown Location';
                }
            }
            
            return 'Location Unknown';
            
        } catch (\Exception $e) {
            \Log::addError('Failed to get user location: ' . $e->getMessage());
            return 'Location Error';
        }
    }

    /**
     * Create a clean text preview from AI response content
     * Removes HTML, links, action buttons, and more info sections
     */
    private function createCleanMessagePreview(string $message): string
    {
        // Remove HTML tags
        $cleanText = strip_tags($message);
        
        // Remove action buttons and more info sections
        $cleanText = preg_replace('/More Information:.*$/s', '', $cleanText);
        $cleanText = preg_replace('/Test Button.*$/s', '', $cleanText);
        
        // Remove any remaining action button text
        $cleanText = preg_replace('/\[.*?\]/', '', $cleanText);
        
        // Clean up extra whitespace and newlines
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);
        $cleanText = trim($cleanText);
        
        // Remove any remaining special characters that might make the preview unclear
        $cleanText = str_replace(['&nbsp;', '&amp;', '&quot;', '&lt;', '&gt;'], [' ', '&', '"', '<', '>'], $cleanText);
        
        // Limit length for preview (e.g., 150 characters)
        if (strlen($cleanText) > 150) {
            $cleanText = substr($cleanText, 0, 147) . '...';
        }
        
        return $cleanText;
    }

    /**
     * Update chat record with first user message preview
     */
    private function updateChatWithFirstMessage(int $chatId, string $message): void
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
            
            if ($chat && empty($chat->getFirstMessage())) {
                // For user messages, just clean up whitespace (no HTML to remove)
                $cleanPreview = trim(preg_replace('/\s+/', ' ', $message));
                
                // Limit length for preview
                if (strlen($cleanPreview) > 150) {
                    $cleanPreview = substr($cleanPreview, 0, 147) . '...';
                }
                
                $chat->setFirstMessage($cleanPreview);
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
        } catch (\Exception $e) {
            \Log::addError('Failed to update chat with first message: ' . $e->getMessage());
        }
    }

    /**
     * Update chat record with last message preview
     */
    private function updateChatWithLastMessage(int $chatId, string $message): void
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
            
            if ($chat) {
                // Create clean text preview (removes HTML, links, action buttons)
                $cleanPreview = $this->createCleanMessagePreview($message);
                
                $chat->setLastMessage($cleanPreview);
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
        } catch (\Exception $e) {
            \Log::addError('Failed to update chat with last message: ' . $e->getMessage());
        }
    }

    /**
     * Increment user message count for a chat record
     */
    private function incrementUserMessageCount(int $chatId): void
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
            
            if ($chat) {
                $currentCount = $chat->getUserMessageCount() ?? 0;
                $chat->setUserMessageCount($currentCount + 1);
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
        } catch (\Exception $e) {
            \Log::addError('Failed to increment user message count: ' . $e->getMessage());
        }
    }

    /**
     * Update chat record with complete chat history
     */
    private function updateChatWithCompleteHistory(int $chatId, array $messages): void
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
            
            if ($chat) {
                // Convert messages to JSON for storage
                $chatHistoryJson = json_encode($messages, JSON_PRETTY_PRINT);
                
                // Update the complete chat history
                $chat->setCompleteChatHistory($chatHistoryJson);
                
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
        } catch (\Exception $e) {
            // Log the error but don't fail the chat request
            \Log::addError('Failed to update chat with complete history: ' . $e->getMessage());
        }
    }

    /**
     * Log chat from chatbot block to database
     */
    public function log_chat()
    {
        try {
            $request = $this->app->make('request');
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'error' => 'Invalid request data'
                ]);
            }
            
            $chatbotId = $data['chatbot_id'] ?? '';
            $pageTitle = $data['page_title'] ?? '';
            $pageUrl = $data['page_url'] ?? '';
            $pageType = $data['page_type'] ?? '';
            $messages = $data['messages'] ?? [];
            $timestamp = $data['timestamp'] ?? time();
            $sessionId = $data['session_id'] ?? '';
            
            if (empty($messages)) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'error' => 'No messages to log'
                ]);
            }
            
            // Create or update chat record
            $chatId = $this->createOrUpdateChatRecord($chatbotId, $pageTitle, $pageUrl, $pageType, $sessionId);
            
            if ($chatId) {
                // Log individual messages
                $this->logChatMessages($chatId, $messages);
                
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => true,
                    'message' => 'Chat logged successfully. Chat ID: ' . $chatId,
                    'chat_id' => $chatId
                ]);
            } else {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'error' => 'Failed to create chat record'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::addError('Failed to log chat: ' . $e->getMessage());
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => false,
                'error' => 'Failed to log chat: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update existing chat record in database
     */
    public function update_chat()
    {
        try {
            $request = $this->app->make('request');
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'error' => 'Invalid request data'
                ]);
            }
            
            $chatId = $data['chat_id'] ?? 0;
            $messages = $data['messages'] ?? [];
            
            if (empty($chatId)) {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'success' => false,
                    'error' => 'No chat ID provided'
                ]);
            }
            
            // Update the existing chat record with new messages
            $this->logChatMessages($chatId, $messages);
            
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => true,
                'message' => 'Chat updated successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::addError('Failed to update chat: ' . $e->getMessage());
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => false,
                'error' => 'Failed to update chat: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create or update chat record for chatbot block
     */
    private function createOrUpdateChatRecord(string $chatbotId, string $pageTitle, string $pageUrl, string $pageType, string $sessionId = ''): ?int
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            
            // Check if there's already an existing chat record for this session
            $chat = null;
            if (!empty($sessionId)) {
                $chat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)
                    ->findOneBy(['sessionId' => $sessionId]);
            }
            
            // Only create a new chat record if one doesn't already exist for this session
            if (!$chat) {
                $chat = new \KatalysisProAi\Entity\Chat();
                
                // Set chat record properties (only for new chats)
                $chat->setStarted(new \DateTime());
                $chat->setLocation($this->getUserGeographicalLocation());
                $chat->setLlm('gpt-4');
                $chat->setLaunchPageTitle($pageTitle);
                $chat->setLaunchPageUrl($pageUrl);
                $chat->setLaunchPageType($pageType);
                $chat->setCreatedDate(new \DateTime());
                $chat->setUtmSource('chatbot_block');
                $chat->setUtmMedium('chatbot');
                $chat->setUtmCampaign('website_chat');
                $chat->setUtmTerm($chatbotId);
                $chat->setUtmContent('block_chat');
                $chat->setSessionId($sessionId);
                
                // Set created by (current user or null if not logged in)
                $user = new \Concrete\Core\User\User();
                if ($user && $user->isRegistered()) {
                    $chat->setCreatedBy($user->getUserID());
                }
                
                // Persist the new chat record
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
            return $chat->getId();
            
        } catch (\Exception $e) {
            \Log::addError('Failed to create/update chat record: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log individual chat messages
     */
    private function logChatMessages(int $chatId, array $messages): void
    {
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->find(\KatalysisProAi\Entity\Chat::class, $chatId);
            
            if ($chat) {
                // Count user messages for engagement tracking
                $userMessageCount = 0;
                foreach ($messages as $message) {
                    if (isset($message['sender']) && $message['sender'] === 'user') {
                        $userMessageCount++;
                    }
                }
                
                // Find first user message for firstMessage field
                $firstUserMessage = null;
                foreach ($messages as $message) {
                    if (isset($message['sender']) && $message['sender'] === 'user') {
                        $firstUserMessage = $message;
                        break;
                    }
                }
                
                // Find last message for lastMessage field
                $lastMessage = end($messages);
                
                // Update all fields in one operation
                $chat->setUserMessageCount($userMessageCount);
                
                // Set first message if not already set
                if ($firstUserMessage && isset($firstUserMessage['content']) && empty($chat->getFirstMessage())) {
                    $cleanPreview = trim(preg_replace('/\s+/', ' ', $firstUserMessage['content']));
                    if (strlen($cleanPreview) > 150) {
                        $cleanPreview = substr($cleanPreview, 0, 147) . '...';
                    }
                    $chat->setFirstMessage($cleanPreview);
                }
                
                // Set last message
                if ($lastMessage && isset($lastMessage['content'])) {
                    $cleanPreview = $this->createCleanMessagePreview($lastMessage['content']);
                    $chat->setLastMessage($cleanPreview);
                }
                
                // Store complete chat history
                $chatHistoryJson = json_encode($messages, JSON_PRETTY_PRINT);
                $chat->setCompleteChatHistory($chatHistoryJson);
                
                // Persist all changes in one operation
                $entityManager->persist($chat);
                $entityManager->flush();
            }
            
        } catch (\Exception $e) {
            \Log::addError('Failed to log chat messages: ' . $e->getMessage());
        }
    }

    /**
     * Handle form processing responses
     */
    private function handleFormResponse($formResult)
    {
        switch ($formResult['type']) {
            case 'next_step':
                $step = $formResult['step'];
                $progress = $formResult['progress'];
                
                return new JsonResponse([
                    'type' => 'form_step',
                    'content' => $step['question'],
                    'step_data' => [
                        'field_key' => $step['stepKey'],
                        'field_type' => $step['fieldType'],
                        'options' => $step['options'] ?? null,
                        'validation' => $step['validation'] ?? [],
                        'placeholder' => $step['placeholder'] ?? null
                    ],
                    'progress' => $progress,
                    'is_form_active' => true
                ]);
                
            case 'form_complete':
                $completionAction = $formResult['completion_action'];
                
                // Generate AI response using the response instruction as a prompt
                $followupMessage = $completionAction['followup_message'] ?? null;
                $responseInstruction = $completionAction['response_instruction'] ?? $formResult['message'] ?? null;
                
                if (!empty($followupMessage)) {
                    // Use the generated followup message if available
                    $aiResponseContent = $followupMessage;
                } elseif (!empty($responseInstruction)) {
                    // Try to generate AI response using the response instruction
                    try {
                        // Create context with form data
                        $formDataSummary = '';
                        if (!empty($formResult['collected_data'])) {
                            $formDataSummary = "Based on the form submission:\n";
                            foreach ($formResult['collected_data'] as $key => $value) {
                                $formDataSummary .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
                            }
                            $formDataSummary .= "\n";
                        }
                        
                        $aiPrompt = $formDataSummary . $responseInstruction;
                        
                        // Use RagAgent instead of AiAgent since it works more reliably
                        $ragAgent = new RagAgent();
                        $ragAgent->setApp($this->app);
                        $response = $ragAgent->answer(new UserMessage($aiPrompt));
                        $rawContent = $response->getContent();
                        
                        // Remove ACTIONS tags from form completion responses
                        $aiResponseContent = preg_replace('/\s*\[ACTIONS:[^\]]*\]/', '', $rawContent);
                        
                    } catch (\Exception $e) {
                        // AI generation failed, use fallback completion message
                        $userName = $formResult['collected_data']['name'] ?? '';
                        if ($userName) {
                            $aiResponseContent = "Thank you " . $userName . " for completing the form! We'll review your information and get back to you soon.";
                        } else {
                            $aiResponseContent = "Thank you for completing the form! We'll review your information and get back to you soon.";
                        }
                    }
                } else {
                    // No instruction available, use default
                    $aiResponseContent = 'Thank you for completing the form!';
                }
                
                // Process completion action
                $response = [
                    'type' => 'form_complete',
                    'content' => $aiResponseContent,
                    'completion_action' => $completionAction['action'],
                    'collected_data' => $formResult['collected_data']
                ];
                
                // Add follow-up actions based on AI decision
                if ($completionAction['action'] === 'schedule_demo') {
                    $response['actions'] = [
                        [
                            'id' => 'demo_calendar',
                            'name' => 'Schedule Demo',
                            'icon' => 'fas fa-calendar-alt'
                        ]
                    ];
                } elseif ($completionAction['action'] === 'send_pricing') {
                    $response['actions'] = [
                        [
                            'id' => 'pricing_info',
                            'name' => 'Get Pricing',
                            'icon' => 'fas fa-dollar-sign'
                        ]
                    ];
                }
                
                return new JsonResponse($response);
                
            case 'validation_error':
                return new JsonResponse([
                    'type' => 'form_validation_error',
                    'content' => $formResult['error'],
                    'step_data' => [
                        'field_key' => $formResult['step']['stepKey'],
                        'field_type' => $formResult['step']['fieldType'],
                        'options' => $formResult['step']['options'] ?? null,
                        'validation' => $formResult['step']['validation'] ?? []
                    ],
                    'is_form_active' => true
                ]);
                
            default:
                return new JsonResponse([
                    'error' => 'Unknown form response type'
                ], 500);
        }
    }
    
    /**
     * Start a form from an action
     */
    public function start_form()
    {
        try {
            $request = $this->app->make('request');
            $jsonData = json_decode($request->getContent(), true);
            
            error_log('start_form request data: ' . print_r($jsonData, true));
            
            $actionId = $jsonData['action_id'] ?? null;
            $chatId = $jsonData['chat_id'] ?? null;
            $sessionId = $jsonData['session_id'] ?? null;
            
            if (!$actionId) {
                return new JsonResponse(['error' => 'Missing action ID'], 400);
            }
            
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            
            // If no chat ID provided, try to find existing chat by session ID before creating new
            if (!$chatId) {
                if ($sessionId) {
                    $chat = $entityManager->getRepository(Chat::class)
                        ->findOneBy(['sessionId' => $sessionId]);
                    if ($chat) {
                        $chatId = $chat->getId();
                        error_log('start_form - Found existing chat with ID: ' . $chatId . ' for session: ' . $sessionId);
                    }
                }
                
                // Only create new chat if no existing one found
                if (!$chatId) {
                    $chat = new Chat();
                    $chat->setUserId(0); // Anonymous user for now
                    $chat->setMode('form');
                    $chat->setConversationHistory('[]');
                    $chat->setStartTime(new \DateTime());
                    if ($sessionId) {
                        $chat->setSessionId($sessionId);
                    }
                    
                    $entityManager->persist($chat);
                    $entityManager->flush();
                    $chatId = $chat->getId();
                    error_log('start_form - Created new chat with ID: ' . $chatId . ' for session: ' . $sessionId);
                }
            } else {
                $chat = $entityManager->getRepository(Chat::class)->find($chatId);
                if (!$chat) {
                    return new JsonResponse(['error' => 'Chat not found'], 404);
                }
            }
            
            // Get the action
            $actionService = new \KatalysisProAi\ActionService($entityManager);
            $actionEntity = $actionService->getActionById($actionId);
            
            error_log('start_form - Action entity found: ' . ($actionEntity ? 'YES' : 'NO'));
            if ($actionEntity) {
                error_log('start_form - Action details: ID=' . $actionEntity->getId() . ', Name=' . $actionEntity->getName() . ', Type=' . $actionEntity->getActionType());
            }
            
            if (!$actionEntity || !in_array($actionEntity->getActionType(), ['form', 'dynamic_form', 'simple_form'])) {
                error_log('start_form - Invalid form action: ' . ($actionEntity ? $actionEntity->getActionType() : 'NULL'));
                return new JsonResponse(['error' => 'Invalid form action'], 400);
            }
            
            // Convert action entity to array format expected by form processor
            $formStepsJson = $actionEntity->getFormSteps();
            $formSteps = $formStepsJson ? json_decode($formStepsJson, true) : [];
            
            error_log('start_form - Form steps JSON: ' . $formStepsJson);
            error_log('start_form - Form steps decoded: ' . json_encode($formSteps));
            
            // Check if action has form steps defined
            if (empty($formSteps) || !is_array($formSteps)) {
                error_log('start_form - No form steps defined for action ID: ' . $actionId);
                return new JsonResponse([
                    'error' => 'No form steps defined for this action',
                    'debug_info' => [
                        'action_id' => $actionId,
                        'action_name' => $actionEntity->getName(),
                        'action_type' => $actionEntity->getActionType(),
                        'form_steps_json' => $formStepsJson,
                        'form_steps_decoded' => $formSteps,
                        'form_steps_count' => is_array($formSteps) ? count($formSteps) : 0
                    ]
                ], 400);
            }
            
            // Create action array format expected by ChatFormProcessor
            $action = [
                'id' => $actionEntity->getId(),
                'name' => $actionEntity->getName(),
                'actionType' => $actionEntity->getActionType(),
                'icon' => $actionEntity->getIcon(),
                'triggerInstruction' => $actionEntity->getTriggerInstruction(),
                'responseInstruction' => $actionEntity->getResponseInstruction(),
                'formSteps' => $formSteps,
                'showImmediately' => $actionEntity->getShowImmediately()
            ];
            
            error_log('start_form - Action showImmediately setting: ' . ($actionEntity->getShowImmediately() ? 'true' : 'false'));
            
            // Note: showImmediately is designed to work when AI suggests actions in ask_ai endpoint
            // When users click buttons directly, this setting doesn't apply as they're explicitly choosing to start the form
            // The form will always start immediately when called via start_form endpoint
            
            // Handle different form types
            if ($actionEntity->getActionType() === 'simple_form') {
                // For simple forms, return all fields at once
                return new JsonResponse([
                    'type' => 'simple_form_started',
                    'content' => 'Please fill out the form below:',
                    'chat_id' => $chatId,
                    'action_type' => 'simple_form',
                    'form_steps' => $formSteps,
                    'action_id' => $actionId,
                    'action_name' => $actionEntity->getName(),
                    'is_form_active' => true
                ]);
            } else {
                // For regular forms, use existing step-by-step logic
                $formProcessor = new ChatFormProcessor($this->app);
                error_log('start_form - About to call startForm for action: ' . $actionEntity->getName());
                $firstStep = $formProcessor->startForm($chat, $action);
                
                error_log('start_form - startForm result: ' . json_encode($firstStep));
                
                if (!$firstStep) {
                    error_log('start_form - No form steps returned from startForm');
                    return new JsonResponse(['error' => 'No form steps defined'], 400);
                }
                
                return new JsonResponse([
                    'type' => 'form_started',
                    'content' => $firstStep['question'],
                    'chat_id' => $chatId,
                    'step_data' => [
                        'field_key' => $firstStep['stepKey'],
                        'field_type' => $firstStep['fieldType'],
                        'options' => $firstStep['options'] ?? null,
                        'validation' => $firstStep['validation'] ?? [],
                        'placeholder' => $firstStep['placeholder'] ?? null
                    ],
                    'progress' => [
                        'current_step' => 1,
                        'total_steps' => count($action['formSteps']),
                        'percentage' => 0
                    ],
                    'is_form_active' => true
                ]);
            }
            
        } catch (\Exception $e) {
            error_log('Form start failed: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString());
            
            // Return JSON error response instead of letting it bubble up as 500 HTML
            $response = new JsonResponse([
                'error' => 'Failed to start form: ' . $e->getMessage(),
                'debug_info' => [
                    'action_id' => $actionId,
                    'chat_id' => $chatId,
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                    'error_class' => get_class($e)
                ]
            ], 400); // Use 400 instead of 500 to ensure JSON response
            
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
    
    /**
     * Cancel an active form
     */
    public function cancel_form()
    {
        $request = $this->app->make('request');
        $jsonData = json_decode($request->getContent(), true);
        
        $chatId = $jsonData['chat_id'] ?? null;
        
        if (!$chatId) {
            return new JsonResponse(['error' => 'Missing chat ID'], 400);
        }
        
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $chat = $entityManager->getRepository(Chat::class)->find($chatId);
            
            if (!$chat) {
                return new JsonResponse(['error' => 'Chat not found'], 404);
            }
            
            $formProcessor = new ChatFormProcessor($entityManager);
            $result = $formProcessor->cancelActiveForm($chat);
            
            return new JsonResponse([
                'type' => 'form_cancelled',
                'content' => $result['message']
            ]);
            
        } catch (\Exception $e) {
            \Log::addError('Form cancellation failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to cancel form'], 500);
        }
    }
    
    /**
     * Test endpoint to verify routing works
     */
    public function test_endpoint()
    {
        error_log('test_endpoint method called');
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Test endpoint works']);
        exit;
    }

    /**
     * Submit simple form with all field data
     */
    public function submit_simple_form()
    {
        error_log('submit_simple_form method called');
        
        // Set error reporting to catch all issues
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        try {
            // Get the request object and handle JSON data
            $request = $this->app->make('request');
            
            // Try to get JSON data from request body first
            $requestContent = $request->getContent();
            $contentType = $request->headers->get('Content-Type');
            
            if (!empty($requestContent)) {
                // Try to decode as JSON first
                $jsonData = json_decode($requestContent, true);
                if ($jsonData === null) {
                    // JSON decode failed, try $_POST
                    $jsonData = $_POST;
                }
            } else {
                // No request content, use $_POST
                $jsonData = $_POST;
            }
            
            error_log('submit_simple_form request data: ' . print_r($jsonData, true));
            error_log('submit_simple_form raw request content: ' . $request->getContent());
            error_log('submit_simple_form content type: ' . $contentType);
            error_log('submit_simple_form json_decode result: ' . ($jsonData === null ? 'NULL' : 'NOT NULL'));
            
            $actionId = $jsonData['action_id'] ?? null;
            $chatId = $jsonData['chat_id'] ?? null;
            $formData = $jsonData['form_data'] ?? [];
            $sessionId = $jsonData['session_id'] ?? null; // Add session ID extraction
            
            error_log('submit_simple_form parsed values - actionId: ' . ($actionId ?? 'NULL') . ', chatId: ' . ($chatId ?? 'NULL') . ', sessionId: ' . ($sessionId ?? 'NULL'));
            
            if (!$actionId) {
                error_log('submit_simple_form FAILING - actionId is falsy: ' . var_export($actionId, true));
                $response = new JsonResponse(['error' => 'Missing action ID', 'debug' => [
                    'received_data' => $jsonData,
                    'action_id_value' => $actionId,
                    'action_id_isset' => isset($jsonData['action_id']),
                    'raw_content' => $request->getContent()
                ]], 400);
                $response->send();
                exit;
            }
            
            if (!$chatId) {
                return new JsonResponse(['error' => 'Missing chat ID'], 400);
            }
            
            if (empty($formData)) {
                return new JsonResponse(['error' => 'No form data provided'], 400);
            }
            
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            
            // Get the action and chat
            $actionService = new \KatalysisProAi\ActionService($entityManager);
            $actionEntity = $actionService->getActionById($actionId);
            
            if (!$actionEntity || $actionEntity->getActionType() !== 'simple_form') {
                return new JsonResponse(['error' => 'Invalid simple form action'], 400);
            }
            
            $chat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)->find($chatId);
            if (!$chat) {
                error_log('submit_simple_form - Chat not found for ID: ' . $chatId . ', trying to find by session ID: ' . ($sessionId ?? 'NULL'));
                
                // If chat not found and we have a session ID, try to find existing chat by session
                if ($sessionId) {
                    $existingChat = $entityManager->getRepository(\KatalysisProAi\Entity\Chat::class)
                        ->findOneBy(['sessionId' => $sessionId]);
                    if ($existingChat) {
                        $chat = $existingChat;
                        $chatId = $existingChat->getId();
                        error_log('submit_simple_form - Found existing chat by session ID: ' . $chatId);
                    }
                }
                
                if (!$chat) {
                    return new JsonResponse(['error' => 'Chat not found'], 404);
                }
            } else {
                error_log('submit_simple_form - Using provided chat ID: ' . $chatId);
            }
            
            // Validate form data against form steps
            $formStepsJson = $actionEntity->getFormSteps();
            $formSteps = $formStepsJson ? json_decode($formStepsJson, true) : [];
            
            if (empty($formSteps)) {
                return new JsonResponse(['error' => 'No form steps defined'], 400);
            }
            
            // Validate all required fields are provided
            $errors = [];
            foreach ($formSteps as $step) {
                $fieldKey = $step['field_key'] ?? $step['stepKey'] ?? null;
                if (!$fieldKey) continue;
                
                $required = ($step['validation']['required'] ?? true) !== false;
                $value = $formData[$fieldKey] ?? '';
                
                if ($required && empty($value)) {
                    $errors[] = "Field '{$fieldKey}' is required";
                }
            }
            
            if (!empty($errors)) {
                return new JsonResponse([
                    'error' => 'Validation failed',
                    'validation_errors' => $errors
                ], 400);
            }
            
            // Store the form submission in chat history
            $historyArray = json_decode($chat->getCompleteChatHistory(), true) ?? [];
            
            // Add form submission to history
            $formattedData = '';
            foreach ($formData as $key => $value) {
                $displayKey = ucfirst(str_replace('_', ' ', $key));
                $formattedData .= "{$displayKey}: {$value}\n";
            }
            
            $submissionRecord = [
                'sender' => 'user',
                'content' => "Form Submitted - {$actionEntity->getName()}:\n{$formattedData}",
                'timestamp' => time() * 1000, // Convert to milliseconds for consistency
                'type' => 'simple_form_submission',
                'action_id' => $actionId,
                'form_data' => $formData
            ];
            $historyArray[] = $submissionRecord;
            
            // Generate a personalized confirmation message
            $responseInstruction = $actionEntity->getResponseInstruction();
            
            if (!empty($responseInstruction)) {
                // Use the response instruction to create a personalized message
                $userName = $formData['name'] ?? $formData['Name'] ?? '';
                
                // Create a confirmation message based on the response instruction
                if (stripos($responseInstruction, 'thank') !== false && stripos($responseInstruction, 'touch') !== false) {
                    $responseMessage = "Thank you" . (!empty($userName) ? ", {$userName}," : "") . " for your form submission! We'll be in touch soon to arrange a convenient time. Is there anything else I can help you with?";
                } elseif (stripos($responseInstruction, 'thank') !== false) {
                    $responseMessage = "Thank you" . (!empty($userName) ? ", {$userName}," : "") . " for submitting the form! Your request has been received and we'll respond shortly.";
                } else {
                    // Use the response instruction with placeholder replacement
                    $processedResponse = $responseInstruction;
                    foreach ($formData as $key => $value) {
                        $processedResponse = str_replace("{{$key}}", $value, $processedResponse);
                        $processedResponse = str_replace("{{" . strtoupper($key) . "}}", $value, $processedResponse);
                    }
                    $responseMessage = $processedResponse;
                }
            } else {
                // Default confirmation message
                $userName = $formData['name'] ?? $formData['Name'] ?? '';
                $responseMessage = "Thank you" . (!empty($userName) ? ", {$userName}," : "") . " for your form submission! We'll be in touch soon.";
            }
            
            // Add the confirmation message to chat history
            $confirmationRecord = [
                'sender' => 'ai',
                'content' => $responseMessage,
                'timestamp' => time() * 1000, // Convert to milliseconds for consistency
                'type' => 'simple_form_confirmation'
            ];
            $historyArray[] = $confirmationRecord;
            
            // Update chat history with both form submission and confirmation
            $chat->setCompleteChatHistory(json_encode($historyArray));
            
            // Update the lastMessage field with the confirmation response
            $chat->setLastMessage($responseMessage);
            
            $entityManager->persist($chat);
            $entityManager->flush();
            
            $response = new JsonResponse([
                'success' => true,
                'message' => $responseMessage,
                'chat_id' => $chat->getId(),
                'submission_id' => $chat->getId() . '_' . time()
            ]);
            $response->send();
            exit;
            
        } catch (\Throwable $e) {
            error_log('Simple form submission failed: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString());
            
            // Ensure we always return JSON
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to submit form: ' . $e->getMessage(),
                'debug_info' => [
                    'action_id' => $actionId ?? 'undefined',
                    'chat_id' => $chatId ?? 'undefined',
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                    'error_class' => get_class($e)
                ]
            ]);
            exit;
        }
    }
    
    /**
     * Get action information
     */
    public function get_action_info()
    {
        $request = $this->app->make('request');
        $jsonData = json_decode($request->getContent(), true);
        
        $actionId = $jsonData['action_id'] ?? null;
        
        if (!$actionId) {
            return new JsonResponse(['error' => 'Missing action ID'], 400);
        }
        
        try {
            $entityManager = $this->app->make('Doctrine\ORM\EntityManager');
            $action = $entityManager->getRepository(\KatalysisProAi\Entity\Action::class)->find($actionId);
            
            if (!$action) {
                return new JsonResponse(['error' => 'Action not found'], 404);
            }
            
            return new JsonResponse([
                'id' => $action->getId(),
                'name' => $action->getName(),
                'actionType' => $action->getActionType() ?: 'basic',
                'icon' => $action->getIcon(),
                'triggerInstruction' => $action->getTriggerInstruction(),
                'responseInstruction' => $action->getResponseInstruction(),
                'showImmediately' => $action->getShowImmediately()
            ]);
            
        } catch (\Exception $e) {
            \Log::addError('Get action info failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to get action info'], 500);
        }
    }
}