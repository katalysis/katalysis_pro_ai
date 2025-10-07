/**
 * Enhanced AI Search Frontend Controller
 * Handles progressive search loading and UI management
 */
class KatalysisEnhancedSearch {
    constructor(blockId, options = {}) {
        console.log('KatalysisEnhancedSearch constructor called with blockId:', blockId);
        
        this.blockId = blockId;
        this.options = {
            enableTyping: true,
            showResultCount: true,
            enableAsyncLoading: true,
            searchDelay: 300,
            typingSpeed: 50,
            ...options
        };
        
        this.container = document.querySelector(`[data-block-id="${blockId}"]`);
        if (!this.container) {
            console.error('KatalysisEnhancedSearch: Container not found for block ID:', blockId);
            return;
        }
        
        this.searchInput = this.container.querySelector('.search-input');
        this.searchForm = this.container.querySelector('.search-form');
        this.loadingDiv = this.container.querySelector('.search-loading');
        this.resultsDiv = this.container.querySelector('.search-results');
        this.errorDiv = this.container.querySelector('.search-error');
        
        if (!this.searchInput || !this.searchForm) {
            console.error('KatalysisEnhancedSearch: Required elements not found');
            return;
        }
        
        this.searchTimeout = null;
        this.currentQuery = '';
        this.isSearching = false;
        
        console.log('KatalysisEnhancedSearch: All elements found, initializing...');
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupRoutes();
    }
    
