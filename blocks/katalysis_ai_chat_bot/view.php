<?php
defined('C5_EXECUTE') or die("Access Denied.");

// Only show if AI is configured
if (empty($openaiKey) || empty($openaiModel)) {
    return;
}

$blockID = $b->getBlockID();
$uniqueID = 'chatbot-' . $blockID;
?>

<?php
$c = Page::getCurrentPage();
if (is_object($c) && $c->isEditMode()) {
    $loc = Localization::getInstance();
    $loc->pushActiveContext(Localization::CONTEXT_UI);
    ?>
	<div class="ccm-edit-mode-disabled-item">
		<div style="padding: 8px;"><?php echo t('Content disabled in edit mode.'); ?></div>
	</div>
    <?php
    $loc->popActiveContext();
} else { 
?>

<div id="<?php echo $uniqueID; ?>" class="katalysis-ai-chatbot-block" 
     data-position="<?php echo htmlspecialchars($chatbotPosition ?? 'bottom-right'); ?>"
     data-theme="<?php echo htmlspecialchars($theme ?? 'light'); ?>">
    
    
    <div class="chatbot-container">
        <div class="chatbot-toggle" onclick="toggleChatbot('<?php echo $uniqueID; ?>')">
            <i class="fa fa-comments"></i>
            <span class="toggle-text"><?php echo t('Chat with us'); ?></span>
        </div>
        
        <div class="chatbot-interface" style="display: none;">
            <div class="chatbot-header">
                <div class="chatbot-header-title">
                    <i class="fa fa-robot"></i> <span id="<?php echo $uniqueID; ?>-ai-header-greeting" class="ai-header-greeting"><?php echo t('AI Assistant'); ?></span>
                </div>
                <div class="chatbot-header-actions">
                    <button class="chatbot-clear" onclick="clearChatHistory('<?php echo $uniqueID; ?>')" title="<?php echo t('Clear Chat'); ?>">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button class="chatbot-close" onclick="toggleChatbot('<?php echo $uniqueID; ?>')">
                        <i class="fa fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            
            <div class="chatbot-messages" id="<?php echo $uniqueID; ?>-messages">
                <!-- Messages will be populated here -->
            </div>
            
            <div class="chatbot-input">
                <input type="text" class="chatbot-input-field" 
                       id="<?php echo $uniqueID; ?>-input" 
                       placeholder="<?php echo t('Type your message...'); ?>"
                       onkeypress="handleChatInput(event, '<?php echo $uniqueID; ?>')">
                <button class="chatbot-send-btn" 
                        onclick="sendChatMessage('<?php echo $uniqueID; ?>')">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize chatbot when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeChatbot('<?php echo $uniqueID; ?>', {
        pageTitle: <?php echo json_encode($pageTitle ?? ''); ?>,
        pageUrl: <?php echo json_encode($pageUrl ?? ''); ?>,
        pageType: <?php echo json_encode($pageType ?? ''); ?>,
        welcomePrompt: <?php echo json_encode($welcomePrompt ?? ''); ?>,
        isEditMode: <?php echo json_encode($isEditMode ?? false); ?>,
        colors: {
            primary: <?php echo json_encode($primaryColor ?? '#7749F8'); ?>,
            primaryDark: <?php echo json_encode($primaryDarkColor ?? '#4D2DA5'); ?>,
            secondary: <?php echo json_encode($secondaryColor ?? '#6c757d'); ?>,
            success: <?php echo json_encode($successColor ?? '#28a745'); ?>,
            light: <?php echo json_encode($lightColor ?? '#ffffff'); ?>,
            dark: <?php echo json_encode($darkColor ?? '#333333'); ?>,
            border: <?php echo json_encode($borderColor ?? '#e9ecef'); ?>,
            shadow: <?php echo json_encode($shadowColor ?? 'rgba(0,0,0,0.1)'); ?>,
            hoverBg: <?php echo json_encode($hoverBgColor ?? 'rgba(255,255,255,0.2)'); ?>
        }
    });
});

function initializeChatbot(chatbotId, config) {
    // Store config globally
    window.chatbotConfigs = window.chatbotConfigs || {};
    window.chatbotConfigs[chatbotId] = config;
    
    // Initialize form handling for this chatbot
    if (typeof initializeChatForms === 'function') {
        initializeChatForms(chatbotId);
    }
    
    
    // Check if we have an existing chat session for this user across all pages
    let sessionId = localStorage.getItem('chatbot_global_session_id');
    let sessionTimestamp = localStorage.getItem('chatbot_global_session_timestamp');
    
    // Check if session has expired (24 hours)
    const sessionExpiry = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    const now = Date.now();
    
    if (sessionId && sessionTimestamp && (now - parseInt(sessionTimestamp)) > sessionExpiry) {
        // Session expired, clear it and start fresh
        localStorage.removeItem('chatbot_global_session_id');
        localStorage.removeItem(`chatbot_chat_id_${sessionId}`);
        localStorage.removeItem(`chatbot_global_history_${sessionId}`);
        sessionId = null;
        sessionTimestamp = null;
    }
    
    if (!sessionId) {
        // Generate a new global session ID if none exists
        sessionId = generateSessionId();
        localStorage.setItem('chatbot_global_session_id', sessionId);
        localStorage.setItem('chatbot_global_session_timestamp', now.toString());
    }
    
    // Check if we have an existing chat ID for this global session
    const existingChatId = localStorage.getItem(`chatbot_chat_id_${sessionId}`);
    
    // Set the session ID and existing chat ID in the config
    window.chatbotConfigs[chatbotId].sessionId = sessionId;
    if (existingChatId) {
        window.chatbotConfigs[chatbotId].existingChatId = parseInt(existingChatId);
    }
    
    // Add page unload listener to log conversation when user leaves
    window.addEventListener('beforeunload', () => {
        logCompleteConversationToDatabase(chatbotId);
    });
    
    // Load chat history first
    const hasHistory = loadChatHistory(chatbotId);
    
    // Try to restore welcome message from separate storage
    const savedWelcomeMessage = localStorage.getItem(`chatbot_welcome_${chatbotId}`);
    if (savedWelcomeMessage) {
        const cleanHeaderText = cleanTextForHeader(savedWelcomeMessage);
        updateAIHeaderGreeting(chatbotId, cleanHeaderText);
    } else {
        // Set default header greeting until welcome message is generated
        updateAIHeaderGreeting(chatbotId, 'AI Assistant');
    }
    
    // Determine initial interface state based on existing chat and user preference
    const isMinimized = localStorage.getItem(`chatbot_minimized_${chatbotId}`) === 'true';
    
    if (isMinimized) {
        // User has minimized the chat, show button only
        showChatButton(chatbotId);
    } else if (hasHistory && existingChatId) {
        // Check if there are actual user messages (not just system messages)
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const hasUserMessages = messagesContainer && messagesContainer.querySelectorAll('.chatbot-message-user').length > 0;
        
        if (hasUserMessages) {
            // Existing conversation with user messages - show open chat interface
            showOpenChatInterface(chatbotId);
        } else {
            // Existing chat ID but no user messages - show welcome interface
            showWelcomeInterface(chatbotId);
            
            // Generate welcome message if not already generated
            if (!savedWelcomeMessage) {
                setTimeout(() => {
                    generateWelcomeMessage(chatbotId, config);
                }, 100);
            }
        }
    } else {
        // New conversation - show button initially, then welcome message and input
        showChatButton(chatbotId);
        
        // Generate welcome message and show input field
        setTimeout(() => {
            generateWelcomeMessage(chatbotId, config);
        }, 100);
    }
}

/**
 * Generate a unique session ID for tracking individual chat conversations
 */
function generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function toggleChatbot(chatbotId) {
    const interface = document.querySelector(`#${chatbotId} .chatbot-interface`);
    const toggle = document.querySelector(`#${chatbotId} .chatbot-toggle`);
    
    if (interface.style.display === 'none') {
        // Opening the chat - use the new logic
        handleChatButtonClick(chatbotId);
        
        // Save that chat is not minimized
        localStorage.setItem(`chatbot_minimized_${chatbotId}`, 'false');
    } else {
        // Minimizing the chat
        interface.style.display = 'none';
        toggle.style.display = 'block';
        
        // Save that chat is minimized
        localStorage.setItem(`chatbot_minimized_${chatbotId}`, 'true');
    }
}

function handleChatInput(event, chatbotId) {
    if (event.key === 'Enter') {
        // Check if this is the first message in a new conversation
        const config = window.chatbotConfigs[chatbotId];
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const hasExistingMessages = messagesContainer && messagesContainer.children.length > 0;
        
        if (!hasExistingMessages && config && !config.existingChatId) {
            // First message in new conversation, ensure welcome interface is shown
            showWelcomeInterface(chatbotId);
        }
        
        sendChatMessage(chatbotId);
    }
}

function sendChatMessage(chatbotId) {
    const input = document.getElementById(`${chatbotId}-input`);
    const message = input.value.trim();
    
    if (!message) return;
    
    // Check if this is the first message in a new conversation
    const config = window.chatbotConfigs[chatbotId];
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    const hasExistingMessages = messagesContainer && messagesContainer.children.length > 0;
    
    if (!hasExistingMessages && config && !config.existingChatId) {
        // First message in new conversation, ensure welcome interface is shown
        showWelcomeInterface(chatbotId);
    }
    
    // Add user message
    addChatMessage(chatbotId, message, 'user');
    input.value = '';
    
    // Send to AI
    sendToAI(chatbotId, message);
}

function addChatMessage(chatbotId, message, sender) {
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message chatbot-message-${sender}`;
    
    const icon = sender === 'user' ? 'fa-user' : 'fa-robot';
    
    // Store the current timestamp on the message element for later retrieval
    messageDiv.setAttribute('data-timestamp', Date.now().toString());
    
    // Check if message contains HTML tags
    const containsHTML = /<[^>]*>/g.test(message);
    
    if (containsHTML) {
        // For HTML content (like AI responses with buttons), render as HTML
        messageDiv.innerHTML = `
            <div class="message-content">
                <i class="fa ${icon}"></i>
                <span>${message}</span>
            </div>
        `;
    } else {
        // For plain text (like user messages), escape HTML for security
        messageDiv.innerHTML = `
            <div class="message-content">
                <i class="fa ${icon}"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;
    }
    
    messagesContainer.appendChild(messageDiv);
    
    // Show messages container when first message is added
    if (!messagesContainer.classList.contains('has-messages')) {
        messagesContainer.classList.add('has-messages');
    }
    
    // Ensure the messages container is visible
    messagesContainer.style.display = 'block';
    messagesContainer.style.opacity = '1';
    
    // Save to localStorage only (don't log to database yet)
    saveChatHistoryToLocalStorage(chatbotId);
    
    // Scroll to bottom to show the full last message
    setTimeout(() => {
        scrollToBottom(chatbotId);
    }, 10);
}

