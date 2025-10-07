<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php
// Add page type meta tag for search logging context
$currentPage = \Concrete\Core\Page\Page::getCurrentPage();
$pageTypeName = $currentPage ? $currentPage->getPageTypeName() : 'unknown';
$this->addHeaderItem('<meta name="page-type" content="' . h($pageTypeName) . '">');

// Include search actions JavaScript for action button functionality (prevent duplicates)
$searchActionsScript = '
<script>
if (!window.SearchActionsLoaded) {
    window.SearchActionsLoaded = true;
    var script = document.createElement("script");
    script.src = "/packages/katalysis_pro_ai/js/search-actions.js";
    script.async = false;
    document.head.appendChild(script);
}
</script>';
$this->addFooterItem($searchActionsScript);
?>

<div class="katalysis-ai-enhanced-search" data-block-id="<?= $blockID ?>"
    data-search-url="<?= $view->action('perform_search', \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('token')->generate('search_action'), $blockID) ?>"
    data-ai-response-url="<?= $view->action('generate_ai_response', \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('token')->generate('ai_response_action'), $blockID) ?>">
    <!-- Search Input -->
    <div class="search-input-container bg-primary">
        <div class="container-xl py-5">
            <div class="row">
                <div class="col-auto">
                    <img class="me-2"
                        src="/packages/katalysis_pro_ai/blocks/katalysis_ai_enhanced_search/images/chat-bot-icon.svg"
                        alt="<?php echo t('AI Search'); ?>" class="img-fluid">
                    <span class="search-title"><?php echo h($buttonText) ?></span>
                </div>
                <!-- Search Form -->
                <div class="search-form-container col">
                    <form class="search-form" onsubmit="return false;">
                        <div class="search-input-group">
                            <input type="text" name="query" class="form-control search-input w-100"
                                placeholder="<?= h($searchPlaceholder) ?>" autocomplete="off"
                                data-typing="<?= $enableTyping ? 'true' : 'false' ?>">
                            <button class="btn btn-primary search-button" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> <!-- Loading States -->
    <div class="search-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Searching...</div>
    </div>

    <!-- Search Results Container -->
    <div class="container-xl">

        <div class="search-results py-4" style="display: none;">

            <!-- Debug Panel -->
            <?php if (isset($enableDebugPanel) && $enableDebugPanel): ?>
            <div class="debug-panel mb-4">
                <div class="card">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-bug"></i> Enhanced AI Search Debug Information
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- AI Query Analysis Section -->
                        <div class="ai-analysis-section mb-3">
                            <div class="debug-line mb-2">
                                <strong>Query:</strong>
                                "<span class="original-query text-primary"></span>" →
                                <span class="enhanced-query text-info"></span>
                            </div>
                            <div class="debug-line mb-2">
                                <strong>Primary Intent:</strong>
                                <span class="detected-category text-success"></span>
                                <small class="text-muted">(How AI categorized the main purpose of your query)</small>
                            </div>
                            <div class="debug-line mb-2">
                                <strong>Search Strategy:</strong>
                                <span class="search-strategy text-muted">Multi-target AI approach</span>
                                <small class="text-muted ms-2">(Parallel search across specialists, places, and content
                                    for comprehensive results)</small>
                            </div>
                            <!-- Three-Column AI Analysis -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="analysis-card border rounded p-2 mb-2">
                                        <h6 class="mb-1 text-success"><i class="fas fa-user-tie"></i> Specialism</h6>
                                        <div class="detected-specialism text-success fw-bold">-</div>
                                        <small class="text-muted specialism-confidence"></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="analysis-card border rounded p-2 mb-2">
                                        <h6 class="mb-1 text-info"><i class="fas fa-user"></i> Person</h6>
                                        <div class="detected-person text-info fw-bold">-</div>
                                        <small class="text-muted person-confidence"></small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="analysis-card border rounded p-2 mb-2">
                                        <h6 class="mb-1 text-warning"><i class="fas fa-map-marker-alt"></i> Place</h6>
                                        <div class="detected-place text-warning fw-bold">-</div>
                                        <small class="text-muted place-confidence"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Available Options & Processing Section -->
                        <div class="processing-section">
                            <div class="row">
                                <!-- Specialism Processing -->
                                <div class="col-md-4">
                                    <div class="processing-card border rounded p-2 mb-2">
                                        <h6 class="mb-2 text-success"><i class="fas fa-list"></i> Specialism Processing
                                        </h6>
                                        <div class="available-specialisms-section mb-2">
                                            <small class="text-muted d-block">Available Options:</small>
                                            <div class="available-specialisms small">Loading...</div>
                                        </div>
                                        <div class="specialism-outcome">
                                            <small class="text-muted d-block">Selection Process:</small>
                                            <div class="specialism-selection-method text-info small"></div>
                                            <div class="specialism-selection-reason text-muted small"></div>
                                        </div>
                                        <div class="content-results mt-2">
                                            <small class="text-muted d-block">Content Results:</small>
                                            <div class="total-results-summary small">
                                                <span class="total-results text-warning">0</span> pages/articles found
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Person Processing -->
                                <div class="col-md-4">
                                    <div class="processing-card border rounded p-2 mb-2">
                                        <h6 class="mb-2 text-info"><i class="fas fa-users"></i> Person Processing</h6>
                                        <div class="available-people-section mb-2">
                                            <small class="text-muted d-block">Available Options:</small>
                                            <div class="available-people-summary small">
                                                <span class="available-people-count text-warning">0</span> people
                                            </div>
                                            <div class="available-people-names text-muted small"></div>
                                        </div>
                                        <div class="people-outcome">
                                            <small class="text-muted d-block">Selection Process:</small>
                                            <div class="specialist-selection-method text-success small"></div>
                                            <div class="specialist-selection-priority text-info small"></div>
                                            <div class="specialist-selection-fallback text-warning small"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Place Processing -->
                                <div class="col-md-4">
                                    <div class="processing-card border rounded p-2 mb-2">
                                        <h6 class="mb-2 text-warning"><i class="fas fa-building"></i> Place Processing
                                        </h6>
                                        <div class="available-places-section mb-2">
                                            <small class="text-muted d-block">Available Options:</small>
                                            <div class="available-places-summary small">
                                                <span class="available-places-count text-warning">0</span> places
                                            </div>
                                            <div class="available-places-names text-muted small"></div>
                                        </div>
                                        <div class="places-outcome">
                                            <small class="text-muted d-block">Selection Process:</small>
                                            <div class="places-selection-method text-info small"></div>
                                            <div class="places-selection-reason text-muted small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <strong>Performance:</strong>
                        <span class="performance-breakdown text-secondary">Ready for search</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>




            <?php $showSpecialists = true; // For testing - replace with config ?>
            <?php $showReviews = true; // For testing - replace with config ?>
            <?php $enableAsyncLoading = true; // For testing - replace with config ?>
            <!-- Search Results Sections -->
            <div class="search-sections">

                <div class="row justify-content-between">
                    <!-- Main Results Column -->
                    <div class="<?php echo ($showSpecialists || $showReviews) ? 'col-md-7' : 'col-12' ?>">

                        <!-- AI Response Section -->
                        <div class="ai-response-section">
                            <div class="ai-response-content">
                                <!-- AI generated response will be inserted here -->
                            </div>

                            <!-- Actions Section -->
                            <div class="actions-section bg-gradient-primary" style="display: none;">
                                <h3>How We Can Help</h3>
                                <div class="actions-grid"></div>
                            </div>
                        </div>

                        <div class="main-results">

                            <!-- Our Services (Fallback) -->
                            <div class="result-section our-services" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-briefcase"></i>
                                    Our Services
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- About Us (Fallback) -->
                            <div class="result-section about-us" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-users"></i>
                                    About Us
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- Legal Service Pages -->
                            <div class="result-section legal-service-pages" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-balance-scale"></i>
                                    Legal Service Pages
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- Category Pages -->
                            <div class="result-section category-pages" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-folder"></i>
                                    Service Categories
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- Calculators & Tools -->
                            <div class="result-section calculators" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-calculator"></i>
                                    Calculators & Tools
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- Guides & Resources -->
                            <div class="result-section guides" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-book"></i>
                                    Guides & Resources
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>

                            <!-- Articles & Case Studies -->
                            <div class="result-section articles" style="display: none;">
                                <h4 class="result-section-title">
                                    <i class="fas fa-file-alt"></i>
                                    Articles & Case Studies
                                    <span class="result-count" style="display: none;"></span>
                                </h4>
                                <div class="results-grid"></div>
                            </div>
                        </div>

                    </div>
                    <!-- Sidebar Column -->
                    <?php if ($showSpecialists || $showReviews): ?>
                        <div class="col-md-4">
                            <div class="search-sidebar">

                                <!-- Supporting Content (Async Loaded) -->
                                <?php if ($enableAsyncLoading): ?>
                                    <div class="supporting-content" style="display: none;">

                                        <!-- People (Specialists) -->
                                        <div class="support-section people-section" style="display: none;">
                                            <h4>
                                                Recommended Specialists
                                            </h4>
                                            <div class="people-grid"></div>
                                        </div>

                                        <!-- Places (Offices) -->
                                        <div class="support-section places-section" style="display: none;">
                                            <h4>
                                                Our Offices
                                            </h4>
                                            <div class="places-grid"></div>
                                        </div>

                                        <!-- Reviews -->
                                        <div class="support-section reviews-section" style="display: none;">
                                            <h4>
                                                Relevant Reviews
                                            </h4>
                                            <div class="reviews-grid"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Performance Info (Debug) -->
                <div class="performance-info" style="display: none;">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i>
                        Search completed in <span class="performance-time">0ms</span>
                    </small>
                </div>
            </div>

            <!-- Error Messages -->
            <div class="search-error alert alert-danger" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="error-message">Search failed. Please try again.</span>
            </div>
        </div>
    </div>

    <!-- Include required assets -->
    <?php
    $view->requireAsset('javascript', 'jquery');
    $view->requireAsset('css', 'font-awesome');

    // Set correct block path
    $blockPath = '/packages/katalysis_pro_ai/blocks/katalysis_ai_enhanced_search';
    $jsPath = $blockPath . '/js/search.js';
    $cssPath = $blockPath . '/css/search.css';

    // Add Google Maps API and JavaScript initialization in footer
    $config = \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('config');
    $googleMapsApiKey = $config->get('katalysis.pro.google_maps_api_key');
    $initScript = '
