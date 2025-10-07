<?php
defined('C5_EXECUTE') or die('Access Denied.');

?>

<form method="post" enctype="multipart/form-data" action="<?= $controller->action('save') ?>">
    <?php $token->output('save_search_settings'); ?>
    <div id="ccm-dashboard-content-inner">

        <!-- Full-width System Overview -->
        <div class="alert alert-primary mb-5">
            <h4><i class="fas fa-search"></i> <?php echo t('AI Search System Architecture'); ?></h4>
            <p class="mb-3">
                <?php echo t('This system uses a three-stage AI pipeline to deliver intelligent, context-aware search responses:'); ?>
            </p>
            <div class="row">
                <div class="col-md-8">
                    <ol class="mb-0">
                        <li class="mb-2"><strong><?php echo t('Stage 1: Intent Analysis'); ?></strong> -
                            <?php echo t('AI analyzes query intent and performs semantic vector search across your indexed content to find candidate documents.'); ?>
                        </li>
                        <li class="mb-2"><strong><?php echo t('Stage 2: Document Selection (Configurable)'); ?></strong>
                            -
                            <?php echo t('Either AI evaluates candidates for quality and relevance, or algorithmic sorting by similarity scores selects the best documents.'); ?>
                        </li>
                        <li class="mb-2"><strong><?php echo t('Stage 3: Response Generation'); ?></strong> -
                            <?php echo t('AI generates structured responses using selected documents, following your customizable section format and guidelines.'); ?>
                        </li>
                        <li class="mb-2"><strong><?php echo t('Parallel Processing: Recommendations'); ?></strong> -
                            <?php echo t('Simultaneously finds relevant specialists, reviews, and places using AI-driven matching and topic relationships.'); ?>
                        </li>
                        <li class="mb-0"><strong><?php echo t('Quality Control'); ?></strong> -
                            <?php echo t('False positive filtering ensures precise search results and reduces irrelevant matches.'); ?>
                        </li>
                    </ol>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded">
                        <h6 class="mb-2"><i class="fas fa-info-circle text-primary"></i>
                            <?php echo t('Key AI Components'); ?></h6>
                        <div class="mb-2">
                            <strong><?php echo t('Always AI-Powered:'); ?></strong><br>
                            <small
                                class="text-muted"><?php echo t('• Intent analysis'); ?><br><?php echo t('• Response generation'); ?><br><?php echo t('• Specialist matching'); ?><br><?php echo t('• Review selection'); ?></small>
                        </div>
                        <div>
                            <strong><?php echo t('Configurable (AI vs Algorithm):'); ?></strong><br>
                            <small
                                class="text-muted"><?php echo t('• Document selection'); ?><br><?php echo t('• Content quality evaluation'); ?><br><?php echo t('• Relevance ranking'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap Tabs for Configuration Sections -->
        <ul class="nav nav-tabs mb-4" id="searchConfigTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stage1-tab" data-bs-toggle="tab" data-bs-target="#stage1"
                    type="button" role="tab" aria-controls="stage1" aria-selected="true">
                    <i class="fas fa-cog"></i> <?php echo t('Stage 1: Search Intent'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="search-recommendations-tab" data-bs-toggle="tab"
                    data-bs-target="#search-recommendations" type="button" role="tab"
                    aria-controls="search-recommendations" aria-selected="false">
                    <i class="fas fa-search"></i> <?php echo t('Stage 2: Document Selection'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="response-generation-tab" data-bs-toggle="tab"
                    data-bs-target="#response-generation" type="button" role="tab" aria-controls="response-generation"
                    aria-selected="false">
                    <i class="fas fa-comments"></i> <?php echo t('Stage 3: Response Generation'); ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="debugging-tab" data-bs-toggle="tab" data-bs-target="#debugging"
                    type="button" role="tab" aria-controls="debugging" aria-selected="false">
                    <i class="fas fa-bug"></i> <?php echo t('Debugging & Testing'); ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="searchConfigTabsContent">

            <!-- Tab 1: Stage 1 - Document Selection Method -->
            <div class="tab-pane fade show active" id="stage1" role="tabpanel" aria-labelledby="stage1-tab">
                <div class="row justify-content-between mb-4">
                    <div class="col-lg-6">
                        <fieldset class="mb-4">
                            <legend><i class="fas fa-search"></i>
                                <?php echo t('Search Intent Analysis'); ?></legend>

                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('use_ai_document_selection', 1, $useAISelection, ['class' => 'form-check-input', 'id' => 'use_ai_document_selection']) ?>
                                    <?php echo $form->label('use_ai_document_selection', t('Enable AI intent analysis before searching'), ['class' => 'form-check-label fw-bold']) ?>
                                </div>
                            </div>

                            <div id="ai_selection_settings"
                                style="<?php echo $useAISelection ? '' : 'display: none;'; ?>">
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-dollar-sign"></i> <?php echo t('AI Intent Analysis Cost Impact'); ?>
                                    </h6>
                                    <p class="mb-0 small">
                                        <?php echo t('Each search query will make an API call to analyze user intent. With high traffic, this can significantly increase OpenAI costs.') ?>
                                    </p>
                                </div>
                                <div class="alert alert-info">
                                    <p class="mb-0 small">
                                        <i class="fas fa-info-circle"></i>
                                        <?php echo t('When disabled, Typesense will handle search directly using its built-in algorithms and attribute weighting.') ?>
                                    </p>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-lg-5">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-search"></i> <?php echo t('Search Intent Analysis'); ?></h5>
                            <p><?php echo t('Choose whether AI analyzes user queries before searching:'); ?></p>

                            <div class="mb-3">
                                <h6 class="text-success mb-2"><i class="fas fa-robot"></i> <?php echo t('With AI Intent Analysis'); ?></h6>
                                <p class="small mb-1"><?php echo t('AI understands what users really want before searching'); ?></p>
                                <div class="small text-muted">
                                    <strong><?php echo t('Better:'); ?></strong> <?php echo t('More accurate results, understands context and intent'); ?><br>
                                    <strong><?php echo t('Costs:'); ?></strong> <?php echo t('API calls for each search, slight delay'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-primary mb-2"><i class="fas fa-cog"></i> <?php echo t('Direct Typesense Search'); ?></h6>
                                <p class="small mb-1"><?php echo t('Typesense searches directly using built-in algorithms'); ?></p>
                                <div class="small text-muted">
                                    <strong><?php echo t('Faster:'); ?></strong> <?php echo t('Immediate search, no API costs'); ?><br>
                                    <strong><?php echo t('Limited:'); ?></strong> <?php echo t('Keyword-based, less context understanding'); ?>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong><?php echo t('Note:'); ?></strong> <?php echo t('Disabling AI intent analysis may affect search functionality - testing recommended.'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Search & Recommendations -->
            <div class="tab-pane fade" id="search-recommendations" role="tabpanel"
                aria-labelledby="search-recommendations-tab">
                <div class="row justify-content-between mb-4">
                    <div class="col-lg-6">
                        <fieldset class="mb-4">
                            <legend><i class="fas fa-sliders-h"></i> <?php echo t('Search Configuration'); ?></legend>

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

                            <div class="form-group">
                                <?php echo $form->label('max_articles_case_studies', t('Number of query based secondary pages')) ?>
                                <?php echo $form->number('max_articles_case_studies', $maxArticlesCaseStudies, [
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
                    </div>

                    <div class="col-lg-5">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-search"></i> <?php echo t('Search Configuration Overview'); ?></h5>
                            <p><?php echo t('These settings control the scope and format of search results, determining what content is included and how it\'s presented to users.'); ?>
                            </p>

                            <h6><?php echo t('Search Result Types:'); ?></h6>
                            <ul>
                                <li><strong><?php echo t('Service Pages:'); ?></strong>
                                    <?php echo t('Primary content pages selected by AI or algorithmic methods'); ?></li>
                                <li><strong><?php echo t('Secondary Pages:'); ?></strong>
                                    <?php echo t('Articles and case studies that match query context'); ?></li>
                                <li><strong><?php echo t('Page Links:'); ?></strong>
                                    <?php echo t('Direct navigation links to source pages'); ?></li>
                                <li><strong><?php echo t('Content Snippets:'); ?></strong>
                                    <?php echo t('Preview text from each selected page'); ?></li>
                            </ul>

                            <h6><?php echo t('Performance Considerations:'); ?></h6>
                            <ul>
                                <li><?php echo t('More candidate documents improve selection quality but increase processing time'); ?>
                                </li>
                                <li><?php echo t('Higher result counts provide comprehensive information but may overwhelm users'); ?>
                                </li>
                                <li><?php echo t('Snippets add context but increase response length'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-between">
                    <div class="col-lg-6">

                        <fieldset class="mb-4">
                            <legend><i class="fas fa-users"></i> <?php echo t('AI-Powered Recommendations') ?></legend>

                            <div class="form-group">
                                <div class="form-check">
                                    <?php echo $form->checkbox('enable_specialists', 1, $enableSpecialists, ['class' => 'form-check-input', 'id' => 'enable_specialists']) ?>
                                    <?php echo $form->label('enable_specialists', t('Show People recommendations'), ['class' => 'form-check-label']) ?>
                                </div>
                            </div>

                            <div id="specialists_settings"
                                style="<?php echo $enableSpecialists ? '' : 'display: none;'; ?>">
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
                        </fieldset>
                    </div>

                    <div class="col-lg-5">
                        <div class="card bg-light mt-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users"></i>
                                    <?php echo t('Smart Recommendation System') ?></h5>
                                <p class="card-text">
                                    <?php echo t('AI-powered recommendations enhance search results by suggesting relevant specialists, places, and reviews based on query context.') ?>
                                </p>

                                <h6 class="mt-3"><?php echo t('How Recommendations Work:') ?></h6>
                                <ul class="list-unstyled small">
                                    <li class="mb-2">
                                        <strong><?php echo t('People Recommendations:') ?></strong><br>
                                        <span
                                            class="text-muted"><?php echo t('AI evaluates specialists for relevance, expertise, and location match'); ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong><?php echo t('Places Recommendations:') ?></strong><br>
                                        <span
                                            class="text-muted"><?php echo t('Geographic locations suggested based on query context'); ?></span>
                                    </li>
                                    <li class="mb-2">
                                        <strong><?php echo t('Reviews Integration:') ?></strong><br>
                                        <span
                                            class="text-muted"><?php echo t('Relevant testimonials and case studies are surfaced'); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                            
            <!-- Tab 3: Response Generation -->
            <div class="tab-pane fade" id="response-generation" role="tabpanel"
                aria-labelledby="response-generation-tab">
                <div class="row justify-content-between">
                    <div class="col-lg-6">
                        <fieldset class="mb-4">
                            <legend><i class="fas fa-edit"></i> <?php echo t('Response Format Configuration'); ?>
                            </legend>
                    <!-- Response Guidelines -->

                        <div class="form-group mb-4">
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


                            <?php if ($hasOldFormat): ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i>
                                        <?php echo t('Format Migration Available'); ?></h6>
                                    <p><?php echo t('Your response format will be automatically migrated to the new section-based system when you save. The "Related Services" section will be removed as it\'s now handled separately.'); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Response Sections Management -->
                            <div class="form-group mb-4">
                                <?php echo $form->label(
                                    "responseSections",
                                    t("Response Sections"),
                                    [
                                        "class" => "control-label"
                                    ]
                                ); ?>

                                <div id="responseSections" class="border rounded p-3 bg-light">
                                    <!-- Sections will be populated by JavaScript -->
                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="addResponseSection()">
                                        <i class="fas fa-plus"></i> <?php echo t('Add Section'); ?>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        onclick="restoreDefaultResponseSections()">
                                        <i class="fas fa-undo"></i> <?php echo t('Restore Default Sections'); ?>
                                    </button>
                                </div>

                                <!-- Hidden field to store sections JSON -->
                                <input type="hidden" id="response_sections" name="response_sections"
                                    value="<?php echo htmlspecialchars($responseSections); ?>">
                            </div>

                            

                            <div class="text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    onclick="restoreDefaultResponseGuidelines()">
                                    <i class="fas fa-undo"></i> <?php echo t('Restore Default Guidelines'); ?>
                                </button>
                            </div>
                    </div>


                    <div class="col-lg-5">
                        <div class="alert alert-info mt-5">
                            <h5><i class="fas fa-comments"></i> <?php echo t('Response Generation Overview'); ?></h5>
                            <p><?php echo t('Configure how AI generates structured, professional responses using selected documents and your custom format.'); ?>
                            </p>

                            <h6><?php echo t('Key Features:'); ?></h6>
                            <ul>
                                <li><strong><?php echo t('Flexible Sections:'); ?></strong>
                                    <?php echo t('Create custom response sections with individual control over headings, length, and content'); ?>
                                </li>
                                <li><strong><?php echo t('Drag & Drop:'); ?></strong>
                                    <?php echo t('Easily reorder sections to match your preferred response flow'); ?>
                                </li>
                                <li><strong><?php echo t('Enable/Disable:'); ?></strong>
                                    <?php echo t('Toggle sections on/off without deleting them'); ?></li>
                                <li><strong><?php echo t('Quality Control:'); ?></strong>
                                    <?php echo t('False positive filtering ensures accurate search results'); ?></li>
                            </ul>

                            <h6><?php echo t('Best Practices:'); ?></h6>
                            <ul>
                                <li><?php echo t('Keep sections focused and specific for better AI responses'); ?></li>
                                <li><?php echo t('Use 1-2 sentences per section for concise, actionable content'); ?>
                                </li>
                                <li><?php echo t('Test different section combinations using the debug panel'); ?></li>
                                <li><?php echo t('Add common phonetic mistakes to false positive filtering'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="row justify-content-between">
                        <div class="col-lg-6">


                            <fieldset class="mb-4">
                                <legend><i class="fas fa-shield-alt"></i>
                                    <?php echo t('Quality Control: False Positive Filtering'); ?></legend>

                                <!-- Dynamic word pairs container -->
                                <div id="falsePositivePairs" class="mb-3">
                                    <!-- Pairs will be populated by JavaScript -->
                                </div>

                                <!-- Add new pair button -->
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        onclick="addFalsePositivePair()">
                                        <i class="fas fa-plus"></i> <?php echo t('Add Word Pair'); ?>
                                    </button>
                                </div>

                                <!-- Hidden textarea to store JSON data -->
                                <input type="hidden" id="known_false_positives" name="known_false_positives"
                                    value="<?php echo htmlspecialchars($knownFalsePositives); ?>">
                            </fieldset>
                        </div>
                        <div class="col-lg-5">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-shield-alt"></i>
                                    <?php echo t('False Positive Filtering Overview'); ?></h5>
                                <p><?php echo t('This feature helps ensure search accuracy by filtering out phonetically similar words that may cause irrelevant results.'); ?>
                                </p>

                                <h6><?php echo t('How It Works:'); ?></h6>
                                <ol>
                                    <li><?php echo t('You add pairs of commonly confused words (e.g., "crash" and "crush")'); ?>
                                    </li>
                                    <li><?php echo t('When a user searches for one word, the system excludes results containing the other word in the pair'); ?>
                                    </li>
                                    <li><?php echo t('This reduces false positives and improves result relevance'); ?>
                                    </li>
                                </ol>

                                <h6><?php echo t('Tips for Effective Use:'); ?></h6>
                                <ul>
                                    <li><?php echo t('Focus on words that are frequently confused in your content context'); ?>
                                    </li>
                                    <li><?php echo t('Start with 5-10 pairs and expand as you identify more issues'); ?>
                                    </li>
                                    <li><?php echo t('Regularly review search logs to find new false positive candidates'); ?>
                                    </li>
                                    <li><?php echo t('Use the debug panel to test filtering effectiveness'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Debugging & Testing -->
            <div class="tab-pane fade" id="debugging" role="tabpanel" aria-labelledby="debugging-tab">
                <div class="row">
                    <div class="col-lg-6">
                        <fieldset class="mb-4">
                            <legend><i class="fas fa-bug"></i> <?php echo t('Debug & Testing Settings') ?></legend>

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

                    <div class="col-lg-6">

                        <div class="alert alert-warning mt-3">
                            <h6><i class="fas fa-exclamation-triangle"></i> <?php echo t('Development Mode Only') ?>
                            </h6>
                            <p class="mb-2 small">
                                <?php echo t('Debug information should only be enabled during development and testing. It significantly increases the amount of information displayed with search results.') ?>
                            </p>
                            <p class="mb-0 small">
                                <?php echo t('Remember to disable this setting in production environments to maintain clean user experience.') ?>
                            </p>
                        </div>

                    </div>
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

    /* ENHANCEMENT: Input validation styling */
    .form-control.is-invalid {
        border-color: #dc3545;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .form-control.is-valid {
        border-color: #198754;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.8-.77 1.39-1.4.8-.77L6.7 2.4l.8.77-3.2 3.2-.8.77-2.4-2.4z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
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
        sectionDiv.addEventListener('dragstart', function (e) {
            draggedSection = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        sectionDiv.addEventListener('dragend', function (e) {
            this.classList.remove('dragging');
            draggedSection = null;
        });

        sectionDiv.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        sectionDiv.addEventListener('drop', function (e) {
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



    // ENHANCEMENT: Input validation function
    function validateNumericInputs() {
        const numericInputs = document.querySelectorAll('input[type="number"]');
        numericInputs.forEach(input => {
            input.addEventListener('change', function () {
                const min = parseInt(this.getAttribute('min')) || 0;
                const max = parseInt(this.getAttribute('max')) || 100;
                const value = parseInt(this.value);

                // Remove existing validation classes
                this.classList.remove('is-invalid', 'is-valid');

                if (isNaN(value) || value < min || value > max) {
                    this.classList.add('is-invalid');
                    showValidationMessage(this, `<?php echo t('Value must be between'); ?> ${min} <?php echo t('and'); ?> ${max}`);
                } else {
                    this.classList.add('is-valid');
                    hideValidationMessage(this);
                }

                // Update performance warnings
                updatePerformanceWarnings();
            });
        });
    }

    // Show validation message
    function showValidationMessage(input, message) {
        hideValidationMessage(input); // Remove any existing message

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        feedback.textContent = message;
        feedback.setAttribute('data-validation-for', input.id);

        input.parentNode.appendChild(feedback);
    }

    // Hide validation message
    function hideValidationMessage(input) {
        const existingFeedback = input.parentNode.querySelector(`[data-validation-for="${input.id}"]`);
        if (existingFeedback) {
            existingFeedback.remove();
        }
    }


    // Initialize everything on page load
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize response sections interface
        loadResponseSections();

        // Initialize false positive pairs interface
        loadFalsePositivePairs();

        // ENHANCEMENT: Initialize input validation
        validateNumericInputs();

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