function saveChatHistory(chatbotId) {
    try {
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const messages = messagesContainer.querySelectorAll('.chatbot-message:not(.typing-indicator)');
        
        const chatHistory = [];
        messages.forEach(message => {
            const sender = message.classList.contains('chatbot-message-user') ? 'user' : 'ai';
            const content = message.querySelector('.message-content span').innerHTML;
            
            // Filter out welcome messages to prevent them from being stored
            const isWelcomeMessage = sender === 'ai' && 
                (content.includes('Welcome') || 
                 content.includes('Hello') || 
                 content.includes('How can I help') ||
                 content.includes('How can we help'));
            
            if (!isWelcomeMessage) {
                // Get the actual timestamp when the message was created
                const timestamp = message.getAttribute('data-timestamp');
                chatHistory.push({ sender, content, timestamp: timestamp ? parseInt(timestamp) : Date.now() });
            }
        });
        
        // Save to localStorage
        localStorage.setItem(`chatbot_history_${chatbotId}`, JSON.stringify(chatHistory));
        
        // Only log to database if there are actual conversation messages (not just welcome messages)
        if (chatHistory.length === 0) {
            return;
        }
        
        // If there's a pending welcome message, let sendToAI handle the chat creation
        if (window.pendingWelcomeMessage) {
            return;
        }
        
        // Handle database logging based on whether this is a new session or existing one
        const config = window.chatbotConfigs[chatbotId];
        if (config) {
            
            // Check if we already have a chat record for this session
            const existingChatId = config.existingChatId;
            
            // Check if we're currently in the process of creating a chat record
            if (config.isCreatingChat) {
                return;
            }
            
            if (existingChatId) {
                // Update existing chat record
                updateChatInDatabase(chatbotId, chatHistory);
            } else {
                // Create new chat record for this session
                config.isCreatingChat = true; // Set flag to prevent multiple calls
                logChatToDatabase(chatbotId, chatHistory);
            }
        } else {
        }
        
    } catch (error) {
    }
}

/**
 * Save chat history to localStorage only (no database logging)
 */
function saveChatHistoryToLocalStorage(chatbotId) {
    try {
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const messages = messagesContainer.querySelectorAll('.chatbot-message:not(.typing-indicator)');
        
        const chatHistory = [];
        messages.forEach(message => {
            const sender = message.classList.contains('chatbot-message-user') ? 'user' : 'ai';
            const content = message.querySelector('.message-content span').innerHTML;
            
            // Filter out welcome messages to prevent them from being stored
            const isWelcomeMessage = sender === 'ai' && 
                (content.includes('Welcome') || 
                 content.includes('Hello') || 
                 content.includes('How can I help') ||
                 content.includes('How can we help'));
            
            if (!isWelcomeMessage) {
                // Get the actual timestamp when the message was created
                const timestamp = message.getAttribute('data-timestamp');
                chatHistory.push({ sender, content, timestamp: timestamp ? parseInt(timestamp) : Date.now() });
            }
        });
        
        // Save to both local and global storage for persistence across page navigation
        localStorage.setItem(`chatbot_history_${chatbotId}`, JSON.stringify(chatHistory));
        
        // Also save to global session storage
        const config = window.chatbotConfigs[chatbotId];
        if (config && config.sessionId) {
            const globalHistoryKey = `chatbot_global_history_${config.sessionId}`;
            localStorage.setItem(globalHistoryKey, JSON.stringify(chatHistory));
        }
        
    } catch (error) {
    }
}

/**
 * Log the complete conversation to database (called when conversation ends or explicitly requested)
 */
function logCompleteConversationToDatabase(chatbotId) {
    try {
        const config = window.chatbotConfigs[chatbotId];
        
        // Skip database logging when in edit mode
        if (config && config.isEditMode) {
            return;
        }
        
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const messages = messagesContainer.querySelectorAll('.chatbot-message:not(.typing-indicator)');
        
        const chatHistory = [];
        messages.forEach(message => {
            const sender = message.classList.contains('chatbot-message-user') ? 'user' : 'ai';
            const content = message.querySelector('.message-content span').innerHTML;
            // Get the actual timestamp when the message was created
            const timestamp = message.getAttribute('data-timestamp');
            chatHistory.push({ sender, content, timestamp: timestamp ? parseInt(timestamp) : Date.now() });
        });
        
        if (chatHistory.length === 0) {
            return;
        }
        
        // Only log to database if there are actual user messages (not just welcome messages)
        const hasUserMessages = chatHistory.some(msg => msg.sender === 'user');
        if (!hasUserMessages) {
            return;
        }
        
        // If there's a pending welcome message, let sendToAI handle the chat creation
        if (window.pendingWelcomeMessage) {
            return;
        }
        
        // Handle database logging based on whether this is a new session or existing one
        if (config) {
            
            // Check if we already have a chat record for this session
            const existingChatId = config.existingChatId;
            
            if (existingChatId) {
                // Update existing chat record
                updateChatInDatabase(chatbotId, chatHistory);
            } else {
                // Create new chat record for this session
                logChatToDatabase(chatbotId, chatHistory);
            }
        } else {
        }
        
    } catch (error) {
    }
}

function loadChatHistory(chatbotId) {
    try {
        const config = window.chatbotConfigs[chatbotId];
        if (!config || !config.sessionId) {
            return false;
        }
        
        // First try to load from global session storage
        const globalHistoryKey = `chatbot_global_history_${config.sessionId}`;
        let savedHistory = localStorage.getItem(globalHistoryKey);
        
        // Fallback to page-specific history if no global history exists
        if (!savedHistory) {
            savedHistory = localStorage.getItem(`chatbot_history_${chatbotId}`);
        }
        
        if (savedHistory) {
            const chatHistory = JSON.parse(savedHistory);
            const messagesContainer = document.getElementById(`${chatbotId}-messages`);
            
            
            // Filter out welcome messages - only show actual conversation messages
            const conversationMessages = chatHistory.filter(msg => {
                // Skip messages that look like welcome messages
                const isWelcomeMessage = msg.sender === 'ai' && 
                    (msg.content.includes('Welcome') || 
                     msg.content.includes('Hello') || 
                     msg.content.includes('How can I help') ||
                     msg.content.includes('How can we help'));
                return !isWelcomeMessage;
            });
            
            
            // Clear existing messages
            messagesContainer.innerHTML = '';
            
            // Only restore actual conversation messages, not welcome messages
            if (conversationMessages.length > 0) {
                conversationMessages.forEach(msg => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `chatbot-message chatbot-message-${msg.sender}`;
                    
                    // Restore the original timestamp from the saved message
                    if (msg.timestamp) {
                        messageDiv.setAttribute('data-timestamp', msg.timestamp.toString());
                    }
                    
                    const icon = msg.sender === 'user' ? 'fa-user' : 'fa-robot';
                    const containsHTML = /<[^>]*>/g.test(msg.content);
                    
                    if (containsHTML) {
                        messageDiv.innerHTML = `
                            <div class="message-content">
                                <i class="fa ${icon}"></i>
                                <span>${msg.content}</span>
                            </div>
                        `;
                    } else {
                        messageDiv.innerHTML = `
                            <div class="message-content">
                                <i class="fa ${icon}"></i>
                                <span>${escapeHtml(msg.content)}</span>
                            </div>
                        `;
                    }
                    
                    messagesContainer.appendChild(messageDiv);
                });
                
                // Show messages container only if there are actual conversation messages
                const hasUserMessages = conversationMessages.some(msg => msg.sender === 'user');
                if (hasUserMessages) {
                    messagesContainer.classList.add('has-messages');
                }
                
                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                // Ensure we're at the very bottom to show the full last message
                setTimeout(() => {
                    scrollToBottom(chatbotId);
                }, 10);
                
                
                // Only show open chat interface if there are actual user messages
                if (hasUserMessages) {
                    const isMinimized = localStorage.getItem(`chatbot_minimized_${chatbotId}`) === 'true';
                    if (!isMinimized) {
                        showOpenChatInterface(chatbotId);
                    }
                }
            }
            
            // Return true if we had any messages (even if filtered out)
            return chatHistory.length > 0;
        }
        return false; // Indicate that no history was loaded
    } catch (error) {
        return false; // Indicate that history loading failed
    }
}

function logChatToDatabase(chatbotId, chatHistory) {
    const config = window.chatbotConfigs[chatbotId];
    
    // Skip database logging when in edit mode
    if (config && config.isEditMode) {
        return;
    }
    
    // Prepare chat data for database logging
    const chatData = {
        chatbot_id: chatbotId,
        session_id: config.sessionId || 'unknown',
        page_title: config.pageTitle || '',
        page_url: config.pageUrl || '',
        page_type: config.pageType || '',
        messages: chatHistory,
        timestamp: Date.now()
    };
    
    // Send to backend endpoint for database logging
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/log_chat/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(chatData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Get chat ID directly from response
            if (data.chat_id) {
                const chatId = parseInt(data.chat_id);
                const config = window.chatbotConfigs[chatbotId];
                
                // Store chat ID in both config and localStorage for persistence across page navigation
                config.existingChatId = chatId;
                config.isCreatingChat = false; // Clear the flag
                
                // Store chat ID in localStorage using session ID as key
                if (config.sessionId) {
                    localStorage.setItem(`chatbot_chat_id_${config.sessionId}`, chatId.toString());
                }
                
            } else {
                window.chatbotConfigs[chatbotId].isCreatingChat = false; // Clear the flag on error too
            }
        } else {
            window.chatbotConfigs[chatbotId].isCreatingChat = false; // Clear the flag on error
        }
    })
    .catch(error => {
        // Clear the flag on error
        if (window.chatbotConfigs[chatbotId]) {
            window.chatbotConfigs[chatbotId].isCreatingChat = false;
        }
    });
}

/**
 * Update existing chat record in database with new messages
 */
function updateChatInDatabase(chatbotId, chatHistory) {
    const config = window.chatbotConfigs[chatbotId];
    
    if (!config || !config.existingChatId) {
        return;
    }
    
    // Skip database updates when in edit mode
    if (config.isEditMode) {
        return;
    }
    
    
    // Prepare chat data for updating
    const chatData = {
        chat_id: config.existingChatId,
        messages: chatHistory,
        timestamp: Date.now()
    };
    
    // Send to backend endpoint for updating chat
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/update_chat/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(chatData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
        } else {
        }
    })
    .catch(error => {
    });
}