    bindEvents() {
        // Search form submission - intercept and convert to AJAX
        this.searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.performSearch();
        });
        
        // Real-time search on input
        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            if (query.length >= 3) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, this.options.searchDelay);
            } else if (query.length === 0) {
                this.hideResults();
            }
        });
        
        // Handle Enter key in search input
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });
    }
    
    setupRoutes() {
        // Get URLs from data attributes
        this.searchUrl = this.container.dataset.searchUrl;
        this.supportingContentUrl = this.container.dataset.supportUrl;
        this.csrfToken = this.container.dataset.csrfToken;
        
        // Debug: Log the URLs being used
        console.log('KatalysisEnhancedSearch - Block ID:', this.blockId);
        console.log('KatalysisEnhancedSearch - Search URL:', this.searchUrl);
        console.log('KatalysisEnhancedSearch - Supporting Content URL:', this.supportingContentUrl);
        console.log('KatalysisEnhancedSearch - CSRF Token:', this.csrfToken ? 'Present' : 'Missing');
    }
    
    async performSearch(query = null) {
        if (this.isSearching) return;
        
        const searchQuery = query || this.searchInput.value.trim();
        if (!searchQuery) return;
        
        this.currentQuery = searchQuery;
        this.isSearching = true;
        
        try {
            // Show loading state
            this.showLoading();
            
            // Phase 1: Fast search (Typesense + AI response)
            const searchData = new FormData();
            searchData.append('query', searchQuery);
            searchData.append('ccm_token', this.csrfToken);
            
            console.log('KatalysisEnhancedSearch - Making request to:', this.searchUrl);
            console.log('KatalysisEnhancedSearch - Request data:', {
                query: searchQuery,
                token: CCM_SECURITY_TOKEN ? 'Present' : 'Missing'
            });

            const response = await fetch(this.searchUrl, {
                method: 'POST',
                body: searchData
            });
            
            console.log('KatalysisEnhancedSearch - Response status:', response.status, response.statusText);

            if (!response.ok) {
                // Try to get error details from response
                let errorDetails = `HTTP ${response.status}: ${response.statusText}`;
                try {
                    const errorText = await response.text();
                    console.log('KatalysisEnhancedSearch - Error response body:', errorText);
                    errorDetails += '\n' + errorText.substring(0, 500);
                } catch (e) {
                    console.log('KatalysisEnhancedSearch - Could not read error response');
                }
                throw new Error(errorDetails);
            }
            
            const result = await response.json();
            console.log('KatalysisEnhancedSearch - Response data:', result);
            
            if (!result.success) {
                throw new Error(result.error || 'Search failed');
            }
            
            // Display main results immediately
            this.displayMainResults(result);
            
            // Phase 2: Load supporting content asynchronously (if enabled)
            if (this.options.enableAsyncLoading && result.intent) {
                this.loadSupportingContentAsync(searchQuery, result.intent);
            }
            
        } catch (error) {
            console.error('Search error:', error);
            this.showError(error.message);
        } finally {
            this.isSearching = false;
            this.hideLoading();
        }
    }
    
    async loadSupportingContentAsync(query, intent) {
        try {
            const supportingData = new FormData();
            supportingData.append('query', query);
            supportingData.append('intent', JSON.stringify(intent));
            supportingData.append('ccm_token', CCM_SECURITY_TOKEN);
            
            const response = await fetch(this.supportingContentUrl, {
                method: 'POST',
                body: supportingData
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.displaySupportingContent(result);
                }
            }
        } catch (error) {
            console.warn('Supporting content loading failed:', error);
        }
    }
    
    displayMainResults(result) {
        // Clear previous results
        this.clearResults();
        
        // Display AI response
        this.displayAIResponse(result.ai_response, result.actions);
        
        // Display search result categories
        this.displaySearchCategories(result.search_results);
        
        // Show performance info if available
        if (result.performance && result.performance.total_time_ms) {
            this.displayPerformanceInfo(result.performance.total_time_ms);
        }
        
        // Show results container
        this.resultsDiv.style.display = 'block';
        
        // Scroll to results
        this.resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    displayAIResponse(responseText, actions = []) {
        const responseSection = this.container.querySelector('.ai-response-section');
        const responseContent = responseSection.querySelector('.response-text');
        const actionsSection = responseSection.querySelector('.actions-section');
        const actionsGrid = actionsSection.querySelector('.actions-grid');
        
        // Display response with typing effect (if enabled)
        if (this.options.enableTyping && responseText) {
            this.typeText(responseContent, responseText);
        } else {
            responseContent.innerHTML = this.formatResponse(responseText);
        }
        
        // Display actions
        if (actions && actions.length > 0) {
            actionsGrid.innerHTML = '';
            actions.forEach(action => {
                const actionElement = this.createActionElement(action);
                actionsGrid.appendChild(actionElement);
            });
            actionsSection.style.display = 'block';
        }
        
        responseSection.style.display = 'block';
    }
    
    displaySearchCategories(searchResults) {
        if (!searchResults || !searchResults.categories) return;
        
        const categories = searchResults.categories;
        const categoryMappings = {
            'legal_service_pages': 'legal-service-pages',
            'category_pages': 'category-pages',
            'calculators': 'calculators',
            'guides': 'guides',
            'articles': 'articles'
        };
        
        Object.entries(categories).forEach(([key, categoryData]) => {
            const sectionClass = categoryMappings[key];
            if (!sectionClass) return;
            
            const section = this.container.querySelector(`.result-section.${sectionClass}`);
            if (!section) return;
            
            const resultsGrid = section.querySelector('.results-grid');
            const resultCount = section.querySelector('.result-count');
            
            // Clear previous results
            resultsGrid.innerHTML = '';
            
            // Add results
            if (categoryData.items && categoryData.items.length > 0) {
                categoryData.items.forEach(item => {
                    const resultElement = this.createResultElement(item);
                    resultsGrid.appendChild(resultElement);
                });
                
                // Update count
                if (this.options.showResultCount && resultCount) {
                    resultCount.textContent = `(${categoryData.count || categoryData.items.length})`;
                    resultCount.style.display = 'inline';
                }
                
                section.style.display = 'block';
            }
        });
    }
    
    displaySupportingContent(result) {
        const supportingDiv = this.container.querySelector('.supporting-content');
        if (!supportingDiv) return;
        
        // Display people (specialists)
        if (result.people && result.people.length > 0) {
            this.displayPeopleSection(result.people);
        }
        
        // Display places
        if (result.places && result.places.length > 0) {
            this.displayPlacesSection(result.places);
        }
        
        // Display reviews
        if (result.reviews && result.reviews.length > 0) {
            this.displayReviewsSection(result.reviews);
        }
        
        supportingDiv.style.display = 'block';
    }
    
    createResultElement(item) {
        const div = document.createElement('div');
        div.className = 'search-result-item';
        
        div.innerHTML = `
            <div class="result-header">
                <h5 class="result-title">
                    <a href="${this.escapeHtml(item.url)}" class="result-link">
                        ${this.escapeHtml(item.title)}
                    </a>
                </h5>
            </div>
            ${item.snippet ? `<div class="result-snippet">${this.escapeHtml(item.snippet)}</div>` : ''}
        `;
        
        return div;
    }
    
    createActionElement(action) {
        const div = document.createElement('div');
        div.className = 'action-item';
        
        div.innerHTML = `
            <div class="action-card" data-action-id="${action.id}">
                <div class="action-icon">
                    <i class="${action.icon || 'fas fa-arrow-right'}"></i>
                </div>
                <div class="action-content">
                    <h6 class="action-title">${this.escapeHtml(action.name)}</h6>
                    <p class="action-description">${this.escapeHtml(action.triggerInstruction || '')}</p>
                </div>
            </div>
        `;
        
        // Add click handler
        div.addEventListener('click', () => {
            this.triggerAction(action);
        });
        
        return div;
    }
    
    formatResponse(text) {
        if (!text) return '';
        
        // Convert section headers to HTML
        return text.replace(/^([A-Z][A-Z\s]+):/gm, '<h5 class="response-section-header">$1:</h5>')
                  .replace(/\n/g, '<br>');
    }
    
    typeText(element, text, speed = null) {
        const typingSpeed = speed || this.options.typingSpeed;
        element.innerHTML = '';
        
        let index = 0;
        const formattedText = this.formatResponse(text);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = formattedText;
        const plainText = tempDiv.textContent || tempDiv.innerText;
        
        const typeInterval = setInterval(() => {
            if (index < plainText.length) {
                element.innerHTML = this.formatResponse(plainText.substring(0, index + 1));
                index++;
            } else {
                clearInterval(typeInterval);
                element.innerHTML = formattedText; // Final formatted version
            }
        }, typingSpeed);
    }
    
    showLoading() {
        this.loadingDiv.style.display = 'block';
        this.resultsDiv.style.display = 'none';
        this.errorDiv.style.display = 'none';
    }
    
    hideLoading() {
        this.loadingDiv.style.display = 'none';
    }
    
    showError(message) {
        this.errorDiv.querySelector('.error-message').textContent = message;
        this.errorDiv.style.display = 'block';
        this.resultsDiv.style.display = 'none';
    }
    
    clearResults() {
        this.resultsDiv.style.display = 'none';
        this.errorDiv.style.display = 'none';
        
        // Clear all result sections
        this.container.querySelectorAll('.result-section').forEach(section => {
            section.style.display = 'none';
            const grid = section.querySelector('.results-grid');
            if (grid) grid.innerHTML = '';
        });
        
        // Clear supporting content
        const supportingDiv = this.container.querySelector('.supporting-content');
        if (supportingDiv) {
            supportingDiv.style.display = 'none';
        }
    }
    
    displayPerformanceInfo(timeMs) {
        const performanceDiv = this.container.querySelector('.performance-info');
        const timeSpan = performanceDiv.querySelector('.performance-time');
        if (timeSpan) {
            timeSpan.textContent = `${timeMs}ms`;
            performanceDiv.style.display = 'block';
        }
    }
    
    // Helper methods
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    triggerAction(action) {
        // Handle action triggers - can be extended based on action type
        console.log('Action triggered:', action);
        // TODO: Implement action handling (forms, booking, etc.)
    }
    
    displayPeopleSection(people) {
        // TODO: Implement people display
        console.log('People to display:', people);
    }
    
    displayPlacesSection(places) {
        // TODO: Implement places display
        console.log('Places to display:', places);
    }
    
    displayReviewsSection(reviews) {
        // TODO: Implement reviews display
        console.log('Reviews to display:', reviews);
    }
}

// Auto-initialize if jQuery is available
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Enhanced search blocks will be initialized by their individual view.php files
    });
}