<!-- Google Maps API -->
<script src="//maps.google.com/maps/api/js?key=' . $googleMapsApiKey . '" async defer></script>
<script>
console.log("=== Enhanced AI Search Block Debug ===");
console.log("Block ID: ' . $blockID . '");
console.log("JS Path: ' . $jsPath . '");
console.log("CSS Path: ' . $cssPath . '");
console.log("Google Maps API Key:", "' . ($googleMapsApiKey ? 'CONFIGURED' : 'NOT SET') . '");
console.log("Container exists:", document.querySelector("[data-block-id=\'' . $blockID . '\']") ? "YES" : "NO");
console.log("Search URL:", document.querySelector("[data-block-id=\'' . $blockID . '\']")?.getAttribute("data-search-url"));
</script>
<script src="' . $jsPath . '?v=' . time() . '" onerror="console.error(\'Failed to load search.js from: ' . $jsPath . '\')"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Debug logging - only in development
    const debugMode = ' . (\Concrete\Core\Support\Facade\Config::get('katalysis.debug.enabled', false) ? 'true' : 'false') . ';
    
    if (debugMode) {
        console.log("DOM loaded, checking for KatalysisEnhancedSearch class...");
    }
    
    if (typeof KatalysisEnhancedSearch === "undefined") {
        console.error("KatalysisEnhancedSearch class not found - JavaScript file may not have loaded");
        return;
    }
    
    if (debugMode) {
        console.log("Initializing KatalysisEnhancedSearch for block ' . $blockID . '");
    }
    
    try {
        new KatalysisEnhancedSearch(' . $blockID . ', {
            enableTyping: ' . ($enableTyping ? 'true' : 'false') . ',
            showResultCount: ' . ($showResultCount ? 'true' : 'false') . ',
            enableAsyncLoading: ' . ($enableAsyncLoading ? 'true' : 'false') . '
        });
        
        if (debugMode) {
            console.log("KatalysisEnhancedSearch initialized successfully");
        }
    } catch (error) {
        console.error("Failed to initialize KatalysisEnhancedSearch:", error);
    }
});
</script>';

    $view->addFooterItem($initScript);
    ?>