function clearChatHistory(chatbotId) {
    if (confirm('Are you sure you want to clear the chat history? This cannot be undone.')) {
        try {
            // Clear from localStorage
            localStorage.removeItem(`chatbot_history_${chatbotId}`);
            localStorage.removeItem(`chatbot_welcome_${chatbotId}`);
            
            // Clear global session storage
            const config = window.chatbotConfigs[chatbotId];
            if (config && config.sessionId) {
                localStorage.removeItem(`chatbot_global_history_${config.sessionId}`);
                localStorage.removeItem(`chatbot_chat_id_${config.sessionId}`);
            }
            
            // Clear global session data
            localStorage.removeItem('chatbot_global_session_id');
            localStorage.removeItem('chatbot_global_session_timestamp');
            
            // Clear from display
            const messagesContainer = document.getElementById(`${chatbotId}-messages`);
            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                messagesContainer.classList.remove('has-messages'); // Ensure class is removed
            }
            
            // Generate a new session ID for the new conversation
            const newSessionId = generateSessionId();
            window.chatbotConfigs[chatbotId].sessionId = newSessionId;
            
            // Store the new session ID globally
            localStorage.setItem('chatbot_global_session_id', newSessionId);
            localStorage.setItem('chatbot_global_session_timestamp', Date.now().toString());
            
            // Clear the old chat ID from localStorage
            const oldSessionId = localStorage.getItem('chatbot_global_session_id');
            if (oldSessionId) {
                localStorage.removeItem(`chatbot_chat_id_${oldSessionId}`);
            }
            
            // Reset the existing chat ID to force creation of new chat record
            window.chatbotConfigs[chatbotId].existingChatId = null;
            
            // Clear any creation flags
            window.chatbotConfigs[chatbotId].isCreatingChat = false;
            
            // Reset form state
            window.chatbotConfigs[chatbotId].isFormActive = false;
            
            // Reset header greeting to default temporarily
            updateAIHeaderGreeting(chatbotId, 'AI Assistant');
            
            // Reset minimized state and show welcome interface
            localStorage.setItem(`chatbot_minimized_${chatbotId}`, 'false');
            showWelcomeInterface(chatbotId);
            
            // Generate new welcome message for fresh start
            const currentConfig = window.chatbotConfigs[chatbotId];
            if (currentConfig) {
                setTimeout(() => {
                    generateWelcomeMessage(chatbotId, currentConfig);
                }, 100);
            }
            // No fallback message needed - welcome message will be generated for header
            
        } catch (error) {
        }
    }
}

function sendToAI(chatbotId, message) {
    const config = window.chatbotConfigs[chatbotId];
    
    // Show typing indicator (don't trigger has-messages class)
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chatbot-message chatbot-message-ai typing-indicator';
    typingDiv.innerHTML = `
        <div class="message-content">
            <i class="fa fa-robot"></i>
            <span>...</span>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Prepare request data including form context if available
    const requestData = {
        message: message,
        mode: 'rag',
        page_type: config.pageType,
        page_title: config.pageTitle,
        page_url: config.pageUrl
    };
    
    // Add session context for form processing
    if (config.sessionId) {
        requestData.session_id = config.sessionId;
    }
    if (config.existingChatId) {
        requestData.chat_id = config.existingChatId;
    }
    
    // Include pending welcome message if this is the first user message
    if (window.pendingWelcomeMessage && !config.existingChatId) {
        requestData.welcome_message = window.pendingWelcomeMessage;
        requestData.new_chat = true;
        // Clear the pending welcome message after including it
        window.pendingWelcomeMessage = null;
    }
    
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/ask_ai/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Debug logging to see what response we received
        console.log('sendToAI - Response received:', data);
        console.log('sendToAI - Response type:', data.type);
        console.log('sendToAI - Response keys:', Object.keys(data));
        
        // Remove typing indicator
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        const typingIndicator = messagesContainer.querySelector('.typing-indicator');
        if (typingIndicator) {
            messagesContainer.removeChild(typingIndicator);
        }
        
        // Hide messages container if no messages remain
        if (messagesContainer.children.length === 0) {
            messagesContainer.classList.remove('has-messages');
        }
        
        // Store chat ID if this was a new chat creation
        if (data.chat_id && !config.existingChatId) {
            config.existingChatId = parseInt(data.chat_id);
            
            // Store chat ID in localStorage using session ID as key
            if (config.sessionId) {
                localStorage.setItem(`chatbot_chat_id_${config.sessionId}`, data.chat_id.toString());
            }
        }
        
        if (data.error) {
            // Show more specific error information if available
            let errorMessage = 'Sorry, I encountered an error. Please try again.';
            
            if (data.details) {
                errorMessage = `Error: ${data.details}`;
                if (data.type) {
                    errorMessage += ` (${data.type})`;
                }
            }
            
            addChatMessage(chatbotId, errorMessage, 'ai');
        } else if (data.type && (data.type.startsWith('form_') || data.type.startsWith('simple_form_'))) {
            // Handle form responses (including simple_form_started)
            console.log('sendToAI - Form response detected, calling handleFormResponseInBlock');
            console.log('sendToAI - Form response type:', data.type);
            handleFormResponseInBlock(chatbotId, data);
        } else {
            console.log('sendToAI - Not a form response, processing as regular AI response');
            console.log('sendToAI - Response type:', data.type);
            console.log('sendToAI - startsWith form_:', data.type && data.type.startsWith('form_'));
            console.log('sendToAI - startsWith simple_form_:', data.type && data.type.startsWith('simple_form_'));
            // Create the complete response content including buttons and links
            let responseContent = data.content;
            
            // Process the response content to convert "contact us" to links
            responseContent = processAIResponseContent(responseContent);
            
            // Check if we have actions or links to add
            const hasActions = data.actions && Array.isArray(data.actions) && data.actions.length > 0;
            const hasLinks = (data.more_info_links && Array.isArray(data.more_info_links) && data.more_info_links.length > 0) || 
                             (data.metadata && Array.isArray(data.metadata) && data.metadata.length > 0);
            
            if (hasActions || hasLinks) {
                responseContent += '<div class="more-info-links">';
                
                // Add heading
                responseContent += '<strong class="more-info-header">More Information:</strong>';
                
                // Add action buttons first (if any)
                if (hasActions) {
                    data.actions.forEach(action => {
                        responseContent += `<button class="action-button" onclick="executeAction('${chatbotId}', ${action.id})">`;
                        responseContent += `<i class="${action.icon || 'fas fa-cog'}"></i> ${action.name || 'Action'}`;
                        responseContent += '</button>';
                    });
                }
                
                // Add links list (if any)
                if (hasLinks) {
                    const links = data.more_info_links || data.metadata;
                    links.forEach(link => {
                        if (link && link.url && link.title) {
                            responseContent += `<a href="${link.url}" target="_blank" class="link-button">`;
                            responseContent += '<i class="fas fa-link"></i> ' + link.title;
                            responseContent += '</a>';
                        }
                    });
                }
                
                responseContent += '</div>';
            }
            
            // Add the complete response as one message
            addChatMessage(chatbotId, responseContent, 'ai');
            
            // Ensure we scroll to bottom to show the full response with buttons/links
            setTimeout(() => {
                scrollToBottom(chatbotId);
            }, 50);
            
            // Log conversation to database after AI response
            setTimeout(() => {
                logCompleteConversationToDatabase(chatbotId);
            }, 500);
            
            // Start automatic notification timer for chat silence
            startAutoNotificationTimer(chatbotId, 'silence');
            
        }
    })
    .catch(error => {
        // Store fallback welcome message separately for header restoration
        localStorage.setItem(`chatbot_welcome_${chatbotId}`, 'Hello! How can I help you today?');
        // Only update header with fallback greeting, don't add to chat
        updateAIHeaderGreeting(chatbotId, 'Hello! How can I help you today?');
        // Show welcome interface
        setTimeout(() => {
            showWelcomeInterface(chatbotId);
        }, 50);
    });
}

function generateWelcomeMessage(chatbotId, config) {
    
    if (!config.welcomePrompt) {
        // Store fallback welcome message separately for header restoration
        localStorage.setItem(`chatbot_welcome_${chatbotId}`, 'Hello! How can I help you today?');
        // Only update header with fallback greeting, don't add to chat
        updateAIHeaderGreeting(chatbotId, 'Hello! How can I help you today?');
        // Show welcome interface even with fallback message
        setTimeout(() => {
            showWelcomeInterface(chatbotId);
        }, 50);
        return;
    }
    
    // Process placeholders in the welcome prompt
    let processedPrompt = config.welcomePrompt;
    if (config.pageTitle) {
        processedPrompt = processedPrompt.replace(/{page_title}/g, config.pageTitle);
    }
    if (config.pageUrl) {
        processedPrompt = processedPrompt.replace(/{page_url}/g, config.pageUrl);
    }
    
    // Get current time of day
    const now = new Date();
    const hour = now.getHours();
    let timeOfDay = 'morning';
    if (hour >= 12 && hour < 17) {
        timeOfDay = 'afternoon';
    } else if (hour >= 17) {
        timeOfDay = 'evening';
    }
    processedPrompt = processedPrompt.replace(/{time_of_day}/g, timeOfDay);
    
    // Now send the processed prompt to AI
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/ask_ai/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify({
            message: processedPrompt,
            mode: 'basic',
            is_welcome_generation: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            // Store fallback welcome message separately for header restoration
            const fallbackMessage = 'Hello! How can I help you today?';
            localStorage.setItem(`chatbot_welcome_${chatbotId}`, fallbackMessage);
            // Only update header with fallback greeting, don't add to chat
            updateAIHeaderGreeting(chatbotId, fallbackMessage);
            // Show welcome interface even with fallback message
            setTimeout(() => {
                showWelcomeInterface(chatbotId);
            }, 50);
        } else {
            // Store welcome message separately for header restoration and for later chat saving
            localStorage.setItem(`chatbot_welcome_${chatbotId}`, data.content);
            
            // Store the welcome message for saving when user starts actual conversation
            window.pendingWelcomeMessage = data.content;
            
            // Only update header with clean text, don't add to chat area
            const cleanHeaderText = cleanTextForHeader(data.content);
            updateAIHeaderGreeting(chatbotId, cleanHeaderText);
            
            // Show welcome interface after welcome message is generated
            setTimeout(() => {
                showWelcomeInterface(chatbotId);
            }, 50);
        }
    })
    .catch(error => {
        // Store fallback welcome message separately for header restoration
        localStorage.setItem(`chatbot_welcome_${chatbotId}`, 'Hello! How can I help you today?');
        // Only update header with fallback greeting, don't add to chat
        updateAIHeaderGreeting(chatbotId, 'Hello! How can I help you today?');
        // Show welcome interface
        setTimeout(() => {
            showWelcomeInterface(chatbotId);
        }, 50);
    });
}

function executeAction(chatbotId, actionId) {
    const config = window.chatbotConfigs[chatbotId];
    console.log('executeAction called:', chatbotId, actionId, config);
    
    // First check if this is a form action by getting action info
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/get_action_info/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify({
            action_id: actionId
        })
    })
    .then(response => {
        console.log('get_action_info response status:', response.status);
        return response.json();
    })
    .then(actionInfo => {
        console.log('Action info received:', actionInfo);
        
        // Check if this is a form action
        if (actionInfo.actionType === 'form' || actionInfo.actionType === 'dynamic_form' || actionInfo.actionType === 'simple_form') {
            console.log('Form action detected');
            console.log('Show immediately setting:', actionInfo.showImmediately);
            
            // Check if this action should be shown immediately
            if (actionInfo.showImmediately) {
                console.log('Action has show immediately enabled, starting form...');
                // Use the block's own form handling with proper CSRF tokens
                startFormFromActionBlock(chatbotId, actionId);
            } else {
                console.log('Action does not have show immediately enabled, showing in More Information list');
                // Show the action in the "More Information" list instead of starting the form
                addChatMessage(chatbotId, `I can help you with that! Here's what you can do:`, 'ai');
                
                // Add a small delay to make the flow feel natural
                setTimeout(() => {
                    let actionsHtml = '<div class="more-info-links"><strong class="more-info-header">More Information:</strong>';
                    actionsHtml += `<button class="action-button" onclick="executeAction('${chatbotId}', ${actionId})">`;
                    actionsHtml += `<i class="${actionInfo.icon || 'fas fa-cog'}"></i> ${actionInfo.name || 'Action'}`;
                    actionsHtml += '</button>';
                    actionsHtml += '</div>';
                    addChatMessage(chatbotId, actionsHtml, 'ai');
                }, 500);
            }
        } else {
            console.log('Regular action detected:', actionInfo.actionType);
            // Handle regular action
            executeRegularAction(chatbotId, actionId);
        }
    })
    .catch(error => {
        console.error('Error getting action info:', error);
        addChatMessage(chatbotId, 'Error checking action type: ' + error.message, 'ai');
        // Fallback to regular action execution
        executeRegularAction(chatbotId, actionId);
    });
}

