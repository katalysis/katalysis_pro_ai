/**
 * AI Search Action System
 * Provides action button functionality for AI search responses
 * Based on chat-forms.js but adapted for search interface
 */

(function() {
    'use strict';

    // Store for managing action state
    let actionState = {
        currentActionId: null,
        currentStepIndex: 0,
        formData: {},
        completedSteps: []
    };

    /**
     * Initialize action buttons in search response
     * Called when search results are displayed
     */
    function initializeSearchActions(actions, aiResponse) {
        console.log('SearchActions.initializeSearchActions called with:', {
            actions: actions,
            aiResponse: aiResponse,
            actionsType: typeof actions,
            actionsIsArray: Array.isArray(actions),
            actionsLength: actions ? actions.length : 'N/A'
        });
        
        if (!actions || actions.length === 0) {
            console.log('SearchActions - No actions available for search results');
            return;
        }

        console.log('SearchActions - Initializing search actions:', actions);
        console.log('SearchActions - AI Response for action parsing:', aiResponse);
        
        // Parse AI-selected actions from the response
        const aiSelectedActionIds = parseAiSelectedActions(aiResponse);
        
        // Filter actions based on AI selection
        let relevantActions;
        if (aiSelectedActionIds.length > 0) {
            console.log('AI selected specific actions:', aiSelectedActionIds);
            relevantActions = actions.filter(action => aiSelectedActionIds.includes(parseInt(action.id)));
        } else {
            console.log('No AI action selection found, using context filtering');
            relevantActions = filterActionsForContext(actions);
        }
        
        if (relevantActions.length > 0) {
            console.log('Displaying', relevantActions.length, 'relevant actions');
            displayActionButtons(relevantActions);
        } else {
            console.log('No relevant actions found for current context');
        }
    }

    /**
     * Parse AI-selected action IDs from the response text
     * Looks for patterns like [ACTIONS:1,4] or [ACTIONS:1] etc.
     */
    function parseAiSelectedActions(aiResponse) {
        if (!aiResponse || typeof aiResponse !== 'string') {
            return [];
        }

        // Look for [ACTIONS:1,4] or similar patterns
        const actionPattern = /\[ACTIONS?:([0-9,\s]+)\]/gi;
        const matches = actionPattern.exec(aiResponse);
        
        if (matches && matches[1]) {
            const actionIdsString = matches[1];
            console.log('Found AI action selection:', actionIdsString);
            
            // Parse comma-separated action IDs
            const actionIds = actionIdsString
                .split(',')
                .map(id => parseInt(id.trim()))
                .filter(id => !isNaN(id));
                
            console.log('Parsed action IDs:', actionIds);
            return actionIds;
        }
        
        return [];
    }

    /**
     * Filter actions based on search context
     * Filters actions based on showImmediately flag and other criteria
     */
    function filterActionsForContext(actions) {
        console.log('Available actions for filtering:', actions);
        
        return actions.filter(function(action) {
            // Basic filtering criteria:
            
            // 1. Action must have proper configuration
            if (!action.name || !action.trigger_instruction) {
                console.log('Filtering out action with incomplete config:', action.name);
                return false;
            }
            
            // 2. If showImmediately is false, don't show in search results
            if (action.showImmediately === false) {
                console.log('Filtering out action not marked for immediate display:', action.name);
                return false;
            }
            
            // 3. For now, show actions that are marked to show immediately or have no explicit setting
            if (action.showImmediately === true || action.showImmediately === undefined) {
                console.log('Including action:', action.name);
                return true;
            }
            
            return false;
        });
    }

    /**
     * Display action buttons below AI response
     */
    function displayActionButtons(actions) {
        console.log('SearchActions - displayActionButtons called with:', actions);
        console.log('SearchActions - jQuery available:', typeof $ !== 'undefined');
        console.log('SearchActions - Document ready state:', document.readyState);
        
        // Enhanced Search: Try to find the dedicated actions grid first
        let actionsContainer = $('.katalysis-ai-enhanced-search .actions-grid');
        let isEnhancedSearch = actionsContainer.length > 0;
        
        console.log('SearchActions - Enhanced search container query result:', {
            selector: '.katalysis-ai-enhanced-search .actions-grid',
            found: actionsContainer.length,
            isEnhancedSearch: isEnhancedSearch
        });
        
        // Fallback to original AI search structure
        if (!isEnhancedSearch) {
            actionsContainer = $('.katalysis-ai-search .ai-response-content');
        }
        
        console.log('SearchActions - Container found:', {
            isEnhancedSearch: isEnhancedSearch,
            containerLength: actionsContainer.length,
            containerSelector: isEnhancedSearch ? '.actions-grid' : '.ai-response-content'
        });
        
        if (actionsContainer.length === 0) {
            console.warn('SearchActions - No suitable container found for action buttons');
            console.log('SearchActions - Available containers on page:');
            console.log('  .katalysis-ai-enhanced-search:', $('.katalysis-ai-enhanced-search').length);
            console.log('  .actions-section:', $('.actions-section').length);
            console.log('  .actions-grid:', $('.actions-grid').length);
            return;
        }

        // Remove any existing action buttons and forms
        if (isEnhancedSearch) {
            // Enhanced search: clear the actions grid
            actionsContainer.empty();
        } else {
            // Original search: remove from ai response content
            actionsContainer.find('.search-action-buttons').remove();
            actionsContainer.find('.search-action-form').remove();
        }
        
        // Reset action state when displaying new buttons
        actionState = {
            currentActionId: null,
            currentStepIndex: 0,
            formData: {},
            completedSteps: []
        };

        let actionButtonsHtml = '';
        
        if (isEnhancedSearch) {
            // Enhanced Search: Use simpler button structure for actions grid
            actions.forEach(function(action) {
                actionButtonsHtml += `
                    <button type="button" 
                            class="btn search-action-btn mb-2 me-2" 
                            data-action-id="${action.id}"
                            data-action-name="${escapeHtml(action.name)}"
                            data-action-trigger="${escapeHtml(action.trigger_instruction)}"
                            data-action-response="${escapeHtml(action.response_instruction)}">
                        <i class="${action.icon} me-2"></i>
                        ${escapeHtml(action.name)}
                    </button>
                `;
            });
        } else {
            // Original Search: Use card structure for AI response content
            actionButtonsHtml = '<div class="card bg-gradient-primary search-action-buttons mt-3">';
            actionButtonsHtml += '<div class="card-body">';
            actionButtonsHtml += '<h3>Next Steps</h3>';
            actionButtonsHtml += '<p><strong>Choose an option here, contact one of our specialists or find out more from the relevant pages listed below:</strong></p>';
            actionButtonsHtml += '<div class="d-flex flex-wrap gap-2">';
            
            actions.forEach(function(action) {
                actionButtonsHtml += `
                    <button type="button" 
                            class="btn btn-primary search-action-btn" 
                            data-action-id="${action.id}"
                            data-action-name="${escapeHtml(action.name)}"
                            data-action-trigger="${escapeHtml(action.trigger_instruction)}"
                            data-action-response="${escapeHtml(action.response_instruction)}">
                        <i class="${action.icon}"></i>
                        ${escapeHtml(action.name)}
                    </button>
                `;
            });
            
            actionButtonsHtml += '</div>';
            actionButtonsHtml += '</div>';
            actionButtonsHtml += '</div>';
        }

        // Append action buttons to appropriate container
        console.log('SearchActions - Appending HTML to container:', actionButtonsHtml.substring(0, 200) + '...');
        actionsContainer.append(actionButtonsHtml);

        // Bind click events
        bindActionButtonEvents();

    }

    /**
     * Bind click events to action buttons
     */
    function bindActionButtonEvents() {
        // Remove any existing event handlers first
        $(document).off('click.searchActions', '.search-action-btn');
        
        // Bind click events to action buttons
        $(document).on('click.searchActions', '.search-action-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const actionId = $btn.data('action-id');
            const actionName = $btn.data('action-name');
            const triggerInstruction = $btn.data('action-trigger');
            const responseInstruction = $btn.data('action-response');
            
            startActionFromButton(actionId, actionName, triggerInstruction, responseInstruction);
        });
    }    /**
     * Start an action when button is clicked
     */
    function startActionFromButton(actionId, actionName, triggerInstruction, responseInstruction) {
        // Remove any existing forms first
        $('.search-action-form').remove();
        
        // Set action state
        actionState.currentActionId = actionId;
        actionState.currentStepIndex = 0;
        actionState.formData = {};
        actionState.completedSteps = [];

        // Get action details from server and start form flow
        getActionDetails(actionId).then(function(actionDetails) {
            
            if (actionDetails && actionDetails.formSteps) {
                try {
                    let formSteps = null;
                    
                    // Handle both string and object formSteps
                    if (typeof actionDetails.formSteps === 'string') {
                        if (actionDetails.formSteps.trim() === '') {
                            executeSimpleAction(actionDetails);
                            return;
                        }
                        formSteps = JSON.parse(actionDetails.formSteps);
                    } else if (typeof actionDetails.formSteps === 'object') {
                        formSteps = actionDetails.formSteps;
                    }
                    
                    if (formSteps && Array.isArray(formSteps) && formSteps.length > 0) {
                        // Check if this is database format (fields directly) or test format (nested under steps)
                        let hasValidFields = false;
                        let isDatabaseFormat = false;
                        
                        // Check first item to determine format
                        const firstItem = formSteps[0];
                        if (firstItem.stepKey || firstItem.fieldType || firstItem.question) {
                            isDatabaseFormat = true;
                            // Each item in formSteps IS a field - validate they have required properties
                            formSteps.forEach((field) => {
                                if (field.stepKey && field.fieldType && field.question) {
                                    hasValidFields = true;
                                }
                            });
                        } else {
                            // Old test format with nested fields
                            formSteps.forEach((step) => {
                                if (step.fields && Array.isArray(step.fields) && step.fields.length > 0) {
                                    hasValidFields = true;
                                }
                            });
                        }
                        
                        if (hasValidFields) {
                            displayActionForm(actionDetails, formSteps);
                        } else {
                            // Create test form steps based on action type
                            let testFormSteps = [];
                            
                            if (actionDetails.name && actionDetails.name.toLowerCase().includes('meeting')) {
                                testFormSteps = [
                                    {
                                        title: "Personal Information",
                                        description: "Please provide your contact details",
                                        fields: [
                                            {
                                                name: "full_name",
                                                type: "text",
                                                label: "Full Name",
                                                placeholder: "Enter your full name",
                                                required: true
                                            },
                                            {
                                                name: "email",
                                                type: "email", 
                                                label: "Email Address",
                                                placeholder: "your.email@example.com",
                                                required: true
                                            },
                                            {
                                                name: "phone",
                                                type: "tel",
                                                label: "Phone Number",
                                                placeholder: "Your phone number",
                                                required: true
                                            }
                                        ]
                                    },
                                    {
                                        title: "Meeting Details",
                                        description: "Tell us about your legal issue",
                                        fields: [
                                            {
                                                name: "legal_issue",
                                                type: "select",
                                                label: "Type of Legal Issue",
                                                options: [
                                                    { value: "", text: "Please select..." },
                                                    { value: "wills_probate", text: "Wills & Probate" },
                                                    { value: "personal_injury", text: "Personal Injury" },
                                                    { value: "family_law", text: "Family Law" },
                                                    { value: "conveyancing", text: "Conveyancing" },
                                                    { value: "other", text: "Other" }
                                                ],
                                                required: true
                                            },
                                            {
                                                name: "description",
                                                type: "textarea",
                                                label: "Brief Description",
                                                placeholder: "Please provide a brief description of your legal issue...",
                                                required: true
                                            }
                                        ]
                                    },
                                    {
                                        title: "Preferred Meeting Time",
                                        description: "When would you like to meet?",
                                        fields: [
                                            {
                                                name: "preferred_date",
                                                type: "date",
                                                label: "Preferred Date",
                                                required: true
                                            },
                                            {
                                                name: "preferred_time",
                                                type: "select",
                                                label: "Preferred Time",
                                                options: [
                                                    { value: "", text: "Please select..." },
                                                    { value: "09:00", text: "9:00 AM" },
                                                    { value: "10:00", text: "10:00 AM" },
                                                    { value: "11:00", text: "11:00 AM" },
                                                    { value: "14:00", text: "2:00 PM" },
                                                    { value: "15:00", text: "3:00 PM" },
                                                    { value: "16:00", text: "4:00 PM" }
                                                ],
                                                required: true
                                            },
                                            {
                                                name: "urgent",
                                                type: "checkbox",
                                                label: "This is urgent",
                                                required: false
                                            }
                                        ]
                                    }
                                ];
                            } else if (actionDetails.name && actionDetails.name.toLowerCase().includes('contact')) {
                                console.log('Creating contact form steps');
                                testFormSteps = [
                                    {
                                        title: "Contact Information", 
                                        description: "Please provide your details",
                                        fields: [
                                            {
                                                name: "full_name",
                                                type: "text",
                                                label: "Full Name",
                                                placeholder: "Enter your full name",
                                                required: true
                                            },
                                            {
                                                name: "email",
                                                type: "email",
                                                label: "Email Address", 
                                                placeholder: "your.email@example.com",
                                                required: true
                                            },
                                            {
                                                name: "phone",
                                                type: "tel",
                                                label: "Phone Number",
                                                placeholder: "Your phone number",
                                                required: false
                                            }
                                        ]
                                    },
                                    {
                                        title: "Your Message",
                                        description: "Tell us how we can help",
                                        fields: [
                                            {
                                                name: "subject",
                                                type: "text",
                                                label: "Subject",
                                                placeholder: "Brief subject line",
                                                required: true
                                            },
                                            {
                                                name: "message",
                                                type: "textarea",
                                                label: "Message",
                                                placeholder: "Please tell us about your legal issue and how we can help...",
                                                required: true
                                            }
                                        ]
                                    }
                                ];
                            } else {
                                console.log('Creating generic form steps');
                                testFormSteps = [
                                    {
                                        title: "Contact Information",
                                        description: "Please provide your contact details",
                                        fields: [
                                            {
                                                name: "name",
                                                type: "text",
                                                label: "Full Name",
                                                placeholder: "Enter your full name",
                                                required: true
                                            },
                                            {
                                                name: "email",
                                                type: "email",
                                                label: "Email Address",
                                                placeholder: "your.email@example.com", 
                                                required: true
                                            }
                                        ]
                                    }
                                ];
                            }
                            
                            console.log('Created test form steps:', testFormSteps);
                            // Don't override actionType for meeting forms - let them be multi-step
                            displayActionForm(actionDetails, testFormSteps);
                        }
                    } else {
                        console.log('Invalid or empty formSteps array');
                        console.log('Creating fallback form steps...');
                        
                        // Always create test form steps when database steps are invalid
                        const testFormSteps = [
                            {
                                title: "Contact Information",
                                description: "Please provide your contact details",
                                fields: [
                                    {
                                        name: "name",
                                        type: "text",
                                        label: "Full Name",
                                        placeholder: "Enter your full name",
                                        required: true
                                    },
                                    {
                                        name: "email",
                                        type: "email",
                                        label: "Email Address",
                                        placeholder: "your.email@example.com",
                                        required: true
                                    }
                                ]
                            }
                        ];
                        
                        // Override actionType for single-step fallback forms
                        actionDetails.actionType = 'simple_form';
                        
                        console.log('Using fallback form steps:', testFormSteps);
                        displayActionForm(actionDetails, testFormSteps);
                    }
                } catch (e) {
                    console.error('Error parsing form steps:', e, 'Raw formSteps:', actionDetails.formSteps);
                    executeSimpleAction(actionDetails);
                }
            } else {
                console.log('No form steps found, executing simple action');
                // Fallback to simple action
                executeSimpleAction({
                    id: actionId,
                    name: actionName,
                    triggerInstruction: triggerInstruction,
                    responseInstruction: responseInstruction
                });
            }
        }).catch(function(error) {
            console.error('Error getting action details:', error);
            showActionError('Unable to start action. Please try again.');
        });
    }

    /**
     * Get action details from server
     */
    function getActionDetails(actionId) {
        return fetch('/dashboard/katalysis_pro_ai/search_settings/get_action_details/' + actionId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.action;
            } else {
                throw new Error(data.error || 'Failed to get action details');
            }
        })
        .catch(error => {
            console.error('Error fetching action details:', error);
            return null;
        });
    }

    /**
     * Execute a simple action (no form steps)
     */
    function executeSimpleAction(actionDetails) {
        console.log('Executing simple action:', actionDetails);
        
        showActionMessage(`Starting ${actionDetails.name}...`, 'info');
        
        // For simple actions, we can show different behaviors based on action type
        if (actionDetails.actionType === 'redirect' && actionDetails.responseInstruction) {
            // If it's a redirect action, use the response instruction as URL
            setTimeout(function() {
                showActionMessage(`Redirecting to ${actionDetails.name}...`, 'info');
                window.open(actionDetails.responseInstruction, '_blank');
            }, 1000);
        } else if (actionDetails.actionType === 'contact' || actionDetails.name.toLowerCase().includes('contact')) {
            // For contact actions, show contact information or form
            setTimeout(function() {
                showActionMessage(`${actionDetails.name} - Please call us at 01978 291000 or email info@psr-solicitors.co.uk`, 'success');
            }, 1000);
        } else if (actionDetails.actionType === 'download' || actionDetails.name.toLowerCase().includes('download')) {
            // For download actions
            setTimeout(function() {
                showActionMessage(`${actionDetails.name} completed. Check your downloads folder.`, 'success');
            }, 1000);
        } else {
            // Generic simple action
            setTimeout(function() {
                showActionMessage(`${actionDetails.name} completed successfully!`, 'success');
            }, 1000);
        }
    }

    /**
     * Display action form for multi-step actions
     */
    function displayActionForm(actionDetails, formSteps) {
        // Determine if this is a simple form (all fields at once, no steps)
        // Only simple_form type shows all fields at once
        const isSimpleForm = actionDetails.actionType === 'simple_form';
        
        // Create form modal or inline form area
        const formHtml = createActionFormHtml(actionDetails, formSteps, isSimpleForm);
        
        // Remove any existing action form
        $('.search-action-form').remove();
        
        // Add form after action buttons - handle both Enhanced and Original search structures
        let insertTarget = $('.search-action-buttons'); // Original search
        
        if (insertTarget.length === 0) {
            // Enhanced search - use actions-grid
            insertTarget = $('.actions-grid');
        }
        
        if (insertTarget.length > 0) {
            insertTarget.after(formHtml);
        } else {
            console.error('No suitable container found for form insertion');
        }
        
        // Initialize form step
        console.log('Starting form at step 0 with actionState:', actionState);
        displayFormStep(0, formSteps, isSimpleForm);
    }

    /**
     * Create action form HTML structure
     */
    function createActionFormHtml(actionDetails, formSteps, isSimpleForm = false) {
        console.log('Creating form HTML - Simple form:', isSimpleForm);
        
        const progressHtml = isSimpleForm ? '' : `
            <div class="action-form-progress mb-3">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <small class="text-muted">Step <span class="current-step">1</span> of <span class="total-steps">${formSteps.length}</span></small>
            </div>
        `;
        
        const buttonClass = isSimpleForm ? 'justify-content-end' : 'justify-content-between';
        const prevButtonHtml = isSimpleForm ? '' : `
            <button type="button" class="btn btn-outline-secondary prev-step" disabled>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
        `;
        
        return `
            <div class="search-action-form mt-4 p-4 border rounded bg-light" data-simple-form="${isSimpleForm}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="${actionDetails.icon || 'fas fa-play'}"></i>
                        ${escapeHtml(actionDetails.name)}
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary close-action-form">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                ${progressHtml}
                
                <div class="action-form-content">
                    <!-- Form step content will be inserted here -->
                </div>
                
                <div class="action-form-buttons mt-3 d-flex ${buttonClass}">
                    ${prevButtonHtml}
                    <button type="button" class="btn btn-primary next-step">
                        ${isSimpleForm ? 'Submit <i class="fas fa-check"></i>' : 'Next <i class="fas fa-chevron-right"></i>'}
                    </button>
                </div>
                
                <div class="action-form-messages mt-2">
                    <!-- Messages will be displayed here -->
                </div>
            </div>
        `;
    }

    /**
     * Display a specific form step
     */
    function displayFormStep(stepIndex, formSteps, isSimpleForm = false) {
        if (!isSimpleForm && (stepIndex < 0 || stepIndex >= formSteps.length)) {
            console.error('Invalid step index:', stepIndex, 'Total steps:', formSteps.length);
            return;
        }

        actionState.currentStepIndex = stepIndex;

        if (isSimpleForm) {
            // For simple forms, display ALL fields at once (without step headers)
            let allFieldsHtml = `<div class="form-step simple-form-all-fields" data-step-index="all">`;
            
            // Database format: each item in formSteps array IS a field
            formSteps.forEach(function(field, fieldIdx) {
                if (field.stepKey || field.name) {
                    allFieldsHtml += createFormField(field);
                }
            });
            
            allFieldsHtml += '</div>';
            $('.search-action-form .action-form-content').html(allFieldsHtml);
            
        } else {
            // For database format, we need to group fields by some criteria or treat each as a step
            // For now, let's assume one field per step for dynamic_form and form types
            if (stepIndex >= formSteps.length) {
                console.error('Step index out of range:', stepIndex, 'Total fields:', formSteps.length);
                return;
            }
            
            const field = formSteps[stepIndex];

            // Update progress
            const progress = ((stepIndex + 1) / formSteps.length) * 100;
            $('.search-action-form .progress-bar').css('width', progress + '%');
            $('.search-action-form .current-step').text(stepIndex + 1);

            // Create step content - one field per step
            let stepHtml = `<div class="form-step" data-step-index="${stepIndex}">`;
            
            if (field.stepKey || field.name) {
                stepHtml += createFormField(field);
            } else {
                console.warn('No valid field found for step', stepIndex, 'Field data:', field);
                stepHtml += '<p class="text-muted">No field configured for this step.</p>';
            }

            stepHtml += '</div>';
            $('.search-action-form .action-form-content').html(stepHtml);
        }
        
        console.log('Form content updated');

        // Update navigation buttons (only for multi-step forms)
        if (!isSimpleForm) {
            $('.search-action-form .prev-step').prop('disabled', stepIndex === 0);
            
            const isLastStep = stepIndex === formSteps.length - 1;
            const nextButton = $('.search-action-form .next-step');
            nextButton.html(isLastStep ? 'Submit <i class="fas fa-check"></i>' : 'Next <i class="fas fa-chevron-right"></i>');
        }

        // Bind form navigation events
        bindFormNavigationEvents(formSteps, isSimpleForm);
    }

    /**
     * Create form field HTML
     */
    function createFormField(field) {
        // Handle both test form structure and database structure
        const fieldName = field.stepKey || field.name;
        const fieldType = field.fieldType || field.type || 'text';
        const fieldLabel = field.question || field.label;
        const fieldPlaceholder = field.placeholder || '';
        const isRequired = field.validation?.required || field.required || false;
        const fieldOptions = field.options || [];
        
        if (!fieldName || !fieldLabel) {
            console.warn('Invalid field data - missing name or label:', field);
            return '<div class="mb-3"><p class="text-danger">Invalid field configuration</p></div>';
        }

        const fieldId = 'field_' + fieldName;
        const currentValue = actionState.formData[fieldName] || '';



        let fieldHtml = `<div class="mb-3">`;
        
        // Add label
        fieldHtml += `<label for="${fieldId}" class="form-label">${escapeHtml(fieldLabel)}</label>`;

        switch (fieldType) {
            case 'text':
            case 'email':
            case 'tel':
                fieldHtml += `<input type="${fieldType}" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(currentValue)}" ${isRequired ? 'required' : ''}`;
                if (fieldPlaceholder) fieldHtml += ` placeholder="${escapeHtml(fieldPlaceholder)}"`;
                fieldHtml += '>';
                break;

            case 'textarea':
                fieldHtml += `<textarea class="form-control" id="${fieldId}" name="${fieldName}" ${isRequired ? 'required' : ''}`;
                if (fieldPlaceholder) fieldHtml += ` placeholder="${escapeHtml(fieldPlaceholder)}"`;
                fieldHtml += ` rows="3">${escapeHtml(currentValue)}</textarea>`;
                break;

            case 'select':
                fieldHtml += `<select class="form-control form-select" id="${fieldId}" name="${fieldName}" ${isRequired ? 'required' : ''}>`;
                
                // Add default option for required fields
                if (isRequired) {
                    fieldHtml += `<option value="">Please select...</option>`;
                }
                
                // Handle both database format (array of strings) and test format (array of objects)
                if (fieldOptions && Array.isArray(fieldOptions)) {
                    fieldOptions.forEach(function(option) {
                        let optionValue, optionText;
                        
                        if (typeof option === 'string') {
                            // Database format: ["Morning", "Afternoon", "Evening"]
                            optionValue = option;
                            optionText = option;
                        } else if (option && (option.value !== undefined || option.label !== undefined)) {
                            // Test format: [{value: "morning", text: "Morning"}] or [{value: "morning", label: "Morning"}]
                            optionValue = option.value || option.label || '';
                            optionText = option.text || option.label || option.value || '';
                        } else {
                            console.warn('Invalid option format:', option);
                            return;
                        }
                        
                        const selected = currentValue === optionValue ? 'selected' : '';
                        fieldHtml += `<option value="${escapeHtml(optionValue)}" ${selected}>${escapeHtml(optionText)}</option>`;
                    });
                } else {
                    console.warn('No valid options found for select field:', fieldName);
                }
                fieldHtml += '</select>';
                break;

            case 'checkbox':
                const checked = currentValue === true || currentValue === 'true' || currentValue === '1' ? 'checked' : '';
                fieldHtml += `<div class="form-check">`;
                fieldHtml += `<input type="checkbox" class="form-check-input" id="${fieldId}" name="${fieldName}" value="1" ${checked} ${isRequired ? 'required' : ''}>`;
                fieldHtml += `<label class="form-check-label" for="${fieldId}">${escapeHtml(fieldLabel)}</label>`;
                fieldHtml += `</div>`;
                break;

            case 'radio':
                if (fieldOptions && Array.isArray(fieldOptions)) {
                    fieldOptions.forEach(function(option, index) {
                        let optionValue, optionText;
                        
                        if (typeof option === 'string') {
                            optionValue = option;
                            optionText = option;
                        } else if (option && (option.value !== undefined || option.label !== undefined)) {
                            optionValue = option.value || option.label || '';
                            optionText = option.text || option.label || option.value || '';
                        } else {
                            console.warn('Invalid radio option format:', option);
                            return;
                        }
                        
                        const radioId = fieldId + '_' + index;
                        const checked = currentValue === optionValue ? 'checked' : '';
                        fieldHtml += `<div class="form-check">`;
                        fieldHtml += `<input type="radio" class="form-check-input" id="${radioId}" name="${fieldName}" value="${escapeHtml(optionValue)}" ${checked} ${isRequired ? 'required' : ''}>`;
                        fieldHtml += `<label class="form-check-label" for="${radioId}">${escapeHtml(optionText)}</label>`;
                        fieldHtml += `</div>`;
                    });
                } else {
                    console.warn('No valid options found for radio field:', fieldName);
                    fieldHtml += '<p class="text-muted">No options configured for this radio field.</p>';
                }
                break;

            default:
                console.warn('Unknown field type:', fieldType, 'for field:', fieldName);
                fieldHtml += `<input type="text" class="form-control" id="${fieldId}" name="${fieldName}" value="${escapeHtml(currentValue)}">`;
        }

        if (field.help) {
            fieldHtml += `<small class="form-text text-muted">${escapeHtml(field.help)}</small>`;
        }

        fieldHtml += '</div>';
        

        return fieldHtml;
    }

    /**
     * Bind form navigation events
     */
    function bindFormNavigationEvents(formSteps, isSimpleForm = false) {
        // Remove existing event handlers to prevent duplicates
        
        // Previous button (only for multi-step forms)
        if (!isSimpleForm) {
            $('.search-action-form .prev-step').off('click').on('click', function() {
                if (actionState.currentStepIndex > 0) {
                    displayFormStep(actionState.currentStepIndex - 1, formSteps, isSimpleForm);
                }
            });
        }

        // Next/Submit button
        $('.search-action-form .next-step').off('click').on('click', function() {
            // Validate current step
            if (validateCurrentStep(isSimpleForm)) {
                // Save current step data
                saveCurrentStepData(isSimpleForm);

                if (isSimpleForm || actionState.currentStepIndex >= formSteps.length - 1) {
                    // Submit form (simple forms or last step of multi-step)
                    submitActionForm();
                } else {
                    // Go to next step (multi-step forms only)
                    displayFormStep(actionState.currentStepIndex + 1, formSteps, isSimpleForm);
                }
            }
        });

        $('.search-action-form .close-action-form').off('click').on('click', function() {
            $('.search-action-form').remove();
            actionState = {
                currentActionId: null,
                currentStepIndex: 0,
                formData: {},
                completedSteps: []
            };
        });
    }

    /**
     * Validate current form step
     */
    function validateCurrentStep(isSimpleForm = false) {
        let currentStep;
        
        if (isSimpleForm) {
            // For simple forms, validate all fields in the form
            currentStep = $('.search-action-form .simple-form-all-fields');
            console.log('Validating simple form with all fields');
        } else {
            // For step forms, validate only current step
            currentStep = $('.search-action-form .form-step[data-step-index="' + actionState.currentStepIndex + '"]');
            console.log('Validating step form, step:', actionState.currentStepIndex);
        }
        
        const requiredFields = currentStep.find('[required]');
        let isValid = true;

        console.log('Found', requiredFields.length, 'required fields to validate');

        // Clear previous validation messages
        currentStep.find('.is-invalid').removeClass('is-invalid');
        currentStep.find('.invalid-feedback').remove();

        requiredFields.each(function() {
            const field = $(this);
            const value = field.val().trim();

            if (!value) {
                field.addClass('is-invalid');
                field.after('<div class="invalid-feedback">This field is required.</div>');
                isValid = false;
            }

            // Email validation
            if (field.attr('type') === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Please enter a valid email address.</div>');
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    /**
     * Save current step data
     */
    function saveCurrentStepData(isSimpleForm = false) {
        let currentStep;
        
        if (isSimpleForm) {
            // For simple forms, save all fields from the entire form
            currentStep = $('.search-action-form .simple-form-all-fields');
            console.log('Saving data from simple form with all fields');
        } else {
            // For step forms, save only current step
            currentStep = $('.search-action-form .form-step[data-step-index="' + actionState.currentStepIndex + '"]');
            console.log('Saving data from step form, step:', actionState.currentStepIndex);
        }
        
        const fields = currentStep.find('input, textarea, select');
        console.log('Found', fields.length, 'fields to save');

        fields.each(function() {
            const field = $(this);
            const name = field.attr('name');
            
            if (name) {
                if (field.attr('type') === 'checkbox') {
                    actionState.formData[name] = field.is(':checked');
                } else {
                    actionState.formData[name] = field.val();
                }
                console.log('Saved field:', name, '=', actionState.formData[name]);
            }
        });

        // Mark step as completed
        if (!isSimpleForm && !actionState.completedSteps.includes(actionState.currentStepIndex)) {
            actionState.completedSteps.push(actionState.currentStepIndex);
        }
        
        console.log('Current form data:', actionState.formData);
    }

    /**
     * Submit the completed action form
     */
    function submitActionForm() {
        showActionMessage('Submitting action...', 'info');

        const submitData = {
            action_id: actionState.currentActionId,
            form_data: actionState.formData,
            completed_steps: actionState.completedSteps
        };

        // Here you would send the data to your action processing endpoint
        // For now, we'll simulate a successful submission
        setTimeout(function() {
            showActionMessage('Action completed successfully!', 'success');
            
            // Close form after delay
            setTimeout(function() {
                $('.search-action-form').fadeOut(function() {
                    $(this).remove();
                });
                
                // Reset action state
                actionState = {
                    currentActionId: null,
                    currentStepIndex: 0,
                    formData: {},
                    completedSteps: []
                };
            }, 2000);
        }, 1500);
    }

    /**
     * Show action message
     */
    function showActionMessage(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const messageHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Add to form messages area if form exists, otherwise add to search results
        const messageContainer = $('.search-action-form .action-form-messages').length > 0 
            ? $('.search-action-form .action-form-messages')
            : $('.katalysis-ai-enhanced-search .ai-response-content, .katalysis-ai-search .ai-response-content');

        messageContainer.append(messageHtml);

        // Auto-remove success/info messages after delay
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                messageContainer.find('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }

    /**
     * Show action error message
     */
    function showActionError(message) {
        showActionMessage(message, 'error');
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Export functions to global scope for use by search block
    window.SearchActions = {
        initializeSearchActions: initializeSearchActions,
        displayActionButtons: displayActionButtons,
        startActionFromButton: startActionFromButton,
        bindActionButtonEvents: bindActionButtonEvents
    };

})();
