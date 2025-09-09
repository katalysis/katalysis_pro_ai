/**
 * Chat Forms JavaScript
 * Handles AI-driven form interactions in the chatbot
 */

// Global form state
window.chatFormState = {
    activeForm: null,
    currentStep: null,
    formData: {},
    isFormActive: false
};

/**
 * Initialize form handling for a chatbot
 */
function initializeChatForms(chatbotId) {
    // Check for existing active form state
    const savedFormState = localStorage.getItem(`chatbot_form_state_${chatbotId}`);
    if (savedFormState) {
        try {
            window.chatFormState = JSON.parse(savedFormState);
        } catch (e) {
            console.warn('Invalid saved form state');
            window.chatFormState.isFormActive = false;
        }
    }
}

/**
 * Handle form-related responses from the AI
 */
function handleFormResponse(chatbotId, response) {
    switch (response.type) {
        case 'form_step':
            return renderFormStep(chatbotId, response);
        case 'form_complete':
            return handleFormCompletion(chatbotId, response);
        case 'form_validation_error':
            return showFormValidationError(chatbotId, response);
        case 'form_cancelled':
            return handleFormCancellation(chatbotId, response);
        default:
            return false; // Not a form response
    }
}

/**
 * Render a form step in the chat
 */
function renderFormStep(chatbotId, response) {
    const stepData = response.step_data;
    const progress = response.progress;
    
    // Update global form state
    window.chatFormState.isFormActive = true;
    window.chatFormState.currentStep = stepData.field_key;
    saveFormState(chatbotId);
    
    // Create form step HTML
    let formStepHtml = `
        <div class="chat-form-step" data-field-key="${stepData.field_key}">
            <div class="form-step-question">
                ${response.content}
            </div>
            <div class="form-step-input">
                ${renderFormInput(stepData)}
            </div>
            ${progress ? renderProgressBar(progress) : ''}
            <div class="form-step-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cancelForm('${chatbotId}')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
    
    // Add to chat messages
    addChatMessage(chatbotId, formStepHtml, 'ai');
    
    // Focus on the input field
    setTimeout(() => {
        focusFormInput(chatbotId, stepData.field_key);
    }, 100);
    
    return true;
}

/**
 * Render form input based on field type
 */
function renderFormInput(stepData) {
    const fieldKey = stepData.field_key;
    const fieldType = stepData.field_type;
    const options = stepData.options;
    const placeholder = stepData.placeholder || '';
    
    switch (fieldType) {
        case 'text':
        case 'email':
            return `
                <input type="${fieldType}" 
                       class="form-control chat-form-input" 
                       id="form-input-${fieldKey}"
                       name="${fieldKey}"
                       placeholder="${placeholder}"
                       onkeypress="handleFormInputKeypress(event, '${fieldKey}')"
                       autocomplete="off">
            `;
            
        case 'textarea':
            return `
                <textarea class="form-control chat-form-input" 
                          id="form-input-${fieldKey}"
                          name="${fieldKey}"
                          placeholder="${placeholder}"
                          rows="3"
                          onkeypress="handleFormInputKeypress(event, '${fieldKey}')"
                          autocomplete="off"></textarea>
            `;
            
        case 'select':
            if (options && options.length > 0) {
                return renderSelectOptions(fieldKey, options);
            } else {
                return `<div class="alert alert-warning">No options configured for this field</div>`;
            }
            
        default:
            return `
                <input type="text" 
                       class="form-control chat-form-input" 
                       id="form-input-${fieldKey}"
                       name="${fieldKey}"
                       placeholder="${placeholder}"
                       onkeypress="handleFormInputKeypress(event, '${fieldKey}')"
                       autocomplete="off">
            `;
    }
}

/**
 * Render select field as buttons
 */
function renderSelectOptions(fieldKey, options) {
    let html = '<div class="chat-form-options">';
    
    options.forEach((option, index) => {
        html += `
            <button type="button" 
                    class="btn btn-outline-primary chat-form-option" 
                    data-value="${option}"
                    onclick="selectFormOption('${fieldKey}', '${option}')">
                ${option}
            </button>
        `;
    });
    
    html += '</div>';
    return html;
}

/**
 * Handle form option selection
 */
function selectFormOption(fieldKey, value) {
    // Update UI to show selection
    const optionsContainer = document.querySelector(`[data-field-key="${fieldKey}"] .chat-form-options`);
    if (optionsContainer) {
        // Remove active state from all buttons
        optionsContainer.querySelectorAll('.chat-form-option').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        // Add active state to selected button
        const selectedBtn = optionsContainer.querySelector(`[data-value="${value}"]`);
        if (selectedBtn) {
            selectedBtn.classList.remove('btn-outline-primary');
            selectedBtn.classList.add('btn-primary');
        }
    }
    
    // Submit the form with the selected value
    submitFormField(fieldKey, value);
}

/**
 * Handle keypress in form inputs
 */
function handleFormInputKeypress(event, fieldKey) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        const input = event.target;
        submitFormField(fieldKey, input.value);
    }
}

/**
 * Submit form field value
 */
function submitFormField(fieldKey, value) {
    if (!window.chatFormState.isFormActive) {
        console.warn('No active form to submit to');
        return;
    }
    
    // Validate required fields
    if (!value || value.trim() === '') {
        showFormFieldError(fieldKey, 'This field is required.');
        return;
    }
    
    // Store the value
    window.chatFormState.formData[fieldKey] = value;
    
    // Disable the input to show it's been submitted
    const inputElement = document.getElementById(`form-input-${fieldKey}`);
    if (inputElement) {
        inputElement.disabled = true;
        inputElement.classList.add('submitted');
    }
    
    // Send the value as a regular chat message
    // The backend will handle it as a form response
    const chatbotId = getCurrentChatbotId();
    if (chatbotId) {
        sendChatMessage(chatbotId, value);
    }
}

/**
 * Show form field validation error
 */
function showFormFieldError(fieldKey, errorMessage) {
    const stepContainer = document.querySelector(`[data-field-key="${fieldKey}"]`);
    if (stepContainer) {
        // Remove existing error
        const existingError = stepContainer.querySelector('.form-field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error
        const errorHtml = `
            <div class="form-field-error alert alert-danger alert-sm mt-2">
                <i class="fas fa-exclamation-triangle"></i> ${errorMessage}
            </div>
        `;
        stepContainer.querySelector('.form-step-input').insertAdjacentHTML('afterend', errorHtml);
        
        // Re-focus the input
        focusFormInput(getCurrentChatbotId(), fieldKey);
    }
}

/**
 * Handle form validation errors from server
 */
function showFormValidationError(chatbotId, response) {
    const stepData = response.step_data;
    const errorMessage = response.content;
    
    showFormFieldError(stepData.field_key, errorMessage);
    return true;
}

/**
 * Handle form completion
 */
function handleFormCompletion(chatbotId, response) {
    // Clear form state
    window.chatFormState.isFormActive = false;
    window.chatFormState.currentStep = null;
    window.chatFormState.formData = {};
    saveFormState(chatbotId);
    
    // Add completion message
    addChatMessage(chatbotId, response.content, 'ai');
    
    // Handle any follow-up actions
    if (response.actions && response.actions.length > 0) {
        setTimeout(() => {
            displayActionButtons(response.actions);
        }, 500);
    }
    
    return true;
}

/**
 * Handle form cancellation
 */
function handleFormCancellation(chatbotId, response) {
    // Clear form state
    window.chatFormState.isFormActive = false;
    window.chatFormState.currentStep = null;
    window.chatFormState.formData = {};
    saveFormState(chatbotId);
    
    // Add cancellation message
    addChatMessage(chatbotId, response.content, 'ai');
    
    return true;
}

/**
 * Cancel active form
 */
function cancelForm(chatbotId) {
    if (!window.chatFormState.isFormActive) {
        return;
    }
    
    if (!confirm('Are you sure you want to cancel this form? Your progress will be lost.')) {
        return;
    }
    
    // Send cancel request to server
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/cancel_form/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            chat_id: getCurrentChatId(chatbotId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.type === 'form_cancelled') {
            handleFormCancellation(chatbotId, data);
        }
    })
    .catch(error => {
        console.error('Error cancelling form:', error);
        // Clear form state locally anyway
        handleFormCancellation(chatbotId, {
            content: 'Form cancelled. How else can I help you?'
        });
    });
}

/**
 * Start a form from an action
 */
function startFormFromAction(chatbotId, actionId) {
    console.log('startFormFromAction called with:', chatbotId, actionId);
    const chatId = getCurrentChatId(chatbotId);
    console.log('Chat ID:', chatId);
    
    fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/start_form/', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action_id: actionId,
            chat_id: chatId
        })
    })
    .then(response => {
        console.log('Form start response:', response);
        return response.json();
    })
    .then(data => {
        console.log('Form start data:', data);
        if (data.type === 'form_started') {
            console.log('Rendering form step');
            renderFormStep(chatbotId, data);
        } else if (data.error) {
            console.error('Form start error:', data.error);
            addChatMessage(chatbotId, `Error starting form: ${data.error}`, 'ai');
        } else {
            console.warn('Unexpected response type:', data);
            addChatMessage(chatbotId, 'Unexpected response from form system.', 'ai');
        }
    })
    .catch(error => {
        console.error('Error starting form:', error);
        addChatMessage(chatbotId, 'Sorry, I encountered an error starting the form. Please try again.', 'ai');
    });
}

/**
 * Render progress bar for form steps
 */
function renderProgressBar(progress) {
    const percentage = progress.percentage || 0;
    const currentStep = progress.current_step || 0;
    const totalSteps = progress.total_steps || 0;
    
    return `
        <div class="form-progress mt-2">
            <div class="progress">
                <div class="progress-bar" role="progressbar" 
                     style="width: ${percentage}%" 
                     aria-valuenow="${percentage}" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <small class="text-muted">Step ${currentStep} of ${totalSteps}</small>
        </div>
    `;
}

/**
 * Focus form input field
 */
function focusFormInput(chatbotId, fieldKey) {
    const input = document.getElementById(`form-input-${fieldKey}`);
    if (input && !input.disabled) {
        input.focus();
    }
}

/**
 * Save form state to localStorage
 */
function saveFormState(chatbotId) {
    localStorage.setItem(`chatbot_form_state_${chatbotId}`, JSON.stringify(window.chatFormState));
}

/**
 * Helper function to get current chat ID
 */
function getCurrentChatId(chatbotId) {
    // This should be implemented based on your existing chat session management
    const config = window.chatbotConfigs && window.chatbotConfigs[chatbotId];
    return config?.existingChatId || null;
}

/**
 * Helper function to get current chatbot ID
 */
function getCurrentChatbotId() {
    // This should return the current active chatbot ID
    // Implementation depends on your existing chatbot setup
    return Object.keys(window.chatbotConfigs || {})[0];
}

/**
 * Enhanced addChatMessage function that handles forms
 */
function addChatMessageWithForms(chatbotId, message, sender) {
    // Call the original addChatMessage function
    if (typeof addChatMessage === 'function') {
        addChatMessage(chatbotId, message, sender);
    }
    
    // If this is an AI response, check for form handling
    if (sender === 'ai' && typeof message === 'object') {
        handleFormResponse(chatbotId, message);
    }
}

/**
 * Enhanced sendToAI function that handles form processing
 */
function sendToAIWithForms(chatbotId, message) {
    // Check if we're in an active form
    if (window.chatFormState.isFormActive) {
        // Send with session ID for form processing
        const config = window.chatbotConfigs[chatbotId];
        const requestData = {
            message: message,
            mode: 'rag',
            session_id: config?.sessionId,
            chat_id: config?.existingChatId
        };
        
        fetch('/index.php/dashboard/katalysis_pro_ai/chat_bot_settings/ask_ai/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            // Handle form responses
            if (!handleFormResponse(chatbotId, data)) {
                // Not a form response, handle normally
                handleNormalAIResponse(chatbotId, data);
            }
        })
        .catch(error => {
            console.error('AI request failed:', error);
            addChatMessage(chatbotId, 'Sorry, I encountered an error. Please try again.', 'ai');
        });
    } else {
        // Not in a form, use normal AI processing
        if (typeof sendToAI === 'function') {
            sendToAI(chatbotId, message);
        }
    }
}

/**
 * Handle normal AI responses (non-form)
 */
function handleNormalAIResponse(chatbotId, data) {
    if (data.error) {
        addChatMessage(chatbotId, `Error: ${data.error}`, 'ai');
    } else {
        addChatMessage(chatbotId, data.content, 'ai');
        
        // Handle action buttons
        if (data.actions && data.actions.length > 0) {
            setTimeout(() => {
                displayActionButtons(data.actions);
            }, 500);
        }
    }
}

// CSS for form styling
const formStyles = `
<style>
.chat-form-step {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    margin: 10px 0;
    border-left: 4px solid var(--chatbot-primary, #7749F8);
}

.form-step-question {
    font-weight: 500;
    margin-bottom: 10px;
    color: #333;
}

.chat-form-input {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.chat-form-input:focus {
    border-color: var(--chatbot-primary, #7749F8);
    box-shadow: 0 0 0 3px rgba(119, 73, 248, 0.1);
}

.chat-form-input.submitted {
    background-color: #f8f9fa;
    border-color: #28a745;
}

.chat-form-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0;
}

.chat-form-option {
    border: 2px solid var(--chatbot-primary, #7749F8);
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.chat-form-option:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(119, 73, 248, 0.2);
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
    background-color: var(--chatbot-primary, #7749F8);
    border-radius: 3px;
}

.form-step-actions {
    margin-top: 10px;
    text-align: right;
}

.form-field-error {
    border-left: 4px solid #dc3545;
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Hide regular chat input when form is active */
.chatbot-interface.form-active .chatbot-input {
    opacity: 0.5;
    pointer-events: none;
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', formStyles);

// Initialize forms when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize forms for all chatbots
        if (window.chatbotConfigs) {
            Object.keys(window.chatbotConfigs).forEach(chatbotId => {
                initializeChatForms(chatbotId);
            });
        }
    });
} else {
    // DOM already loaded
    if (window.chatbotConfigs) {
        Object.keys(window.chatbotConfigs).forEach(chatbotId => {
            initializeChatForms(chatbotId);
        });
    }
}