function executeRegularAction(chatbotId, actionId) {
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/execute_action/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify({
            action_id: actionId,
            conversation_context: 'Action button clicked from chatbot block'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            addChatMessage(chatbotId, 'Sorry, I encountered an error executing that action.', 'ai');
        } else {
            addChatMessage(chatbotId, data.response, 'ai');
        }
    })
    .catch(error => {
        addChatMessage(chatbotId, 'Sorry, I encountered an error executing that action.', 'ai');
    });
}

function startFormFromActionBlock(chatbotId, actionId) {
    // Get the current chat session ID from the config
    const config = window.chatbotConfigs[chatbotId];
    const chatId = config?.existingChatId || null;
    
    console.log('startFormFromActionBlock called:', {
        chatbotId,
        actionId,
        chatId,
        config
    });
    
    const requestData = {
        action_id: actionId,
        chat_id: chatId,
        session_id: config.sessionId
    };
    
    console.log('Sending start_form request:', requestData);
    
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/start_form/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('start_form response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('start_form response data:', data);
        if (data.type === 'form_started' || data.type === 'simple_form_started') {
            console.log('Form started successfully');
            // Store chat session ID if returned (new session)
            if (data.chat_id) {
                config.existingChatId = data.chat_id;
                console.log('Updated chat ID to:', data.chat_id);
            }
            
            // Set form as active
            config.isFormActive = true;
            console.log('Form marked as active');
            
            // Route to appropriate renderer based on form type
            if (data.type === 'simple_form_started') {
                console.log('Using renderSimpleForm for simple_form_started');
                renderSimpleForm(chatbotId, data);
            } else {
                console.log('Using renderSimpleFormStep for form_started');
                renderSimpleFormStep(chatbotId, data);
            }
        } else if (data.error) {
            console.error('Form start error from server:', data.error);
            addChatMessage(chatbotId, `Error starting form: ${data.error}`, 'ai');
        } else {
            console.warn('Unexpected form start response:', data);
            addChatMessage(chatbotId, 'Unexpected response from form system.', 'ai');
        }
    })
    .catch(error => {
        console.error('Error starting form (catch block):', error);
        addChatMessage(chatbotId, `Form start failed: ${error.message}. Please check the browser console for details.`, 'ai');
    });
}

function renderSimpleFormStep(chatbotId, data) {
    const stepData = data.step_data;
    const progress = data.progress;
    
    console.log('renderSimpleFormStep called with data:', data);
    console.log('Step data:', stepData);
    console.log('Progress:', progress);
    
    let formHtml = 'step info: <div class="simple-form-container" data-field-key="' + stepData.field_key + '">';
    
    // Progress indicator at the top
    if (progress) {
        const percentage = Math.round((progress.current_step / progress.total_steps) * 100);
        formHtml = 'Step ' + progress.current_step + ' of ' + progress.total_steps + ': <div class="simple-form-container" data-field-key="' + stepData.field_key + '">';
        formHtml += '<div class="form-progress mb-3">';
        formHtml += '<div class="progress" style="height: 8px;"><div class="progress-bar progress-bar-striped" style="width: ' + percentage + '%"></div></div>';
        formHtml += '</div>';
    }
    
    formHtml += '<div class="form-label fw-semibold">' + data.content + '</div>';
    formHtml += '<div class="form-group">';
    
    // Render input based on field type
    if (stepData.field_type === 'select' && stepData.options) {
        // Convert options to array if it's a string
        let optionsArray = stepData.options;
        if (typeof stepData.options === 'string') {
            optionsArray = stepData.options.split('\n').filter(option => option.trim() !== '');
        }
        
        if (Array.isArray(optionsArray) && optionsArray.length > 0) {
            formHtml += '<div class="form-actions">';
            optionsArray.forEach(option => {
                formHtml += '<button type="button" class="action-button" ';
                formHtml += 'onclick="submitFormOptionBlock(\'' + stepData.field_key + '\', \'' + option.trim() + '\', \'' + chatbotId + '\')">';
                formHtml += '✓ ' + option.trim() + '</button>';
            });
            formHtml += '</div>';
        } else {
            // Fallback to text input if options are invalid
            const inputType = stepData.field_type === 'textarea' ? 'textarea' : 'input';
            const placeholder = stepData.placeholder || 'Type your answer here...';
            
            const inputTag = inputType === 'textarea' ? 
                '<textarea class="form-control simple-form-input mb-2" id="form-input-' + stepData.field_key + '" name="' + stepData.field_key + '" placeholder="' + placeholder + '" rows="3"></textarea>' :
                '<input type="' + (stepData.field_type || 'text') + '" class="form-control simple-form-input mb-2" id="form-input-' + stepData.field_key + '" name="' + stepData.field_key + '" placeholder="' + placeholder + '">';
            
            formHtml += inputTag;
            formHtml += '<div class="form-actions">';
            formHtml += '<button type="button" class="action-button" onclick="submitFormInputBlock(\'' + stepData.field_key + '\', \'' + chatbotId + '\')">';
            formHtml += '<i class="fas fa-paper-plane"></i>Submit & Continue';
            formHtml += '</button>';
            
            // Add skip button if the field is optional
            if (stepData.validation && !stepData.validation.required) {
                formHtml += '<button type="button" class="action-button-secondary" onclick="submitFormValueBlock(\'' + stepData.field_key + '\', \'\', \'' + chatbotId + '\')">';
                formHtml += 'Skip';
                formHtml += '</button>';
            }
            formHtml += '</div>';
        }
    } else {
        const inputType = stepData.field_type === 'textarea' ? 'textarea' : 'input';
        const placeholder = stepData.placeholder || 'Type your answer here...';
                
        const inputTag = inputType === 'textarea' ? 
            '<textarea class="form-control simple-form-input mb-2" id="form-input-' + stepData.field_key + '" name="' + stepData.field_key + '" placeholder="' + placeholder + '" rows="3"></textarea>' :
            '<input type="' + (stepData.field_type || 'text') + '" class="form-control simple-form-input mb-2" id="form-input-' + stepData.field_key + '" name="' + stepData.field_key + '" placeholder="' + placeholder + '">';
        
        formHtml += inputTag;
        formHtml += '<div class="form-actions">';
        formHtml += '<button type="button" class="action-button" onclick="submitFormInputBlock(\'' + stepData.field_key + '\', \'' + chatbotId + '\')">';
        formHtml += '<i class="fas fa-paper-plane"></i>Submit & Continue';
        formHtml += '</button>';
        
        // Add skip button if the field is optional
        if (stepData.validation && !stepData.validation.required) {
            formHtml += '<button type="button" class="action-button-secondary" onclick="submitFormValueBlock(\'' + stepData.field_key + '\', \'\', \'' + chatbotId + '\')">';
            formHtml += 'Skip';
            formHtml += '</button>';
        }
        formHtml += '</div>';
    }
    
    formHtml += '</div>';
    formHtml += '</div>';
    
    console.log('Form HTML generated:', formHtml);
    
    // Add the form step as an AI message
    addChatMessage(chatbotId, formHtml, 'ai');
    
    // Focus on the input if it's a text input
    if (stepData.field_type !== 'select') {
        setTimeout(() => {
            const input = document.getElementById('form-input-' + stepData.field_key);
            console.log('Form input setup - Field key:', stepData.field_key);
            console.log('Form input setup - Input element found:', input);
            
            if (input) {
                console.log('Form input setup - Input element properties:', {
                    tagName: input.tagName,
                    type: input.type,
                    id: input.id,
                    name: input.name,
                    value: input.value,
                    placeholder: input.placeholder
                });
                
                input.focus();
                
                // Add keyboard handlers
                input.addEventListener('keypress', function(event) {
                    console.log('Keypress event:', event.key, 'ctrlKey:', event.ctrlKey, 'tagName:', input.tagName.toLowerCase());
                    
                    // For regular inputs, Enter submits
                    if (event.key === 'Enter' && input.tagName.toLowerCase() !== 'textarea') {
                        console.log('Enter pressed on regular input, submitting form');
                        event.preventDefault();
                        submitFormInputBlock(stepData.field_key, chatbotId);
                    }
                    // For textareas, Ctrl+Enter submits
                    if (event.key === 'Enter' && event.ctrlKey && input.tagName.toLowerCase() === 'textarea') {
                        console.log('Ctrl+Enter pressed on textarea, submitting form');
                        event.preventDefault();
                        submitFormInputBlock(stepData.field_key, chatbotId);
                    }
                });
                
                // Add input event listener to track value changes
                input.addEventListener('input', function(event) {
                    console.log('Input event - Value changed to:', input.value);
                });
                
                // Add change event listener
                input.addEventListener('change', function(event) {
                    console.log('Change event - Value changed to:', input.value);
                });
            } else {
                console.error('Form input setup - Input element not found for field:', stepData.field_key);
            }
        }, 100);
    }
}

