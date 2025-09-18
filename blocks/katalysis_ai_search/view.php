<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<style>
    .katalysis-ai-search .page-type-badge {
        display: inline-block;
        padding: 2px 8px;
        font-size: 0.75em;
        border-radius: 12px;
        margin-bottom: 5px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .katalysis-ai-search .page-type-badge.legal-service {
        background-color: #007bff;
        color: white;
    }

    .katalysis-ai-search .page-type-badge.general {
        background-color: #6c757d;
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
        color: #007bff;
        text-decoration: none;
    }

    .katalysis-ai-search .page-result-item h6 a:hover {
        text-decoration: underline;
    }

    .katalysis-ai-search .page-snippet {
        margin: 8px 0;
        color: #666;
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

    .katalysis-ai-search .page-link {
        margin: 8px 0 0 0;
        font-size: 0.9em;
    }

    .katalysis-ai-search .page-link a {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }

    .katalysis-ai-search .page-link a:hover {
        text-decoration: underline;
    }

    .katalysis-ai-search .specialist-card,
    .katalysis-ai-search .review-card {
        margin-bottom: 15px;
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 6px;
        background: #f8f9fa;
    }

    .katalysis-ai-search .specialist-card h6 {
        margin: 0 0 5px 0;
        color: #007bff;
    }

    .katalysis-ai-search .specialist-title {
        margin: 0 0 3px 0;
        font-weight: 500;
        color: #495057;
    }

    .katalysis-ai-search .specialist-expertise {
        margin: 0 0 8px 0;
        font-size: 0.9em;
        color: #6c757d;
    }

    .katalysis-ai-search .specialist-office {
        margin: 0;
        font-size: 0.85em;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .katalysis-ai-search .specialist-office i {
        font-size: 0.8em;
        margin-right: 2px;
    }

    .katalysis-ai-search .office-name {
        font-weight: 500;
    }

    .katalysis-ai-search .office-location {
        color: #6c757d;
        font-style: italic;
    }

    .katalysis-ai-search .specialist-match-reason {
        margin: 5px 0 0 0;
        font-size: 0.8em;
        color: #6f42c1;
        font-style: italic;
        display: flex;
        align-items: flex-start;
        gap: 4px;
    }

    .katalysis-ai-search .specialist-match-reason i {
        font-size: 0.8em;
        margin-right: 2px;
        color: #6f42c1;
        margin-top: 2px;
    }

    .katalysis-ai-search .review-card blockquote {
        margin: 0 0 8px 0;
        font-style: italic;
        color: #495057;
    }

    .katalysis-ai-search .review-card cite {
        font-size: 0.9em;
        color: #6c757d;
    }

    .katalysis-ai-search .rating {
        margin-top: 5px;
    }

    .katalysis-ai-search .rating i {
        color: #ffc107;
        margin-right: 2px;
    }

    .katalysis-ai-search .place-distance {
        margin: 5px 0 0 0;
        font-size: 0.85em;
        color: #17a2b8;
        display: flex;
        align-items: center;
        gap: 4px;
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
            <div class="search-results-container" style="display: none;">
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
                                     <h6><i class="fas fa-robot"></i> AI Selected Documents</h6>
                                     <div class="debug-ai-selected">
                                        <!-- AI Selected documents will be inserted here -->
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
                
                <div class="row">
                    <!-- Main Results Column -->
                    <div class="<?php echo ($showSpecialists || $showReviews) ? 'col-md-8' : 'col-12' ?>">
                        <div class="main-results">
                            <div class="ai-response-section">
                                <h4><?php echo t('AI Response') ?></h4>
                                <div class="ai-response-content">
                                    <!-- AI generated response will be inserted here -->
                                </div>
                            </div>

                            <div class="page-results-section">
                                <h5><?php echo t('Relevant Pages') ?></h5>
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
                                        <h5><?php echo t('Recommended Specialists') ?></h5>
                                        <div class="specialists-list">
                                            <!-- Specialists will be inserted here -->
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="places-section">
                                    <h5><?php echo t('Our Locations') ?></h5>
                                    <div class="places-list">
                                        <!-- Places will be inserted here -->
                                    </div>
                                </div>

                                <?php if ($showReviews): ?>
                                    <div class="reviews-section">
                                        <h5><?php echo t('Relevant Reviews') ?></h5>
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
            aiResponseSection.html(formatAiResponse(data.response));

            // FIXED: Display AI-prioritized pages immediately from main search response
            if (data.pages && data.pages.length > 0) {
                pageResultsList.html(formatPageResults(data.pages));
                console.log('Displaying AI-prioritized pages:', data.pages.length, 'documents');
            } else {
                pageResultsList.html('<div class="no-results">No relevant pages found</div>');
            }

            // Show loading placeholders for other sections only
            const specialistsList = resultsDiv.find('.specialists-list');
            const reviewsList = resultsDiv.find('.reviews-list');
            const placesList = searchBlock.find('.places-list');

            specialistsList.html('<div class="loading-placeholder"><i class="fas fa-spinner fa-spin"></i> Finding specialists...</div>');
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
                
                console.log('All async content loaded (using AI-prioritized pages from main search)');
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
            // DEBUG: Log all the data received from backend
            console.log('Display results called with data:', data);
            console.log('Specialists data:', data.specialists);
            console.log('Reviews data:', data.reviews);

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
            aiResponseSection.html(formatAiResponse(data.response));

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
            console.log('Places data:', data.places);
            console.log('Places length:', data.places ? data.places.length : 'undefined');
            if (data.places && data.places.length > 0) {
                console.log('Showing places section');
                placesList.html(formatPlaces(data.places));
                searchBlock.find('.places-section').show();
            } else {
                console.log('Hiding places section');
                searchBlock.find('.places-section').hide();
            }

            showResults();
        }

        function formatAiResponse(response) {
            return '<div class="ai-response">' +
                '<div class="response-content">' + formatText(response) + '</div>' +
                '</div>';
        }

        function formatPageResults(pages) {
            if (!pages || pages.length === 0) {
                return '<p class="no-results"><?php echo addslashes(t('No relevant pages found.')) ?></p>';
            }

            let html = '<ul class="page-results">';
            pages.forEach(function (page) {
                html += '<li class="page-result-item">';

                // Add page type badge if provided
                if (page.badge) {
                    const badgeClass = (page.type === 'legal_service_index' || page.type === 'legal_service') ? 'legal-service' : 'general';
                    html += '<span class="page-type-badge ' + badgeClass + '">';
                    html += escapeHtml(page.badge);
                    html += '</span>';
                } else if (page.type && (page.type === 'legal_service_index' || page.type === 'legal_service')) {
                    // Fallback for backward compatibility
                    html += '<span class="page-type-badge legal-service">';
                    html += page.type === 'legal_service_index' ? 'Service Area' : 'Legal Service';
                    html += '</span>';
                }

                if (page.url) {
                    html += '<h6><a href="' + escapeHtml(page.url) + '" target="_blank">' + escapeHtml(page.title) + '</a></h6>';
                } else {
                    html += '<h6>' + escapeHtml(page.title) + '</h6>';
                }

                // Use content instead of snippet (our new structure)
                if (page.content) {
                    html += '<p class="page-snippet">' + escapeHtml(page.content) + '</p>';
                } else if (page.snippet) {
                    html += '<p class="page-snippet">' + escapeHtml(page.snippet) + '</p>';
                }

                if (page.url) {
                    html += '<p class="page-link"><a href="' + escapeHtml(page.url) + '" target="_blank">Read more →</a></p>';
                }

                html += '</li>';
            });
            html += '</ul>';

            return html;
        }

        function formatSpecialists(specialists) {
            // DEBUG: Log the specialists data to see what we're getting
            console.log('Formatting specialists:', specialists);

            let html = '<div class="specialists">';
            specialists.forEach(function (specialist) {
                html += '<div class="specialist-card">';
                html += '<h6>' + escapeHtml(specialist.name) + '</h6>';
                if (specialist.title) {
                    html += '<p class="specialist-title">' + escapeHtml(specialist.title) + '</p>';
                }
                if (specialist.expertise) {
                    html += '<p class="specialist-expertise">' + escapeHtml(specialist.expertise) + '</p>';
                }

                // Add office information if available
                if (specialist.office && (specialist.office.name || specialist.office.town)) {
                    html += '<div class="specialist-office">';
                    html += '<i class="fas fa-map-marker-alt"></i> ';

                    if (specialist.office.name) {
                        html += '<span class="office-name">' + escapeHtml(specialist.office.name) + '</span>';
                        if (specialist.office.town) {
                            html += ', ';
                        }
                    }

                    if (specialist.office.town) {
                        html += '<span class="office-location">' + escapeHtml(specialist.office.town);
                        if (specialist.office.county) {
                            html += ', ' + escapeHtml(specialist.office.county);
                        }
                        html += '</span>';
                    }

                    html += '</div>';
                }

                // Add AI match reasoning if available
                if (specialist.match_reason && specialist.enhanced_by_ai) {
                    html += '<div class="specialist-match-reason">';
                    html += '<i class="fas fa-lightbulb"></i> ';
                    html += escapeHtml(specialist.match_reason);
                    html += '</div>';
                }

                html += '</div>';
            });
            html += '</div>';
            return html;
        }

        function formatReviews(reviews) {
            // DEBUG: Log the reviews data to see what we're getting
            console.log('Formatting reviews:', reviews);

            let html = '<div class="reviews">';
            reviews.forEach(function (review) {
                html += '<div class="review-card">';
                if (review.review) {
                    html += '<blockquote>' + escapeHtml(review.review) + '</blockquote>';
                }
                if (review.client_name) {
                    html += '<cite>— ' + escapeHtml(review.client_name);
                    if (review.organization) {
                        html += ', ' + escapeHtml(review.organization);
                    }
                    html += '</cite>';
                }
                if (review.rating) {
                    html += '<div class="rating">';
                    for (let i = 1; i <= 5; i++) {
                        html += '<i class="fas fa-star' + (i <= review.rating ? '' : '-o') + '"></i>';
                    }
                    html += '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            return html;
        }

        function formatPlaces(places) {
            console.log('Formatting places:', places);

            let html = '<div class="places">';
            places.forEach(function (place) {
                html += '<div class="place-card">';
                if (place.name) {
                    html += '<h6>' + escapeHtml(place.name) + '</h6>';
                }
                if (place.address) {
                    html += '<p class="place-address">' + escapeHtml(place.address) + '</p>';
                }
                if (place.phone) {
                    html += '<p class="place-contact">';
                    html += '<i class="fas fa-phone"></i> ' + escapeHtml(place.phone);
                    html += '</p>';
                }

                // Add AI-provided distance information if available
                if (place.distance_info) {
                    html += '<p class="place-distance">';
                    html += '<i class="fas fa-route"></i> ';
                    html += escapeHtml(place.distance_info);
                    html += '</p>';
                }

                // Add AI match reasoning if available
                if (place.match_reason && place.enhanced_by_ai) {
                    html += '<p class="place-match-reason">';
                    html += '<i class="fas fa-lightbulb"></i> ';
                    html += escapeHtml(place.match_reason);
                    html += '</p>';
                }

                if (place.services && place.services.length > 0) {
                    html += '<p class="place-services">' + escapeHtml(place.services.join(', ')) + '</p>';
                }
                if (place.relevance_score) {
                    html += '<small class="relevance-score">Match: ' + place.relevance_score + '/10</small>';
                }
                html += '</div>';
            });
            html += '</div>';
            return html;
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
            console.log('Populating debug panel with:', debugData);
            
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
                const hasDirectAnswer = response.includes('DIRECT ANSWER:');
                const hasRelatedServices = response.includes('RELATED SERVICES:');
                const hasCapabilities = response.includes('OUR CAPABILITIES:');
                const hasPracticalGuidance = response.includes('PRACTICAL GUIDANCE:');
                const hasWhyChooseUs = response.includes('WHY CHOOSE US:');
                
                const structureScore = [hasDirectAnswer, hasRelatedServices, hasCapabilities, hasPracticalGuidance, hasWhyChooseUs].filter(Boolean).length;
                const totalExpected = 5;
                
                strategyHtml = `
                    <div class="mt-3">
                        <h6 class="text-muted"><i class="fas fa-list-check"></i> Response Structure</h6>
                        <div class="debug-item">
                            <span class="debug-label">Structure Score:</span>
                            <span class="debug-value ${structureScore === totalExpected ? 'text-success' : 'text-warning'}">${structureScore}/${totalExpected} sections</span>
                        </div>
                        <div class="debug-item">
                            <span class="debug-label">Sections Found:</span>
                            <div class="debug-value">
                                <span class="badge ${hasDirectAnswer ? 'bg-success' : 'bg-light text-dark'} me-1">Direct Answer</span>
                                <span class="badge ${hasRelatedServices ? 'bg-success' : 'bg-light text-dark'} me-1">Related Services</span>
                                <span class="badge ${hasCapabilities ? 'bg-success' : 'bg-light text-dark'} me-1">Our Capabilities</span>
                                <span class="badge ${hasPracticalGuidance ? 'bg-success' : 'bg-light text-dark'} me-1">Practical Guidance</span>
                                <span class="badge ${hasWhyChooseUs ? 'bg-success' : 'bg-light text-dark'} me-1">Why Choose Us</span>
                            </div>
                        </div>
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

            // Populate AI Selected Documents section (separated from specialist content)
            const selectedDocsDiv = resultsDiv.find('.debug-ai-selected');
            const specialistDocsDiv = resultsDiv.find('.debug-specialism-matched');
            let selectedDocsHtml = '';
            let specialistDocsHtml = '';
            
            if (debugData.document_selection && debugData.document_selection.selected_documents && debugData.document_selection.selected_documents.length > 0) {
                const docSelection = debugData.document_selection;
                
                // Separate AI-selected from specialist-matched documents
                const aiSelectedDocs = docSelection.selected_documents.filter(doc => doc.ai_selected);
                const specialistMatchedDocs = docSelection.selected_documents.filter(doc => !doc.ai_selected);
                
                // AI Selected Documents
                selectedDocsHtml += `<div class='debug-item'><span class='debug-label'>AI Selected Count:</span> <span class='debug-value'>${aiSelectedDocs.length}</span></div>`;
                
                if (aiSelectedDocs.length > 0) {
                    // Show AI-selected content types distribution
                    const aiTypes = {};
                    aiSelectedDocs.forEach(doc => {
                        aiTypes[doc.page_type] = (aiTypes[doc.page_type] || 0) + 1;
                    });
                    
                    selectedDocsHtml += `<div class='debug-item'><span class='debug-label'>AI Content Types:</span></div>`;
                    selectedDocsHtml += `<div class='page-type-distribution'>`;
                    Object.entries(aiTypes).forEach(([type, count]) => {
                        selectedDocsHtml += `<span class='badge bg-primary text-white me-1'>${escapeHtml(type)}: ${count}</span>`;
                    });
                    selectedDocsHtml += `</div>`;
                    
                    selectedDocsHtml += `<div class='debug-item mt-2'><span class='debug-label'>AI Selection:</span></div>`;
                    selectedDocsHtml += `<ul class='debug-selected-documents'>`;
                    aiSelectedDocs.forEach(function(doc, index) {
                        selectedDocsHtml += `<li>`;
                        
                        // Show AI order if available
                        if (doc.ai_order) {
                            selectedDocsHtml += `<span class='badge bg-info me-1'>AI #${doc.ai_order}</span>`;
                        }
                        
                        selectedDocsHtml += `<strong>${index + 1}. ${escapeHtml(doc.title || 'Untitled')}</strong>`;
                        selectedDocsHtml += ` <span class='badge bg-success'>Score: ${doc.score.toFixed(3)}</span>`;
                        if (doc.page_type) selectedDocsHtml += ` <span class='badge bg-secondary'>${escapeHtml(doc.page_type)}</span>`;
                        selectedDocsHtml += ` <span class='badge bg-primary'>AI Selected</span>`;
                        if (doc.selection_reason) selectedDocsHtml += `<div class='selection-reason'><small>${escapeHtml(doc.selection_reason)}</small></div>`;
                        selectedDocsHtml += `</li>`;
                    });
                    selectedDocsHtml += `</ul>`;
                } else {
                    selectedDocsHtml += `<div class='debug-item'><span class='text-muted'>No AI-selected documents</span></div>`;
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
                    
                    performanceHtml += `
                        <div class="performance-breakdown mt-2">
                            <small class="text-success d-block mb-1"><strong><i class="fas fa-rocket"></i> Optimized Process Breakdown:</strong></small>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-success"><strong>✓ Single AI Call: ${combinedAiTime}ms</strong></small><br>
                                    <small class="text-muted">• Intent analysis included</small><br>
                                    <small class="text-muted">• Response generation included</small><br>
                                    <small class="text-success">• <strong>Replaces 2-step approach</strong></small><br>
                                    <small class="text-info">${breakdown.optimization_notes || 'Optimized for performance'}</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">• RAG Documents: ${breakdown.rag_documents_ms || 0}ms</small><br>
                                    <small class="text-muted">• Specialists: ${breakdown.specialists_search_ms || 0}ms</small><br>
                                    <small class="text-muted">• Reviews: ${breakdown.reviews_search_ms || 0}ms</small><br>
                                    <small class="text-muted">• Places: ${breakdown.places_search_ms || 0}ms</small><br>
                                    <small class="text-success"><strong>AI Efficiency: ${aiPercentage}% of total</strong></small>
                                </div>
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
