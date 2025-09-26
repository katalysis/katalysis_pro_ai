<?php defined('C5_EXECUTE') or die("Access Denied."); 

// Add Google Maps API if not already loaded
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$config = $app->make('config');
$googleMapsApiKey = $config->get('katalysis.base.google_maps_api_key');
if ($googleMapsApiKey) {
    $this->addHeaderItem('<script type="text/javascript" src="//maps.google.com/maps/api/js?key=' . $googleMapsApiKey . '"></script>');
}

// Add search actions JavaScript
$this->addFooterItem('<script src="/packages/katalysis_pro_ai/js/search-actions.js"></script>');
?>
<style>
    .ccm-page .katalysis-ai-search h3{
        text-transform:uppercase;
    }

    .ccm-page .katalysis-ai-search .ai-response-content h2{
        font-size:2rem;
        text-align:left !important;
        margin-bottom:30px;
    }
    .ccm-page .katalysis-ai-search .ai-response-content h3{
        font-size: 1.2rem !important;
        letter-spacing: 0.05rem;
        text-align:left !important;
    }
    .ccm-page .katalysis-ai-search .ai-response-content p.lead {
        font-weight:400;
        text-align:left !important;
    }

    .katalysis-ai-search .page-type-badge.debug-panel {
        background-color: rgb(39.5,39.5,39.5);
        color: white;
    }

    .katalysis-ai-search .page-result-item {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .katalysis-ai-search .page-result-item:last-child {
        border-bottom: none;
    }

    .katalysis-ai-search .page-result-item h6 {
        margin: 5px 0 8px 0;
        font-size: 1.1em;
    }

    .katalysis-ai-search .page-result-item h6 a {
        font-weight: 600;
        text-decoration: none;
    }

    .katalysis-ai-search .page-snippet {
        margin: 8px 0;
        line-height: 1.4;
    }

    /* Loading Placeholder Styles */
    .katalysis-ai-search .loading-placeholder {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
        background: linear-gradient(90deg, #f8f9fa 25%, #e9ecef 50%, #f8f9fa 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 8px;
        margin: 10px 0;
    }

    .katalysis-ai-search .loading-placeholder i {
        margin-right: 8px;
        color: var(--chatbot-primary);
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    .katalysis-ai-search .no-results {
        padding: 20px;
        text-align: center;
        color: #6c757d;
        font-style: italic;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 10px 0;
    }

     .katalysis-ai-search .page-link {
        margin: 8px 0 0 0;
        font-size: 0.9em;
    }

    .katalysis-ai-search .page-link a {
        text-decoration: none;
        font-weight: 500;
    }

    .katalysis-ai-search .page-link a:hover {
        text-decoration: underline;
    }



    .katalysis-ai-search .place-distance i {
        font-size: 0.8em;
        margin-right: 2px;
        color: #17a2b8;
    }

    .katalysis-ai-search .place-match-reason {
        margin: 5px 0 0 0;
        font-size: 0.8em;
        color: #6f42c1;
        font-style: italic;
        display: flex;
        align-items: flex-start;
        gap: 4px;
    }

    .katalysis-ai-search .place-match-reason i {
        font-size: 0.8em;
        margin-right: 2px;
        color: #6f42c1;
        margin-top: 2px;
    }

    /* Action Buttons Styling */

    .katalysis-ai-search .search-action-buttons {
       color:white;
    }

    .katalysis-ai-search .search-action-btn {
        color:white;
        margin: 0 10px 0 0;
        font-weight:700;
        -webkit-transition: all 0.3s; 
        -moz-transition: all 0.3s;
        transition: all 0.3s;
        box-shadow:0 0 0 0.2rem rgba(255, 255, 255, 0.8);
    }
    .katalysis-ai-search .search-action-btn:hover {
        box-shadow:0 0 0 0.2rem rgba(255, 255, 255, 0.4);
    }

    .katalysis-ai-search .search-action-btn i {
        margin-right: 0.5rem;
    }

    /* Action Form Styling */
    .katalysis-ai-search .search-action-form {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .katalysis-ai-search .search-action-form .progress {
        height: 0.5rem;
    }

    .katalysis-ai-search .search-action-form .form-step {
        min-height: 200px;
    }

    .katalysis-ai-search .search-action-form .action-form-buttons {
        border-top: 1px solid #dee2e6;
        padding-top: 1rem;
    }

    .katalysis-ai-search .search-action-form .is-invalid {
        border-color: #dc3545;
    }

    .katalysis-ai-search .search-action-form .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }


    <?php if (\Concrete\Core\Support\Facade\Config::get('katalysis.search.enable_debug_panel', false)): ?>
    /* Debug Panel Performance Styles */
    .katalysis-ai-search .debug-panel {
        font-size: 0.9em;
    }

    .katalysis-ai-search .debug-panel .card-header h6 {
        margin: 0;
        font-size: 1em;
    }

    .katalysis-ai-search .debug-rag-documents li {
        margin-bottom: 8px;
        padding: 8px;
        background-color: #f8f9fa;
        border-radius: 4px;
        border-left: 3px solid #007bff;
    }

    .katalysis-ai-search .debug-selected-documents li {
        margin-bottom: 8px;
        padding: 8px;
        background-color: #d4edda;
        border-radius: 4px;
        border-left: 3px solid #28a745;
    }

    .katalysis-ai-search .debug-rag-documents,
    .katalysis-ai-search .debug-selected-documents {
        max-height: none;
        overflow: visible;
        padding-left: 1rem;
        margin-top: 0.5rem;
    }

    .katalysis-ai-search .debug-panel .col-md-4 h6 {
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .katalysis-ai-search .rag-snippet,
    .katalysis-ai-search .selection-reason {
        font-size: 0.85em;
        color: #666;
        margin-top: 4px;
        font-style: italic;
    }

    .katalysis-ai-search .selection-reason small {
        color: #495057;
    }    .katalysis-ai-search .debug-panel .debug-label {
        font-weight: bold;
        color: #495057;
        display: inline-block;
        min-width: 120px;
    }

    .katalysis-ai-search .debug-panel .debug-value {
        color: #007bff;
        font-family: 'Courier New', monospace;
    }

    .katalysis-ai-search .debug-panel .debug-performance {
        padding: 10px;
        background-color: #e7f3ff;
        border-radius: 6px;
        text-align: center;
    }

    .katalysis-ai-search .debug-panel .performance-time {
        font-size: 1.2em;
        font-weight: bold;
        color: #28a745;
    }

    .katalysis-ai-search .debug-panel .toggle-debug {
        border: 1px solid rgba(255,255,255,0.3);
        font-size: 0.8em;
        padding: 2px 6px;
    }

    .katalysis-ai-search .debug-panel .toggle-debug:hover {
        background-color: rgba(255,255,255,0.1);
    }
    <?php endif; // End debug panel CSS ?>

   
    /* ========================================
   AI SEARCH BLOCK STYLES
   ======================================== */


    /* Search Form Styles */

    .search-input-group {
        position: relative;
    }

    .search-input {
        flex: 1;
        min-width: 200px;
        padding: 0.75rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        font-size: 1.2rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .search-input:focus {
        border-color: var(--chatbot-primary);
        box-shadow: 0 0 0 0.2rem rgba(119, 73, 248, 0.25);
        outline: 0;
    }


    .ccm-page .search-button {
        position: absolute;
        height: 100%;
        right: 0;
        top: 0;
        bottom: 0;
        padding: 0.75rem 1.5rem;
        background-color: transparent;
        border: none;
        border-radius: 0.5rem;
        color: var(--chatbot-primary);
        font-weight: 600;
        font-size: 1.2rem;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
    }

    .search-button:hover {
        color: var(--chatbot-primary-dark);
        transform: translateY(-1px);
    }

    .search-button:active {
        background-color: transparent !important;
    }

    .search-title {
        color: white;
        text-transform: uppercase;
        font-weight: 600;
    }


/* Loading State */
.search-loading {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 1.1rem;
}

.loading-spinner i {
    font-size: 1.2rem;
    color: var(--chatbot-primary);
}



</style>

<div class="katalysis-ai-search ai-search" data-block-id="<?php echo $blockId ?>">

    <div class="bg-primary">
        <div class="container-xl py-5">
            <div class="row">
                <div class="col-auto">
                    <img class="me-2"
                        src="/packages/katalysis_pro_ai/blocks/katalysis_ai_search/images/chat-bot-icon.svg"
                        alt="<?php echo t('AI Search'); ?>" class="img-fluid">
                    <span class="search-title"><?php echo h($buttonText) ?></span>
                </div>
                <!-- Search Form -->
                <div class="search-form-container col">
                    <form class="katalysis-search-form" role="search">
                        <div class="search-input-group">
                            <input type="text" class="form-control search-input"
                                placeholder="<?php echo h($placeholder) ?>" name="search_query" autocomplete="off"
                                required>
                            <button type="submit" class="btn btn-primary search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="container-xl">

        <!-- Loading State -->
        <div class="search-loading" style="display: none;">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <span><?php echo t('Searching...') ?></span>
            </div>
        </div>

        <!-- Results Container (for inline display) -->
        <?php if ($displayMode === 'inline'): ?>
            <div class="search-results-container py-4" style="display: none;">
                <!-- Debug Panel -->
                <?php 
                // Show debug panel only if enabled in settings
                $showDebugPanel = \Concrete\Core\Support\Facade\Config::get('katalysis.search.enable_debug_panel', false);
                ?>
                <?php if ($showDebugPanel): ?>
                <div class="debug-panel mb-4" style="display: none;">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-cog"></i> AI Processing Debug (Combined Analysis)
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-light toggle-debug" data-toggle="collapse" data-target="#debug-details">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6><i class="fas fa-clock"></i> Performance Metrics</h6>
                                <div class="debug-performance">
                                    <!-- Performance metrics will be inserted here -->
                                </div>
                            </div>
                        </div>
                        <div class="card-body collapse show" id="debug-details">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <h6><i class="fas fa-search"></i> Intent Analysis</h6>
                                    <div class="debug-classification">
                                        <!-- Classification details will be inserted here -->
                                    </div>
                                    <div class="debug-strategy">
                                        <!-- Strategy details will be inserted here -->
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <h6><i class="fas fa-database"></i> Candidate Documents</h6>
                                    <div class="debug-ai-input">
                                        <!-- AI Input details will be inserted here -->
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                     <h6><i class="fas fa-sort-amount-down"></i> <span class="selection-method-title">Selected Documents</span></h6>
                                     <div class="debug-ai-selected">
                                        <!-- Selected documents will be inserted here -->
                                     </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                     <h6><i class="fas fa-star"></i> Specialism-Matched Content</h6>
                                     <div class="debug-specialism-matched">
                                        <!-- Specialist-matched articles and case studies will be inserted here -->
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row justify-content-between">
                    <!-- Main Results Column -->
                    <div class="<?php echo ($showSpecialists || $showReviews) ? 'col-md-7' : 'col-12' ?>">
                        <div class="main-results">
                            <div class="ai-response-section">
                                <div class="ai-response-content">
                                    <!-- AI generated response will be inserted here -->
                                </div>
                            </div>

                            <div class="page-results-section">
                                <h4><?php echo t('Relevant Pages') ?></h4>
                                <div class="page-results-list">
                                    <!-- Page results will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <?php if ($showSpecialists || $showReviews): ?>
                        <div class="col-md-4">
                            <div class="search-sidebar">

                                <?php if ($showSpecialists): ?>
                                    <div class="specialists-section">
                                        <h4><?php echo t('Recommended Specialists') ?></h4>
                                        <div class="specialists-list">
                                            <!-- Specialists will be inserted here -->
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="places-section">
                                    <h4><?php echo t('Our Locations') ?></h4>
                                    <div class="places-list">
                                        <!-- Places will be inserted here -->
                                    </div>
                                </div>

                                <?php if ($showReviews): ?>
                                    <div class="reviews-section">
                                        <h4><?php echo t('Relevant Reviews') ?></h4>
                                        <div class="reviews-list">
                                            <!-- Reviews will be inserted here -->
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <div class="search-error" style="display: none;">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span class="error-message"></span>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        const searchBlock = $('.katalysis-ai-search[data-block-id="<?php echo $blockId ?>"]');
        const searchForm = searchBlock.find('.katalysis-search-form');
        const searchInput = searchBlock.find('.search-input');
        const loadingDiv = searchBlock.find('.search-loading');
        const resultsDiv = searchBlock.find('.search-results-container');
        const errorDiv = searchBlock.find('.search-error');

        // Handle form submission
        searchForm.on('submit', function (e) {
            e.preventDefault();
            performSearch();
        });

        function performSearch() {
            const query = searchInput.val().trim();

            if (!query) {
                showError('<?php echo addslashes(t('Please enter a search query')) ?>');
                return;
            }

            // Show loading state
            hideError();
            hideResults();
            showLoading();

            // Prepare request data
            const formData = new FormData();
            formData.append('query', query);
            formData.append('block_id', '<?php echo $blockId ?>');
            // formData.append('async_mode', 'true'); // DISABLED: Use synchronous mode for comprehensive responses
            
            // Add page context information for enhanced search logging
            formData.append('launch_page_url', window.location.href);
            formData.append('launch_page_title', document.title);
            formData.append('launch_page_type', '<?php echo addslashes(\Concrete\Core\Page\Page::getCurrentPage()->getPageTypeName()) ?>');
            
            // Add UTM and session tracking
            const urlParams = new URLSearchParams(window.location.search);
            formData.append('utm_source', urlParams.get('utm_source') || '');
            formData.append('utm_medium', urlParams.get('utm_medium') || '');
            formData.append('utm_campaign', urlParams.get('utm_campaign') || '');
            formData.append('utm_term', urlParams.get('utm_term') || '');
            formData.append('utm_content', urlParams.get('utm_content') || '');

            <?php if ($displayMode === 'redirect' && $resultsPageId): ?>
                // Redirect to results page
                const resultsUrl = '<?php echo \Concrete\Core\Support\Facade\Url::to(\Page::getByID($resultsPageId)) ?>';
                window.location.href = resultsUrl + '?search=' + encodeURIComponent(query);
                return;
            <?php endif; ?>

            // STEP 1: Get fast AI response and intent analysis
            fetch('<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/search_settings/perform_search') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    hideLoading();

                    if (data.success) {
                        // Show initial AI response immediately (1-2 seconds)
                        displayInitialResults(data);
                        
                        // Start loading additional content asynchronously
                        loadAdditionalContent(query, data.intent);
                    } else {
                        showError(data.error || '<?php echo addslashes(t('Search failed. Please try again.')) ?>');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError('<?php echo addslashes(t('Search failed. Please try again.')) ?>');
                    console.error('Search error:', error);
                });
        }

        function displayInitialResults(data) {
            // Populate debug panel if debug data is available and debug panel exists
            if (data.debug && resultsDiv.find('.debug-panel').length > 0 && typeof populateDebugPanel === 'function') {
                populateDebugPanel(data.debug, data.response);
                resultsDiv.find('.debug-panel').show();
            }

            const aiResponseSection = resultsDiv.find('.ai-response-content');
            const pageResultsList = resultsDiv.find('.page-results-list');

            // Display AI response immediately
            aiResponseSection.html(formatAiResponse(data.response, data.configured_sections || []));

            // Initialize actions if available
            if (data.actions && data.actions.length > 0 && typeof window.SearchActions !== 'undefined') {
                window.SearchActions.initializeSearchActions(data.actions, data.response);
            }

            // FIXED: Display AI-prioritized pages immediately from main search response
            if (data.pages && data.pages.length > 0) {
                pageResultsList.html(formatPageResults(data.pages));
            } else {
                pageResultsList.html('<div class="no-results">No relevant pages found</div>');
            }

            // Show loading placeholders for other sections only
            const specialistsList = resultsDiv.find('.specialists-list');
            const reviewsList = resultsDiv.find('.reviews-list');
            const placesList = searchBlock.find('.places-list');

            specialistsList.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Finding our people...</div>');
            reviewsList.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Loading reviews...</div>');
            placesList.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Finding locations...</div>');

            // Show all sections
            searchBlock.find('.specialists-section').show();
            searchBlock.find('.reviews-section').show();
            searchBlock.find('.places-section').show();

            showResults();
        }

        function loadAdditionalContent(query, intent) {
            // FIXED: Handle undefined intent properly
            let intentJson = '{}'; // Default to empty object
            if (intent && typeof intent === 'object') {
                intentJson = JSON.stringify(intent);
            }
            
            // FIXED: Load additional content in parallel (excluding pages since we already have AI-prioritized pages)
            Promise.allSettled([
                // REMOVED: loadPages(query) - using AI-prioritized pages from main search response
                loadSpecialists(query, intentJson),
                loadReviews(query, intentJson),
                loadPlaces(query, intentJson)
            ]).then(results => {
                // Handle each result
                const [specialistsResult, reviewsResult, placesResult] = results;
                
                // REMOVED: pages handling since we display them immediately from main search response
                
                if (specialistsResult.status === 'fulfilled' && specialistsResult.value.success) {
                    displaySpecialists(specialistsResult.value.specialists);
                } else {
                    searchBlock.find('.specialists-section').hide();
                }
                
                if (reviewsResult.status === 'fulfilled' && reviewsResult.value.success) {
                    displayReviews(reviewsResult.value.reviews);
                } else {
                    searchBlock.find('.reviews-section').hide();
                }
                
                if (placesResult.status === 'fulfilled' && placesResult.value.success) {
                    displayPlaces(placesResult.value.places);
                } else {
                    searchBlock.find('.places-section').hide();
                }
            });
        }

        // REMOVED: loadPages() function since we now use AI-prioritized pages from main search response

        async function loadSpecialists(query, intentJson) {
            const formData = new FormData();
            formData.append('query', query);
            formData.append('intent', intentJson);
            
            const response = await fetch('<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/search_settings/load_specialists') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            return await response.json();
        }

        async function loadReviews(query, intentJson) {
            const formData = new FormData();
            formData.append('query', query);
            formData.append('intent', intentJson);
            
            const response = await fetch('<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/search_settings/load_reviews') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            return await response.json();
        }

        async function loadPlaces(query, intentJson) {
            const formData = new FormData();
            formData.append('query', query);
            formData.append('intent', intentJson);
            
            const response = await fetch('<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/search_settings/load_places') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            return await response.json();
        }

        // REMOVED: displayPages() function since we display AI-prioritized pages directly in displayInitialResults()

        function displaySpecialists(specialists) {
            const specialistsList = resultsDiv.find('.specialists-list');
            if (specialists && specialists.length > 0) {
                specialistsList.html(formatSpecialists(specialists));
                searchBlock.find('.specialists-section').show();
            } else {
                searchBlock.find('.specialists-section').hide();
            }
        }

        function displayReviews(reviews) {
            const reviewsList = resultsDiv.find('.reviews-list');
            if (reviews && reviews.length > 0) {
                reviewsList.html(formatReviews(reviews));
                searchBlock.find('.reviews-section').show();
            } else {
                searchBlock.find('.reviews-section').hide();
            }
        }

        function displayPlaces(places) {
            const placesList = searchBlock.find('.places-list');
            if (places && places.length > 0) {
                placesList.html(formatPlaces(places));
                searchBlock.find('.places-section').show();
            } else {
                searchBlock.find('.places-section').hide();
            }
        }

        function displayResults(data) {
            // Populate debug panel if debug data is available and debug panel exists
            if (data.debug && resultsDiv.find('.debug-panel').length > 0 && typeof populateDebugPanel === 'function') {
                populateDebugPanel(data.debug, data.response);
                resultsDiv.find('.debug-panel').show();
            }

            const aiResponseSection = resultsDiv.find('.ai-response-content');
            const pageResultsList = resultsDiv.find('.page-results-list');
            const specialistsList = resultsDiv.find('.specialists-list');
            const reviewsList = resultsDiv.find('.reviews-list');

            // Display AI response
            aiResponseSection.html(formatAiResponse(data.response, data.configured_sections || []));

            // Initialize actions if available
            if (data.actions && data.actions.length > 0 && typeof window.SearchActions !== 'undefined') {
                window.SearchActions.initializeSearchActions(data.actions);
            }

            // Display page results
            pageResultsList.html(formatPageResults(data.pages));

            // Display specialists
            if (data.specialists && data.specialists.length > 0) {
                specialistsList.html(formatSpecialists(data.specialists));
                searchBlock.find('.specialists-section').show();
            } else {
                searchBlock.find('.specialists-section').hide();
            }

            // Display reviews
            if (data.reviews && data.reviews.length > 0) {
                reviewsList.html(formatReviews(data.reviews));
                searchBlock.find('.reviews-section').show();
            } else {
                searchBlock.find('.reviews-section').hide();
            }

            // Handle places results
            const placesList = searchBlock.find('.places-list');
            if (data.places && data.places.length > 0) {
                placesList.html(formatPlaces(data.places));
                searchBlock.find('.places-section').show();
            } else {
                searchBlock.find('.places-section').hide();
            }

            showResults();
        }

        function formatAiResponse(response, configuredSections = []) {
            if (!response) {
                return '';
            }
            
            // Clean up the response - remove action markers like [ACTIONS:4] and similar
            let cleanResponse = response.replace(/\[ACTIONS?:[0-9,\s]+\]/gi, '').trim();
            
            // Use configured sections if available, fallback to default sections
            const expectedSections = configuredSections.length > 0 
                ? configuredSections 
                : ['OUR CAPABILITIES:', 'WHY CHOOSE US:', 'PRACTICAL GUIDANCE:', 'ACT NOW:'];
            let hasExpectedFormat = expectedSections.some(section => cleanResponse.includes(section));
            
            if (hasExpectedFormat) {
                let sections = [];
                
                // Find the direct answer (everything before the first section header)
                let firstSectionPos = cleanResponse.length;
                expectedSections.forEach(function(sectionHeader) {
                    const pos = cleanResponse.indexOf(sectionHeader);
                    if (pos > -1 && pos < firstSectionPos) {
                        firstSectionPos = pos;
                    }
                });
                
                // Extract direct answer if it exists
                if (firstSectionPos > 0) {
                    const directAnswer = cleanResponse.substring(0, firstSectionPos).trim();
                    if (directAnswer) {
                        sections.push({ header: '', content: directAnswer });
                    }
                }
                
                // Process each expected section in order
                expectedSections.forEach(function(sectionHeader) {
                    const startPos = cleanResponse.indexOf(sectionHeader);
                    if (startPos > -1) {
                        // Find the end position (start of next section or end of content)
                        let endPos = cleanResponse.length;
                        for (let i = 0; i < expectedSections.length; i++) {
                            const nextHeader = expectedSections[i];
                            if (nextHeader !== sectionHeader) {
                                const nextHeaderPos = cleanResponse.indexOf(nextHeader, startPos + sectionHeader.length);
                                if (nextHeaderPos > -1 && nextHeaderPos < endPos) {
                                    endPos = nextHeaderPos;
                                }
                            }
                        }
                        
                        // Extract section content
                        const sectionContent = cleanResponse.substring(startPos + sectionHeader.length, endPos).trim();
                        const sectionName = sectionHeader.replace(':', '').trim();
                        
                        if (sectionContent) {
                            sections.push({ header: sectionName, content: sectionContent });
                        }
                    }
                });
                
                // Generate HTML from sections
                let html = '';
                sections.forEach(function(section) {
                    if (section.header === '') {
                        // Direct answer - use h2 heading
                        html += '<div class="direct-answer"><h2>' + escapeHtml(section.content) + '</h2></div>';
                    } else {
                        // Regular section with header
                        html += '<div class="ai-section">';
                        html += '<h3>' + escapeHtml(section.header) + '</h3>';
                        html += '<p>' + escapeHtml(section.content) + '</p>';
                        html += '</div>';
                    }
                });
                
                if (html) {
                    return html;
                }
            }
            
            // Fallback for unexpected formats
            return '<div class="direct-answer"><h2>' + escapeHtml(cleanResponse) + '</h2></div>';
        }

        function formatPageResults(pages) {
            if (!pages || pages.length === 0) {
                return '<p class="no-results"><?php echo addslashes(t('No relevant pages found.')) ?></p>';
            }

            let html = '<div class="page-results">';
            pages.forEach(function (page) {
                html += '<div class="page-result-item">';

                // Add page type badge based on provided data
                if (page.badge) {
                    let badgeClass = 'general'; // default
                    
                    // Set badge class based on type
                    if (page.type === 'legal_service_index' || page.type === 'legal_service') {
                        badgeClass = 'legal-service';
                    } else if (page.type === 'case_study') {
                        badgeClass = 'case-study';
                    } else if (page.type === 'article') {
                        badgeClass = 'article';
                    } else if (page.type === 'calculator') {
                        badgeClass = 'calculator';
                    }
                    
                    html += '<span class="badge text-bg-secondary page-type-badge ' + badgeClass + '">';
                    html += escapeHtml(page.badge);
                    html += '</span>';
                } else if (page.type) {
                    // Fallback badges based on type
                    let badgeText = '';
                    let badgeClass = 'general';
                    
                    if (page.type === 'legal_service_index') {
                        badgeText = 'Service Area';
                        badgeClass = 'legal-service';
                    } else if (page.type === 'legal_service') {
                        badgeText = 'Legal Service';
                        badgeClass = 'legal-service';
                    } else if (page.type === 'case_study') {
                        badgeText = 'Case Study';
                        badgeClass = 'case-study';
                    } else if (page.type === 'article') {
                        badgeText = 'Article';
                        badgeClass = 'article';
                    } else if (page.type === 'calculator') {
                        badgeText = 'Calculator';
                        badgeClass = 'calculator';
                    }
                    
                    if (badgeText) {
                        html += '<span class="badge text-bg-secondary page-type-badge ' + badgeClass + '">';
                        html += badgeText;
                        html += '</span>';
                    }
                }

                if (page.url) {
                    html += '<h6><a href="' + escapeHtml(page.url) + '" target="_blank">' + escapeHtml(page.title) + '</a></h6>';
                } else {
                    html += '<h6>' + escapeHtml(page.title) + '</h6>';
                }

                // Display content/description
                if (page.content && page.content.trim()) {
                    html += '<p class="page-snippet">' + escapeHtml(page.content) + '</p>';
                } else if (page.snippet && page.snippet.trim()) {
                    html += '<p class="page-snippet">' + escapeHtml(page.snippet) + '</p>';
                }

                // Add read more link
                if (page.url) {
                    html += '<p class="page-link"><a href="' + escapeHtml(page.url) + '" target="_blank">Read more →</a></p>';
                }

                html += '</div>';
            });
            html += '</div>';

            return html;
        }

        function formatSpecialists(specialists) {
            let html = '<div class="kpeople ai-search-results"><div class="people-list">';
            specialists.forEach(function (specialist) {
                html += '<div class="kperson">';
                
                // Image section with optional profile link
                html += '<div class="kpeople-image">';
                if (specialist.image_url) {
                    html += '<img src="' + escapeHtml(specialist.image_url) + '" alt="' + escapeHtml(specialist.image_alt || specialist.name) + '" class="img-fluid" />';
                    
                    // Add profile link overlay if available
                    if (specialist.profile_url) {
                        html += '<a href="' + escapeHtml(specialist.profile_url) + '" class="hover-link btn align-middle">';
                        html += '<i class="fas fa-user"></i>';
                        html += '<p class="link-details">' + escapeHtml(specialist.name) + '</p>';
                        html += '</a>';
                    }
                } else {
                    // Placeholder image or default person icon
                    html += '<div class="placeholder-image d-flex align-items-center justify-content-center bg-light" style="height: 200px; border-radius: 8px;">';
                    html += '<i class="fas fa-user fa-3x text-muted"></i>';
                    html += '</div>';
                }
                html += '</div>';
                
                // Details section
                html += '<div class="kpeople-detail">';
                
                // Name with optional profile link
                if (specialist.profile_url) {
                    html += '<p class="kpeople-name"><a href="' + escapeHtml(specialist.profile_url) + '">' + escapeHtml(specialist.name) + '</a>';
                } else {
                    html += '<p class="kpeople-name">' + escapeHtml(specialist.name);
                }
                
                // Add qualification if available
                if (specialist.qualification) {
                    html += ' <small>' + escapeHtml(specialist.qualification) + '</small>';
                }
                html += '</p>';
                
                // Job title
                if (specialist.title) {
                    html += '<p class="kpeople-job-title">' + escapeHtml(specialist.title) + '</p>';
                }
                
                // Add specialisms/expertise under name/title
                if (specialist.expertise) {
                    html += '<p class="kpeople-specialisms">EXPERTISE: ';
                    html += escapeHtml(specialist.expertise);
                    html += '</p>';
                }

                // Add office/place information if available
                if (specialist.office && (specialist.office.name || specialist.office.town)) {
                    html += '<p class="kpeople-place">USUAL OFFICE: ';
                    html += '<i class="fas fa-map-marker-alt me-1"></i>';

                    let locationParts = [];
                    if (specialist.office.name) {
                        locationParts.push(specialist.office.name);
                    }
                    if (specialist.office.town) {
                        locationParts.push(specialist.office.town);
                    }
                    if (specialist.office.county) {
                        locationParts.push(specialist.office.county);
                    }
                    
                    html += escapeHtml(locationParts.join(', '));
                    html += '</p>';
                }

                // Contact information inside kpeople-detail section
                if (specialist.email || specialist.phone || specialist.mobile) {
                    html += '<ul class="kpeople-contact">';
                    
                    if (specialist.email) {
                        html += '<li><i class="fas fa-envelope"></i>';
                        html += '<a href="mailto:' + escapeHtml(specialist.email) + '">' + escapeHtml(specialist.email) + '</a></li>';
                    }
                    
                    if (specialist.phone) {
                        html += '<li><i class="fas fa-phone"></i>' + escapeHtml(specialist.phone) + '</li>';
                    }
                    
                    if (specialist.mobile) {
                        html += '<li><i class="fas fa-mobile-alt"></i>' + escapeHtml(specialist.mobile) + '</li>';
                    }
                    
                    html += '</ul>';
                }

                html += '</div>'; // kpeople-detail

                html += '</div>'; // kperson
            });
            html += '</div>'; // people-list
            html += '</div>'; // kpeople small-list
            return html;
        }

        function formatReviews(reviews) {
            if (!reviews || reviews.length === 0) {
                return '<div class="kreviews"><p class="text-muted">No reviews available</p></div>';
            }

            let sliderId = 'review-slider-search-' + Date.now();
            let html = '<div class="kreviews">';
            
            // Carousel container
            html += '<div id="' + sliderId + '" class="carousel slide review-slider review-slider-wide" role="listbox" aria-label="Reviews" data-bs-ride="carousel">';
            html += '<div class="carousel-inner">';
            
            // Add carousel controls if more than one review
            if (reviews.length > 1) {
                // Accessibility description
                html += '<div id="' + sliderId + '-description" class="visually-hidden">';
                html += 'Review carousel containing ' + reviews.length + ' customer reviews. Use the previous and next buttons to navigate through reviews.';
                html += '</div>';
            }
            
            // Create carousel items
            reviews.forEach(function (review, index) {
                html += '<div class="carousel-item' + (index === 0 ? ' active' : '') + '">';
                html += '<div class="content-container d-flex align-items-center">';
                html += '<blockquote>';
                
                // Extract (if available)
                if (review.extract) {
                    html += '<p class="kreviews-extract">' + escapeHtml(review.extract) + '</p>';
                }
                
                // Main review text
                if (review.review) {
                    html += escapeHtml(review.review);
                }
                
                // Footer with citation information
                let hasFooterInfo = review.client_name || review.organization || review.date || review.source || review.rating;
                if (hasFooterInfo) {
                    html += '<footer class="blockquote-footer">';
                    html += '<cite title="Source Title">';
                    
                    // Author/Client name
                    if (review.client_name) {
                        html += '<span class="kreviews-author">' + escapeHtml(review.client_name) + '</span>';
                    }
                    
                    // Organization (with optional URL)
                    if (review.organization) {
                        if (review.url) {
                            html += '<a href="' + escapeHtml(review.url) + '" class="kreviews-organisation">';
                            html += escapeHtml(review.organization);
                            html += '</a>';
                        } else {
                            html += '<span class="kreviews-organisation">' + escapeHtml(review.organization) + '</span>';
                        }
                    }
                    
                    // Date (formatted if available)
                    if (review.date) {
                        html += '<span class="kreviews-date">' + escapeHtml(review.date) + '</span>';
                    }
                    
                    // Source and rating
                    if (review.source && review.rating) {
                        html += '<span class="kreviews-source">';
                        if (review.source === 'google') {
                            html += '<img src="/packages/katalysis_pro/blocks/katalysis_reviews/images/google-' + review.rating + '.svg" alt="' + review.rating + ' Star Google Rating"/>';
                        } else if (review.source === 'direct') {
                            html += '<img src="/packages/katalysis_pro/blocks/katalysis_reviews/images/generic-' + review.rating + '.svg" alt="' + review.rating + ' Star"/>';
                        } else {
                            // Fallback star display
                            for (let i = 1; i <= 5; i++) {
                                html += '<i class="fas fa-star' + (i <= review.rating ? '' : '-o') + '"></i>';
                            }
                        }
                        html += '</span>';
                    } else if (review.rating) {
                        // Just rating without source
                        html += '<span class="kreviews-rating">';
                        for (let i = 1; i <= 5; i++) {
                            html += '<i class="fas fa-star' + (i <= review.rating ? '' : '-o') + '"></i>';
                        }
                        html += '</span>';
                    }
                    
                    html += '</cite>';
                    html += '</footer>';
                }
                
                html += '</blockquote>';
                html += '</div>'; // content-container
                html += '</div>'; // carousel-item
            });
            
            html += '</div>'; // carousel-inner
            
            // Add navigation controls if more than one review
            if (reviews.length > 1) {
                // Previous button
                html += '<a class="carousel-control carousel-control-prev" ';
                html += 'href="#' + sliderId + '" role="button" data-bs-slide="prev" data-bs-target="#' + sliderId + '" ';
                html += 'aria-label="Show previous review" aria-describedby="' + sliderId + '-description">';
                html += '<span class="carousel-control-icon carousel-control-prev-icon" aria-hidden="true"></span>';
                html += '<span class="visually-hidden">Previous review</span>';
                html += '</a>';
                
                // Next button
                html += '<a class="carousel-control carousel-control-next" ';
                html += 'href="#' + sliderId + '" role="button" data-bs-slide="next" data-bs-target="#' + sliderId + '" ';
                html += 'aria-label="Show next review" aria-describedby="' + sliderId + '-description">';
                html += '<span class="carousel-control-icon carousel-control-next-icon" aria-hidden="true"></span>';
                html += '<span class="visually-hidden">Next review</span>';
                html += '</a>';
            }
            
            html += '</div>'; // carousel
            html += '</div>'; // kreviews
            
            // Initialize carousel after a short delay to ensure DOM is ready
            setTimeout(function() {
                initializeReviewCarousel(sliderId);
            }, 100);
            
            return html;
        }

        function initializeReviewCarousel(sliderId) {
            try {
                // Unbind smooth scroll from carousel controls
                $('a[class^=carousel-control-]').unbind('click.SmoothScroll');
                
                // Carousel height normalization
                var items = $('#' + sliderId + ' .carousel-item');
                var heights = [];
                var tallest;

                if (items.length) {
                    function normalizeHeights() {
                        heights = [];
                        items.each(function() {
                            heights.push($(this).height());
                        });
                        tallest = Math.max.apply(null, heights);
                        items.each(function() {
                            $(this).css('min-height', tallest + 'px');
                            $(this).find('.content-container').css('min-height', tallest + 'px');
                        });
                    }
                    
                    normalizeHeights();

                    $(window).on('resize orientationchange', function() {
                        tallest = 0;
                        heights.length = 0;
                        items.each(function() {
                            $(this).css('min-height', '0');
                        });
                        normalizeHeights();
                    });
                }
            } catch (e) {
                console.log('Carousel initialization error:', e);
            }
        }

        function formatPlaces(places) {
            let html = '<div class="kplaces">';
            places.forEach(function (place) {
                html += '<div class="kplace card mb-3">';
                
                // Card header with place name
                if (place.name) {
                    html += '<div class="card-header">';
                    html += '<h4>' + escapeHtml(place.name) + '</h4>';
                    html += '</div>';
                }
                
                // Card body with place details
                html += '<div class="kplace-detail card-body">';
                
                // Full address
                if (place.address) {
                    html += '<p class="card-text">' + escapeHtml(place.address).replace(/\n/g, '<br>') + '</p>';
                }
                
                // Contact list
                let hasContact = place.email || place.phone || place.fax;
                if (hasContact) {
                    html += '<ul class="klist contactlist">';
                    
                    if (place.email) {
                        html += '<li>';
                        html += '<svg class="ki-email"><use xlink:href="/themes/katalysis_psr_solicitors_theme_bootstrap/kicon.svg#ki-email"></use></svg>';
                        html += '&nbsp;<a href="mailto:' + escapeHtml(place.email) + '">' + escapeHtml(place.email) + '</a>';
                        html += '</li>';
                    }
                    
                    if (place.phone) {
                        html += '<li>';
                        html += '<svg class="ki-phone"><use xlink:href="/themes/katalysis_psr_solicitors_theme_bootstrap/kicon.svg#ki-phone"></use></svg>';
                        html += '&nbsp;<a href="tel:' + escapeHtml(place.phone.replace(/\s/g, '')) + '">' + escapeHtml(place.phone) + '</a>';
                        html += '</li>';
                    }
                    
                    if (place.fax) {
                        html += '<li>';
                        html += '<svg class="ki-fax"><use xlink:href="/themes/katalysis_psr_solicitors_theme_bootstrap/kicon.svg#ki-fax"></use></svg>';
                        html += '&nbsp;' + escapeHtml(place.fax);
                        html += '</li>';
                    }
                    
                    html += '</ul>';
                }
                
                // Additional information sections
                if (place.opening_hours) {
                    html += '<div class="card-text">';
                    html += '<strong>Opening Hours:</strong><br>';
                    html += '<span class="text-muted">' + escapeHtml(place.opening_hours) + '</span>';
                    html += '</div>';
                }
                
                if (place.parking_info) {
                    html += '<div class="card-text">';
                    html += '<strong>Parking:</strong><br>';
                    html += '<span class="text-muted">' + escapeHtml(place.parking_info) + '</span>';
                    html += '</div>';
                }
                
                if (place.accessibility) {
                    html += '<div class="card-text">';
                    html += '<strong>Accessibility:</strong><br>';
                    html += '<span class="text-muted">' + escapeHtml(place.accessibility) + '</span>';
                    html += '</div>';
                }

                // Services if available
                if (place.services && place.services.length > 0) {
                    html += '<div class="card-text">';
                    html += '<strong>Services:</strong><br>';
                    html += '<span class="text-muted">' + escapeHtml(place.services.join(', ')) + '</span>';
                    html += '</div>';
                }
                
                // Action buttons
                if (place.page_url) {
                    html += '<a href="' + escapeHtml(place.page_url) + '" class="btn d-block w-100 btn-primary mb-2">' + escapeHtml(place.name) + ' Office details</a>';
                }
                
                // AI-provided distance information if available (legacy support)
                if (place.distance_info) {
                    html += '<small class="text-muted d-block">';
                    html += '<i class="fas fa-route me-1"></i>' + escapeHtml(place.distance_info);
                    html += '</small>';
                }

                // AI match reasoning if available (legacy support)
                if (place.match_reason && place.enhanced_by_ai) {
                    html += '<small class="text-muted d-block">';
                    html += '<i class="fas fa-lightbulb me-1"></i>' + escapeHtml(place.match_reason);
                    html += '</small>';
                }
                
                // Relevance score (if debugging)
                if (place.relevance_score) {
                    html += '<small class="relevance-score text-muted d-block">Match: ' + place.relevance_score + '/10</small>';
                }
                
                html += '</div>'; // kplace-detail card-body
                
                // Google Map if coordinates are available
                if (place.latitude && place.longitude) {
                    let mapId = 'googleMapCanvas' + Math.floor(Math.random() * 10000);
                    html += '<div id="' + mapId + '" class="googleMapCanvas" style="width: 100%; height: 200px"></div>';
                    
                    // Initialize map after HTML is inserted
                    setTimeout(function() {
                        initializeGoogleMap(mapId, parseFloat(place.latitude), parseFloat(place.longitude), place.name);
                    }, 100);
                }
                
                html += '</div>'; // kplace card
            });
            html += '</div>'; // kplaces
            return html;
        }

        function initializeGoogleMap(mapId, lat, lng, placeName) {
            try {
                if (typeof google === 'undefined' || !google.maps) {
                    console.log('Google Maps API not loaded, skipping map for ' + placeName);
                    document.getElementById(mapId).innerHTML = '<p class="text-muted text-center p-3">Map unavailable</p>';
                    return;
                }

                var latlng = new google.maps.LatLng(lat, lng);
                var mapOptions = {
                    zoom: 14,
                    center: latlng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    streetViewControl: false,
                    scrollwheel: false,
                    mapTypeControl: false,
                    styles: [
                        {
                            "featureType": "water",
                            "elementType": "geometry.fill",
                            "stylers": [{"color": "#d3d3d3"}]
                        },
                        {
                            "featureType": "transit",
                            "stylers": [{"color": "#808080"}, {"visibility": "off"}]
                        },
                        {
                            "featureType": "road.highway",
                            "elementType": "geometry.stroke",
                            "stylers": [{"visibility": "on"}, {"color": "#b3b3b3"}]
                        },
                        {
                            "featureType": "road.highway",
                            "elementType": "geometry.fill",
                            "stylers": [{"color": "#ffffff"}]
                        },
                        {
                            "featureType": "road.local",
                            "elementType": "geometry.fill",
                            "stylers": [{"visibility": "on"}, {"color": "#ffffff"}, {"weight": 1.8}]
                        },
                        {
                            "featureType": "road.local",
                            "elementType": "geometry.stroke",
                            "stylers": [{"color": "#d7d7d7"}]
                        },
                        {
                            "featureType": "poi",
                            "elementType": "geometry.fill",
                            "stylers": [{"visibility": "on"}, {"color": "#ebebeb"}]
                        },
                        {
                            "featureType": "administrative",
                            "elementType": "geometry",
                            "stylers": [{"color": "#a7a7a7"}]
                        },
                        {
                            "featureType": "road.arterial",
                            "elementType": "geometry.fill",
                            "stylers": [{"color": "#ffffff"}]
                        },
                        {
                            "featureType": "landscape",
                            "elementType": "geometry.fill",
                            "stylers": [{"visibility": "on"}, {"color": "#efefef"}]
                        },
                        {
                            "featureType": "road",
                            "elementType": "labels.text.fill",
                            "stylers": [{"color": "#696969"}]
                        },
                        {
                            "featureType": "administrative",
                            "elementType": "labels.text.fill",
                            "stylers": [{"visibility": "on"}, {"color": "#737373"}]
                        },
                        {
                            "featureType": "poi",
                            "elementType": "labels.icon",
                            "stylers": [{"visibility": "off"}]
                        },
                        {
                            "featureType": "poi",
                            "elementType": "labels",
                            "stylers": [{"visibility": "off"}]
                        },
                        {
                            "featureType": "road.arterial",
                            "elementType": "geometry.stroke",
                            "stylers": [{"color": "#d6d6d6"}]
                        },
                        {
                            "featureType": "road",
                            "elementType": "labels.icon",
                            "stylers": [{"visibility": "off"}]
                        },
                        {
                            "featureType": "poi",
                            "elementType": "geometry.fill",
                            "stylers": [{"color": "#dadada"}]
                        }
                    ]
                };

                var map = new google.maps.Map(document.getElementById(mapId), mapOptions);
                
                // Create custom marker
                var marker = new google.maps.Marker({
                    position: latlng,
                    map: map,
                    title: placeName
                });

            } catch (e) {
                console.error('Error initializing map for ' + placeName + ':', e);
                document.getElementById(mapId).innerHTML = '<p class="text-muted text-center p-3">Unable to display map: ' + e.message + '</p>';
            }
        }

        function formatText(text) {
            return text.replace(/\n/g, '<br>');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showLoading() {
            loadingDiv.show();
        }

        function hideLoading() {
            loadingDiv.hide();
        }

        function showResults() {
            resultsDiv.show();
        }

        function hideResults() {
            resultsDiv.hide();
        }

        function showError(message) {
            errorDiv.find('.error-message').text(message);
            errorDiv.show();
        }

        function hideError() {
            errorDiv.hide();
        }

        <?php if ($showDebugPanel): ?>
        function populateDebugPanel(debugData, responseText = null) {
            // Populate Intent Analysis (from combined response)
            const classificationDiv = resultsDiv.find('.debug-classification');
            let classificationHtml = '';
            
            if (debugData.intent_analysis || debugData.query_classification) {
                const intent = debugData.intent_analysis || debugData.query_classification;
                classificationHtml = `
                    <div class="debug-item">
                        <span class="debug-label">Intent Type:</span>
                        <span class="debug-value">${intent.intent_type || intent.primary_intent || 'unknown'}</span>
                    </div>
                    <div class="debug-item">
                        <span class="debug-label">Confidence:</span>
                        <span class="debug-value">${intent.confidence ? (intent.confidence * 100).toFixed(1) + '%' : 'Not provided'}</span>
                    </div>
                    <div class="debug-item">
                        <span class="debug-label">Service Area:</span>
                        <span class="debug-value">${intent.service_area || intent.service_detected || 'None detected'}</span>
                    </div>
                    <div class="debug-item">
                        <span class="debug-label">Location:</span>
                        <span class="debug-value">${intent.location_mentioned || intent.location_detected || 'None detected'}</span>
                    </div>
                    ${intent.person_name ? `
                    <div class="debug-item">
                        <span class="debug-label">Person Name:</span>
                        <span class="debug-value text-info"><i class="fas fa-user"></i> ${intent.person_name}</span>
                    </div>
                    ` : ''}
                    ${intent.specialism_id ? `
                    <div class="debug-item">
                        <span class="debug-label">Specialism ID:</span>
                        <span class="debug-value">${intent.specialism_id}</span>
                    </div>
                    ` : ''}
                    <div class="debug-item">
                        <span class="debug-label">Urgency:</span>
                        <span class="debug-value">${intent.urgency_level || intent.urgency || 'medium'}</span>
                    </div>
                    ${intent.key_phrases && intent.key_phrases.length > 0 ? `
                    <div class="debug-item">
                        <span class="debug-label">Key Phrases:</span>
                        <div class="debug-value">
                            ${intent.key_phrases.map(phrase => `<span class="badge bg-light text-dark me-1">${phrase}</span>`).join('')}
                        </div>
                    </div>
                    ` : ''}
                `;
            }
            classificationDiv.html(classificationHtml);
            
            // Populate Response Structure Analysis (strategy section)
            const strategyDiv = resultsDiv.find('.debug-strategy');
            let strategyHtml = '';
            
            if (responseText) {
                const response = responseText;
                // Updated to detect new 4-section structure (no Related Services)
                const hasDirectAnswer = response.includes('DIRECT ANSWER:') || response.match(/^[^:]+(?=\s*OUR CAPABILITIES:|$)/);
                const hasCapabilities = response.includes('OUR CAPABILITIES:');
                const hasPracticalGuidance = response.includes('PRACTICAL GUIDANCE:');
                const hasWhyChooseUs = response.includes('WHY CHOOSE US:');
                
                // Check for any unwanted sections that should not appear
                const hasRelatedServices = response.includes('RELATED SERVICES:');
                const hasUnwantedSections = hasRelatedServices;
                
                const structureScore = [hasDirectAnswer, hasCapabilities, hasPracticalGuidance, hasWhyChooseUs].filter(Boolean).length;
                const totalExpected = 4;
                
                strategyHtml = `
                    <div class="mt-3">
                        <h6 class="text-muted"><i class="fas fa-list-check"></i> Response Structure</h6>
                        <div class="debug-item">
                            <span class="debug-label">Structure Score:</span>
                            <span class="debug-value ${structureScore === totalExpected && !hasUnwantedSections ? 'text-success' : 'text-warning'}">${structureScore}/${totalExpected} sections</span>
                        </div>
                        <div class="debug-item">
                            <span class="debug-label">Sections Found:</span>
                            <div class="debug-value">
                                <span class="badge ${hasDirectAnswer ? 'bg-success' : 'bg-light text-dark'} me-1">Direct Answer</span>
                                ${hasRelatedServices ? '<span class="badge bg-danger text-white me-1">Related Services</span>' : ''}
                                <span class="badge ${hasCapabilities ? 'bg-success' : 'bg-light text-dark'} me-1">Our Capabilities</span>
                                <span class="badge ${hasPracticalGuidance ? 'bg-success' : 'bg-light text-dark'} me-1">Practical Guidance</span>
                                <span class="badge ${hasWhyChooseUs ? 'bg-success' : 'bg-light text-dark'} me-1">Why Choose Us</span>
                            </div>
                        </div>
                        ${hasUnwantedSections ? `
                        <div class="debug-item">
                            <span class="debug-label text-danger">Warning:</span>
                            <span class="debug-value text-danger">Unwanted sections detected - check prompt enforcement</span>
                        </div>
                        ` : ''}
                        <div class="debug-item">
                            <span class="debug-label">Response Length:</span>
                            <span class="debug-value">${response.length} characters</span>
                        </div>
                    </div>
                `;
            }
            strategyDiv.html(strategyHtml);
            
            // Populate AI Input section (middle column - candidate documents)
            const aiInputDiv = resultsDiv.find('.debug-ai-input');
            let candidateDocsHtml = '';
            
            // Show document selection debug info (new AI-based approach)
            if (debugData.document_selection) {
                const docSelection = debugData.document_selection;
                candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>Method:</span> <span class='debug-value'>${escapeHtml(docSelection.selection_method || '')}</span></div>`;
                candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>Total Candidates:</span> <span class='debug-value'>${docSelection.total_candidate_docs || 0}</span></div>`;
                candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>Score Threshold:</span> <span class='debug-value'>≥ ${docSelection.score_threshold || 'N/A'}</span></div>`;
                
                // Show page type distribution
                if (docSelection.page_type_distribution) {
                    candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>Content Types Available:</span></div>`;
                    candidateDocsHtml += `<div class='page-type-distribution'>`;
                    Object.entries(docSelection.page_type_distribution).forEach(([type, count]) => {
                        candidateDocsHtml += `<span class='badge bg-light text-dark me-1'>${escapeHtml(type)}: ${count}</span>`;
                    });
                    candidateDocsHtml += `</div>`;
                }
                
                if (docSelection.candidate_documents && docSelection.candidate_documents.length > 0) {
                    candidateDocsHtml += `<div class='debug-item mt-2'><span class='debug-label'>Candidate Documents:</span></div>`;
                    candidateDocsHtml += `<ul class='debug-rag-documents'>`;
                    docSelection.candidate_documents.forEach(function(doc, index) {
                        candidateDocsHtml += `<li><strong>${index + 1}. ${escapeHtml(doc.title || 'Untitled')}</strong>`;
                        
                        // Show original and boosted scores
                        if (doc.original_score && doc.boost_applied) {
                            candidateDocsHtml += ` <span class='badge bg-warning'>Original: ${doc.original_score.toFixed(3)}</span>`;
                            candidateDocsHtml += ` <span class='badge bg-success'>Boosted: ${doc.score.toFixed(3)}</span>`;
                            candidateDocsHtml += ` <span class='badge bg-info'>+${((doc.score - doc.original_score) * 100).toFixed(1)}%</span>`;
                        } else {
                            candidateDocsHtml += ` <span class='badge bg-info'>Score: ${doc.score.toFixed(3)}</span>`;
                        }
                        
                        if (doc.page_type) candidateDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.page_type)}</span>`;
                        if (doc.source && doc.source === 'database_enhancement') candidateDocsHtml += ` <span class='badge bg-primary'>Enhanced</span>`;
                        if (doc.specialisms) candidateDocsHtml += ` <span class='badge bg-light text-dark'>${escapeHtml(doc.specialisms)}</span>`;
                        if (doc.content_preview) candidateDocsHtml += `<div class='rag-snippet'>${escapeHtml(doc.content_preview)}</div>`;
                        candidateDocsHtml += `</li>`;
                    });
                    candidateDocsHtml += `</ul>`;
                }
            }
            
            // Handle legacy AI input if no document selection data
            if (!debugData.document_selection && debugData.ai_input) {
                candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>Query Sent to AI:</span> <span class='debug-value'>${escapeHtml(debugData.ai_input.query || '')}</span></div>`;
                if (debugData.ai_input.documents && debugData.ai_input.documents.length > 0) {
                    candidateDocsHtml += `<div class='debug-item'><span class='debug-label'>RAG Documents Sent:</span></div>`;
                    candidateDocsHtml += `<ul class='debug-rag-documents'>`;
                    debugData.ai_input.documents.forEach(function(doc) {
                        candidateDocsHtml += `<li><strong>${escapeHtml(doc.title || doc.sourceName || 'Untitled')}</strong>`;
                        if (doc.section) candidateDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.section)}</span>`;
                        if (doc.score !== undefined) candidateDocsHtml += ` <span class='badge bg-info'>Score: ${doc.score.toFixed(2)}</span>`;
                        if (doc.snippet) candidateDocsHtml += `<div class='rag-snippet'>${escapeHtml(doc.snippet)}</div>`;
                        candidateDocsHtml += `</li>`;
                    });
                    candidateDocsHtml += `</ul>`;
                }
            }
            
            aiInputDiv.html(candidateDocsHtml);

            // Populate Document Selection section
            const selectedDocsDiv = resultsDiv.find('.debug-ai-selected');
            const specialistDocsDiv = resultsDiv.find('.debug-specialism-matched');
            let selectedDocsHtml = '';
            let specialistDocsHtml = '';
            
            if (debugData.document_selection && debugData.document_selection.selected_documents && debugData.document_selection.selected_documents.length > 0) {
                const docSelection = debugData.document_selection;
                const isAiSelectionEnabled = docSelection.ai_selection_enabled || false;
                const selectionMethod = docSelection.selection_method || 'unknown';
                
                // All selected documents (whether AI-selected or algorithmically selected)
                const allSelectedDocs = docSelection.selected_documents;
                const specialistMatchedDocs = docSelection.supplementary_content || [];
                
                // Document Selection Results (rename based on actual method used)
                const sectionTitle = isAiSelectionEnabled ? 'AI Selected' : 'Algorithm Selected';
                
                // Update the section header dynamically
                resultsDiv.find('.selection-method-title').html(
                    isAiSelectionEnabled ? 
                        '<i class="fas fa-robot"></i> AI Selected Documents' : 
                        '<i class="fas fa-sort-amount-down"></i> Algorithm Selected Documents'
                );
                
                selectedDocsHtml += `<div class='debug-item'><span class='debug-label'>${sectionTitle} Count:</span> <span class='debug-value'>${allSelectedDocs.length}</span></div>`;
                selectedDocsHtml += `<div class='debug-item'><span class='debug-label'>Selection Method:</span> <span class='debug-value badge ${isAiSelectionEnabled ? 'bg-primary' : 'bg-success'}'>${selectionMethod}</span></div>`;
                
                if (allSelectedDocs.length > 0) {
                    // Show selected content types distribution
                    const selectedTypes = {};
                    allSelectedDocs.forEach(doc => {
                        selectedTypes[doc.page_type] = (selectedTypes[doc.page_type] || 0) + 1;
                    });
                    
                    selectedDocsHtml += `<div class='debug-item'><span class='debug-label'>Content Types:</span></div>`;
                    selectedDocsHtml += `<div class='page-type-distribution'>`;
                    Object.entries(selectedTypes).forEach(([type, count]) => {
                        selectedDocsHtml += `<span class='badge bg-primary text-white me-1'>${escapeHtml(type)}: ${count}</span>`;
                    });
                    selectedDocsHtml += `</div>`;
                    
                    selectedDocsHtml += `<div class='debug-item mt-2'><span class='debug-label'>Selected Documents:</span></div>`;
                    selectedDocsHtml += `<ul class='debug-selected-documents'>`;
                    allSelectedDocs.forEach(function(doc, index) {
                        selectedDocsHtml += `<li>`;
                        
                        // Show AI order if available
                        if (doc.ai_order) {
                            selectedDocsHtml += `<span class='badge bg-info me-1'>AI #${doc.ai_order}</span>`;
                        }
                        
                        selectedDocsHtml += `<strong>${index + 1}. ${escapeHtml(doc.title || 'Untitled')}</strong>`;
                        selectedDocsHtml += ` <span class='badge bg-success'>Score: ${doc.score.toFixed(3)}</span>`;
                        if (doc.page_type) selectedDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.page_type)}</span>`;
                        selectedDocsHtml += ` <span class='badge ${isAiSelectionEnabled ? 'bg-primary' : 'bg-info'}'>${isAiSelectionEnabled ? 'AI Selected' : 'Algorithm'}</span>`;
                        if (doc.selection_reason) selectedDocsHtml += `<div class='selection-reason'><small>${escapeHtml(doc.selection_reason)}</small></div>`;
                        selectedDocsHtml += `</li>`;
                    });
                    selectedDocsHtml += `</ul>`;
                } else {
                    selectedDocsHtml += `<div class='debug-item'><span class='text-muted'>No selected documents</span></div>`;
                }
                
                // Specialist-Matched Documents
                if (debugData.document_selection && debugData.document_selection.supplementary_content && debugData.document_selection.supplementary_content.all_content_debug) {
                    // Use the debug information that contains all 10 articles and case studies
                    const allSupplementaryContent = debugData.document_selection.supplementary_content.all_content_debug;
                    
                    specialistDocsHtml += `<div class='debug-item'><span class='debug-label'>Specialist-Matched Count:</span> <span class='debug-value'>${allSupplementaryContent.length}</span></div>`;
                    
                    if (allSupplementaryContent.length > 0) {
                        // Show content types distribution for all supplementary content
                        const specialistTypes = {};
                        allSupplementaryContent.forEach(doc => {
                            specialistTypes[doc.page_type] = (specialistTypes[doc.page_type] || 0) + 1;
                        });
                        
                        specialistDocsHtml += `<div class='debug-item'><span class='debug-label'>Content Types:</span></div>`;
                        specialistDocsHtml += `<div class='page-type-distribution'>`;
                        Object.entries(specialistTypes).forEach(([type, count]) => {
                            specialistDocsHtml += `<span class='badge bg-warning text-dark me-1'>${escapeHtml(type)}: ${count}</span>`;
                        });
                        specialistDocsHtml += `</div>`;
                        
                        specialistDocsHtml += `<div class='debug-item mt-2'><span class='debug-label'>Specialist Selection:</span></div>`;
                        specialistDocsHtml += `<ul class='debug-selected-documents'>`;
                        allSupplementaryContent.forEach(function(doc, index) {
                            specialistDocsHtml += `<li>`;
                            specialistDocsHtml += `<strong>${index + 1}. ${escapeHtml(doc.title || 'Untitled')}</strong>`;
                            specialistDocsHtml += ` <span class='badge bg-success'>Score: ${doc.score.toFixed(3)}</span>`;
                            if (doc.page_type) specialistDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.page_type)}</span>`;
                            if (doc.selection_reason) specialistDocsHtml += `<div class='selection-reason'><small>${escapeHtml(doc.selection_reason)}</small></div>`;
                            specialistDocsHtml += `</li>`;
                        });
                        specialistDocsHtml += `</ul>`;
                    } else {
                        specialistDocsHtml += `<div class='debug-item'><span class='text-muted'>No specialist-matched content</span></div>`;
                    }
                } else {
                    // Fallback to legacy method if new debug structure not available
                    const specialistMatchedDocs = docSelection.selected_documents ? docSelection.selected_documents.filter(doc => !doc.ai_selected) : [];
                    
                    specialistDocsHtml += `<div class='debug-item'><span class='debug-label'>Specialist-Matched Count:</span> <span class='debug-value'>${specialistMatchedDocs.length}</span></div>`;
                    
                    if (specialistMatchedDocs.length > 0) {
                        // Show specialist-matched content types distribution
                        const specialistTypes = {};
                        specialistMatchedDocs.forEach(doc => {
                            specialistTypes[doc.page_type] = (specialistTypes[doc.page_type] || 0) + 1;
                        });
                        
                        specialistDocsHtml += `<div class='debug-item'><span class='debug-label'>Content Types:</span></div>`;
                        specialistDocsHtml += `<div class='page-type-distribution'>`;
                        Object.entries(specialistTypes).forEach(([type, count]) => {
                            specialistDocsHtml += `<span class='badge bg-warning text-dark me-1'>${escapeHtml(type)}: ${count}</span>`;
                        });
                        specialistDocsHtml += `</div>`;
                        
                        specialistDocsHtml += `<div class='debug-item mt-2'><span class='debug-label'>Specialist Selection:</span></div>`;
                        specialistDocsHtml += `<ul class='debug-selected-documents'>`;
                        specialistMatchedDocs.forEach(function(doc, index) {
                            specialistDocsHtml += `<li>`;
                            specialistDocsHtml += `<strong>${index + 1}. ${escapeHtml(doc.title || 'Untitled')}</strong>`;
                            specialistDocsHtml += ` <span class='badge bg-success'>Score: ${doc.score.toFixed(3)}</span>`;
                            if (doc.page_type) specialistDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.page_type)}</span>`;
                            if (doc.selection_reason) specialistDocsHtml += `<div class='selection-reason'><small>${escapeHtml(doc.selection_reason)}</small></div>`;
                            specialistDocsHtml += `</li>`;
                        });
                        specialistDocsHtml += `</ul>`;
                    } else {
                        specialistDocsHtml += `<div class='debug-item'><span class='text-muted'>No specialist-matched content</span></div>`;
                    }
                }
            } else {
                selectedDocsHtml += `<div class='debug-item'><span class='text-muted'>No AI selection data available</span></div>`;
                specialistDocsHtml += `<div class='debug-item'><span class='text-muted'>No specialist content available</span></div>`;
            }
            
            selectedDocsDiv.html(selectedDocsHtml);
            specialistDocsDiv.html(specialistDocsHtml);

            // Populate performance metrics for optimized single-call process
            const performanceDiv = resultsDiv.find('.debug-performance');
            let performanceHtml = '';
            if (debugData.processing_time_ms) {
                const timeMs = debugData.processing_time_ms;
                const timeColor = timeMs < 2000 ? '#28a745' : timeMs < 4000 ? '#ffc107' : '#dc3545';
                
                performanceHtml = `
                    <div class="performance-time" style="color: ${timeColor}">
                        <i class="fas fa-stopwatch"></i> ${timeMs}ms
                    </div>
                    <small class="text-muted">
                        ${timeMs < 2000 ? 'Excellent performance' : timeMs < 4000 ? 'Good performance' : 'Slower than expected'}
                        • Optimized Single AI Call (Intent + Response Combined)
                    </small>
                `;
                
                // Add detailed timing breakdown if available
                if (debugData.performance_breakdown) {
                    const breakdown = debugData.performance_breakdown;
                    const combinedAiTime = breakdown.combined_ai_call_ms || breakdown.ai_response_ms || 0;
                    const aiPercentage = breakdown.ai_percentage || 0;
                    
                    const isAiSelection = breakdown.rag_detail && breakdown.rag_detail.ai_selection_used;
                    
                    performanceHtml += `
                        <div class="performance-breakdown mt-2">
                            <small class="text-primary d-block mb-2"><strong><i class="fas fa-chart-line"></i> Processing Timeline:</strong></small>
                            
                            <div class="process-steps">
                                <div class="step-item mb-2">
                                    <strong class="text-primary">1. Document Retrieval & Processing: ${Math.round(((breakdown.rag_detail?.vector_retrieval_ms || breakdown.document_retrieval_ms || 0) + (breakdown.rag_detail?.candidate_preparation_ms || 0) + (breakdown.rag_detail?.document_selection_ms || 0) + (breakdown.rag_detail?.result_creation_ms || 0)) * 100) / 100}ms</strong>
                                    <div class="step-detail">
                                        <small class="text-muted">• Vector retrieval: ${Math.round((breakdown.rag_detail?.vector_retrieval_ms || breakdown.document_retrieval_ms || 0) * 100) / 100}ms</small><br>
                                        <small class="text-muted">• Find candidates: ${Math.round((breakdown.rag_detail?.candidate_preparation_ms || 0) * 100) / 100}ms</small><br>
                                        <small class="text-muted">• ${isAiSelection ? 'AI selection' : 'Score ranking'}: ${Math.round((breakdown.rag_detail?.document_selection_ms || 0) * 100) / 100}ms</small><br>
                                        <small class="text-muted">• Result creation: ${Math.round((breakdown.rag_detail?.result_creation_ms || 0) * 100) / 100}ms</small><br>
                                        <br><small class="text-success">⚡ Single retrieval for both AI and page display</small>
                                        ${isAiSelection ? '<br><small class="text-warning">🤖 AI making smart choices</small>' : '<br><small class="text-success">⚡ Fast algorithmic sorting</small>'}
                                    </div>
                                </div>
                                
                                <div class="step-item mb-2">
                                    <strong class="text-success">2. AI Response Generation: ${Math.round((combinedAiTime || 0) * 100) / 100}ms</strong>
                                    <div class="step-detail">
                                        <small class="text-muted">• Query analysis & intent detection</small><br>
                                        <small class="text-muted">• Structured response creation</small><br>
                                        <small class="text-muted">• Action selection</small>
                                    </div>
                                </div>
                                
                                <div class="step-item">
                                    <strong class="text-secondary">3. Supporting Content: ${Math.round((breakdown.supporting_content_ms || ((breakdown.breakdown_detail?.specialists_search_ms || 0) + (breakdown.breakdown_detail?.reviews_search_ms || 0) + (breakdown.breakdown_detail?.places_search_ms || 0) + (breakdown.breakdown_detail?.actions_retrieval_ms || 0))) * 100) / 100}ms</strong>
                                    <div class="step-detail">
                                        <small class="text-muted">• Specialists, reviews, locations & actions</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="efficiency-summary mt-2 p-2 bg-light rounded">
                                <small class="text-success"><strong>AI Processing: ${aiPercentage}% of total time</strong></small><br>
                                <small class="text-muted">${breakdown.optimization_notes || 'Optimized single-call architecture'}</small>
                            </div>
                        </div>
                    `;
                }
            }
            performanceDiv.html(performanceHtml);
        }

        // Toggle debug panel collapse
        $(document).on('click', '.toggle-debug', function() {
            const icon = $(this).find('i');
            const target = $($(this).data('target'));
            
            if (target.hasClass('show')) {
                icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
        <?php endif; // End debug panel conditional ?>
    });
</script>