function renderSimpleForm(chatbotId, data) {
    console.log('renderSimpleForm called with data:', data);
    console.log('Data type:', typeof data);
    console.log('Data keys:', Object.keys(data));
    console.log('Fields data:', data.fields);
    console.log('Form steps data:', data.form_steps);
    
    const formFields = data.fields || data.form_steps || [];
    const actionName = data.action_name || 'Form';
    
    console.log('Form fields to render:', formFields);
    console.log('Action name:', actionName);
    
    if (!formFields || !Array.isArray(formFields) || formFields.length === 0) {
        console.error('No form fields provided for simple form');
        console.error('Form fields value:', formFields);
        console.error('Form fields type:', typeof formFields);
        console.error('Form fields is array:', Array.isArray(formFields));
        addChatMessage(chatbotId, 'Error: No form fields defined', 'ai');
        return;
    }
    
    let formHtml = 'Please complete and submit:<div class="simple-form-container" data-action-id="' + data.action_id + '" data-chat-id="' + data.chat_id + '" data-form-id="' + Date.now() + '">';
    formHtml += '<strong class="simple-form-header">' + actionName + '</strong>';
    
    // Render all fields
    formFields.forEach((step, index) => {
        const fieldKey = step.stepKey || step.field_key || 'field_' + index;
        const fieldType = step.fieldType || step.field_type || 'text';
        const label = step.question || step.label || 'Field ' + (index + 1);
        const placeholder = step.placeholder || 'Enter ' + label.toLowerCase() + '...';
        const required = step.validation?.required !== false;
        
        formHtml += '<div class="form-group mb-2">';
        formHtml += '<label class="form-label fw-semibold">' + label;
        if (required) formHtml += ' <span class="text-danger">*</span>';
        formHtml += '</label>';
        
        if (fieldType === 'select' && step.options) {
            // Convert options to array if it's a string
            let optionsArray = step.options;
            if (typeof step.options === 'string') {
                optionsArray = step.options.split('\n').filter(option => option.trim() !== '');
            }
            
            if (Array.isArray(optionsArray) && optionsArray.length > 0) {
                // Render as buttons like Dynamic Form does
                formHtml += '<div class="form-actions">';
                optionsArray.forEach(option => {
                    formHtml += '<button type="button" class="action-button" ';
                    formHtml += 'onclick="selectSimpleFormOption(\'' + fieldKey + '\', \'' + option.trim() + '\', \'' + chatbotId + '\')">';
                    formHtml += '✓ ' + option.trim() + '</button>';
                });
                formHtml += '</div>';
                
                // Add hidden input to store selected value
                formHtml += '<input type="hidden" class="simple-form-input" id="simple-form-' + fieldKey + '" name="' + fieldKey + '" value=""' + (required ? ' required' : '') + '>';
            } else {
                // Fallback to text input if options are invalid
                const inputType = fieldType === 'email' ? 'email' : fieldType === 'number' ? 'number' : 'text';
                formHtml += '<input type="' + inputType + '" class="form-control simple-form-input" id="simple-form-' + fieldKey + '" name="' + fieldKey + '" placeholder="' + placeholder + '"' + (required ? ' required' : '') + '>';
            }
        } else if (fieldType === 'textarea') {
            formHtml += '<textarea class="form-control simple-form-input" id="simple-form-' + fieldKey + '" name="' + fieldKey + '" placeholder="' + placeholder + '" rows="3"' + (required ? ' required' : '') + '></textarea>';
        } else {
            const inputType = fieldType === 'email' ? 'email' : fieldType === 'number' ? 'number' : 'text';
            formHtml += '<input type="' + inputType + '" class="form-control simple-form-input" id="simple-form-' + fieldKey + '" name="' + fieldKey + '" placeholder="' + placeholder + '"' + (required ? ' required' : '') + '>';
        }
        
        formHtml += '<div class="invalid-feedback" id="error-' + fieldKey + '"></div>';
        formHtml += '</div>';
    });
    
    // Submit button
    formHtml += '<div class="form-actions">';
    formHtml += '<button type="button" class="action-button" onclick="submitSimpleForm(\'' + chatbotId + '\')">';
    formHtml += '<i class="fas fa-paper-plane"></i>Submit Form';
    formHtml += '</button>';
    formHtml += '</div>';
    
    formHtml += '</div>';
    
    // Add the form as an AI message
    addChatMessage(chatbotId, formHtml, 'ai');
    
    // Focus on the first input
    setTimeout(() => {
        const firstInput = document.querySelector('.simple-form-container .simple-form-input');
        if (firstInput) {
            firstInput.focus();
        }
    }, 100);
}

function selectSimpleFormOption(fieldKey, option, chatbotId) {
    // Find the hidden input for this field
    const hiddenInput = document.getElementById('simple-form-' + fieldKey);
    if (!hiddenInput) {
        return;
    }
    
    // Set the value
    hiddenInput.value = option;
    
    // Find the form group containing this field
    const formGroup = hiddenInput.closest('.form-group');
    if (!formGroup) {
        return;
    }
    
    // Reset all buttons in this field group
    const buttons = formGroup.querySelectorAll('.action-button');
    buttons.forEach(btn => {
        btn.classList.remove('selected');
        btn.style.backgroundColor = '';
        btn.style.borderColor = '';
        btn.style.color = '';
    });
    
    // Find and mark the selected button
    const selectedButton = Array.from(buttons).find(btn => {
        return btn.textContent.trim() === '✓ ' + option;
    });
    
    if (selectedButton) {
        selectedButton.classList.add('selected');
        selectedButton.style.backgroundColor = '#28a745';
        selectedButton.style.borderColor = '#28a745';
        selectedButton.style.color = 'white';
    }
}

function submitSimpleForm(chatbotId) {
    console.log('submitSimpleForm called for chatbot:', chatbotId);
    
    // Find the most recent simple-form-container (the one at the bottom of the chat)
    const formContainers = document.querySelectorAll('.simple-form-container');
    const formContainer = formContainers[formContainers.length - 1]; // Get the last one
    
    if (!formContainer) {
        console.error('Simple form container not found');
        return;
    }
    
    // Additional check: ensure this is a simple form (has data-action-id and data-chat-id)
    const actionId = formContainer.getAttribute('data-action-id');
    const chatId = formContainer.getAttribute('data-chat-id');
    
    // Get config for session ID
    const config = window.chatbotConfigs[chatbotId];
    const formId = formContainer.getAttribute('data-form-id');
    
    console.log('Form container found:', {
        actionId,
        chatId,
        formId,
        totalContainers: formContainers.length,
        containerIndex: formContainers.length - 1
    });
    
    const inputs = formContainer.querySelectorAll('.simple-form-input');
    
    console.log('Form submission details:', { actionId, chatId, inputCount: inputs.length });
    console.log('Form container attributes:', {
        'data-action-id': formContainer.getAttribute('data-action-id'),
        'data-chat-id': formContainer.getAttribute('data-chat-id'),
        'data-form-id': formContainer.getAttribute('data-form-id'),
        allAttributes: Array.from(formContainer.attributes).map(attr => attr.name + '=' + attr.value)
    });
    
    if (!actionId || !chatId) {
        console.error('Missing required IDs:', { actionId, chatId });
        console.error('This might be a progressive form container (has data-field-key instead)');
        alert('Error: Missing required form identifiers');
        return;
    }
    
    // Collect all field values
    const formData = {};
    let hasErrors = false;
    
    inputs.forEach(input => {
        const fieldKey = input.name;
        const value = input.value.trim();
        const required = input.hasAttribute('required');
        
        // Clear previous errors
        const errorDiv = document.getElementById('error-' + fieldKey);
        if (errorDiv) {
            errorDiv.textContent = '';
        }
        input.classList.remove('is-invalid');
        
        // Validate required fields
        if (required && !value) {
            hasErrors = true;
            input.classList.add('is-invalid');
            if (errorDiv) {
                errorDiv.textContent = 'This field is required';
            }
        } else {
            formData[fieldKey] = value;
        }
    });
    
    if (hasErrors) {
        console.log('Form validation failed');
        return;
    }
    
    const payload = {
        action_id: actionId,
        chat_id: chatId,
        form_data: formData
    };
    
    // Disable submit button and show loading - target by onclick attribute to distinguish from option buttons
    const submitBtn = formContainer.querySelector('button[onclick*="submitSimpleForm"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    }
    
    // Submit to backend
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/submit_simple_form/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify({
            action_id: actionId,
            chat_id: chatId,
            form_data: formData,
            session_id: config.sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Simple form submission response:', data);
        
        if (data.success) {
            // First show what the user submitted with HTML formatting for frontend
            let submissionSummary = 'Form submitted: <div class="form-submission-summary"><ul>';
            for (const [key, value] of Object.entries(formData)) {
                const displayKey = key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' ');
                submissionSummary += `<li><strong>${displayKey}:</strong> ${value}</li>`;
            }
            submissionSummary += '</ul></div>';
            addChatMessage(chatbotId, submissionSummary, 'user');
            
            // Then show the confirmation message
            addChatMessage(chatbotId, data.message || 'Form submitted successfully!', 'ai');
            
            // Mark form as inactive
            const config = window.chatbotConfigs[chatbotId];
            if (config) {
                config.isFormActive = false;
            }
            
            // Start automatic notification timer for form submission
            startAutoNotificationTimer(chatbotId, 'form');
            
            // Hide the specific form that was submitted with a brief success message
            const chatContainer = document.getElementById(`${chatbotId}-messages`);
            if (chatContainer) {
                const formContainers = chatContainer.querySelectorAll('.simple-form-container');
                formContainers.forEach(form => {
                    // Show a brief success message
                    form.innerHTML = '<div class="alert alert-success text-center mb-0"><i class="fas fa-check-circle me-2"></i>Form submitted successfully!</div>';
                    
                    // Hide the form after a short delay
                    setTimeout(() => {
                        form.style.display = 'none';
                    }, 2000);
                });
            }
            
        } else {
            console.error('Form submission failed:', data.error);
            addChatMessage(chatbotId, 'Error: ' + (data.error || 'Form submission failed'), 'ai');
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Form';
            }
        }
    })
    .catch(error => {
        console.error('Form submission error:', error);
        addChatMessage(chatbotId, 'Error submitting form: ' + error.message, 'ai');
        
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Form';
        }
    });
}

