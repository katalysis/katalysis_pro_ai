<?php
defined('C5_EXECUTE') or die('Access Denied.');

?>

<form method="post" enctype="multipart/form-data" action="<?= $controller->action('save') ?>">
    <?php $token->output('save_search_settings'); ?>
    <div id="ccm-dashboard-content-inner">

        <div class="row mb-5 justify-content-between">

            <div class="col-12 col-md-8 col-lg-6">
                <div class="alert alert-primary mb-5">
                    <h5><i class="fas fa-search"></i> <?php echo t('AI Search System Overview'); ?></h5>
                    <p class="mb-3">
                        <?php echo t('This system provides intelligent AI-powered search that understands your content and provides comprehensive responses. Here\'s how it works:'); ?>
                    </p>
                    <ul class="mb-0">
                        <li><strong><?php echo t('AI Intent Analysis'); ?></strong> -
                            <?php echo t('Advanced AI analyzes query intent, identifies relevant legal specialisms, and determines optimal response strategy automatically.'); ?>
                        </li>
                        <li><strong><?php echo t('Comprehensive AI Responses'); ?></strong> -
                            <?php echo t('Generates detailed 5-point structured responses with relevant legal information and professional guidance.'); ?>
                        </li>
                        <li><strong><?php echo t('Smart Vector Search'); ?></strong> -
                            <?php echo t('Uses advanced vector similarity to find the most relevant pages, content, and resources from your knowledge base.'); ?>
                        </li>
                        <li><strong><?php echo t('Intelligent Specialist Matching'); ?></strong> -
                            <?php echo t('AI automatically evaluates all specialists against query context and recommends the most relevant experts based on expertise and specialisms.'); ?>
                        </li>
                        <li><strong><?php echo t('Context-Aware Review Selection'); ?></strong> -
                            <?php echo t('Automatically selects relevant client testimonials using specialism-based topic relationships and AI similarity matching.'); ?>
                        </li>
                        <li><strong><?php echo t('Performance Optimized'); ?></strong> -
                            <?php echo t('Pure AI-driven architecture delivers 50-70% faster response times with intelligent caching and optimized algorithms.'); ?>
                        </li>
                    </ul>
                </div>

                <h5 class="mb-4"><?php echo t('AI Prompt Configuration'); ?></h5>

                <fieldset class="mb-5">
                    <legend><?php echo t('AI Response Format Configuration'); ?></legend>
                    
                    <?php if ($hasOldFormat): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> <?php echo t('Format Migration Available'); ?></h6>
                        <p><?php echo t('Your response format will be automatically migrated to the new section-based system when you save. The "Related Services" section will be removed as it\'s now handled separately.'); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info mb-4">
                        <h6><?php echo t('Section-Based Response Management'); ?></h6>
                        <p><?php echo t('Configure AI responses using flexible sections. Each section can be individually controlled with custom properties like headings, sentence counts, and enabled status.'); ?></p>
                        <ul class="mb-0">
                            <li><?php echo t('Add, remove, and reorder response sections as needed'); ?></li>
                            <li><?php echo t('Control section headings and content length'); ?></li>
                            <li><?php echo t('Enable/disable sections without deleting them'); ?></li>
                            <li><?php echo t('Separate response guidelines for overall tone and style'); ?></li>
                        </ul>
                    </div>

                    <!-- Response Sections Management -->
                    <div class="form-group mb-4">
                        <label class="control-label"><?php echo t('Response Sections'); ?></label>
                        
                        <div id="responseSections" class="border rounded p-3 bg-light">
                            <!-- Sections will be populated by JavaScript -->
                        </div>
                        
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addResponseSection()">
                                <i class="fas fa-plus"></i> <?php echo t('Add Section'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="restoreDefaultResponseSections()">
                                <i class="fas fa-undo"></i> <?php echo t('Restore Default Sections'); ?>
                            </button>
                        </div>
                        
                        <!-- Hidden field to store sections JSON -->
                        <input type="hidden" id="response_sections" name="response_sections" value="<?php echo htmlspecialchars($responseSections); ?>">
                    </div>

                    <!-- Response Guidelines -->
                    <div class="form-group">
                        <?php echo $form->label(
                            "response_guidelines",
                            t("General Response Guidelines"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>

                        <?php echo $form->textarea(
                            "response_guidelines",
                            $responseGuidelines,
                            [
                                "class" => "form-control",
                                "max-length" => "5000",
                                "style" => "field-sizing: content;",
                                "rows" => "8",
                                "placeholder" => t("Enter general guidelines for tone, style, and approach...")
                            ]
                        ); ?>
                        <small class="form-text text-muted">
                            <?php echo t('These guidelines apply to all sections and control the overall tone, style, and approach of AI responses.'); ?>
                        </small>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="restoreDefaultResponseGuidelines()">
                            <i class="fas fa-undo"></i> <?php echo t('Restore Default Guidelines'); ?>
                        </button>
                    </div>
                </fieldset>

                <fieldset class="mb-5">
                    <legend><?php echo t('False Positive Word Pairs'); ?></legend>

                    <div class="alert alert-info mb-3">
                        <h6><?php echo t('About False Positive Filtering'); ?></h6>
                        <p><?php echo t('When users search for one word, sometimes phonetically similar words incorrectly appear in results. Add word pairs below to filter out these false matches.'); ?>
                        </p>
                        <p><strong><?php echo t('Example:'); ?></strong>
                            <?php echo t('If someone searches "crash" but results incorrectly include "crush", add this pair to filter out the false match.'); ?>
                        </p>
                    </div>

                    <!-- Dynamic word pairs container -->
                    <div id="falsePositivePairs" class="mb-3">
                        <!-- Pairs will be populated by JavaScript -->
                    </div>

                    <!-- Add new pair button -->
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFalsePositivePair()">
                            <i class="fas fa-plus"></i> <?php echo t('Add Word Pair'); ?>
                        </button>
                    </div>

                    <!-- Hidden textarea to store JSON data -->
                    <input type="hidden" id="known_false_positives" name="known_false_positives"
                        value="<?php echo htmlspecialchars($knownFalsePositives); ?>">
                </fieldset>




            </div>

            <div class="col-12 col-md-8 col-lg-5" style="max-width:500px;">

                <fieldset class="mb-5">
                    <legend><?php echo t('Search Results Configuration'); ?></legend>


                    <div class="form-group">
                        <?php echo $form->label('candidate_documents_count', t('Number of candidate pages to process')) ?>
                        <?php echo $form->number('candidate_documents_count', $candidateDocumentsCount, [
                            'min' => 10,
                            'max' => 30,
                            'class' => 'form-control'
                        ]) ?>
                        <small class="form-text text-muted">
                            <?php echo t('Total candidate documents retrieved from vector search (10-30). More candidates provide better selection but increase processing time.') ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <?php echo $form->label('max_results', t('Number of service page results')) ?>
                        <?php echo $form->number('max_results', $maxResults, [
                            'min' => 2,
                            'max' => 12,
                            'class' => 'form-control'
                        ]) ?>
                        <small class="form-text text-muted">
                            <?php echo t('Final number of pages selected for display (2-12). Used for both AI and algorithmic selection.') ?>
                        </small>
                    </div>

                    <fieldset class="border p-3 mb-4">
                        <legend class="text-primary" style="font-size: 1rem; width: auto;"><?php echo t('AI Document Selection'); ?></legend>
                        
                        <div class="alert alert-info">
                            <h6><?php echo t('Document Selection Method'); ?></h6>
                            <p class="mb-2"><?php echo t('Choose how documents are selected for AI processing:'); ?></p>
                            <ul class="mb-0">
                                <li><strong><?php echo t('AI Selection:'); ?></strong> <?php echo t('Uses OpenAI to intelligently evaluate and select the most relevant documents based on query context and content quality.'); ?></li>
                                <li><strong><?php echo t('Algorithmic Selection:'); ?></strong> <?php echo t('Uses vector similarity scores to select documents (faster but less intelligent).'); ?></li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <?php echo $form->checkbox('use_ai_document_selection', 1, $useAISelection, ['class' => 'form-check-input', 'id' => 'use_ai_document_selection']) ?>
                                <?php echo $form->label('use_ai_document_selection', t('Enable AI-powered document selection'), ['class' => 'form-check-label']) ?>
                            </div>
                            <small class="form-text text-muted">
                                <?php echo t('When enabled, OpenAI will evaluate and select the most relevant documents. Requires additional API calls but provides better quality selection.'); ?>
                            </small>
                        </div>

                        <div id="ai_selection_settings" style="<?php echo $useAISelection ? '' : 'display: none;'; ?>">
                            <div class="form-group">
                                <?php echo $form->label('max_selected_documents', t('Maximum documents for AI evaluation'), ['class' => 'form-label']) ?>
                                <?php echo $form->number('max_selected_documents', $maxSelectedDocuments, [
                                    'min' => 4,
                                    'max' => 10,
                                    'class' => 'form-control'
                                ]) ?>
                                <small class="form-text text-muted">
                                    <?php echo t('Number of candidate documents to send to AI for evaluation (4-10). Higher numbers provide better selection but use more API credits.'); ?>
                                </small>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-group">
                        <?php echo $form->label('max_articles_case_studies', t('Number of query based secondary pages')) ?>
                        <?php echo $form->number('max_articles_case_studies', 4, [
                            'min' => 2,
                            'max' => 8,
                            'class' => 'form-control'
                        ]) ?>
                        <small class="form-text text-muted">
                            <?php echo t('Articles and case studies based on content matching (2-8)') ?>
                        </small>
                    </div>


                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('include_page_links', 1, $includePageLinks, ['class' => 'form-check-input']) ?>
                            <?php echo $form->label('include_page_links', t('Show direct page links with results'), ['class' => 'form-check-label']) ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('show_snippets', 1, $showSnippets, ['class' => 'form-check-input']) ?>
                            <?php echo $form->label('show_snippets', t('Show content snippets from pages'), ['class' => 'form-check-label']) ?>
                        </div>
                    </div>
                </fieldset>


                <fieldset class="mb-5">
                    <legend><?php echo t('Recommendation Settings'); ?></legend>



                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('enable_specialists', 1, $enableSpecialists, ['class' => 'form-check-input', 'id' => 'enable_specialists']) ?>
                            <?php echo $form->label('enable_specialists', t('Show People recommendations'), ['class' => 'form-check-label']) ?>
                        </div>
                    </div>

                    <div id="specialists_settings" style="<?php echo $enableSpecialists ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <?php echo $form->label('max_specialists', t('Maximum to show'), ['class' => 'form-label']) ?>
                            <?php echo $form->number('max_specialists', $maxSpecialists, [
                                'min' => 1,
                                'max' => 5,
                                'class' => 'form-control'
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('enable_places', 1, true, ['class' => 'form-check-input', 'id' => 'enable_places']) ?>
                            <?php echo $form->label('enable_places', t('Show Places recommendations'), ['class' => 'form-check-label']) ?>
                        </div>
                    </div>

                    <div id="places_settings">
                        <div class="form-group">
                            <?php echo $form->label('max_places', t('Maximum to show'), ['class' => 'form-label']) ?>
                            <?php echo $form->number('max_places', 3, [
                                'min' => 1,
                                'max' => 5,
                                'class' => 'form-control'
                            ]) ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('enable_reviews', 1, $enableReviews, ['class' => 'form-check-input', 'id' => 'enable_reviews']) ?>
                            <?php echo $form->label('enable_reviews', t('Show relevant Reviews'), ['class' => 'form-check-label']) ?>
                        </div>
                    </div>

                    <div id="reviews_settings" style="<?php echo $enableReviews ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <?php echo $form->label('max_reviews', t('Maximum to show'), ['class' => 'form-label']) ?>
                            <?php echo $form->number('max_reviews', $maxReviews, [
                                'min' => 1,
                                'max' => 6,
                                'class' => 'form-control'
                            ]) ?>
                        </div>
                    </div>


                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            // Toggle specialists settings visibility
                            const specialistsCheckbox = document.getElementById('enable_specialists');
                            const specialistsSettings = document.getElementById('specialists_settings');

                            if (specialistsCheckbox && specialistsSettings) {
                                specialistsCheckbox.addEventListener('change', function () {
                                    specialistsSettings.style.display = this.checked ? 'block' : 'none';
                                });
                            }

                            // Toggle places settings visibility
                            const placesCheckbox = document.getElementById('enable_places');
                            const placesSettings = document.getElementById('places_settings');

                            if (placesCheckbox && placesSettings) {
                                placesCheckbox.addEventListener('change', function () {
                                    placesSettings.style.display = this.checked ? 'block' : 'none';
                                });
                            }

                            // Toggle reviews settings visibility
                            const reviewsCheckbox = document.getElementById('enable_reviews');
                            const reviewsSettings = document.getElementById('reviews_settings');

                            if (reviewsCheckbox && reviewsSettings) {
                                reviewsCheckbox.addEventListener('change', function () {
                                    reviewsSettings.style.display = this.checked ? 'block' : 'none';
                                });
                            }

                            // Toggle AI document selection settings visibility
                            const aiSelectionCheckbox = document.getElementById('use_ai_document_selection');
                            const aiSelectionSettings = document.getElementById('ai_selection_settings');

                            if (aiSelectionCheckbox && aiSelectionSettings) {
                                aiSelectionCheckbox.addEventListener('change', function () {
                                    aiSelectionSettings.style.display = this.checked ? 'block' : 'none';
                                });
                            }
                        });
                    </script>
                </fieldset>


                <fieldset class="mb-5">
                    <legend><?php echo t('Debug & Testing') ?></legend>
                    <div class="form-group">
                        <div class="form-check">
                            <?php echo $form->checkbox('enable_debug_panel', 1, $enableDebugPanel, ['class' => 'form-check-input']) ?>
                            <?php echo $form->label('enable_debug_panel', t('Show debug panel in search results'), ['class' => 'form-check-label']) ?>
                        </div>
                        <small class="form-text text-muted">
                            <?php echo t('Display detailed debug information with search results for analysis and troubleshooting') ?>
                        </small>
                    </div>
                </fieldset>


            </div>
        </div>

    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="float-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save" aria-hidden="true"></i> <?php echo t('Save Settings'); ?>
                </button>
            </div>
        </div>
    </div>
</form>

<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    .badge {
        font-size: 0.875em;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .alert h6 {
        margin-bottom: 0.5rem;
    }

    .alert ul {
        margin-bottom: 0.5rem;
    }

    .alert li {
        margin-bottom: 0.25rem;
    }



    /* Response Sections Styling */
    .response-section {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.15s ease-in-out;
    }

    .response-section:hover {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
    }

    .response-section.dragging {
        opacity: 0.5;
        transform: rotate(2deg);
    }

    .section-header {
        background: #f8f9fa;
        border-radius: 0.25rem;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-drag-handle {
        cursor: move;
        color: #6c757d;
        font-size: 1.2em;
    }

    .section-drag-handle:hover {
        color: #495057;
    }

    .section-toggle-switch {
        margin-left: auto;
    }

    .section-controls {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 0.75rem;
        align-items: start;
    }

    .section-properties {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        align-items: center;
    }

    .section-property-group {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .section-property-group input[type="number"] {
        width: 60px;
    }

    .section-disabled {
        opacity: 0.6;
        background: #f8f9fa;
    }

    .section-disabled .form-control {
        background-color: #e9ecef;
        opacity: 0.65;
    }

    #responseSections:empty::after {
        content: "<?php echo t('No sections defined. Click "Add Section" to get started.'); ?>";
        color: #6c757d;
        font-style: italic;
        text-align: center;
        display: block;
        padding: 2rem;
    }

    /* False Positive Pairs Styling */
    .false-positive-pair {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .false-positive-pair input {
        border: 1px solid #ced4da;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .false-positive-pair input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    #falsePositivePairs:empty+.d-flex .btn-outline-primary {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

</style>

<script>
    // Default values from PHP
    const defaultResponseSections = <?php echo $defaultResponseSections; ?>;
    const defaultResponseGuidelines = <?php echo json_encode($defaultResponseGuidelines); ?>;

    // Response sections management
    let responseSectionCount = 0;
    let draggedSection = null;

    // Section Management Functions
    function loadResponseSections() {
        const hiddenField = document.getElementById('response_sections');
        const container = document.getElementById('responseSections');

        if (!hiddenField || !container) return;

        try {
            const sections = JSON.parse(hiddenField.value || '[]');
            container.innerHTML = '';
            responseSectionCount = 0;

            if (sections.length === 0) {
                // Load default sections if none exist
                const defaultSections = JSON.parse(defaultResponseSections);
                defaultSections.forEach(section => {
                    addResponseSectionRow(section.name, section.description, section.enabled, section.show_heading, section.sentence_count);
                });
                updateResponseSectionsJSON();
                return;
            }

            sections.forEach(section => {
                addResponseSectionRow(
                    section.name || '',
                    section.description || '',
                    section.enabled !== false,
                    section.show_heading !== false,
                    section.sentence_count || 2
                );
            });

        } catch (e) {
            console.error('Error parsing response sections:', e);
            container.innerHTML = '<div class="alert alert-danger"><?php echo t('Error loading sections. Please restore defaults.'); ?></div>';
        }
    }

    function addResponseSection() {
        addResponseSectionRow('NEW SECTION', 'Describe the content for this section', true, true, 2);
        updateResponseSectionsJSON();
    }

    function addResponseSectionRow(name = '', description = '', enabled = true, showHeading = true, sentenceCount = 2) {
        const container = document.getElementById('responseSections');
        const sectionId = ++responseSectionCount;

        const sectionDiv = document.createElement('div');
        sectionDiv.className = `response-section ${enabled ? '' : 'section-disabled'}`;
        sectionDiv.id = `section-${sectionId}`;
        sectionDiv.draggable = true;

        sectionDiv.innerHTML = `
            <div class="section-header">
                <i class="fas fa-grip-vertical section-drag-handle" title="<?php echo t('Drag to reorder'); ?>"></i>
                <strong class="section-name-display">${name}</strong>
                <div class="section-toggle-switch">
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" ${enabled ? 'checked' : ''} 
                               onchange="toggleSection(${sectionId})" title="<?php echo t('Enable/disable this section'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="section-controls">
                <div>
                    <div class="form-group mb-2">
                        <label class="form-label small"><?php echo t('Section Name'); ?></label>
                        <input type="text" class="form-control form-control-sm section-name-input" 
                               value="${name}" onchange="updateSectionName(${sectionId}); updateResponseSectionsJSON();"
                               placeholder="<?php echo t('Section name (e.g., DIRECT ANSWER)'); ?>">
                    </div>
                    <div class="form-group mb-2">
                        <label class="form-label small"><?php echo t('Description'); ?></label>
                        <textarea class="form-control form-control-sm" rows="2" 
                                  onchange="updateResponseSectionsJSON()" 
                                  placeholder="<?php echo t('What should this section contain?'); ?>">${description}</textarea>
                    </div>
                </div>
                
                <div class="section-properties">
                    <div class="section-property-group">
                        <input type="checkbox" class="form-check-input" ${showHeading ? 'checked' : ''} 
                               onchange="updateResponseSectionsJSON()" id="heading-${sectionId}">
                        <label class="form-check-label small" for="heading-${sectionId}"><?php echo t('Show heading'); ?></label>
                    </div>
                    <div class="section-property-group">
                        <label class="form-label small"><?php echo t('Sentences:'); ?></label>
                        <input type="number" class="form-control form-control-sm" min="1" max="5" 
                               value="${sentenceCount}" onchange="updateResponseSectionsJSON()">
                    </div>
                </div>
                
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="removeResponseSection(${sectionId})" title="<?php echo t('Remove this section'); ?>">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        // Add drag and drop event listeners
        sectionDiv.addEventListener('dragstart', function(e) {
            draggedSection = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        sectionDiv.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            draggedSection = null;
        });

        sectionDiv.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        sectionDiv.addEventListener('drop', function(e) {
            e.preventDefault();
            if (draggedSection && draggedSection !== this) {
                const container = this.parentNode;
                const afterElement = getDragAfterElement(container, e.clientY);
                if (afterElement == null) {
                    container.appendChild(draggedSection);
                } else {
                    container.insertBefore(draggedSection, afterElement);
                }
                updateResponseSectionsJSON();
            }
        });

        container.appendChild(sectionDiv);
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.response-section:not(.dragging)')];
        
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

    function toggleSection(sectionId) {
        const sectionDiv = document.getElementById(`section-${sectionId}`);
        const checkbox = sectionDiv.querySelector('.section-toggle-switch input[type="checkbox"]');
        
        if (checkbox.checked) {
            sectionDiv.classList.remove('section-disabled');
        } else {
            sectionDiv.classList.add('section-disabled');
        }
        
        updateResponseSectionsJSON();
    }

    function updateSectionName(sectionId) {
        const sectionDiv = document.getElementById(`section-${sectionId}`);
        const nameInput = sectionDiv.querySelector('.section-name-input');
        const nameDisplay = sectionDiv.querySelector('.section-name-display');
        
        nameDisplay.textContent = nameInput.value || 'UNNAMED SECTION';
    }

    function removeResponseSection(sectionId) {
        const sectionDiv = document.getElementById(`section-${sectionId}`);
        if (sectionDiv && confirm('<?php echo t('Are you sure you want to remove this section?'); ?>')) {
            sectionDiv.remove();
            updateResponseSectionsJSON();
        }
    }

    function updateResponseSectionsJSON() {
        const sections = [];
        const sectionDivs = document.querySelectorAll('.response-section');

        sectionDivs.forEach(sectionDiv => {
            const nameInput = sectionDiv.querySelector('.section-name-input');
            const descriptionTextarea = sectionDiv.querySelector('textarea');
            const enabledCheckbox = sectionDiv.querySelector('.section-toggle-switch input[type="checkbox"]');
            const headingCheckbox = sectionDiv.querySelector('input[type="checkbox"]:not(.section-toggle-switch input)');
            const sentenceInput = sectionDiv.querySelector('input[type="number"]');

            const name = nameInput?.value.trim();
            const description = descriptionTextarea?.value.trim();

            if (name && description) {
                sections.push({
                    name: name,
                    description: description,
                    enabled: enabledCheckbox?.checked || false,
                    show_heading: headingCheckbox?.checked || false,
                    sentence_count: parseInt(sentenceInput?.value) || 2
                });
            }
        });

        const hiddenField = document.getElementById('response_sections');
        if (hiddenField) {
            hiddenField.value = JSON.stringify(sections);
        }
    }

    // Restore defaults functions
    function restoreDefaultResponseSections() {
        if (confirm('<?php echo t('Are you sure you want to restore the default response sections? This will replace your current sections.'); ?>')) {
            const hiddenField = document.getElementById('response_sections');
            hiddenField.value = defaultResponseSections;
            loadResponseSections();
        }
    }

    function restoreDefaultResponseGuidelines() {
        if (confirm('<?php echo t('Are you sure you want to restore the default response guidelines? This will replace your current guidelines.'); ?>')) {
            document.getElementById('response_guidelines').value = defaultResponseGuidelines;
        }
    }

    // False Positive Pairs Management (keeping existing functionality)
    let falsePositivePairCount = 0;

    function loadFalsePositivePairs() {
        const hiddenField = document.getElementById('known_false_positives');
        const container = document.getElementById('falsePositivePairs');

        if (!hiddenField || !container) return;

        try {
            const pairs = JSON.parse(hiddenField.value || '[]');
            container.innerHTML = '';
            falsePositivePairCount = 0;

            if (pairs.length === 0) {
                container.innerHTML = '<p class="text-muted"><?php echo t('No word pairs defined. Click "Add Word Pair" to get started.'); ?></p>';
                return;
            }

            pairs.forEach(pair => {
                addFalsePositivePairRow(pair.query || '', pair.false || '');
            });

        } catch (e) {
            console.error('Error parsing false positive pairs:', e);
            container.innerHTML = '<div class="alert alert-danger"><?php echo t('Error loading word pairs. Please restore defaults.'); ?></div>';
        }
    }

    function addFalsePositivePair(queryWord = '', falseWord = '') {
        addFalsePositivePairRow(queryWord, falseWord);
        updateFalsePositivesJSON();
    }

    function addFalsePositivePairRow(queryWord = '', falseWord = '') {
        const container = document.getElementById('falsePositivePairs');
        const pairId = ++falsePositivePairCount;

        // Remove "no pairs" message if it exists
        const noMessage = container.querySelector('.text-muted');
        if (noMessage) noMessage.remove();

        const pairDiv = document.createElement('div');
        pairDiv.className = 'row mb-2 false-positive-pair';
        pairDiv.id = `pair-${pairId}`;

        pairDiv.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" placeholder="<?php echo t('Search word (e.g., "crash")'); ?>" 
                   value="${queryWord}" onchange="updateFalsePositivesJSON()">
        </div>
        <div class="col-md-1 text-center d-flex align-items-center justify-content-center">
            <i class="fas fa-arrow-right text-muted"></i>
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" placeholder="<?php echo t('False match to filter (e.g., "crush")'); ?>" 
                   value="${falseWord}" onchange="updateFalsePositivesJSON()">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFalsePositivePair(${pairId})" title="<?php echo t('Remove this pair'); ?>">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;

        container.appendChild(pairDiv);
    }

    function removeFalsePositivePair(pairId) {
        const pairDiv = document.getElementById(`pair-${pairId}`);
        if (pairDiv) {
            pairDiv.remove();
            updateFalsePositivesJSON();

            // Show "no pairs" message if container is empty
            const container = document.getElementById('falsePositivePairs');
            if (container.children.length === 0) {
                container.innerHTML = '<p class="text-muted"><?php echo t('No word pairs defined. Click "Add Word Pair" to get started.'); ?></p>';
            }
        }
    }

    function updateFalsePositivesJSON() {
        const pairs = [];
        const pairRows = document.querySelectorAll('.false-positive-pair');

        pairRows.forEach(row => {
            const inputs = row.querySelectorAll('input[type="text"]');
            const queryWord = inputs[0]?.value.trim();
            const falseWord = inputs[1]?.value.trim();

            if (queryWord && falseWord) {
                pairs.push({
                    query: queryWord,
                    false: falseWord
                });
            }
        });

        const hiddenField = document.getElementById('known_false_positives');
        if (hiddenField) {
            hiddenField.value = JSON.stringify(pairs);
        }
    }

    // Initialize everything on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize response sections interface
        loadResponseSections();

        // Initialize false positive pairs interface
        loadFalsePositivePairs();

        // Toggle specialists settings visibility
        const specialistsCheckbox = document.getElementById('enable_specialists');
        const specialistsSettings = document.getElementById('specialists_settings');

        if (specialistsCheckbox && specialistsSettings) {
            specialistsCheckbox.addEventListener('change', function () {
                specialistsSettings.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Toggle places settings visibility
        const placesCheckbox = document.getElementById('enable_places');
        const placesSettings = document.getElementById('places_settings');

        if (placesCheckbox && placesSettings) {
            placesCheckbox.addEventListener('change', function () {
                placesSettings.style.display = this.checked ? 'block' : 'none';
            });
        }

        // Toggle reviews settings visibility
        const reviewsCheckbox = document.getElementById('enable_reviews');
        const reviewsSettings = document.getElementById('reviews_settings');

        if (reviewsCheckbox && reviewsSettings) {
            reviewsCheckbox.addEventListener('change', function () {
                reviewsSettings.style.display = this.checked ? 'block' : 'none';
            });
        }
    });
</script>
