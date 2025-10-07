<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="katalysis-ai-enhanced-search" id="search-block-<?php echo $blockId ?>">
    <div class="search-form">
        <div class="input-group">
            <input type="text" 
                   class="form-control search-input" 
                   id="search-query-<?php echo $blockId ?>"
                   placeholder="<?php echo h($placeholder) ?>"
                   aria-label="<?php echo t('Search') ?>">
            <button class="btn btn-primary search-button" type="button" id="search-btn-<?php echo $blockId ?>">
                <i class="fas fa-search"></i> <?php echo h($buttonText) ?>
            </button>
        </div>
    </div>

    <div class="search-loading" id="search-loading-<?php echo $blockId ?>" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden"><?php echo t('Loading...') ?></span>
        </div>
        <p><?php echo t('Processing your query...') ?></p>
    </div>

    <div class="search-error" id="search-error-<?php echo $blockId ?>" style="display: none;">
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="error-message"></span>
        </div>
    </div>

    <?php if ($displayMode === 'inline'): ?>
    <div class="search-results" id="search-results-<?php echo $blockId ?>" style="display: none;">
        <div class="ai-response">
            <h3><?php echo t('AI Response') ?></h3>
            <div class="ai-content"></div>
        </div>

        <div class="documents-section" style="display: none;">
            <h4><?php echo t('Related Pages') ?></h4>
            <div class="documents-list"></div>
        </div>

        <?php if ($showSpecialists): ?>
        <div class="specialists-section" style="display: none;">
            <h4><?php echo t('Recommended Specialists') ?></h4>
            <div class="specialists-list"></div>
        </div>
        <?php endif; ?>

        <?php if ($showReviews): ?>
        <div class="reviews-section" style="display: none;">
            <h4><?php echo t('Client Reviews') ?></h4>
            <div class="reviews-list"></div>
        </div>
        <?php endif; ?>

        <?php if ($showPlaces): ?>
        <div class="places-section" style="display: none;">
            <h4><?php echo t('Office Locations') ?></h4>
            <div class="places-list"></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($enableDebug): ?>
    <div class="debug-panel" id="debug-panel-<?php echo $blockId ?>" style="display: none;">
        <h5><?php echo t('Debug Information') ?></h5>
        <pre class="debug-output"></pre>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const blockId = '<?php echo $blockId ?>';
    const displayMode = '<?php echo $displayMode ?>';
    const resultsPageId = <?php echo (int)$resultsPageId ?>;
    const enableDebug = <?php echo $enableDebug ? 'true' : 'false' ?>;

    const searchInput = document.getElementById('search-query-' + blockId);
    const searchBtn = document.getElementById('search-btn-' + blockId);
    const loadingEl = document.getElementById('search-loading-' + blockId);
    const errorEl = document.getElementById('search-error-' + blockId);
    const resultsEl = document.getElementById('search-results-' + blockId);

    function showLoading() {
        loadingEl.style.display = 'block';
        if (resultsEl) resultsEl.style.display = 'none';
        errorEl.style.display = 'none';
    }

    function hideLoading() {
        loadingEl.style.display = 'none';
    }

    function showError(message) {
        errorEl.querySelector('.error-message').textContent = message;
        errorEl.style.display = 'block';
        hideLoading();
    }

    function performSearch() {
        const query = searchInput.value.trim();
        
        if (!query) {
            showError('<?php echo addslashes(t('Please enter a search query')) ?>');
            return;
        }

        if (displayMode === 'redirect' && resultsPageId > 0) {
            // Redirect to results page with query parameter
            window.location.href = '<?php echo \Concrete\Core\Url\Resolver\Manager\ResolverManager::resolve([]) ?>' + 
                                   resultsPageId + '?q=' + encodeURIComponent(query);
            return;
        }

        // Perform inline search
        showLoading();

        const formData = new FormData();
        formData.append('query', query);
        formData.append('block_id', blockId);

        fetch('<?php echo $this->action('search') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (!data.success) {
                showError(data.error || 'Search failed');
                return;
            }

            displayResults(data);
            
            if (enableDebug) {
                displayDebugInfo(data);
            }
        })
        .catch(error => {
            hideLoading();
            showError('Network error: ' + error.message);
        });
    }

    function displayResults(data) {
        if (!resultsEl) return;

        // Display AI response
        const aiContent = resultsEl.querySelector('.ai-content');
        aiContent.innerHTML = '<p>' + escapeHtml(data.ai_response) + '</p>';

        // Display documents
        if (data.documents && data.documents.length > 0) {
            const documentsSection = resultsEl.querySelector('.documents-section');
            const documentsList = resultsEl.querySelector('.documents-list');
            let html = '';
            data.documents.forEach(doc => {
                html += `<div class="document-item">
                    <h5><a href="${escapeHtml(doc.url)}">${escapeHtml(doc.title)}</a></h5>
                    <p>${escapeHtml(doc.content)}</p>
                </div>`;
            });
            documentsList.innerHTML = html;
            documentsSection.style.display = 'block';
        }

        // Display specialists
        if (data.specialists && data.specialists.length > 0) {
            const specialistsSection = resultsEl.querySelector('.specialists-section');
            const specialistsList = resultsEl.querySelector('.specialists-list');
            let html = '';
            data.specialists.forEach(specialist => {
                html += `<div class="specialist-item">
                    <h5><a href="${escapeHtml(specialist.page)}">${escapeHtml(specialist.name)}</a></h5>
                    <p class="job-title">${escapeHtml(specialist.job_title)}</p>
                    <p>${escapeHtml(specialist.short_biography)}</p>
                </div>`;
            });
            specialistsList.innerHTML = html;
            if (specialistsSection) specialistsSection.style.display = 'block';
        }

        // Display reviews
        if (data.reviews && data.reviews.length > 0) {
            const reviewsSection = resultsEl.querySelector('.reviews-section');
            const reviewsList = resultsEl.querySelector('.reviews-list');
            let html = '';
            data.reviews.forEach(review => {
                html += `<div class="review-item">
                    <p class="reviewer-name">${escapeHtml(review.reviewer_name)}</p>
                    <p>${escapeHtml(review.content)}</p>
                </div>`;
            });
            reviewsList.innerHTML = html;
            if (reviewsSection) reviewsSection.style.display = 'block';
        }

        // Display places
        if (data.places && data.places.length > 0) {
            const placesSection = resultsEl.querySelector('.places-section');
            const placesList = resultsEl.querySelector('.places-list');
            let html = '';
            data.places.forEach(place => {
                html += `<div class="place-item">
                    <h5>${escapeHtml(place.name)}</h5>
                    <p>${escapeHtml(place.address)}, ${escapeHtml(place.town)} ${escapeHtml(place.postcode)}</p>
                </div>`;
            });
            placesList.innerHTML = html;
            if (placesSection) placesSection.style.display = 'block';
        }

        resultsEl.style.display = 'block';
    }

    function displayDebugInfo(data) {
        const debugPanel = document.getElementById('debug-panel-' + blockId);
        if (debugPanel) {
            const debugOutput = debugPanel.querySelector('.debug-output');
            debugOutput.textContent = JSON.stringify(data, null, 2);
            debugPanel.style.display = 'block';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Event listeners
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
})();
</script>

<style>
.katalysis-ai-enhanced-search {
    padding: 20px;
}

.search-form {
    margin-bottom: 20px;
}

.search-loading {
    text-align: center;
    padding: 40px;
}

.search-error {
    margin: 20px 0;
}

.search-results {
    margin-top: 30px;
}

.ai-response {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.documents-section,
.specialists-section,
.reviews-section,
.places-section {
    margin-top: 30px;
}

.document-item,
.specialist-item,
.review-item,
.place-item {
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 15px;
}

.debug-panel {
    margin-top: 30px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.debug-output {
    background: white;
    padding: 10px;
    max-height: 400px;
    overflow: auto;
}
</style>