function submitFormInputBlock(fieldKey, chatbotId) {
    console.log('submitFormInputBlock called:', fieldKey, chatbotId);
    const input = document.getElementById('form-input-' + fieldKey);
    console.log('Input element found:', input);
    
    if (input) {
        // Check if already submitted to prevent double submission
        if (input.disabled || input.classList.contains('submitted')) {
            console.warn('Input already submitted, preventing double submission');
            console.warn('Input disabled:', input.disabled);
            console.warn('Input has submitted class:', input.classList.contains('submitted'));
            console.warn('Input ID:', input.id);
            console.warn('Input classes:', input.className);
            
            // Try to clear the state and allow submission
            console.log('Attempting to clear input state and allow submission...');
            input.disabled = false;
            input.classList.remove('submitted');
            console.log('Input state cleared, allowing submission');
        }
        
        console.log('Input element properties:', {
            tagName: input.tagName,
            type: input.type,
            id: input.id,
            name: input.name,
            value: input.value,
            defaultValue: input.defaultValue,
            innerHTML: input.innerHTML,
            outerHTML: input.outerHTML.substring(0, 200) + '...'
        });
        
        const value = input.value.trim();
        console.log('Input value after trim:', value);
        console.log('Input value length:', value.length);
        
        if (value) {
            console.log('Calling submitFormValueBlock with value:', value);
            submitFormValueBlock(fieldKey, value, chatbotId);
        } else {
            console.warn('Input value is empty, not submitting');
            
            // Try to get the value in different ways
            console.log('Trying alternative ways to get input value:');
            console.log('input.value:', input.value);
            console.log('input.textContent:', input.textContent);
            console.log('input.innerText:', input.innerText);
            console.log('input.innerHTML:', input.innerHTML);
            
            // Check if this is a textarea
            if (input.tagName.toLowerCase() === 'textarea') {
                console.log('This is a textarea, checking textarea-specific properties');
                console.log('textarea.value:', input.value);
                console.log('textarea.textContent:', input.textContent);
            }
        }
    } else {
        console.error('Input element not found for field:', fieldKey);
        
        // Try to find the input element in different ways
        console.log('Trying to find input element:');
        console.log('By ID:', document.getElementById('form-input-' + fieldKey));
        console.log('By name:', document.querySelector('input[name="' + fieldKey + '"]'));
        console.log('By name (textarea):', document.querySelector('textarea[name="' + fieldKey + '"]'));
        console.log('All form inputs:', document.querySelectorAll('input, textarea'));
    }
}

function submitFormOptionBlock(fieldKey, option, chatbotId) {
    submitFormValueBlock(fieldKey, option, chatbotId);
}

function submitFormValueBlock(fieldKey, value, chatbotId) {
    console.log('submitFormValueBlock called:', fieldKey, value, chatbotId);
    
    // Check if already submitted to prevent double submission
    const input = document.getElementById('form-input-' + fieldKey);
    if (input && (input.disabled || input.classList.contains('submitted'))) {
        console.warn('Form already submitted, preventing double submission');
        return;
    }
    
    // Disable the input/buttons to show it's submitted
    if (input) {
        input.disabled = true;
        input.value = value;
        input.classList.add('submitted');
        console.log('Input disabled and marked as submitted');
    }
    
    // Disable option buttons
    document.querySelectorAll('[data-field-key="' + fieldKey + '"] .action-button').forEach(btn => {
        btn.disabled = true;
        if (btn.textContent.trim().replace('✓ ', '') === value) {
            btn.classList.add('selected');
        }
    });
    
    // Disable submit button
    document.querySelectorAll('[data-field-key="' + fieldKey + '"] button').forEach(btn => {
        btn.disabled = true;
    });
    
    // Show user response
    console.log('Adding user message:', value);
    addChatMessage(chatbotId, value, 'user');
    
    // Check if we're in an active form
    const config = window.chatbotConfigs[chatbotId];
    if (config && config.isFormActive) {
        // Send form field response directly to backend
        console.log('Sending form field response to backend:', value);
        sendFormFieldResponse(chatbotId, fieldKey, value);
    } else {
        // Send value as regular chat message
        console.log('Sending to AI as regular message:', value);
        sendToAI(chatbotId, value);
    }
}

/**
 * Send form field response directly to backend for form processing
 */
function sendFormFieldResponse(chatbotId, fieldKey, value) {
    const config = window.chatbotConfigs[chatbotId];
    
    // Show typing indicator
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chatbot-message chatbot-message-ai typing-indicator';
    typingDiv.innerHTML = `
        <div class="message-content">
            <i class="fa fa-robot"></i>
            <span>...</span>
        </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    
    // Prepare request data for form field response
    const requestData = {
        message: value,
        mode: 'rag',
        page_type: config.pageType,
        page_title: config.pageTitle,
        page_url: config.pageUrl,
        session_id: config.sessionId,
        chat_id: config.existingChatId,
        is_form_field: true,
        field_key: fieldKey
    };
    
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/ask_ai/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo $csrfToken ?? ''; ?>'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        // Remove typing indicator
        const typingIndicator = messagesContainer.querySelector('.typing-indicator');
        if (typingIndicator) {
            messagesContainer.removeChild(typingIndicator);
        }
        
        // Check if response is ok
        if (!response.ok) {
            console.error('Form field response HTTP error:', response.status, response.statusText);
            
            // Try to get the error details
            response.text().then(errorText => {
                console.error('Form field response error text:', errorText);
                
                // Show error message to user
                let errorMessage = `Server error (${response.status}): ${response.statusText}`;
                if (errorText && !errorText.includes('<!DOCTYPE')) {
                    try {
                        const errorData = JSON.parse(errorText);
                        if (errorData.error) {
                            errorMessage = errorData.error;
                        }
                    } catch (e) {
                        // If it's not JSON, show first 200 chars of error text
                        if (errorText.length > 200) {
                            errorMessage = errorText.substring(0, 200) + '...';
                        } else {
                            errorMessage = errorText;
                        }
                    }
                }
                
                addChatMessage(chatbotId, `Error: ${errorMessage}`, 'ai');
            }).catch(textError => {
                console.error('Could not read error response text:', textError);
                addChatMessage(chatbotId, `Server error (${response.status}): ${response.statusText}`, 'ai');
            });
            
            return;
        }
        
        return response.json();
    })
    .then(data => {
        if (!data) {
            return; // Error already handled above
        }
        
        if (data.error) {
            // Show error message
            let errorMessage = 'Sorry, I encountered an error. Please try again.';
            if (data.details) {
                errorMessage = `Error: ${data.details}`;
                if (data.type) {
                    errorMessage += ` (${data.type})`;
                }
            }
            addChatMessage(chatbotId, errorMessage, 'ai');
        } else if (data.type && (data.type.startsWith('form_') || data.type.startsWith('simple_form_'))) {
            // Handle form responses (including simple_form_started)
            console.log('sendToAI - Form response detected, calling handleFormResponseInBlock');
            console.log('sendToAI - Form response type:', data.type);
            handleFormResponseInBlock(chatbotId, data);
        } else {
            console.log('sendToAI - Not a form response, processing as regular AI response');
            console.log('sendToAI - Response type:', data.type);
            console.log('sendToAI - startsWith form_:', data.type && data.type.startsWith('form_'));
            console.log('sendToAI - startsWith simple_form_:', data.type && data.type.startsWith('simple_form_'));
            // Handle regular AI responses
            let responseContent = data.content;
            responseContent = processAIResponseContent(responseContent);
            
            // Check if we have actions or links to add
            const hasActions = data.actions && Array.isArray(data.actions) && data.actions.length > 0;
            const hasLinks = (data.more_info_links && Array.isArray(data.more_info_links) && data.more_info_links.length > 0) || 
                             (data.metadata && Array.isArray(data.metadata) && data.metadata.length > 0);
            
            if (hasActions || hasLinks) {
                responseContent += '<div class="more-info-links">';
                responseContent += '<strong class="more-info-header">More Information:</strong>';
                
                if (hasActions) {
                    data.actions.forEach(action => {
                        responseContent += `<button class="action-button" onclick="executeAction('${chatbotId}', ${action.id})">`;
                        responseContent += `<i class="${action.icon || 'fas fa-cog'}"></i> ${action.name || 'Action'}`;
                        responseContent += '</button>';
                    });
                }
                
                if (hasLinks) {
                    const links = data.more_info_links || data.metadata;
                    links.forEach(link => {
                        if (link && link.url && link.title) {
                            responseContent += `<a href="${link.url}" target="_blank" class="link-button">`;
                            responseContent += '<i class="fas fa-link"></i> ' + link.title;
                            responseContent += '</a>';
                        }
                    });
                }
                
                responseContent += '</div>';
            }
            
            addChatMessage(chatbotId, responseContent, 'ai');
        }
        
        // Scroll to bottom
        setTimeout(() => {
            scrollToBottom(chatbotId);
        }, 50);
        
        // Log conversation to database
        setTimeout(() => {
            logCompleteConversationToDatabase(chatbotId);
        }, 500);
    })
    .catch(error => {
        console.error('Form field response failed:', error);
        
        // Remove typing indicator
        const typingIndicator = messagesContainer.querySelector('.typing-indicator');
        if (typingIndicator) {
            messagesContainer.removeChild(typingIndicator);
        }
        
        // Show more specific error message
        let errorMessage = 'Sorry, I encountered an error processing your response. Please try again.';
        
        if (error.name === 'SyntaxError' && error.message.includes('Unexpected token')) {
            errorMessage = 'The server returned an invalid response. This usually indicates a server error. Please try again or contact support.';
        } else if (error.message) {
            errorMessage = `Error: ${error.message}`;
        }
        
        addChatMessage(chatbotId, errorMessage, 'ai');
    });
}

/**
 * Clear form state to prevent conflicts between different forms
 */
function clearFormState() {
    console.log('Clearing form state...');
    
    // Remove any existing form containers from the DOM
    const existingFormContainers = document.querySelectorAll('.simple-form-container');
    existingFormContainers.forEach(container => {
        console.log('Removing existing form container:', container);
        container.remove();
    });
    
    // Remove submitted class from all inputs (both simple-form-input and form-input-*)
    const submittedInputs = document.querySelectorAll('.simple-form-input.submitted, input.submitted, textarea.submitted');
    submittedInputs.forEach(input => {
        input.classList.remove('submitted');
        input.disabled = false;
        console.log('Cleared submitted state for input:', input.id);
    });
    
    // Also clear any inputs with form-input-* IDs that might be disabled
    const formInputs = document.querySelectorAll('input[id^="form-input-"], textarea[id^="form-input-"]');
    formInputs.forEach(input => {
        if (input.disabled || input.classList.contains('submitted')) {
            input.disabled = false;
            input.classList.remove('submitted');
            console.log('Cleared state for form input:', input.id);
        }
    });
    
    // Remove disabled state from all buttons
    const disabledButtons = document.querySelectorAll('.action-button:disabled');
    disabledButtons.forEach(button => {
        button.disabled = false;
        console.log('Cleared disabled state for button:', button.textContent.trim());
    });
    
    // Remove selected class from option buttons
    const selectedButtons = document.querySelectorAll('.action-button.selected');
    selectedButtons.forEach(button => {
        button.classList.remove('selected');
        button.style.backgroundColor = '';
        button.style.borderColor = '';
        button.style.color = '';
        console.log('Cleared selected state for button:', button.textContent.trim());
    });
    
    console.log('Form state cleared successfully');
}

function handleFormResponseInBlock(chatbotId, data) {
    const config = window.chatbotConfigs[chatbotId];
    
    switch (data.type) {
        case 'form_started':
            // Handle form start response
            console.log('Form started response received:', data);
            
            // Clear any previous form state
            clearFormState();
            
            // Store chat session ID if returned (new session)
            if (data.chat_id && config) {
                config.existingChatId = data.chat_id;
                console.log('Updated chat ID to:', data.chat_id);
            }
            
            // Set form as active
            if (config) {
                config.isFormActive = true;
                console.log('Form marked as active - started');
            }
            
            // Render the first form step
            console.log('Rendering first form step');
            renderSimpleFormStep(chatbotId, data);
            break;
            
        case 'simple_form_started':
            // Handle simple form start response (all fields at once)
            console.log('Simple form started response received:', data);
            console.log('Simple form data type:', typeof data);
            console.log('Simple form data keys:', Object.keys(data));
            console.log('Simple form fields:', data.fields);
            
            // Clear any previous form state
            clearFormState();
            
            // Store chat session ID if returned (new session)
            if (data.chat_id && config) {
                config.existingChatId = data.chat_id;
                console.log('Updated chat ID to:', data.chat_id);
            }
            
            // Set form as active
            if (config) {
                config.isFormActive = true;
                console.log('Simple form marked as active - started');
            }
            
            // Render the simple form with all fields
            console.log('About to call renderSimpleForm with data:', data);
            renderSimpleForm(chatbotId, data);
            console.log('renderSimpleForm called successfully');
            break;
            
        case 'form_step':
            // Always use enhanced renderSimpleFormStep for better debugging and UX
            console.log('Using enhanced renderSimpleFormStep with debugging (handleFormResponseInBlock)');
            renderSimpleFormStep(chatbotId, data);
            break;
            
        case 'form_complete':
            // Show completion message
            addChatMessage(chatbotId, data.content || 'Form completed successfully!', 'ai');
            
            // Clear form state and reset
            clearFormState();
            if (config) {
                config.isFormActive = false;
                console.log('Form marked as inactive - completed');
            }
            
            // Handle any follow-up actions
            if (data.actions && data.actions.length > 0) {
                setTimeout(() => {
                    let actionsHtml = '<div class="more-info-links"><strong class="more-info-header">More Information:</strong>';
                    data.actions.forEach(action => {
                        actionsHtml += `<button class="action-button" onclick="executeAction('${chatbotId}', ${action.id})">`;
                        actionsHtml += `<i class="${action.icon || 'fas fa-cog'}"></i> ${action.name || 'Action'}`;
                        actionsHtml += '</button>';
                    });
                    actionsHtml += '</div>';
                    addChatMessage(chatbotId, actionsHtml, 'ai');
                }, 500);
            }
            break;
            
        case 'form_validation_error':
            // Show validation error
            const errorMessage = data.content || 'Please check your input and try again.';
            addChatMessage(chatbotId, `<span style="color: #dc3545;">${errorMessage}</span>`, 'ai');
            break;
            
        case 'form_cancelled':
            // Show cancellation message
            addChatMessage(chatbotId, data.content || 'Form cancelled. How else can I help you?', 'ai');
            
            // Clear form state and reset
            clearFormState();
            if (config) {
                config.isFormActive = false;
                console.log('Form marked as inactive - cancelled');
            }
            break;
            
        default:
            console.warn('Unknown form response type:', data.type);
            addChatMessage(chatbotId, data.content || 'Unexpected form response.', 'ai');
            
            // Reset form state on unknown response type
            if (config) {
                config.isFormActive = false;
                console.log('Form marked as inactive - unknown response type');
            }
            break;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Function to scroll chat to bottom
function scrollToBottom(chatbotId) {
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

// Function to clean text for header display (remove HTML tags)
function cleanTextForHeader(text) {
    if (!text || typeof text !== 'string') {
        return 'AI Assistant';
    }
    
    // Remove HTML tags
    let cleanText = text.replace(/<[^>]*>/g, '');
    
    // Return full text without truncation for welcome messages
    return cleanText.trim();
}

// Function to process AI response content and convert "contact us" to links
function processAIResponseContent(content) {
    if (content && typeof content === 'string') {
        // Case-insensitive replacement for "contact us" variations
        return content.replace(/\b(contact us|Contact Us|CONTACT US)\b/g, '<a href="/contact" target="_blank" class="chatbot-text-link">$1</a>');
    }
    return content;
}

function updateAIHeaderGreeting(chatbotId, greeting) {
    
    const headerElement = document.querySelector(`#${chatbotId} .ai-header-greeting`);
    if (headerElement) {
        headerElement.textContent = greeting;
    }
}

