<?php
/** @noinspection DuplicatedCode */

defined('C5_EXECUTE') or die('Access denied');

use KatalysisProAi\Entity\Action;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;


/** @var $entry Action */
/** @var $form Form */
/** @var $token Token */

$app = Application::getFacadeApplication();
/** @var EntityManagerInterface $entityManager */
$entityManager = $app->make(EntityManagerInterface::class);

// Ensure we have default values
$name = isset($name) ? $name : '';
$icon = isset($icon) ? $icon : '';
$triggerInstruction = isset($triggerInstruction) ? $triggerInstruction : '';
$responseInstruction = isset($responseInstruction) ? $responseInstruction : '';
$actionType = isset($actionType) ? $actionType : 'basic';
$formSteps = isset($formSteps) ? $formSteps : '';
$formConfig = isset($formConfig) ? $formConfig : '';
$createdBy = isset($createdBy) ? $createdBy : '';
$createdDate = isset($createdDate) ? $createdDate : '';
$createdByName = isset($createdByName) ? $createdByName : '';
?>



<form action="#" method="post">
    <?php echo $token->output("save_katalysis_actions_entity"); ?>

    <div class="row justify-content-between mt-4">
        <div class="col-7">
            <div class="row">
                <fieldset>
                    <legend><?php echo t('Action Details'); ?></legend>
                    <div class="form-group">
                        <?php echo $form->label(
                            "name",
                            t("Name"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>
                        <span class="text-muted small">
                            <?php echo t('Required') ?>
                        </span>
                        <?php echo $form->text(
                            "name",
                            $name,
                            [
                                "class" => "form-control",
                                "required" => "required",
                                "max-length" => "255",
                            ]
                        ); ?>
                    </div>
                    <div class="form-group">
                        <?php echo $form->label(
                            "actionType",
                            t("Action Type"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>
                        <span class="text-muted small">
                            <?php echo t('Required') ?>
                        </span>
                        <?php echo $form->select(
                            "actionType",
                            [
                                'basic' => t('Basic Button - AI follows response instruction'),
                                'simple_form' => t(text: 'Form - All Fields at Once'),
                                'form' => t('Form - Static Steps'),
                                'dynamic_form' => t('Form - AI Controlled Steps')
                            ],
                            $actionType,
                            [
                                "class" => "form-control",
                                "id" => "actionType"
                            ]
                        ); ?>

                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox(
                                "enabled",
                                1,
                                $enabled ?? false,
                                [
                                    "class" => "form-check-input"
                                ]
                            ); ?>
                            <?php echo $form->label(
                                "enabled",
                                t("Enabled"),
                                [
                                    "class" => "form-check-label"
                                ]
                            ); ?>
                        </div>
                    </div>
                </fieldset>
                <div class="col-6">
                    <div class="form-group">
                        <?php echo $form->label(
                            "triggerInstruction",
                            t("Trigger Instruction"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>
                        <span class="text-muted small">
                            <?php echo t('Required') ?>
                        </span>

                        <?php echo $form->textarea(
                            "triggerInstruction",
                            $triggerInstruction,
                            [
                                "class" => "form-control mb-3",
                                "required" => "required",
                                "rows" => "3",
                                "placeholder" => t("e.g., Show this button when the user expresses interest in booking a meeting or getting work done.")
                            ]
                        ); ?>
                        <div class="alert alert-info mb-2">
                            <?php echo t('Tell the LLM when to show this action button. Example: <em>"Show this button when the user expresses interest in booking a meeting or getting work done."</em>'); ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <?php echo $form->label(
                            "responseInstruction",
                            t("Response Instruction"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>
                        <span class="text-muted small">
                            <?php echo t('Required') ?>
                        </span>

                        <?php echo $form->textarea(
                            "responseInstruction",
                            $responseInstruction,
                            [
                                "class" => "form-control mb-3",
                                "required" => "required",
                                "rows" => "3",
                                "placeholder" => t("e.g., Ask the user for their preferred meeting time and suggest available slots.")
                            ]
                        ); ?>
                        <div class="alert alert-info mb-2">
                            <?php echo t('Tell the LLM what to do when this action button is pressed. Example: <em>"Ask the user for their preferred meeting time and suggest available slots."</em>'); ?>
                        </div>
                    </div>
                </div>
            </div>

            </fieldset>

        </div>
        <div class="col-md-3">
            <fieldset>
                <legend>Button Settings</legend>

                <script type="text/javascript">
                    $(function () {


                        Concrete.Vue.activateContext('cms', function (Vue, config) {
                            new Vue({
                                el: '#ccm-icon-selector-<?= h($bID) ?>',
                                components: config.components
                            })
                        })
                    });
                </script>

                <div class="mb-3 ccm-block-select-icon">
                    <?php echo $form->label('icon', t('Icon')) ?>
                    <div id="ccm-icon-selector-<?= h($bID) ?>">
                        <icon-selector name="icon" selected="<?= h($icon) ?>" title="<?= t('Choose Icon') ?>"
                            empty-option-label="<?= h(tc('Icon', '** None Selected')) ?>" />
                    </div>

                    <style type="text/css">
                        div.ccm-block-select-icon .input-group-addon {
                            min-width: 70px;
                        }

                        div.ccm-block-select-icon i {
                            font-size: 22px;
                        }

                    </style>

                </div>
            </fieldset>
        </div>
    </div>

    <!-- Form Builder Section (shown only for form types) -->
    <div id="form-builder-section" class="row justify-content-between mt-4" style="display: none;">
        <div class="col-7">
            <fieldset>
                <legend><?php echo t('Form Builder'); ?></legend>

                <!-- Hidden textarea for form submission - managed by visual editor -->
                <?php echo $form->textarea(
                    "formSteps",
                    $formSteps,
                    [
                        "class" => "form-control d-none",
                        "readonly" => true
                    ]
                ); ?>
                <!-- Form Builder UI -->
                <div id="form-builder-visual">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFormStep('text')">
                                <i class="fas fa-plus"></i> <?php echo t('Add Text Field'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFormStep('email')">
                                <i class="fas fa-plus"></i> <?php echo t('Add Email Field'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="addFormStep('select')">
                                <i class="fas fa-plus"></i> <?php echo t('Add Select Field'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="addFormStep('textarea')">
                                <i class="fas fa-plus"></i> <?php echo t('Add Textarea'); ?>
                            </button>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="loadSampleForm('contact')">
                                <?php echo t('Load Contact Form'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="loadSampleForm('lead_qualification')">
                                <?php echo t('Load Lead Qualification'); ?>
                            </button>
                        </div>
                    </div>

                    <div id="form-steps-container">
                        <!-- Form steps will be rendered here -->
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="col-md-3">
            <fieldset>
                <legend><?php echo t('Form Settings'); ?></legend>
                <div class="form-group">
                    <div class="form-check">
                        <?php echo $form->checkbox(
                            "showImmediately",
                            1,
                            $showImmediately ?? false,
                            [
                                "class" => "form-check-input"
                            ]
                        ); ?>
                        <?php echo $form->label(
                            "showImmediately",
                            t("Show Form Immediately"),
                            [
                                "class" => "form-check-label"
                            ]
                        ); ?>
                    </div>
                    <div class="alert alert-info mb-2 mt-2">
                        <?php echo t('When this is the highest priority action display the form immediately instead of showing the Further Information list.'); ?>
                    </div>
                </div>
            </fieldset>
        </div>
    </div>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">

        <?php if ($controller->getAction() != 'add' && !empty($createdByName)) { ?>
            <fieldset class="pb-2 text-end" style="padding-right:25px;">
                <small class="form-text text-muted">
                    Created by <a
                        href="/dashboard/users/search/view/<?php echo $createdBy; ?>"><?php echo $createdByName; ?></a>
                    | <?php echo $createdDate; ?>.
                </small>
            </fieldset>
        <?php } ?>

        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo Url::to("/dashboard/katalysis_pro_ai/actions"); ?>" class="btn btn-secondary">
                <i class="fa fa-chevron-left"></i> <?php echo t('Back'); ?>
            </a>

            <div class="float-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save" aria-hidden="true"></i> <?php echo t('Save'); ?>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    // Track unsaved changes
    window.hasUnsavedChanges = false;
    
    // Function to mark changes as unsaved
    function markUnsavedChanges() {
        window.hasUnsavedChanges = true;
        updateSaveButtonState();
        
        // Auto-save form steps to database after a short delay
        clearTimeout(window.autoSaveTimeout);
        window.autoSaveTimeout = setTimeout(function() {
            autoSaveFormSteps();
        }, 2000); // 2 second delay
    }
    
    // Function to mark changes as saved
    function markChangesSaved() {
        window.hasUnsavedChanges = false;
        updateSaveButtonState();
    }
    
    // Update save button state and text
    function updateSaveButtonState() {
        const saveButton = $('button[type="submit"]').first();
        if (window.hasUnsavedChanges) {
            saveButton.removeClass('btn-secondary').addClass('btn-warning');
            saveButton.html('<i class="fas fa-save"></i> Save Changes*');
        } else {
            saveButton.removeClass('btn-warning').addClass('btn-secondary');
            saveButton.html('<i class="fas fa-save"></i> Save');
        }
    }
    
    // Auto-save form steps to database
    function autoSaveFormSteps() {
        const actionId = $('input[name="actionId"]').val();
        const formStepsJson = $('textarea[name="formSteps"]').val();
        
        if (!actionId || !formStepsJson) {
            return;
        }
        
        // Show auto-save indicator
        showAutoSaveIndicator('Saving...');
        
        $.ajax({
            url: '<?php echo $this->action("save_form_steps"); ?>',
            method: 'POST',
            data: {
                action_id: actionId,
                form_steps: formStepsJson,
                ccm_token: '<?php echo $token->generate("save_form_steps"); ?>'
            },
            success: function(response) {
                showAutoSaveIndicator('Auto-saved', 'success');
                // Keep the main form unsaved changes indicator for other fields
                updateSaveButtonState();
            },
            error: function() {
                showAutoSaveIndicator('Save failed', 'error');
            }
        });
    }
    
    // Show auto-save status indicator
    function showAutoSaveIndicator(message, type = 'info') {
        // Remove existing indicator
        $('.auto-save-indicator').remove();
        
        let className = 'text-muted';
        let icon = 'fas fa-spinner fa-spin';
        
        if (type === 'success') {
            className = 'text-success';
            icon = 'fas fa-check';
        } else if (type === 'error') {
            className = 'text-danger';
            icon = 'fas fa-exclamation-triangle';
        }
        
        const indicator = $(`<small class="auto-save-indicator ${className} ms-2"><i class="${icon}"></i> ${message}</small>`);
        $('legend:contains("Form Builder")').append(indicator);
        
        // Auto-hide success/error messages after 3 seconds
        if (type === 'success' || type === 'error') {
            setTimeout(() => {
                indicator.fadeOut();
            }, 3000);
        }
    }
    
    $(document).ready(function () {
        // Show/hide form builder based on action type
        function toggleFormBuilder() {
            const actionType = $('#actionType').val();
            if (actionType === 'form' || actionType === 'dynamic_form' || actionType === 'simple_form') {
                $('#form-builder-section').show();
                loadFormStepsFromJson();
            } else {
                $('#form-builder-section').hide();
            }
        }

        // Initial state
        toggleFormBuilder();

        // Listen for action type changes
        $('#actionType').on('change', toggleFormBuilder);

        // Global form steps array
        window.formSteps = [];

        // Load form steps from JSON textarea
        function loadFormStepsFromJson() {
            try {
                const jsonText = $('textarea[name="formSteps"]').val();
                if (jsonText.trim()) {
                    const parsedSteps = JSON.parse(jsonText);

                    // Validate that parsed data is an array
                    if (Array.isArray(parsedSteps)) {
                        // Filter out null/undefined elements and validate structure
                        window.formSteps = parsedSteps.filter(step => step && typeof step === 'object');
                    } else {
                        console.warn('Invalid JSON structure - expected array');
                        window.formSteps = [];
                    }

                    renderFormSteps();
                }
            } catch (e) {
                console.warn('Invalid JSON in form steps:', e);
                window.formSteps = [];
                renderFormSteps(); // Still render to show empty state
            }
        }

        // Save form steps to JSON textarea
        function saveFormStepsToJson() {
            $('textarea[name="formSteps"]').val(JSON.stringify(window.formSteps, null, 2));
        }

        // Render visual form steps
        function renderFormSteps() {
            const container = $('#form-steps-container');
            container.empty();

            // Add validation for formSteps array
            if (!window.formSteps || !Array.isArray(window.formSteps)) {
                console.warn('Invalid formSteps data');
                return;
            }

            window.formSteps.forEach((step, index) => {
                // Skip if step is undefined or null
                if (!step) {
                    console.warn(`Skipping undefined step at index ${index}`);
                    return;
                }

                // Provide default values for missing properties
                const stepKey = step.stepKey || `field_${index + 1}`;
                const fieldType = step.fieldType || 'text';
                const question = step.question || '(No question set)';
                const stepHtml = `
                <div class="card mb-3 form-step-card" data-step-index="${index}" draggable="true">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-grip-vertical me-2 drag-handle" style="cursor: move;" title="Drag to reorder"></i>
                            Step ${index + 1}: ${fieldType.toUpperCase()} - ${stepKey}
                        </h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editFormStep(${index})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFormStep(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Question:</strong> ${question}
                            </div>
                            <div class="col-md-6">
                                <strong>Required:</strong> ${step.validation?.required ? 'Yes' : 'No'}
                            </div>
                        </div>
                        ${step.options ? `<div class="mt-2"><strong>Options:</strong> ${step.options.join(', ')}</div>` : ''}
                        ${step.conditionalLogic ? `
                            <div class="mt-2">
                                <strong><i class="fas fa-robot me-1"></i>AI Logic:</strong>
                                <div class="badge bg-info">AI Controlled</div>
                                <div class="mt-1"><small class="text-muted">${step.conditionalLogic.decision_prompt}</small></div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
                container.append(stepHtml);
            });
            
            // Initialize drag and drop functionality
            initializeDragAndDrop();
        }

        // Initialize drag and drop functionality for form steps
        function initializeDragAndDrop() {
            let draggedElement = null;
            let draggedIndex = null;
            
            // Add event listeners to all form step cards
            $('.form-step-card').each(function() {
                const card = this;
                
                card.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    draggedIndex = parseInt($(this).data('step-index'));
                    $(this).addClass('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.outerHTML);
                });
                
                card.addEventListener('dragend', function(e) {
                    $(this).removeClass('dragging');
                    $('.drag-over').removeClass('drag-over');
                    draggedElement = null;
                    draggedIndex = null;
                });
                
                card.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const afterElement = getDragAfterElement(container, e.clientY);
                    const dragging = document.querySelector('.dragging');
                    
                    if (afterElement == null) {
                        container.appendChild(dragging);
                    } else {
                        container.insertBefore(dragging, afterElement);
                    }
                });
                
                card.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                });
                
                card.addEventListener('dragleave', function(e) {
                    $(this).removeClass('drag-over');
                });
                
                card.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedElement !== this) {
                        const dropIndex = parseInt($(this).data('step-index'));
                        reorderFormSteps(draggedIndex, dropIndex);
                    }
                    
                    $('.drag-over').removeClass('drag-over');
                });
            });
            
            const container = document.getElementById('form-steps-container');
            
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                const dragging = document.querySelector('.dragging');
                
                if (dragging) {
                    if (afterElement == null) {
                        container.appendChild(dragging);
                    } else {
                        container.insertBefore(dragging, afterElement);
                    }
                }
            });
            
            container.addEventListener('drop', function(e) {
                e.preventDefault();
                
                // Get the final position and reorder
                const dragging = document.querySelector('.dragging');
                if (dragging && draggedIndex !== null) {
                    const cards = Array.from(container.querySelectorAll('.form-step-card'));
                    const newIndex = cards.indexOf(dragging);
                    
                    if (newIndex !== draggedIndex && newIndex !== -1) {
                        reorderFormSteps(draggedIndex, newIndex);
                    }
                }
            });
        }
        
        // Helper function to determine where to insert the dragged element
        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.form-step-card:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }
        
        // Reorder form steps in the array
        function reorderFormSteps(fromIndex, toIndex) {
            if (fromIndex === toIndex || !window.formSteps) return;
            
            // Move the element in the array
            const movedStep = window.formSteps.splice(fromIndex, 1)[0];
            window.formSteps.splice(toIndex, 0, movedStep);
            
            // Update sort orders
            window.formSteps.forEach((step, idx) => {
                step.sortOrder = idx + 1;
            });
            
            // Update JSON and re-render
            saveFormStepsToJson();
            renderFormSteps();
            
            // Mark as having unsaved changes
            markUnsavedChanges();
        }

        // Add new form step
        window.addFormStep = function (fieldType) {
            const newStep = {
                stepKey: `field_${window.formSteps.length + 1}`,
                fieldType: fieldType,
                question: `What's your ${fieldType}?`,
                sortOrder: window.formSteps.length + 1,
                validation: { required: true }
            };

            if (fieldType === 'select') {
                newStep.options = ['Option 1', 'Option 2', 'Option 3'];
            }

            window.formSteps.push(newStep);
            saveFormStepsToJson();
            renderFormSteps();
            markUnsavedChanges();
        };

        // Remove form step
        window.removeFormStep = function (index) {
            if (confirm('Are you sure you want to remove this step?')) {
                window.formSteps.splice(index, 1);
                // Update sort orders
                window.formSteps.forEach((step, idx) => {
                    step.sortOrder = idx + 1;
                });
                saveFormStepsToJson();
                renderFormSteps();
                markUnsavedChanges();
            }
        };

        // Edit form step (simple prompt-based editing)
        window.editFormStep = function (index) {
            console.log('editFormStep called with index:', index);
            console.log('window.formSteps:', window.formSteps);
            console.log('formSteps length:', window.formSteps ? window.formSteps.length : 'undefined');
            console.log('index >= 0:', index >= 0);
            console.log('index < length:', window.formSteps ? index < window.formSteps.length : 'N/A');
            console.log('step exists:', window.formSteps ? !!window.formSteps[index] : 'N/A');

            if (!window.formSteps || index < 0 || index >= window.formSteps.length || !window.formSteps[index]) {
                console.error('Invalid step index or step not found:', index);
                console.error('Validation failed - window.formSteps:', !!window.formSteps);
                console.error('Validation failed - index >= 0:', index >= 0);
                console.error('Validation failed - index < length:', window.formSteps ? index < window.formSteps.length : 'N/A');
                console.error('Validation failed - step exists:', window.formSteps ? !!window.formSteps[index] : 'N/A');
                return;
            }

            const step = window.formSteps[index];
            console.log('Step found:', step);

            // Ensure step has required properties
            if (!step.stepKey) {
                step.stepKey = `field_${index + 1}`;
            }
            if (!step.fieldType) {
                step.fieldType = 'text';
            }
            if (!step.question) {
                step.question = 'Enter your response';
            }

            console.log('Step after validation:', step);

            // Hide all other edit forms first
            $('.form-step-edit-form').remove();
            console.log('Removed existing edit forms');

            // Store original card body content
            const targetElement = $(`.card[data-step-index="${index}"]`);
            const originalCardBody = targetElement.find('.card-body').html();
            targetElement.data('original-body', originalCardBody);
            
            // Auto-generate field key if empty
            let fieldKey = step.stepKey;
            if (!fieldKey) {
                fieldKey = `field_${Date.now()}_${index}`;
                step.stepKey = fieldKey;
            }

            // Create inline edit form content
            const editFormContent = `
                <div class="form-group mb-3">
                    <label for="edit-fieldType-${index}" class="form-label">Field Type</label>
                    <select class="form-control" id="edit-fieldType-${index}">
                        <option value="text" ${step.fieldType === 'text' ? 'selected' : ''}>Text</option>
                        <option value="email" ${step.fieldType === 'email' ? 'selected' : ''}>Email</option>
                        <option value="textarea" ${step.fieldType === 'textarea' ? 'selected' : ''}>Textarea</option>
                        <option value="select" ${step.fieldType === 'select' ? 'selected' : ''}>Select</option>
                    </select>
                </div>
                
                <div class="form-group mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="edit-required-${index}" ${step.validation?.required ? 'checked' : ''} />
                        <label class="form-check-label" for="edit-required-${index}">Required Field</label>
                    </div>
                </div>
            
                <div class="form-group mb-3">
                    <label for="edit-question-${index}" class="form-label">Question</label>
                    <input type="text" class="form-control" id="edit-question-${index}" value="${step.question || ''}" />
                    <small class="form-text text-muted">The question to ask the user</small>
                </div>
                
                <div class="form-group mb-3" id="options-group-${index}" style="${step.fieldType === 'select' ? '' : 'display: none;'}">
                    <label for="edit-options-${index}" class="form-label">Options</label>
                    <textarea class="form-control" id="edit-options-${index}" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3">${step.options ? step.options.join('\n') : ''}</textarea>
                    <small class="form-text text-muted">One option per line</small>
                </div>
                
                <div class="form-group mb-3">
                    <h6 class="mb-2"><i class="fas fa-robot me-1"></i>AI Conditional Logic</h6>
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="edit-ai-decides-${index}" ${step.conditionalLogic?.ai_decides ? 'checked' : ''} />
                        <label class="form-check-label" for="edit-ai-decides-${index}">Enable AI-controlled conditional logic</label>
                        <small class="form-text text-muted d-block">When enabled, AI will decide whether to show this step based on previous answers</small>
                    </div>
                    
                    <div id="ai-logic-group-${index}" style="${step.conditionalLogic?.ai_decides ? '' : 'display: none;'}">
                        <label for="edit-decision-prompt-${index}" class="form-label">Decision Prompt</label>
                        <textarea class="form-control" id="edit-decision-prompt-${index}" rows="3" placeholder="Describe when this field should be shown...">${step.conditionalLogic?.decision_prompt || ''}</textarea>
                        <small class="form-text text-muted">Instructions for the AI on when to show this field. Be specific about the conditions.</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="button" class="btn btn-primary" onclick="saveFormStepEdit(${index})">Save Changes</button>
                    <button type="button" class="btn btn-secondary ms-2" onclick="cancelFormStepEdit(${index})">Cancel</button>
                </div>
            `;

            // Replace card body content with edit form
            targetElement.find('.card-body').html(editFormContent);
            targetElement.addClass('form-step-edit-form');
            console.log('Card body replaced with edit form');

            // Verify edit form was added
            const insertedForm = $('.form-step-edit-form');
            console.log('Edit form elements after insertion:', insertedForm.length);

            // Handle field type change to show/hide options
            $(`#edit-fieldType-${index}`).on('change', function () {
                const selectedType = $(this).val();
                if (selectedType === 'select') {
                    $(`#options-group-${index}`).show();
                } else {
                    $(`#options-group-${index}`).hide();
                }
            });
            
            // Handle AI logic checkbox to show/hide decision prompt
            $(`#edit-ai-decides-${index}`).on('change', function () {
                const isChecked = $(this).is(':checked');
                if (isChecked) {
                    $(`#ai-logic-group-${index}`).show();
                } else {
                    $(`#ai-logic-group-${index}`).hide();
                }
            });
        };

        // Save form step edit
        window.saveFormStepEdit = function (index) {
            const step = window.formSteps[index];

            // Get values from form
            const newQuestion = $(`#edit-question-${index}`).val().trim();
            const newFieldType = $(`#edit-fieldType-${index}`).val();
            const isRequired = $(`#edit-required-${index}`).is(':checked');

            // Validate required fields (field key is auto-generated)
            if (!newQuestion) {
                alert('Question is required');
                $(`#edit-question-${index}`).focus();
                return;
            }

            // Update step (field key remains auto-generated)
            step.question = newQuestion;
            step.fieldType = newFieldType;
            step.validation = { required: isRequired };

            // Handle options for select fields
            if (newFieldType === 'select') {
                const optionsText = $(`#edit-options-${index}`).val().trim();
                if (optionsText) {
                    step.options = optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt);
                } else {
                    step.options = [];
                }
            } else {
                delete step.options;
            }
            
            // Handle AI conditional logic
            const aiDecides = $(`#edit-ai-decides-${index}`).is(':checked');
            if (aiDecides) {
                const decisionPrompt = $(`#edit-decision-prompt-${index}`).val().trim();
                if (decisionPrompt) {
                    step.conditionalLogic = {
                        ai_decides: true,
                        decision_prompt: decisionPrompt
                    };
                } else {
                    // If checkbox is checked but no prompt provided, remove conditional logic
                    delete step.conditionalLogic;
                }
            } else {
                // If checkbox is unchecked, remove conditional logic
                delete step.conditionalLogic;
            }

            // Restore original card body and update display
            const targetElement = $(`.card[data-step-index="${index}"]`);
            targetElement.removeClass('form-step-edit-form');

            // Update JSON and re-render to show updated content
            saveFormStepsToJson();
            renderFormSteps();
            
            // Mark as having unsaved changes
            markUnsavedChanges();
        };

        // Cancel form step edit
        window.cancelFormStepEdit = function (index) {
            const targetElement = $(`.card[data-step-index="${index}"]`);
            const originalBody = targetElement.data('original-body');
            
            if (originalBody) {
                // Restore original card body content
                targetElement.find('.card-body').html(originalBody);
                targetElement.removeClass('form-step-edit-form');
            } else {
                // Fallback: re-render the entire form steps
                renderFormSteps();
            }
        };

        // Load sample forms
        window.loadSampleForm = function (type) {
            if (!confirm('This will replace your current form configuration. Continue?')) {
                return;
            }

            let sampleSteps = [];
            let sampleConfig = {};

            if (type === 'contact') {
                sampleSteps = [
                    {
                        stepKey: 'name',
                        fieldType: 'text',
                        question: 'What\'s your name?',
                        validation: { required: true },
                        sortOrder: 1
                    },
                    {
                        stepKey: 'email',
                        fieldType: 'email',
                        question: 'What\'s your email address?',
                        validation: { required: true, email: true },
                        sortOrder: 2
                    },
                    {
                        stepKey: 'message',
                        fieldType: 'textarea',
                        question: 'What can we help you with?',
                        validation: { required: true, min_length: 10 },
                        sortOrder: 3
                    }
                ];
                sampleConfig = {
                    show_immediately: false,
                    progressive: true,
                    completion_message: 'Thank you! We\'ll get back to you within 24 hours.',
                    ai_completion: false
                };
            } else if (type === 'lead_qualification') {
                sampleSteps = [
                    {
                        stepKey: 'name',
                        fieldType: 'text',
                        question: 'What\'s your name?',
                        validation: { required: true },
                        sortOrder: 1
                    },
                    {
                        stepKey: 'company',
                        fieldType: 'text',
                        question: 'What company do you work for?',
                        validation: { required: true },
                        sortOrder: 2
                    },
                    {
                        stepKey: 'company_size',
                        fieldType: 'select',
                        question: 'How many employees does your company have?',
                        options: ['1-10', '11-50', '51-200', '201-1000', '1000+'],
                        sortOrder: 3,
                        conditionalLogic: {
                            ai_decides: true,
                            decision_prompt: 'Ask about company size unless it\'s a well-known large company'
                        }
                    },
                    {
                        stepKey: 'budget',
                        fieldType: 'select',
                        question: 'What\'s your approximate budget range?',
                        options: ['Under $1k', '$1k-$5k', '$5k-$25k', '$25k+'],
                        sortOrder: 4,
                        conditionalLogic: {
                            ai_decides: true,
                            decision_prompt: 'Ask about budget if they seem like a qualified prospect'
                        }
                    }
                ];
                sampleConfig = {
                    show_immediately: false,
                    progressive: true,
                    ai_completion: true,
                    completion_prompt: 'Determine best next action based on qualification level'
                };
            }

            window.formSteps = sampleSteps;
            $('textarea[name="formSteps"]').val(JSON.stringify(sampleSteps, null, 2));
            $('textarea[name="formConfig"]').val(JSON.stringify(sampleConfig, null, 2));
            renderFormSteps();
        };

        // Sync JSON changes back to visual builder
        $('textarea[name="formSteps"]').on('blur', function () {
            loadFormStepsFromJson();
        });

        // Initialize form steps on page load
        $(document).ready(function () {
            console.log('Initializing form steps editor...');
            loadFormStepsFromJson();
            console.log('Form steps loaded:', window.formSteps);
            
            // Handle form submission to clear unsaved changes flag
            $('form').on('submit', function() {
                markChangesSaved();
            });
            
            // Warn user about unsaved changes when leaving page
            $(window).on('beforeunload', function() {
                if (window.hasUnsavedChanges) {
                    return 'You have unsaved changes to your form. Are you sure you want to leave?';
                }
            });
            
            // Also warn when navigating away via links
            $('a').on('click', function(e) {
                if (window.hasUnsavedChanges && !$(this).attr('href').startsWith('#')) {
                    if (!confirm('You have unsaved changes. Are you sure you want to leave this page?')) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    });
</script>

<style>
/* Drag and Drop Styles */
.form-step-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.form-step-card.dragging {
    opacity: 0.8;
    transform: scale(1.02);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1000;
}

.form-step-card.drag-over {
    border: 2px dashed #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}

.drag-handle {
    color: #6c757d;
    transition: color 0.2s ease;
}

.drag-handle:hover {
    color: #007bff;
}

.form-step-card:hover .drag-handle {
    color: #495057;
}

/* Visual feedback during drag */
#form-steps-container {
    min-height: 100px;
}

.form-step-card[draggable="true"] {
    cursor: grab;
}

.form-step-card[draggable="true"]:active {
    cursor: grabbing;
}
</style>
