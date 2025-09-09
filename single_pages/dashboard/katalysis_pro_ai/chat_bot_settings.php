<?php
defined('C5_EXECUTE') or die('Access Denied.');

use \Concrete\Core\Page\Page;
use \Concrete\Core\Support\Facade\Url;
use \Concrete\Core\Support\Facade\Config;
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$ps = $app->make('helper/form/page_selector');

?>

<form method="post" enctype="multipart/form-data" action="<?= $controller->action('save') ?>">
    <?php $token->output('ai.settings'); ?>
    <div id="ccm-dashboard-content-inner">

        <div class="row mb-5 justify-content-between">

            <div class="col-12 col-md-8 col-lg-6">
                <div class="alert alert-primary mb-5">
                    <h5><i class="fas fa-robot"></i> <?php echo t('AI Chat Bot System Overview'); ?></h5>
                    <p class="mb-3">
                        <?php echo t('This system provides an intelligent AI chatbot that can understand your website content and provide contextual responses. Here\'s how it works:'); ?>
                    </p>
                    <ul class="mb-0">
                        <li><strong><?php echo t('Content Indexing'); ?></strong> -
                            <?php echo t('Your website pages are automatically indexed, including key attributes like page titles, page types, URLs, and content.'); ?>
                        </li>
                        <li><strong><?php echo t('Context Awareness'); ?></strong> -
                            <?php echo t('The AI uses this indexed information to understand what page the user is on and provide relevant responses.'); ?>
                        </li>
                        <li><strong><?php echo t('Dynamic Responses'); ?></strong> -
                            <?php echo t('AI generates personalized welcome messages and intelligent responses based on the user\'s context and your content.'); ?>
                        </li>
                        <li><strong><?php echo t('Smart Link Selection'); ?></strong> -
                            <?php echo t('The system intelligently selects the most relevant links to include with responses, helping users find related information.'); ?>
                        </li>
                    </ul>
                </div>

                <fieldset class="mb-5">
                    <legend><?php echo t('Chat Bot Settings'); ?></legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <?php echo $form->label(
                                    "instructions",
                                    t("Main AI Instructions"),
                                    [
                                        "class" => "control-label"
                                    ]
                                ); ?>

                                <?php echo $form->textarea(
                                    "instructions",
                                    $instructions,
                                    [
                                        "class" => "form-control",
                                        "max-length" => "10000",
                                        "style" => "field-sizing: content;"
                                    ]
                                ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6><?php echo t('Main AI Instructions'); ?></h6>
                        <p><?php echo t('These instructions define how the AI responds to user questions. They guide the AI\'s personality, tone, and approach to providing information.'); ?>
                        </p>

                        <h6><?php echo t('Available Context Placeholders'); ?></h6>
                        <p><?php echo t('You can use these placeholders to make responses context-aware:'); ?></p>
                        <ul>
                            <li><code>{page_type}</code> -
                                <?php echo t('The page type of the current page (e.g., location, service, blog, page)'); ?>
                            </li>
                            <li><code>{page_title}</code> - <?php echo t('The title of the current page'); ?></li>
                            <li><code>{page_url}</code> - <?php echo t('The URL of the current page'); ?></li>
                        </ul>

                        <h6><?php echo t('Example Instructions'); ?></h6>
                        <div class="alert alert-success">
                            <p><strong><?php echo t('Location-specific responses:'); ?></strong></p>
                            <p><code><?php echo t('If the page type is "location", mention that we are based in your local area and can provide on-site services. Always include local contact information.'); ?></code>
                            </p>

                            <p><strong><?php echo t('Service page responses:'); ?></strong></p>
                            <p><code><?php echo t('If the page type is "service", focus on the specific service mentioned in {page_title} and provide detailed information about our expertise in this area.'); ?></code>
                            </p>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="restoreDefaultInstructions()">
                            <i class="fas fa-undo"></i> <?php echo t('Restore Default Instructions'); ?>
                        </button>
                    </div>
                </fieldset>

                <fieldset class="mb-5">
                    <legend><?php echo t('Welcome Message Prompt'); ?></legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <?php echo $form->label(
                                    "welcome_message_prompt",
                                    t("Welcome Message Prompt"),
                                    [
                                        "class" => "control-label"
                                    ]
                                ); ?>

                                <?php echo $form->textarea(
                                    "welcome_message_prompt",
                                    $welcomeMessagePrompt,
                                    [
                                        "class" => "form-control",
                                        "max-length" => "10000",
                                        "style" => "field-sizing: content;",
                                        "rows" => "12"
                                    ]
                                ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6><?php echo t('Welcome Message Generation'); ?></h6>
                        <p><?php echo t('This prompt controls how the AI generates personalized welcome messages when users first visit the chat. The AI uses this prompt along with current context to create dynamic, relevant greetings.'); ?>
                        </p>

                        <h6><?php echo t('Context Placeholders'); ?></h6>
                        <p><?php echo t('Use these placeholders to include dynamic information:'); ?></p>
                        <ul>
                            <li><code>{time_of_day}</code> -
                                <?php echo t('Automatically replaced with "morning", "afternoon", or "evening" based on current time'); ?>
                            </li>
                            <li><code>{page_title}</code> -
                                <?php echo t('The title of the page the user is currently viewing'); ?>
                            </li>
                            <li><code>{page_url}</code> - <?php echo t('The URL of the current page'); ?></li>
                        </ul>

                        <h6><?php echo t('Example Usage'); ?></h6>
                        <p><code><?php echo t('Good {time_of_day}! Welcome to our {page_title} page. How can we help you today?'); ?></code>
                        </p>
                        <p><small
                                class="text-muted"><?php echo t('This would generate: "Good morning! Welcome to our Legal Services page. How can we help you today?"'); ?></small>
                        </p>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="restoreDefaultWelcomePrompt()">
                            <i class="fas fa-undo"></i> <?php echo t('Restore Default Prompt'); ?>
                        </button>
                    </div>
                </fieldset>

                <fieldset class="mb-5">
                    <legend><?php echo t('AI Link Selection Rules'); ?></legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <?php echo $form->label(
                                    "link_selection_rules",
                                    t("Link Selection Rules"),
                                    [
                                        "class" => "control-label"
                                    ]
                                ); ?>

                                <?php echo $form->textarea(
                                    "link_selection_rules",
                                    $linkSelectionRules,
                                    [
                                        "class" => "form-control",
                                        "max-length" => "10000",
                                        "style" => "field-sizing: content;",
                                        "rows" => "15"
                                    ]
                                ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6><?php echo t('Intelligent Link Selection'); ?></h6>
                        <p><?php echo t('These rules guide the AI when selecting which links to include with responses. Instead of showing all available links, the AI intelligently chooses the most relevant ones based on the user\'s question and context.'); ?>
                        </p>

                        <h6><?php echo t('How It Works'); ?></h6>
                        <ul>
                            <li><?php echo t('The system searches your indexed content for relevant documents'); ?></li>
                            <li><?php echo t('The AI analyzes the user\'s question and available documents'); ?></li>
                            <li><?php echo t('Using these rules, the AI selects 1-3 most relevant links'); ?></li>
                            <li><?php echo t('Links are displayed as "More Information" buttons below responses'); ?>
                            </li>
                        </ul>

                        <h6><?php echo t('Page Type Context'); ?></h6>
                        <p><?php echo t('Available page types in your system include:'); ?>
                            <?php
                            $pageTypeNames = array_map(function ($pt) {
                                return $pt['name'];
                            }, $pageTypes);
                            echo implode(', ', array_slice($pageTypeNames, 0, 5));
                            if (count($pageTypeNames) > 5) {
                                echo ' and ' . (count($pageTypeNames) - 5) . ' more';
                            }
                            ?>.
                        </p>

                        <p><small
                                class="text-muted"><?php echo t('You can reference specific page types in your rules to control link selection behavior.'); ?></small>
                        </p>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="restoreDefaultLinkRules()">
                            <i class="fas fa-undo"></i> <?php echo t('Restore Default Rules'); ?>
                        </button>
                    </div>
                </fieldset>

                <fieldset class="mb-5">
                    <legend><?php echo t('Contact Page Configuration'); ?></legend>
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="page"
                                    class="control-label form-label"><?php echo t('Contact Page') ?></label>
                                <?= $ps->selectPage('contact_page_id', isset($contactPageID) ? $contactPageID : null); ?>

                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6><?php echo t('Contact Link Generation'); ?></h6>
                        <p><?php echo t('When the AI mentions "contact us" in responses, it will automatically create a clickable link to the selected page. This ensures users can easily reach your contact information.'); ?>
                        </p>

                        <h6><?php echo t('How It Works'); ?></h6>
                        <ul>
                            <li><?php echo t('Select the page where users can contact you (e.g., Contact Us, Get in Touch, Hire Us)'); ?>
                            </li>
                            <li><?php echo t('The AI will automatically convert "contact us" text to clickable links'); ?>
                            </li>
                            <li><?php echo t('Links open in a new tab for better user experience'); ?></li>
                        </ul>

                        <p><small
                                class="text-muted"><?php echo t('If no page is selected, the system will use "/contact-us" as the default.'); ?></small>
                        </p>
                    </div>
                </fieldset>


            </div>

            <div class="col-12 col-md-8 col-lg-5" style="max-width:500px;">

                <fieldset class="mb-5">
                    <legend>Default Notifications</legend>
                    <div class="form-group">
                        <label class="form-label" for="email_from_email">Sender Email</label>
                        <?php echo $form->email("sender_from_email", $email_from_email,["class" => "form-control"]); ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email_from_name">Sender Name</label>
                        <?php echo $form->text("sender_from_name", $email_from_name,["class" => "form-control"]); ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email_from_name">Recipients</label>
                        <?php echo $form->textarea("recipient_emails", $recipient_emails,["class" => "form-control"]); ?>
                    </div>
                </fieldset>

                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
                <style>
                    @keyframes welcomeBounce {
                        0% {
                            opacity: 0;
                            transform: scale(0.8) translateY(10px);
                        }

                        50% {
                            opacity: 1;
                            transform: scale(1.05) translateY(-2px);
                        }

                        100% {
                            opacity: 1;
                            transform: scale(1) translateY(0);
                        }
                    }

                    .welcome-animate {
                        animation: welcomeBounce 0.6s ease-out forwards;
                    }

                    .ai-header {
                        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                        border-radius: 15px;
                        padding: 20px;
                        border: 1px solid #dee2e6;
                    }

                    .ai-header-content {
                        transition: all 0.3s ease;
                    }

                    .ai-header:hover .ai-header-content {
                        transform: translateY(-2px);
                    }

                    #ai-header-greeting {
                        color: var(--bs-primary, #007cba);
                        font-weight: 600;
                        transition: color 0.3s ease;
                        min-height: 1.5em;
                    }

                </style>
                <script>
                    function renderMarkdown(markdown) {
                        return marked.parse(markdown);
                    }

                    // Function to process AI response content and convert "contact us" to links
                    function processAIResponseContent(content) {
                        if (content && typeof content === 'string') {
                            // Use the configured contact page URL from PHP
                            const contactUrl = <?php echo json_encode($contactPageUrl); ?>;

                            // Case-insensitive replacement for "contact us" variations
                            return content.replace(/\b(contact us|Contact Us|CONTACT US)\b/g, `<a href="${contactUrl}" target="_blank" class="text-primary fw-bold">$1</a>`);
                        }
                        return content;
                    }
                </script>
                <script>

                    // Initialize currentMode variable
                    let currentMode = 'rag'; // Default to RAG mode



                    // Function to regenerate welcome message (resets flag and calls generateAIWelcomeMessage)
                    function regenerateWelcomeMessage() {
                        welcomeMessageGenerated = false;
                        generateAIWelcomeMessage();
                    }

                    // Function to generate AI-powered welcome message
                    function generateAIWelcomeMessage() {

                        const now = new Date();
                        const hour = now.getHours();

                        // Get page information - use debug context if available, otherwise use actual page info
                        let pageTitle = document.title || '';
                        let pageUrl = window.location.href;
                        let pageType = '';

                        // Use debug context if available and debug mode is enabled
                        if (window.katalysisAIDebugMode) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';

                            if (debugPageTitle) pageTitle = debugPageTitle;
                            if (debugPageType) pageType = debugPageType;
                            if (debugPageUrl) pageUrl = debugPageUrl;
                        }


                        // Get the configurable welcome message prompt from the textarea
                        let welcomePrompt = document.getElementById('welcome_message_prompt').value || `<?php echo addslashes($welcomeMessagePrompt); ?>`;


                        // Replace placeholders with actual values
                        welcomePrompt = welcomePrompt.replace(/{time_of_day}/g, hour < 12 ? 'morning' : hour < 17 ? 'afternoon' : 'evening');
                        welcomePrompt = welcomePrompt.replace(/{page_title}/g, pageTitle);
                        welcomePrompt = welcomePrompt.replace(/{page_url}/g, pageUrl);

                        // Append essential formatting instructions
                        welcomePrompt += `<?php echo addslashes($essentialWelcomeMessageInstructions); ?>`;


                        // Prepare request data with debug context if enabled
                        let requestData = {
                            message: welcomePrompt,
                            mode: 'basic' // Use basic mode for welcome message generation to avoid RAG instructions override
                        };

                        // Add debug context if debug mode is enabled and debug fields have values
                        if (window.katalysisAIDebugMode) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';

                            if (debugPageTitle || debugPageType || debugPageUrl) {
                                requestData.debug_context = {
                                    page_title: debugPageTitle,
                                    page_type: debugPageType,
                                    page_url: debugPageUrl
                                };
                            }
                        }


                        // Use the existing AI system to generate the welcome message
                        $.ajax({
                            type: "POST",
                            url: "<?= $controller->action('ask_ai') ?>",
                            data: JSON.stringify(requestData),
                            contentType: "application/json",
                            headers: {
                                'X-CSRF-TOKEN': '<?= $token->generate('ai.settings') ?>'
                            },
                            success: function (data) {

                                let welcomeText = '';

                                if (typeof data === 'object' && data.content) {
                                    welcomeText = data.content;
                                } else if (typeof data === 'string') {
                                    welcomeText = data;
                                }


                                // Show and animate the welcome response
                                const welcomeResponse = document.getElementById('welcome-response');
                                const welcomeElement = document.getElementById('welcome-message');


                                if (welcomeResponse && welcomeElement) {
                                    // Set the message text
                                    if (welcomeText && welcomeText.trim()) {
                                        welcomeElement.textContent = welcomeText;
                                        
                                        // Also update the header greeting
                                        const headerGreeting = document.getElementById('ai-header-greeting');
                                        if (headerGreeting) {
                                            // Display the full welcome message without truncation
                                            headerGreeting.textContent = welcomeText;
                                        } else {
                                        }
                                    } else {
                                        // If AI response is empty, show a simple welcome message
                                        welcomeElement.textContent = 'Hi! How can we help you today?';
                                        
                                        // Update header with simple greeting
                                        const headerGreeting = document.getElementById('ai-header-greeting');
                                        if (headerGreeting) {
                                            headerGreeting.textContent = 'Hi! How can we help you today?';
                                        }
                                    }

                                    // Show the response div
                                    welcomeResponse.style.display = 'flex';

                                    // Add animation class
                                    welcomeResponse.classList.add('welcome-animate');

                                    // Remove animation class after animation completes
                                    setTimeout(function () {
                                        welcomeResponse.classList.remove('welcome-animate');
                                    }, 600);
                                } else {
                                }
                            },
                            error: function (xhr, status, error) {

                                // Show a fallback welcome message on error
                                const welcomeResponse = document.getElementById('welcome-response');
                                const welcomeElement = document.getElementById('welcome-message');

                                if (welcomeResponse && welcomeElement) {
                                    welcomeElement.textContent = 'Hi! How can we help you today?';
                                    welcomeResponse.style.display = 'flex';
                                    welcomeResponse.classList.add('welcome-animate');

                                    // Update header with fallback greeting
                                    const headerGreeting = document.getElementById('ai-header-greeting');
                                    if (headerGreeting) {
                                        headerGreeting.textContent = 'Hi! How can we help you today?';
                                    } else {
                                    }

                                    setTimeout(function () {
                                        welcomeResponse.classList.remove('welcome-animate');
                                    }, 600);
                                }
                            }
                        });
                    }

                    // Mode toggle functionality
                    document.addEventListener('DOMContentLoaded', function () {
                        const ragModeToggle = document.getElementById('ragModeToggle');
                        const modeDescription = document.getElementById('modeDescription');

                        if (ragModeToggle) {
                            // Set initial state
                            currentMode = ragModeToggle.checked ? 'rag' : 'basic';
                            updateModeDescription();

                            // Add event listener
                            ragModeToggle.addEventListener('change', function () {
                                currentMode = this.checked ? 'rag' : 'basic';
                                updateModeDescription();
                            });
                        }

                        function updateModeDescription() {
                            if (modeDescription) {
                                if (currentMode === 'rag') {
                                    modeDescription.textContent = 'RAG Mode: AI will search your indexed content to provide relevant answers.';
                                } else {
                                    modeDescription.textContent = 'Basic Mode: AI will provide general responses without searching indexed content.';
                                }
                            }
                        }
                    });

                    // Chat persistence functions
                    function saveChatHistory() {

                        const chatContainer = document.getElementById('chat');
                        if (!chatContainer) {
                            return;
                        }

                        const chatHistory = chatContainer.innerHTML;

                        try {
                            localStorage.setItem('katalysis_chat_history', chatHistory);
                            localStorage.setItem('katalysis_chat_timestamp', Date.now().toString());
                        } catch (e) {
                        }
                    }

                    function loadChatHistory() {

                        const chatContainer = document.getElementById('chat');
                        if (!chatContainer) {
                            return;
                        }

                        try {
                            const savedHistory = localStorage.getItem('katalysis_chat_history');
                            const timestamp = localStorage.getItem('katalysis_chat_timestamp');


                            if (savedHistory && timestamp) {
                                const age = Date.now() - parseInt(timestamp);
                                const maxAge = 24 * 60 * 60 * 1000; // 24 hours


                                if (age < maxAge) {

                                    // Replace the entire chat container content
                                    chatContainer.innerHTML = savedHistory;


                                    // Check if the loaded chat history contains any messages
                                    const hasMessages = chatContainer.querySelector('.user-message, .ai-response') !== null;

                                    if (!hasMessages && !welcomeMessageGenerated) {
                                        welcomeMessageGenerated = true;
                                        setTimeout(function () {
                                            generateAIWelcomeMessage();
                                        }, 500);
                                    }

                                    // Scroll to bottom after loading
                                    setTimeout(function () {
                                        scrollToBottom();
                                    }, 100);
                                } else {
                                    clearChatHistory();
                                }
                            } else {
                                // If no saved chat history, ensure welcome message is generated
                                if (!welcomeMessageGenerated) {
                                    welcomeMessageGenerated = true;
                                    setTimeout(function () {
                                        generateAIWelcomeMessage();
                                    }, 500);
                                }
                            }
                        } catch (e) {
                            // If there's an error loading chat history, still try to generate welcome message
                            if (!welcomeMessageGenerated) {
                                welcomeMessageGenerated = true;
                                setTimeout(function () {
                                    generateAIWelcomeMessage();
                                }, 500);
                            }
                        }
                    }

                    function clearChatHistory() {

                        // Clear browser localStorage
                        localStorage.removeItem('katalysis_chat_history');
                        localStorage.removeItem('katalysis_chat_timestamp');

                        // Reset chat session ID
                        chatSessionId = null;

                        // Clear server-side chat files
                        $.ajax({
                            type: "POST",
                            url: "<?= $controller->action('clear_chat_history') ?>",
                            headers: {
                                'X-CSRF-TOKEN': '<?= $token->generate('ai.settings') ?>'
                            },
                            success: function (data) {
                                // Instead of reloading, clear the chat container and generate welcome message
                                const chatContainer = document.getElementById('chat');
                                if (chatContainer) {
                                    // Keep the divider and welcome response elements
                                    const divider = chatContainer.querySelector('.divider');
                                    const welcomeResponse = chatContainer.querySelector('#welcome-response');

                                    // Clear the container
                                    chatContainer.innerHTML = '';

                                    // Restore the divider
                                    if (divider) {
                                        chatContainer.appendChild(divider);
                                    }

                                    // Restore the welcome response element
                                    if (welcomeResponse) {
                                        chatContainer.appendChild(welcomeResponse);
                                    }

                                    // Generate welcome message
                                    if (!welcomeMessageGenerated) {
                                        welcomeMessageGenerated = true;
                                        setTimeout(function () {
                                            generateAIWelcomeMessage();
                                        }, 500);
                                    }
                                }
                            },
                            error: function (xhr, status, error) {
                                // Still try to clear locally and generate welcome message
                                const chatContainer = document.getElementById('chat');
                                if (chatContainer) {
                                    const divider = chatContainer.querySelector('.divider');
                                    const welcomeResponse = chatContainer.querySelector('#welcome-response');

                                    chatContainer.innerHTML = '';

                                    if (divider) {
                                        chatContainer.appendChild(divider);
                                    }

                                    if (welcomeResponse) {
                                        chatContainer.appendChild(welcomeResponse);
                                    }

                                    if (!welcomeMessageGenerated) {
                                        welcomeMessageGenerated = true;
                                        setTimeout(function () {
                                            generateAIWelcomeMessage();
                                        }, 500);
                                    }
                                }
                            }
                        });

                    }

                    function scrollToBottom() {
                        const chatContainer = document.getElementById('chat');
                        if (chatContainer) {

                            // Force scroll to bottom
                            chatContainer.scrollTop = chatContainer.scrollHeight;

                            // Also try with a small delay
                            setTimeout(function () {
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            }, 50);
                        }
                    }

                    // Track if welcome message has been generated to prevent duplicates
                    let welcomeMessageGenerated = false;
                    let chatSessionId = null; // Track current chat session

                    // Function to check if welcome message elements exist
                    function checkWelcomeElements() {
                        const welcomeResponse = document.getElementById('welcome-response');
                        const welcomeElement = document.getElementById('welcome-message');


                        if (welcomeResponse) {
                        }

                        return welcomeResponse && welcomeElement;
                    }

                    // Load chat history when page loads
                    $(document).ready(function () {

                        // Check welcome elements immediately
                        checkWelcomeElements();

                        loadChatHistory();

                        // Generate AI welcome message after a delay to ensure everything is loaded
                        setTimeout(function () {
                            if (!welcomeMessageGenerated) {
                                welcomeMessageGenerated = true;
                                generateAIWelcomeMessage();
                            }
                        }, 1000); // Increased delay to ensure chat history is loaded first

                        // Fallback: Ensure welcome message is shown after 3 seconds if still not generated
                        setTimeout(function () {
                            const welcomeResponse = document.getElementById('welcome-response');
                            if (welcomeResponse && welcomeResponse.style.display === 'none') {
                                if (!welcomeMessageGenerated) {
                                    welcomeMessageGenerated = true;
                                    generateAIWelcomeMessage();
                                }
                            }
                        }, 3000);
                    });

                    // Your existing addMessage function
                    function addMessage() {
                        var messageValue = document.getElementById('message').value;
                        if (!messageValue.trim()) {
                            alert('Please enter a message');
                            return;
                        } else {
                            $("#chat").append('<div class="user-message">' + messageValue + '</div>');
                            saveChatHistory(); // Save after user message
                            scrollToBottom();
                        }

                        $("#chat").append('<div class="ai-loading">AI is thinking...</div>');
                        saveChatHistory(); // Save after loading indicator
                        scrollToBottom();

                        // Check if this is a new chat session (no existing chat session ID)
                        const isNewChat = !chatSessionId;

                        // Prepare request data with debug context if enabled
                        let requestData = {
                            message: messageValue,
                            mode: currentMode,
                            new_chat: isNewChat
                        };

                        // Add page context information for new chats
                        if (isNewChat) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';
                            requestData.page_title = debugPageTitle || document.title || '';
                            requestData.page_type = debugPageType || '';
                            requestData.page_url = debugPageUrl || window.location.pathname || '';
                        }

                        // Add debug context if debug mode is enabled
                        if (window.katalysisAIDebugMode) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';

                            if (debugPageTitle || debugPageType || debugPageUrl) {
                                requestData.debug_context = {
                                    page_title: debugPageTitle,
                                    page_type: debugPageType,
                                    page_url: debugPageUrl
                                };
                            }
                        }

                        $.ajax({
                            type: "POST",
                            url: "<?= $controller->action('ask_ai') ?>",
                            data: JSON.stringify(requestData),
                            contentType: "application/json",
                            headers: {
                                'X-CSRF-TOKEN': '<?= $token->generate('ai.settings') ?>'
                            },
                            success: function (data) {
                                $(".ai-loading").remove();

                                // Handle new response format with metadata
                                let responseContent = data;
                                let metadata = [];

                                if (typeof data === 'object' && data.content) {
                                    responseContent = data.content;
                                    metadata = data.metadata || [];
                                }

                                // Process the response content to convert "contact us" to links
                                let processedContent = processAIResponseContent(responseContent);

                                let responseHtml = '<div class="ai-response"><img src="https://d7keiwzj12p9.cloudfront.net/avatars/katalysis-bot-icon-1748356162310.webp" alt="Katalysis Bot"><div class="message-content">' + renderMarkdown(processedContent);

                                // Add "More Info" links if metadata is available
                                responseHtml += '<div class="more-info-links mt-3"><strong class="d-block mb-2">More Information:</strong>';
                                // Insert action buttons here, if any
                                if (data.actions && data.actions.length > 0) {
                                    responseHtml += '<div class="action-buttons">';
                                    data.actions.forEach(function (action) {
                                        responseHtml += '<button class="action-button btn btn-sm btn-primary me-2 mb-2" data-action-id="' + action.id + '" data-action-name="' + action.name + '" data-response-instruction="' + action.responseInstruction + '" onclick="executeAction(' + action.id + ', \'' + action.name + '\')">';
                                        responseHtml += '<i class="' + action.icon + '"></i> ' + action.name;
                                        responseHtml += '</button>';
                                    });
                                    responseHtml += '</div>';
                                }
                                responseHtml += '<ul class="list-unstyled m-0 p-0">';
                                metadata.forEach(function (link) {
                                    responseHtml += '<li><a href="' + link.url + '" target="_blank" class="btn btn-sm btn-outline-primary mb-2"><i class="fas fa-link"></i> ' + link.title + '</a></li>';
                                });
                                responseHtml += '</ul></div>';

                                // Show debug info if enabled
                                if (window.katalysisAIDebugMode) {
                                    responseHtml += displayPageTypesInfo(data);
                                }

                                responseHtml += '</div></div>';
                                $("#chat").append(responseHtml);

                                // Store chat session ID if this is a new chat
                                if (data.chat_id && !chatSessionId) {
                                    chatSessionId = data.chat_id;
                                }

                                saveChatHistory(); // Save after AI response

                                // Display action buttons if provided

                                // Check if action buttons container exists
                                const actionButtons = document.getElementById('action-buttons');
                                if (actionButtons) {
                                }

                                if (data.actions && data.actions.length > 0) {
                                    // displayActionButtons(data.actions); // This function is no longer needed
                                } else {
                                    // hideActionButtons(); // This function is no longer needed
                                }

                                scrollToBottom();
                                document.getElementById('message').value = '';
                            },
                            error: function (xhr, status, error) {
                                $(".ai-loading").remove();
                                $("#chat").append('<div class="ai-error">Error: ' + error + '</div>');
                                saveChatHistory(); // Save after error
                                scrollToBottom();
                            }
                        });
                    }

                    // Update the addMessageWithMode function as well
                    function addMessageWithMode(message) {
                        var messageValue = message || document.getElementById('message').value;
                        if (!messageValue.trim()) {
                            alert('Please enter a message');
                            return;
                        } else {
                            $("#chat").append('<div class="user-message">' + messageValue + '</div>');
                            saveChatHistory(); // Save after each message
                            scrollToBottom(); // Scroll after adding user message
                        }

                        $("#chat").append('<div class="ai-loading">AI is thinking...</div>');
                        saveChatHistory(); // Save after adding loading indicator

                        // Check if this is a new chat session (no existing chat session ID)
                        const isNewChat = !chatSessionId;

                        // Prepare request data with debug context if enabled
                        let requestData = {
                            message: messageValue,
                            mode: currentMode,
                            new_chat: isNewChat
                        };

                        // Add page context information for new chats
                        if (isNewChat) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';
                            requestData.page_title = debugPageTitle || document.title || '';
                            requestData.page_type = debugPageType || '';
                            requestData.page_url = debugPageUrl || window.location.pathname || '';
                        }

                        // Add debug context if debug mode is enabled
                        if (window.katalysisAIDebugMode) {
                            const debugPageTitle = document.getElementById('debug_page_title')?.value || '';
                            const debugPageType = document.getElementById('debug_page_type')?.value || '';
                            const debugPageUrl = document.getElementById('debug_page_url')?.value || '';

                            if (debugPageTitle || debugPageType || debugPageUrl) {
                                requestData.debug_context = {
                                    page_title: debugPageTitle,
                                    page_type: debugPageType,
                                    page_url: debugPageUrl
                                };
                            }
                        }

                        $.ajax({
                            type: "POST",
                            url: "<?= $controller->action('ask_ai') ?>",
                            data: JSON.stringify(requestData),
                            contentType: "application/json",
                            headers: {
                                'X-CSRF-TOKEN': '<?= $token->generate('ai.settings') ?>'
                            },
                            success: function (data) {
                                $(".ai-loading").remove();

                                // Handle new response format with metadata
                                let responseContent = data;
                                let metadata = [];

                                if (typeof data === 'object' && data.content) {
                                    responseContent = data.content;
                                    metadata = data.metadata || [];
                                }

                                // Process the response content to convert "contact us" to links
                                let processedContent = processAIResponseContent(responseContent);

                                let responseHtml = '<div class="ai-response"><img src="https://d7keiwzj12p9.cloudfront.net/avatars/katalysis-bot-icon-1748356162310.webp" alt="Katalysis Bot"><div class="message-content">' + renderMarkdown(processedContent);

                                // Add "More Info" links if metadata is available
                                responseHtml += '<div class="more-info-links mt-3"><strong>More Information:</strong>';
                                // Insert action buttons here, if any
                                if (data.actions && data.actions.length > 0) {
                                    responseHtml += '<div class="action-buttons-inline mt-2 mb-2">';
                                    data.actions.forEach(function (action) {
                                        responseHtml += '<button class="action-button btn btn-sm btn-primary me-2 mb-1" data-action-id="' + action.id + '" data-action-name="' + action.name + '" data-response-instruction="' + action.responseInstruction + '" onclick="executeAction(' + action.id + ', \'' + action.name + '\')">';
                                        responseHtml += '<i class="' + action.icon + '"></i> ' + action.name;
                                        responseHtml += '</button>';
                                    });
                                    responseHtml += '</div>';
                                }
                                responseHtml += '<ul class="list-unstyled mt-2">';
                                metadata.forEach(function (link) {
                                    responseHtml += '<li><a href="' + link.url + '" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-1">' + link.title + '</a></li>';
                                });
                                responseHtml += '</ul></div>';

                                // Show debug info if enabled
                                if (window.katalysisAIDebugMode) {
                                    responseHtml += displayPageTypesInfo(data);
                                }

                                responseHtml += '</div></div>';
                                $("#chat").append(responseHtml);

                                // Store chat session ID if this is a new chat
                                if (data.chat_id && !chatSessionId) {
                                    chatSessionId = data.chat_id;
                                }

                                saveChatHistory(); // Save after AI response

                                // Display action buttons if provided

                                // Check if action buttons container exists
                                const actionButtons = document.getElementById('action-buttons');
                                if (actionButtons) {
                                }

                                if (data.actions && data.actions.length > 0) {
                                    // displayActionButtons(data.actions); // This function is no longer needed
                                } else {
                                    // hideActionButtons(); // This function is no longer needed
                                }

                                scrollToBottom(); // Scroll after adding AI response
                                document.getElementById('message').value = '';
                            },
                            error: function (xhr, status, error) {
                                $(".ai-loading").remove();
                                $("#chat").append('<div class="ai-error">Error: ' + error + '</div>');
                                saveChatHistory();
                                scrollToBottom(); // Scroll after adding error message
                            }
                        });
                    }

                    // Add smooth scrolling for better UX
                    function scrollToBottomSmooth() {
                        const chatContainer = document.getElementById('chat');
                        chatContainer.scrollTo({
                            top: chatContainer.scrollHeight,
                            behavior: 'smooth'
                        });
                    }

                    // Optional: Auto-scroll on window resize
                    window.addEventListener('resize', function () {
                        scrollToBottom();
                    });

                    // Function to build response HTML with inline action buttons
                    function buildResponseHtml(responseContent, metadata, data) {
                        // Process the response content to convert "contact us" to links
                        let processedContent = processAIResponseContent(responseContent);

                        let responseHtml = '<div class="ai-response"><img src="https://d7keiwzj12p9.cloudfront.net/avatars/katalysis-bot-icon-1748356162310.webp" alt="Katalysis Bot"><div>' + renderMarkdown(processedContent);

                        // Add "More Info" links if metadata is available
                        responseHtml += '<div class="more-info-links mt-3"><strong>More Information:</strong>';

                        // Add action buttons inline if available
                        if (data.actions && data.actions.length > 0) {
                            responseHtml += '<div class="action-buttons-inline mt-2 mb-2">';
                            data.actions.forEach(function (action) {
                                responseHtml += '<button class="action-button btn btn-sm btn-primary me-2 mb-1" data-action-id="' + action.id + '" data-action-name="' + action.name + '" data-response-instruction="' + action.responseInstruction + '" onclick="executeAction(' + action.id + ', \'' + action.name + '\')">';
                                responseHtml += '<i class="' + action.icon + '"></i> ' + action.name;
                                responseHtml += '</button>';
                            });
                            responseHtml += '</div>';
                        }

                        responseHtml += '<ul class="list-unstyled mt-2">';
                        metadata.forEach(function (link) {
                            responseHtml += '<li><a href="' + link.url + '" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-1">' + link.title + '</a></li>';
                        });
                        responseHtml += '</ul></div>';

                        // Show debug info if enabled
                        if (window.katalysisAIDebugMode) {
                            responseHtml += displayPageTypesInfo(data);
                        }

                        responseHtml += '</div></div>';
                        return responseHtml;
                    }

                    // Function to display page types information
                    function displayPageTypesInfo(data) {
                        let infoHtml = '<div class="debug-info mt-2 p-2 bg-light border rounded">';

                        // Page Types Information
                        if (data.page_types_used && data.page_types_used.length > 0) {
                            infoHtml += '<small class="text-muted"><strong>Page Types Used:</strong> ' + data.page_types_used.join(', ') + '</small>';

                            if (data.context_info) {
                                infoHtml += '<br><small class="text-muted"><strong>Documents Retrieved:</strong> ' + data.context_info.total_documents_retrieved + '</small>';
                                if (data.context_info.page_types_from_documents && data.context_info.page_types_from_documents.length > 0) {
                                    infoHtml += '<br><small class="text-muted"><strong>From Documents:</strong> ' + data.context_info.page_types_from_documents.join(', ') + '</small>';
                                }
                            }
                        }

                        // Link Selection Debug Information
                        if (data.debug_info && data.debug_info.link_selection) {
                            const linkInfo = data.debug_info.link_selection;
                            infoHtml += '<hr class="my-2">';
                            infoHtml += '<small class="text-muted"><strong>Link Selection:</strong></small><br>';
                            infoHtml += '<small class="text-muted">• Total documents processed: ' + linkInfo.total_documents_processed + '</small><br>';
                            infoHtml += '<small class="text-muted">• Documents with URLs: ' + linkInfo.documents_with_urls + '</small><br>';
                            infoHtml += '<small class="text-muted">• Candidate documents: ' + linkInfo.candidate_documents + '</small><br>';
                            infoHtml += '<small class="text-muted">• Selected links: ' + linkInfo.ai_selected_links + '</small><br>';
                        }

                        // Link Details
                        if (data.debug_info && data.debug_info.scoring_details && data.debug_info.scoring_details.length > 0) {
                            infoHtml += '<hr class="my-2">';
                            infoHtml += '<small class="text-muted"><strong>Selected Links:</strong></small><br>';

                            data.debug_info.scoring_details.forEach(function (link, index) {
                                infoHtml += '<div class="mt-1 p-1 bg-white border rounded">';
                                infoHtml += '<small class="text-muted"><strong>' + (index + 1) + '. ' + link.title + '</strong></small><br>';
                                infoHtml += '<small class="text-muted">• Score: ' + link.final_score.toFixed(3) + '</small><br>';
                                if (link.selection_reason && link.selection_reason !== 'AI chose this as most relevant to the user\'s question') {
                                    infoHtml += '<small class="text-muted">• Note: ' + link.selection_reason + '</small><br>';
                                }
                                infoHtml += '</div>';
                            });
                        }

                        // Action IDs Information
                        if (data.actions && data.actions.length > 0) {
                            infoHtml += '<hr class="my-2">';
                            infoHtml += '<small class="text-muted"><strong>Action IDs:</strong></small><br>';
                            infoHtml += '<small class="text-muted">• Actions suggested: ' + data.actions.length + '</small><br>';

                            data.actions.forEach(function (action, index) {
                                infoHtml += '<div class="mt-1 p-1 bg-white border rounded">';
                                infoHtml += '<small class="text-muted"><strong>' + (index + 1) + '. ' + action.name + '</strong> (ID: ' + action.id + ')</small><br>';
                                infoHtml += '<small class="text-muted">• Icon: ' + action.icon + '</small><br>';
                                infoHtml += '<small class="text-muted">• Trigger: ' + action.triggerInstruction + '</small><br>';
                                infoHtml += '<small class="text-muted">• Response: ' + action.responseInstruction + '</small><br>';
                                infoHtml += '</div>';
                            });
                        } else {
                            infoHtml += '<hr class="my-2">';
                            infoHtml += '<small class="text-muted"><strong>Action IDs:</strong> None suggested</small><br>';
                        }

                        infoHtml += '</div>';
                        return infoHtml;
                    }

                    // Display action buttons
                    function displayActionButtons(actions) {
                        const actionButtons = document.getElementById('action-buttons');
                        if (!actionButtons) {
                            return;
                        }

                        actionButtons.innerHTML = '';

                        actions.forEach(action => {
                            const button = document.createElement('button');
                            button.className = 'action-button btn btn-sm btn-primary me-2 mb-1';
                            button.innerHTML = `<i class="${action.icon}"></i> ${action.name}`;
                            button.setAttribute('data-action-id', action.id);
                            button.setAttribute('data-action-name', action.name);
                            button.setAttribute('data-response-instruction', action.responseInstruction);

                            button.addEventListener('click', function () {
                                executeAction(action.id, action.name);
                            });

                            actionButtons.appendChild(button);
                        });

                        actionButtons.style.display = 'flex';
                    }

                    // Hide action buttons
                    function hideActionButtons() {
                        const actionButtons = document.getElementById('action-buttons');
                        if (actionButtons) {
                            actionButtons.style.display = 'none';
                            actionButtons.innerHTML = '';
                        }
                    }

                    // Execute action
                    function executeAction(actionId, actionName) {

                        // Hide action buttons
                        hideActionButtons();

                        // Get conversation context (last few messages)
                        const messages = document.querySelectorAll('#chat .ai-response, #chat .user-message');
                        const conversationContext = Array.from(messages)
                            .slice(-6) // Last 6 messages
                            .map(msg => msg.textContent || msg.innerText)
                            .join(' | ');

                        // Execute action
                        fetch('<?php echo $controller->action('execute_action'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '<?php echo $token->generate('ai.settings'); ?>'
                            },
                            body: JSON.stringify({
                                action_id: actionId,
                                conversation_context: conversationContext
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.error) {
                                    $("#chat").append('<div class="ai-error">Error: ' + data.error + '</div>');
                                } else {
                                    $("#chat").append('<div class="ai-response"><img src="https://d7keiwzj12p9.cloudfront.net/avatars/katalysis-bot-icon-1748356162310.webp" alt="Katalysis Bot"><div>' + data.content + '</div></div>');
                                }
                                saveChatHistory();
                                scrollToBottom();
                            })
                            .catch(error => {
                                $("#chat").append('<div class="ai-error">Error executing action: ' + error.message + '</div>');
                                saveChatHistory();
                                scrollToBottom();
                            });
                    }
                </script>
                <section>
                    <div class="card border rounded-3 mb-5">
                        <div class="card-body">
                            <!-- Dynamic AI Header -->
                            <div class="ai-header mb-3 text-center">
                                <div class="ai-header-content">
                                    <i class="fa fa-robot fa-2x text-primary mb-2"></i>
                                    <h4 id="ai-header-greeting" class="mb-0"><?php echo t('AI Assistant'); ?></h4>
                                    <p class="text-muted small mb-0"><?php echo t('Powered by AI'); ?></p>
                                </div>
                                <!-- Test button for debugging -->
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="testHeaderUpdate()">
                                    Test Header Update
                                </button>
                            </div>
                            
                            <div id="chat">
                                <div class="divider d-flex align-items-center mb-4">
                                    <p class="text-center mx-3 mb-0 color-muted">Today</p>
                                </div>
                                <div class="ai-response" id="welcome-response" style="display: none;">
                                    <img src="https://d7keiwzj12p9.cloudfront.net/avatars/katalysis-bot-icon-1748356162310.webp"
                                        alt="Katalysis Bot">
                                    <div id="welcome-message" class="message-content font-weight-bold">Generating
                                        welcome message...</div>
                                </div>
                            </div>

                        </div>



                        <div
                            class="card-footer bg-dark rounded-bottom-3 text-muted d-flex justify-content-start align-items-center p-3">
                            <input id="message" tabindex="0" name="message"
                                class="form-control form-control-lg border-0 bg-white px-3 py-2 text-base focus:border-primary focus:outline-none disabled:bg-secondary ltr-placeholder "
                                maxlength="10000" placeholder="Add a message" autocomplete="off" aria-label="question"
                                dir="auto" enterkeyhint="enter"
                                style="height: 42px; border-radius: 0.875rem; min-height: 40px;" />


                            <button type="button" class="btn btn-light ms-2 text-muted" onclick="clearChatHistory()">
                                <i class="fas fa-trash"></i>
                            </button>

                            <button class="btn btn-primary ms-2" onclick="addMessage()" type="button"
                                aria-label="send message"><i class="fas fa-paper-plane"></i></button>
                        </div>

                    </div>
                    <div class="form-group mb-4">
                        <div class="form-check">
                            <?php echo $form->checkbox('debug_mode', 1, $debugMode, ['class' => 'form-check-input', 'id' => 'debug_mode']); ?>
                            <?php echo $form->label('debug_mode', t('Enable Debug Mode (show page type info under each response)'), ['class' => 'form-check-label']); ?>
                        </div>
                    </div>

                    <script>
                        window.katalysisAIDebugMode = <?php echo $debugMode ? 'true' : 'false'; ?>;

                        // Default instructions and link selection rules
                        const defaultInstructions = `<?php echo addslashes($defaultInstructions); ?>`;
                        const defaultLinkRules = `<?php echo addslashes($defaultLinkSelectionRules); ?>`;
                        const defaultWelcomePrompt = `<?php echo addslashes($defaultWelcomeMessagePrompt); ?>`;

                        // Function to restore default instructions
                        function restoreDefaultInstructions() {
                            if (confirm('<?php echo t('Are you sure you want to restore the default instructions? This will replace your current instructions.'); ?>')) {
                                document.getElementById('instructions').value = defaultInstructions;
                                updateInstructionsModifiedStatus();
                            }
                        }

                        // Function to restore default link selection rules
                        function restoreDefaultLinkRules() {
                            if (confirm('<?php echo t('Are you sure you want to restore the default link selection rules? This will replace your current rules.'); ?>')) {
                                document.getElementById('link_selection_rules').value = defaultLinkRules;
                                updateRulesModifiedStatus();
                            }
                        }

                        // Function to restore default welcome prompt
                        function restoreDefaultWelcomePrompt() {
                            if (confirm('<?php echo t('Are you sure you want to restore the default welcome message prompt? This will replace your current prompt.'); ?>')) {
                                document.getElementById('welcome_message_prompt').value = defaultWelcomePrompt;
                                updateWelcomePromptModifiedStatus();
                            }
                        }

                        // Test function for header update debugging
                        function testHeaderUpdate() {
                            const headerGreeting = document.getElementById('ai-header-greeting');
                            if (headerGreeting) {
                                const testText = 'Test Header Update - ' + new Date().toLocaleTimeString();
                                headerGreeting.textContent = testText;
                            } else {
                            }
                        }

                        // Function to check if instructions have been modified from default
                        function updateInstructionsModifiedStatus() {
                            const currentInstructions = document.getElementById('instructions').value;
                            const isModified = currentInstructions.trim() !== defaultInstructions.trim();

                            const restoreButton = document.querySelector('button[onclick="restoreDefaultInstructions()"]');
                            if (restoreButton) {
                                if (isModified) {
                                    restoreButton.classList.remove('btn-outline-secondary');
                                    restoreButton.classList.add('btn-warning');
                                    restoreButton.disabled = false;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Instructions'); ?> <span class="badge bg-warning text-dark">Modified</span>';
                                } else {
                                    restoreButton.classList.remove('btn-warning');
                                    restoreButton.classList.add('btn-outline-secondary');
                                    restoreButton.disabled = true;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Instructions'); ?>';
                                }
                            }
                        }

                        // Function to check if rules have been modified from default
                        function updateRulesModifiedStatus() {
                            const currentRules = document.getElementById('link_selection_rules').value;
                            const isModified = currentRules.trim() !== defaultLinkRules.trim();

                            const restoreButton = document.querySelector('button[onclick="restoreDefaultLinkRules()"]');
                            if (restoreButton) {
                                if (isModified) {
                                    restoreButton.classList.remove('btn-outline-secondary');
                                    restoreButton.classList.add('btn-warning');
                                    restoreButton.disabled = false;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Rules'); ?> <span class="badge bg-warning text-dark">Modified</span>';
                                } else {
                                    restoreButton.classList.remove('btn-warning');
                                    restoreButton.classList.add('btn-outline-secondary');
                                    restoreButton.disabled = true;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Rules'); ?>';
                                }
                            }
                        }

                        // Function to check if welcome prompt has been modified from default
                        function updateWelcomePromptModifiedStatus() {
                            const currentPrompt = document.getElementById('welcome_message_prompt').value;
                            const isModified = currentPrompt.trim() !== defaultWelcomePrompt.trim();

                            const restoreButton = document.querySelector('button[onclick="restoreDefaultWelcomePrompt()"]');
                            if (restoreButton) {
                                if (isModified) {
                                    restoreButton.classList.remove('btn-outline-secondary');
                                    restoreButton.classList.add('btn-warning');
                                    restoreButton.disabled = false;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Prompt'); ?> <span class="badge bg-warning text-dark">Modified</span>';
                                } else {
                                    restoreButton.classList.remove('btn-warning');
                                    restoreButton.classList.add('btn-outline-secondary');
                                    restoreButton.disabled = true;
                                    restoreButton.innerHTML = '<i class="fas fa-undo"></i> <?php echo t('Restore Default Prompt'); ?>';
                                }
                            }
                        }

                        // Check modification status on page load and when textarea changes
                        document.addEventListener('DOMContentLoaded', function () {
                            // Test if header element exists
                            const headerGreeting = document.getElementById('ai-header-greeting');
                            if (headerGreeting) {
                            }
                            
                            // Handle instructions textarea
                            const instructionsTextarea = document.getElementById('instructions');
                            if (instructionsTextarea) {
                                updateInstructionsModifiedStatus();
                                instructionsTextarea.addEventListener('input', updateInstructionsModifiedStatus);
                            }

                            // Handle link selection rules textarea
                            const rulesTextarea = document.getElementById('link_selection_rules');
                            if (rulesTextarea) {
                                updateRulesModifiedStatus();
                                rulesTextarea.addEventListener('input', updateRulesModifiedStatus);
                            }

                            // Handle welcome message prompt textarea
                            const welcomePromptTextarea = document.getElementById('welcome_message_prompt');
                            if (welcomePromptTextarea) {
                                updateWelcomePromptModifiedStatus();
                                welcomePromptTextarea.addEventListener('input', function () {
                                    updateWelcomePromptModifiedStatus();
                                    // Regenerate welcome message when prompt changes
                                    regenerateWelcomeMessage();
                                });
                            }
                        });

                        // Test action extraction function
                        function testActionExtraction() {
                            const resultsDiv = document.getElementById('actionExtractionResults');
                            resultsDiv.innerHTML = '<div class="alert alert-info">Testing action extraction...</div>';

                            fetch('<?php echo $controller->action('test_action_extraction'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                                    } else {
                                        let html = '<div class="alert alert-success">Action extraction test completed successfully!</div>';
                                        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        html += '<thead><tr><th>Original Response</th><th>Clean Response</th><th>Extracted Action IDs</th><th>Has Actions</th></tr></thead><tbody>';

                                        data.test_results.forEach(result => {
                                            html += '<tr>';
                                            html += '<td><code>' + result.original_response + '</code></td>';
                                            html += '<td><code>' + result.clean_response + '</code></td>';
                                            html += '<td><code>' + (result.extracted_action_ids.length > 0 ? result.extracted_action_ids.join(', ') : 'None') + '</code></td>';
                                            html += '<td><span class="badge ' + (result.has_actions ? 'bg-success' : 'bg-secondary') + '">' + (result.has_actions ? 'Yes' : 'No') + '</span></td>';
                                            html += '</tr>';
                                        });

                                        html += '</tbody></table></div>';
                                        resultsDiv.innerHTML = html;
                                    }
                                })
                                .catch(error => {
                                    resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                                });
                        }

                        // Add event listener for force actions button
                        document.addEventListener('DOMContentLoaded', function () {
                            const forceActionsBtn = document.getElementById('testForceActionsBtn');
                            if (forceActionsBtn) {
                                forceActionsBtn.addEventListener('click', function () {
                                    testForceActions();
                                });
                            } else {
                            }
                        });

                        // Test force actions function
                        function testForceActions() {
                            const resultsDiv = document.getElementById('forceActionsResults');
                            resultsDiv.innerHTML = '<div class="alert alert-info">Testing force actions...</div>';

                            fetch('<?php echo $controller->action('test_force_actions'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                                    } else {
                                        let html = '<div class="alert alert-success">Force action test completed!</div>';
                                        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        html += '<thead><tr><th>Test Message</th><th>Original Response</th><th>Clean Response</th><th>Action IDs</th><th>Has Actions</th></tr></thead><tbody>';

                                        html += '<tr>';
                                        html += '<td><code>' + data.test_message + '</code></td>';
                                        html += '<td><code>' + data.original_response + '</code></td>';
                                        html += '<td><code>' + data.clean_response + '</code></td>';
                                        html += '<td><code>' + (data.extracted_action_ids.length > 0 ? data.extracted_action_ids.join(', ') : 'None') + '</code></td>';
                                        html += '<td><span class="badge ' + (data.has_actions ? 'bg-success' : 'bg-secondary') + '">' + (data.has_actions ? 'Yes' : 'No') + '</span></td>';
                                        html += '</tr>';

                                        html += '</tbody></table></div>';
                                        resultsDiv.innerHTML = html;
                                    }
                                })
                                .catch(error => {
                                    resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                                });
                        }

                        // Test direct AI function
                        function testDirectAI() {
                            const resultsDiv = document.getElementById('forceActionsResults');
                            resultsDiv.innerHTML = '<div class="alert alert-info">Testing direct AI...</div>';

                            fetch('<?php echo $controller->action('test_direct_ai'); ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.error) {
                                        resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                                    } else {
                                        let html = '<div class="alert alert-success">Direct AI test completed!</div>';
                                        html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                                        html += '<thead><tr><th>Actions Count</th><th>AI Response</th><th>Clean Response</th><th>Action IDs</th><th>Has Actions</th></tr></thead><tbody>';

                                        html += '<tr>';
                                        html += '<td>' + data.actions_count + '</td>';
                                        html += '<td><code style="font-size: 11px;">' + data.ai_response + '</code></td>';
                                        html += '<td><code style="font-size: 11px;">' + data.clean_response + '</code></td>';
                                        html += '<td><code>' + (data.extracted_action_ids.length > 0 ? data.extracted_action_ids.join(', ') : 'None') + '</code></td>';
                                        html += '<td><span class="badge ' + (data.has_actions ? 'bg-success' : 'bg-secondary') + '">' + (data.has_actions ? 'Yes' : 'No') + '</span></td>';
                                        html += '</tr>';

                                        html += '</tbody></table></div>';

                                        // Also show the direct prompt for debugging
                                        html += '<div class="mt-3"><h6>Direct Prompt Sent to AI:</h6>';
                                        html += '<pre class="bg-light p-3 border rounded" style="font-size: 10px; max-height: 200px; overflow-y: auto;">' + data.direct_prompt + '</pre></div>';

                                        resultsDiv.innerHTML = html;
                                    }
                                })
                                .catch(error => {
                                    resultsDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                                });
                        }
                    </script>

                    <!-- Debug Context Fields -->
                    <fieldset class="mb-4" id="debugContextFields"
                        style="<?php echo $debugMode ? '' : 'display: none;'; ?>">
                        <legend><?php echo t('Debug Context Fields'); ?></legend>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <?php echo $form->label('debug_page_title', t('Page Title'), ['class' => 'control-label']); ?>
                                    <?php echo $form->text('debug_page_title', $debugPageTitle ?? '', [
                                        'class' => 'form-control',
                                        'placeholder' => 'e.g., About Katalysis'
                                    ]); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <?php echo $form->label('debug_page_type', t('Page Type'), ['class' => 'control-label']); ?>
                                    <?php echo $form->text('debug_page_type', $debugPageType ?? '', [
                                        'class' => 'form-control',
                                        'placeholder' => 'e.g., page, location, service'
                                    ]); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <?php echo $form->label('debug_page_url', t('Page URL'), ['class' => 'control-label']); ?>
                                    <?php echo $form->text('debug_page_url', $debugPageUrl ?? '', [
                                        'class' => 'form-control',
                                        'placeholder' => 'e.g., /about, /locations/harpenden'
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <?php echo t('These fields allow you to test the AI with specific page context. They will be used when sending messages in debug mode.'); ?>
                        </div>

                    </fieldset>


                    <script>
                        window.katalysisAIDebugMode = <?php echo $debugMode ? 'true' : 'false'; ?>;

                        // Toggle debug context fields visibility
                        document.addEventListener('DOMContentLoaded', function () {
                            const debugModeCheckbox = document.getElementById('debug_mode');
                            const debugContextFields = document.getElementById('debugContextFields');

                            if (debugModeCheckbox && debugContextFields) {
                                debugModeCheckbox.addEventListener('change', function () {
                                    debugContextFields.style.display = this.checked ? 'block' : 'none';
                                    window.katalysisAIDebugMode = this.checked;
                                });
                            }
                        });
                    </script>

                </section>
            </div>
        </div>

    </div>

    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="float-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save" aria-hidden="true"></i> <?php echo t('Save'); ?>
                </button>
            </div>
        </div>
    </div>
</form>