/**
 * Show the chat button interface (initial state)
 */
function showChatButton(chatbotId) {
    const interface = document.querySelector(`#${chatbotId} .chatbot-interface`);
    const toggle = document.querySelector(`#${chatbotId} .chatbot-toggle`);
    
    if (interface && toggle) {
        interface.style.display = 'none';
        toggle.style.display = 'block';
    }
}

/**
 * Show the welcome message and input field interface
 */
function showWelcomeInterface(chatbotId) {
    const interface = document.querySelector(`#${chatbotId} .chatbot-interface`);
    const toggle = document.querySelector(`#${chatbotId} .chatbot-toggle`);
    
    if (interface && toggle) {
        interface.style.display = 'block';
        toggle.style.display = 'none';
        
        // Hide messages container since we don't want to show it for welcome interface
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        if (messagesContainer) {
            messagesContainer.style.display = 'none';
            messagesContainer.classList.remove('has-messages');
        }
        
    }
}

/**
 * Show the open chat interface with existing conversation
 */
function showOpenChatInterface(chatbotId) {
    const interface = document.querySelector(`#${chatbotId} .chatbot-interface`);
    const toggle = document.querySelector(`#${chatbotId} .chatbot-toggle`);
    
    if (interface && toggle) {
        interface.style.display = 'block';
        toggle.style.display = 'none';
        
        // Ensure messages container is visible and shows messages
        const messagesContainer = document.getElementById(`${chatbotId}-messages`);
        if (messagesContainer) {
            messagesContainer.classList.add('has-messages');
            
            // Scroll to bottom to show the latest messages
            setTimeout(() => {
                scrollToBottom(chatbotId);
            }, 100);
        }
    }
}

/**
 * Handle chat button click - show appropriate interface based on current state
 */
function handleChatButtonClick(chatbotId) {
    const config = window.chatbotConfigs[chatbotId];
    const messagesContainer = document.getElementById(`${chatbotId}-messages`);
    const hasExistingMessages = messagesContainer && messagesContainer.children.length > 0;
    
    if (hasExistingMessages && config && config.existingChatId) {
        // Check if there are actual user messages (not just system messages)
        const hasUserMessages = messagesContainer.querySelectorAll('.chatbot-message-user').length > 0;
        
        if (hasUserMessages) {
            // Existing conversation with user messages - show open chat interface
            showOpenChatInterface(chatbotId);
        } else {
            // Existing chat ID but no user messages - show welcome interface
            showWelcomeInterface(chatbotId);
            
            // Check if we already have a welcome message
            const savedWelcomeMessage = localStorage.getItem(`chatbot_welcome_${chatbotId}`);
            if (savedWelcomeMessage) {
                // Welcome message already exists in header, just show the interface
                showWelcomeInterface(chatbotId);
            } else {
                // Generate welcome message if not already generated
                setTimeout(() => {
                    generateWelcomeMessage(chatbotId, config);
                }, 100);
            }
        }
    } else {
        // New conversation - show welcome interface
        showWelcomeInterface(chatbotId);
        
        // Check if we already have a welcome message
        const savedWelcomeMessage = localStorage.getItem(`chatbot_welcome_${chatbotId}`);
        if (savedWelcomeMessage) {
            // Welcome message already exists in header, just show the interface
            showWelcomeInterface(chatbotId);
        } else {
            // Generate welcome message if not already generated
            setTimeout(() => {
                generateWelcomeMessage(chatbotId, config);
            }, 100);
        }
    }
}

// Auto notification system
window.autoNotificationTimers = window.autoNotificationTimers || {};

function startAutoNotificationTimer(chatbotId, notificationType) {
    console.log(`[AUTO NOTIFY] startAutoNotificationTimer called - chatbotId: ${chatbotId}, type: ${notificationType}`);
    
    const config = window.chatbotConfigs[chatbotId];
    console.log(`[AUTO NOTIFY] config:`, config);
    
    // Don't start timer if no chat ID exists
    if (!config.existingChatId) {
        console.log(`[AUTO NOTIFY] No existing chat ID found. Chat ID: ${config.existingChatId}`);
        return;
    }
    
    // Clear any existing timer for this chatbot
    clearAutoNotificationTimer(chatbotId);
    
    // Get timeout duration based on notification type
    let timeoutMinutes;
    if (notificationType === 'form') {
        timeoutMinutes = <?php echo \Config::get('katalysis.aichatbot.form_silence_minutes', 1); ?>;
    } else {
        timeoutMinutes = <?php echo \Config::get('katalysis.aichatbot.chat_silence_minutes', 3); ?>;
    }
    
    console.log(`[AUTO NOTIFY] Timeout minutes configured: ${timeoutMinutes} for type: ${notificationType}`);
    
    // Check if auto notifications are enabled
    const autoNotificationsEnabled = <?php echo \Config::get('katalysis.aichatbot.enable_auto_notifications', false) ? 'true' : 'false'; ?>;
    console.log(`[AUTO NOTIFY] Auto notifications enabled: ${autoNotificationsEnabled}`);
    
    if (!autoNotificationsEnabled) {
        console.log(`[AUTO NOTIFY] Auto notifications are disabled, exiting`);
        return;
    }
    
    // Convert minutes to milliseconds
    const timeoutMs = timeoutMinutes * 60 * 1000;
    
    console.log(`[AUTO NOTIFY] Starting auto notification timer for ${chatbotId}: ${timeoutMinutes} minutes (${timeoutMs}ms)`);
    console.log(`[AUTO NOTIFY] Chat ID: ${config.existingChatId}`);
    
    // Set timer
    window.autoNotificationTimers[chatbotId] = setTimeout(() => {
        console.log(`[AUTO NOTIFY] Timer expired for ${chatbotId}, calling sendAutoNotification with type: ${notificationType}`);
        console.log(`[AUTO NOTIFY] Attempting to send notification for chat ID: ${config.existingChatId}`);
        sendAutoNotification(chatbotId, notificationType);
    }, timeoutMs);
    
    console.log(`[AUTO NOTIFY] Timer set successfully. Active timers:`, Object.keys(window.autoNotificationTimers));
}

