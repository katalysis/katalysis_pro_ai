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
                    <legend><?php echo t('AI Response Format Instructions'); ?></legend>
                    <div class="form-group">
                        <?php echo $form->label(
                            "response_format_instructions",
                            t("Response Structure & Style Guidelines"),
                            [
                                "class" => "control-label"
                            ]
                        ); ?>

                        <?php echo $form->textarea(
                            "response_format_instructions",
                            $responseFormatInstructions,
                            [
                                "class" => "form-control",
                                "max-length" => "10000",
                                "style" => "field-sizing: content;",
                                "rows" => "15"
                            ]
                        ); ?>
                    </div>
                    <div class="alert alert-info">
                        <h6><?php echo t('Response Customization'); ?></h6>
                        <p><?php echo t('Customize how the AI structures and formats responses to user queries. This controls the style, tone, and organization of generated answers.'); ?>
                        </p>
                        <p><strong><?php echo t('Note:'); ?></strong>
                            <?php echo t('The technical JSON structure for intent analysis is handled automatically - this area is for response formatting guidelines only.'); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="restoreDefaultResponseFormatInstructions()">
                            <i class="fas fa-undo"></i> <?php echo t('Restore Default'); ?>
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
                        <?php echo $form->label('max_results', t('Number of AI generated service page results')) ?>
                        <?php echo $form->number('max_results', $maxResults, [
                            'min' => 4,
                            'max' => 12,
                            'class' => 'form-control'
                        ]) ?>
                        <small class="form-text text-muted">
                            <?php echo t('Maximum AI-selected pages from vector search (4-12)') ?>
                        </small>
                    </div>

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
    const defaultResponseFormatInstructions = <?php echo json_encode($defaultResponseFormatInstructions); ?>;

    // Function to restore default response format instructions
    function restoreDefaultResponseFormatInstructions() {
        if (confirm('<?php echo t('Are you sure you want to restore the default response format instructions? This will replace your current instructions.'); ?>')) {
            document.getElementById('response_format_instructions').value = defaultResponseFormatInstructions;
            updateResponseFormatInstructionsModifiedStatus();
        }
    }

    // Functions to check modification status and update buttons
    function updateResponseFormatInstructionsModifiedStatus() {
        const currentInstructions = document.getElementById('response_format_instructions').value;
        const isModified = currentInstructions.trim() !== defaultResponseFormatInstructions.trim();
        updateButtonStatus('restoreDefaultResponseFormatInstructions', isModified, '<?php echo t('Restore Default'); ?>');
    }

    // Helper function to update button appearance
    function updateButtonStatus(functionName, isModified, baseText) {
        const restoreButton = document.querySelector(`button[onclick="${functionName}()"]`);

        if (restoreButton) {
            if (isModified) {
                restoreButton.classList.remove('btn-outline-secondary');
                restoreButton.classList.add('btn-warning');
                restoreButton.disabled = false;
                restoreButton.innerHTML = `<i class="fas fa-undo"></i> ${baseText} <span class="badge bg-warning text-dark">Modified</span>`;
            } else {
                restoreButton.classList.remove('btn-warning');
                restoreButton.classList.add('btn-outline-secondary');
                restoreButton.disabled = true;
                restoreButton.innerHTML = `<i class="fas fa-undo"></i> ${baseText}`;
            }
        }
    }

    // False Positive Pairs Management
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

    // Initialize modification status on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Update modification status
        updateResponseFormatInstructionsModifiedStatus();

        // Initialize false positive pairs interface
        loadFalsePositivePairs();

        // Add event listeners for textareas
        const responseFormatInstructionsTextarea = document.getElementById('response_format_instructions');
        if (responseFormatInstructionsTextarea) {
            responseFormatInstructionsTextarea.addEventListener('input', updateResponseFormatInstructionsModifiedStatus);
        }
    });
</script>