function clearAutoNotificationTimer(chatbotId) {
    if (window.autoNotificationTimers[chatbotId]) {
        clearTimeout(window.autoNotificationTimers[chatbotId]);
        delete window.autoNotificationTimers[chatbotId];
        console.log(`Cleared auto notification timer for ${chatbotId}`);
    }
}

function sendAutoNotification(chatbotId, notificationType) {
    const config = window.chatbotConfigs[chatbotId];
    
    if (!config.existingChatId) {
        console.log('No chat ID available for auto notification');
        return;
    }
    
    console.log(`Sending auto notification for chat ${config.existingChatId}, type: ${notificationType}`);
    
    // Use same approach as working manual email
    $.ajax({
        url: '<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/chat_bot_settings/send_auto_notification'); ?>',
        type: 'POST',
        data: {
            chat_id: config.existingChatId,
            notification_type: notificationType,
            ccm_token: '<?php echo \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('token')->generate('send_chat_email'); ?>'
        },
        dataType: 'json'
    })
        .done(function(data) {
            if (data.success) {
                console.log('Auto notification sent successfully:', data.message);
            } else {
                console.log('Auto notification failed:', data.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Auto notification error:', error);
            console.error('Response:', xhr.responseText);
        });
}

// Clear auto notification timer when user sends a new message
const originalSendChatMessage = sendChatMessage;
sendChatMessage = function(chatbotId) {
    // Clear any existing timer when user sends a message
    clearAutoNotificationTimer(chatbotId);
    
    // Call original function
    return originalSendChatMessage(chatbotId);
};

</script>

<style>
:root {
    --chatbot-primary: <?php echo $primaryColor ?? '#7749F8'; ?>;
    --chatbot-primary-dark: <?php echo $primaryDarkColor ?? '#4D2DA5'; ?>;
    --chatbot-secondary: <?php echo $secondaryColor ?? '#6c757d'; ?>;
    --chatbot-success: <?php echo $successColor ?? '#28a745'; ?>;
    --chatbot-light: <?php echo $lightColor ?? '#ffffff'; ?>;
    --chatbot-dark: <?php echo $darkColor ?? '#333333'; ?>;
    --chatbot-border: <?php echo $borderColor ?? '#e9ecef'; ?>;
    --chatbot-shadow: <?php echo $shadowColor ?? 'rgba(0,0,0,0.1)'; ?>;
    --chatbot-hover-bg: <?php echo $hoverBgColor ?? 'rgba(255,255,255,0.2)'; ?>;
}


.katalysis-ai-chatbot-block {
    position: fixed;
    z-index: 1000;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    bottom: 20px;
    right: 20px;
}


.chatbot-toggle {
    background: var(--chatbot-primary);
    color: white;
    padding: 15px 20px;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 4px 12px var(--chatbot-shadow);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chatbot-toggle:hover {
    background: var(--chatbot-primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--chatbot-shadow);
}

.chatbot-interface {
    background: var(--chatbot-primary-dark);
    border-radius: 30px 30px 0 30px;
    box-shadow: 0 8px 32px var(--chatbot-shadow);
    width: 350px;
    max-height: 600px;
    display: flex;
    flex-direction: column;
    padding-bottom:10px;
}

.chatbot-header {
    background: transparent;
    color: white;
    padding: 15px;
    border-radius: 30px 30px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chatbot-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.chatbot-header-actions {
    display: flex;
    flex-direction: column-reverse;
    gap: 10px;
}

/* Add right margin to all icons for better spacing */
.chatbot-message i,
.chatbot-input i,
.chatbot-toggle i {
    margin-right: 6px;
}

/* More Information header styling */
.more-info-header, .simple-form-header {
    display: block;
    font-size: 0.7.5rem;
    color: var(--chatbot-secondary);
    font-weight: 600;
}
.simple-form-header {
    margin:10px 0;
}

.chatbot-clear {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 18px;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.chatbot-clear i {
    color: var(--chatbot-primary);
}

.chatbot-clear:hover {
    background: var(--chatbot-hover-bg);
}

.chatbot-close {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 18px;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.chatbot-close:hover {
    background: var(--chatbot-hover-bg);
}

.chatbot-messages {
    /* Fallback for older browsers */
    background: linear-gradient(180deg, 
        rgba(26, 188, 156, 0.1), 
        rgba(26, 188, 156, 0.4)
    );
    /* Modern browsers with color-mix support */
    background: linear-gradient(180deg,
        color-mix(in srgb, var(--chatbot-primary) 10%, white),
        color-mix(in srgb, var(--chatbot-primary) 40%, white)
    );
    border-radius: 15px 15px 0 0;
    padding: 10px 10px 0 10px;
    margin: 0 10px;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: 0 4px 12px var(--chatbot-shadow);
    display: none; /* Hide initially until first message */
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.chatbot-messages.has-messages {
    display: block; /* Show when messages are present */
    opacity: 1;
}

.typing-indicator {
    opacity: 0.7;
    font-style: italic;
}

.typing-indicator .message-content span {
    animation: typing 1.5s infinite;
}

@keyframes typing {
    0%, 20% { opacity: 1; }
    50% { opacity: 0.3; }
    80%, 100% { opacity: 1; }
}

.chatbot-message {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
}

.chatbot-message-user {
    flex-direction: row-reverse;
}

.chatbot-message-user .message-content {
    background: var(--chatbot-primary);
    color: white;
    border-radius: 18px 18px 4px 18px;
}

.chatbot-message-ai .message-content {
    background: #f8f9fa;
    color: #333;
    border-radius: 18px 18px 18px 4px;
}

.message-content {
    padding: 12px 16px;
    max-width: 80%;
    word-wrap: break-word;
}

.chatbot-input {
    margin: 0 10px;
    padding: 10px;
    background-color: white;
    border-radius: 0 0 0 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.chatbot-input-field {
    flex: 1;
    border: none;
    background-color: white;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    outline: none;
}

.chatbot-input-field:focus, 
.chatbot-input-field:active, 
.chatbot-input-field:hover {
    border: none;
    background-color: white;
    box-shadow: none;
}

.chatbot-input-field::placeholder {
    color: black;
}

.chatbot-send-btn {
    height: 42px;
    width: 42px;
    border-radius: 50% !important;
    background-color: var(--chatbot-primary);
    border-color: var(--chatbot-primary);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.chatbot-send-btn:hover {
    background-color: var(--chatbot-primary-dark);
    transform: scale(1.05);
}

.chatbot-actions {
    margin-top: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.more-info-links {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.action-button, .link-button {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    line-height: 1.5;
}

.action-button {
    background-color: var(--chatbot-primary);
    border-color: var(--chatbot-primary);
    color: white;
}

.action-button i {
    margin-right:6px;
}

.action-button:hover {
    background-color: var(--chatbot-primary-dark);
    border-color: var(--chatbot-primary-dark);
    transform: translateY(-1px);
}

a.link-button {
    background-color: white;
    color: var(--chatbot-primary) !important;
    border-color: var(--chatbot-primary);
}

a.link-button:hover {
    background-color: var(--chatbot-primary) !important;
    color: white !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.chatbot-text-link {
    color: var(--chatbot-primary) !important;
    font-weight: bold;
    text-decoration: none;
    transition: color 0.2s ease;
}

.chatbot-text-link:hover {
    color: var(--chatbot-primary-dark) !important;
    text-decoration: underline;
}

/* Form styles */

.form-step-question {
    font-weight: 600;
    margin-bottom: 15px;
    color: #2c3e50;
    font-size: 16px;
}


.simple-form-container .form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.form-progress {
    margin-top: 10px;
}

.form-progress .progress {
    height: 6px;
    border-radius: 3px;
    background-color: #e9ecef;
}

.form-progress .progress-bar {
    background-color: var(--chatbot-primary);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.form-step-actions {
    margin-top: 10px;
    text-align: right;
}

.form-field-error {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
}

.chatbot-title {
    text-align: center;
    margin-bottom: 15px;
    color: #333;
}

.chatbot-title h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

/* Dark theme */
.katalysis-ai-chatbot-block[data-theme="dark"] .chatbot-interface {
    background: #2d3748;
    color: white;
}

.katalysis-ai-chatbot-block[data-theme="dark"] .chatbot-message-ai .message-content {
    background: #4a5568;
    color: white;
}

.katalysis-ai-chatbot-block[data-theme="dark"] .chatbot-input {
    border-top-color: #4a5568;
}

.form-submission-summary {
    margin-top: 10px;
}

.form-submission-summary li {
    margin-bottom: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    .chatbot-interface {
        width: 300px;
        max-height: 400px;
    }
    
    .katalysis-ai-chatbot-block[data-position="center"] {
        position: fixed;
        top: 20px;
        left: 20px;
        right: 20px;
        transform: none;
    }
    
    .chatbot-interface {
        width: 100%;
    }
}

/* Simple Form Styles */
.simple-form-container {
    position: relative;
    padding-top:10px;
}

.simple-form-container .form-header {
    margin-bottom: 20px;
}

.simple-form-container .form-header h5 {
    color: var(--chatbot-primary);
    font-weight: 700;
    margin-bottom: 8px;
}

.simple-form-container .form-group {
    margin-bottom: 20px;
}

.simple-form-container .form-group:last-child {
    margin-bottom: 0;
}


.simple-form-container .form-label {
    color: #2c3e50;
    font-weight: 600;
    font-size:12px;
    margin-bottom: 8px;
    display: block;
}

.simple-form-container .simple-form-input {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.simple-form-container .simple-form-input:focus {
    border-color: var(--chatbot-primary);
    box-shadow: 0 0 0 3px rgba(119, 73, 248, 0.1);
    outline: none;
    transform: translateY(-1px);
}

.simple-form-container .simple-form-input.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.simple-form-container .invalid-feedback {
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.simple-form-container .form-actions {
    text-align: center;
}

.simple-form-container .action-button {
    width: 100%;
}

.simple-form-container .action-button:hover:not(:disabled) {
    transform: translateY(-2px);
}

.simple-form-container .action-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.simple-form-container .text-danger {
    color: #dc3545 !important;
}

@media (max-width: 768px) {
    .simple-form-container {
        padding: 15px;
        margin: 5px 0;
    }
    
    .simple-form-container .btn-primary {
        padding: 12px 30px;
        font-size: 14px;
    }
}
</style> 

<?php } ?>