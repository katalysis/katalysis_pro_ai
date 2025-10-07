<?php
namespace KatalysisProAi;

use CollectionAttributeKey;
use Concrete\Core\Attribute\Key\CollectionKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Feature\Features;
use Concrete\Core\Feature\UsesFeatureInterface;
use Concrete\Core\Page\PageList;
use Concrete\Core\Support\Facade\Core;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Database;

use NeuronAI\RAG\DataLoader\StringDataLoader;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use Concrete\Core\Support\Facade\Config;
use KatalysisProAi\TypesenseVectorStoreFactory;

class PageIndexService {

    public function clearIndex($selectedStores = []): void
    {
        $useTypesense = Config::get('katalysis.search.use_typesense', false);
        
        if ($useTypesense) {
            // For Typesense, clear the specified collections
            $storesToClear = empty($selectedStores) ? ['all_pages'] : $selectedStores;
            
            foreach ($storesToClear as $storeType) {
                try {
                    // Clear the Typesense collection directly without creating a vector store first
                    $this->clearTypesenseCollection($storeType);
                } catch (\Exception $e) {
                    // Log errors but don't echo to UI
                    error_log("Warning: Could not clear collection {$storeType}: " . $e->getMessage());
                }
            }
            return;
        }
        
        // For file-based storage, clear the specified store files
        $storesToClear = empty($selectedStores) ? ['pages'] : $selectedStores;
        
        foreach ($storesToClear as $storeType) {
            $storeFile = DIR_APPLICATION . "/files/neuron/{$storeType}.store";
            if (file_exists($storeFile)) {
                unlink($storeFile);
                error_log("Cleared file store: {$storeType}.store");
            }
        }
    }

    public function buildIndex($selectedStores = [], $pageIds = null)
    {
        $ipl = new PageList();
        $ipl->setSiteTreeToAll();
        
        $pagesByType = [];
        $results = $ipl->getResults();
        
        // Filter results by specific page IDs if provided (more reliable than PageList filtering)
        if ($pageIds !== null && !empty($pageIds)) {
            $pageIdSet = array_flip($pageIds); // Convert to hash set for fast lookup
            $results = array_filter($results, function($page) use ($pageIdSet) {
                return isset($pageIdSet[$page->getCollectionID()]);
            });
        }
        
        // Always build the comprehensive all_pages collection
        $buildAllPages = true;

        foreach ($results as $r) {
            $content = $r->getPageIndexContent();
            
            // Skip pages with empty content
            if (!$this->isValidContent($content)) {
                continue;
            }
            
            // All pages go into the comprehensive collection
            $pageTypeHandle = $r->getPageTypeHandle();
            $storeType = 'all_pages';

            // Get collection name - prioritize the original page since it has the correct name
            $pageId = $r->getCollectionID();
            $collectionName = '';
            $description = '';
            
            // Get collection name - original page from PageList has the correct name
            $versionObject = $r->getVersionObject();
            if ($versionObject) {
                $collectionName = $versionObject->cvName ?: $versionObject->getVersionName();
            }
            if (empty($collectionName)) {
                $collectionName = $r->getCollectionName();
            }
            
            // If still empty, try approved page
            if (empty($collectionName)) {
                $approvedPage = Page::getByID($pageId, 'APPROVED');
                if ($approvedPage && !$approvedPage->isError()) {
                    $approvedVersionObject = $approvedPage->getVersionObject();
                    if ($approvedVersionObject) {
                        $collectionName = $approvedVersionObject->cvName ?: $approvedVersionObject->getVersionName();
                    }
                    if (empty($collectionName)) {
                        $collectionName = $approvedPage->getCollectionName();
                    }
                }
            }
            
            // Get description from approved page if we haven't loaded it yet
            if (!isset($approvedPage)) {
                $approvedPage = Page::getByID($pageId, 'APPROVED');
            }
            if ($approvedPage && !$approvedPage->isError()) {
                $description = $approvedPage->getCollectionDescription();
            }
            
            // Fallback for description
            if (empty($description)) {
                $description = $r->getCollectionDescription();
            }
            
            // Last resort: try to get collection handle and convert to name
            if (empty($collectionName)) {
                $collectionHandle = $r->getCollectionHandle();
                if (!empty($collectionHandle)) {
                    // Convert handle to readable name (capitalize and replace dashes/underscores with spaces)
                    $collectionName = ucwords(str_replace(['-', '_'], ' ', $collectionHandle));
                }
            }
            
            // Fallback for description
            if (empty($description)) {
                $description = $r->getCollectionDescription();
            }
            
            // Final fallback
            if (empty($collectionName)) {
                $collectionName = 'Untitled Page';
            }

            // Get meta title using proper Concrete CMS method
            $metaTitle = '';
            try {
                // Use the standard Concrete CMS method - try original page first since it has the correct data
                $metaTitleAttr = $r->getCollectionAttributeValue('meta_title');
                
                // Fallback to approved page if original doesn't have it
                if (empty($metaTitleAttr) && $approvedPage && !$approvedPage->isError()) {
                    $metaTitleAttr = $approvedPage->getCollectionAttributeValue('meta_title');
                }
                
                // Use meta title if found, otherwise fall back to collection name
                $metaTitle = $metaTitleAttr ?: $collectionName;
                
            } catch (\Exception $e) {
                $metaTitle = $collectionName;
            }
            
            // Ensure we never have empty meta title
            if (empty($metaTitle)) {
                $metaTitle = $collectionName ?: 'Untitled Page';
            }

            $pageData = [
                'title' => (string)($metaTitle ?: 'Untitled Page'),
                'collection_name' => (string)($collectionName ?: 'Untitled Page'),
                'description' => (string)($description ?: ''),
                'content' => $content,
                'link' => (string)($r->getCollectionLink() ?: ''),
                'pagetype' => (string)($pageTypeHandle ?: ''),
                'page_id' => $pageId
            ];
            
            // Group pages by store type
            if (!isset($pagesByType[$storeType])) {
                $pagesByType[$storeType] = [];
            }
            $pagesByType[$storeType][] = $pageData;
        }
        
        return $pagesByType;
    }
    


    public function addDocuments(array $pages): void
    {
        $embeddingProvider = $this->getEmbeddingProvider();
        
        // All pages go into the comprehensive collection
        $storeType = 'all_pages';
        
        // Get the appropriate vector store for this page type
        $vectorStore = $this->getVectorStoreForType($storeType);
        
        $processedCount = 0;
        $skippedCount = 0;
        
        foreach ($pages as $page) {
            // Validate content before processing
            if (!$this->isValidContent($page['content'])) {
                $skippedCount++;
                continue;
            }
            
            try {
                $documents = StringDataLoader::for($page['content'])->getDocuments();
                
                foreach ($documents as $docIndex => $document) {
                    if (!($document instanceof \NeuronAI\RAG\Document)) {
                        throw new \RuntimeException('Expected Document, got: ' . (is_object($document) ? get_class($document) : gettype($document)));
                    }
                    
                    // Generate deterministic ID based on page ID and document content hash
                    // This ensures the same page content gets the same ID for updates instead of duplicates
                    $contentHash = md5($document->content);
                    $deterministicId = "page_{$page['page_id']}_doc_{$docIndex}_{$contentHash}";
                    
                    // Set the deterministic ID using reflection to override the auto-generated uniqid
                    $reflection = new \ReflectionClass($document);
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($document, $deterministicId);
                    
                    // Add page metadata to document (convert all to strings for Typesense compatibility)
                    $document->sourceName = (string)($page['collection_name'] ?: 'Untitled Page'); // Use collection_name (page name) for better search result display, ensure not null
                    $document->sourceType = 'page';
                    $document->addMetadata('meta_title', (string)($page['title'] ?? 'Untitled Page')); // Store meta_title as metadata
                    $document->addMetadata('url_path', (string)($this->extractUrlPath($page['link'] ?? '') ?: '')); // Clean URL path for matching and reconstruction
                    $document->addMetadata('pagetype', (string)($page['pagetype'] ?? 'page')); // Store the page type in metadata
                    $document->addMetadata('specialisms', (string)($this->extractSpecialisms($page) ?: '')); // Extract specialism hierarchy
                    $document->addMetadata('parent_pages', (string)($this->extractParentPages($page) ?: '')); // Extract parent page info
                    $document->addMetadata('page_id', (string)($page['page_id'] ?? '0')); // Convert to string for Typesense compatibility
                    $document->addMetadata('collection_version', (string)($this->getPageVersion($page) ?: '1')); // Track page version for debugging
                    $document->addMetadata('store_type', (string)($storeType ?: 'all_pages')); // Track which collection this belongs to
                    
                    // Add timestamp and update tracking information
                    $document->addMetadata('indexed_date', date('Y-m-d H:i:s')); // Human-readable indexing date
                    $document->addMetadata('page_modified_at', $this->getPageModifiedTime($page)); // When the page was last modified (same format as indexed_date)
                    $document->addMetadata('indexing_version', '2.0'); // Track indexing schema version
                    
                    try {
                        $document->embedding = $embeddingProvider->embedText($document->content);
                        
                        // Progress logging only for very large operations
                        if ($processedCount % 500 == 0) {
                            error_log("Progress: processed {$processedCount} documents");
                        }
                        
                        $vectorStore->addDocument($document);
                        $processedCount++;
                    } catch (\Exception $docException) {
                        // Log specific document errors but continue processing
                        error_log("Failed to process document for page {$page['page_id']}: " . $docException->getMessage());
                        $skippedCount++;
                        continue;
                    }
                }
            } catch (\Exception $e) {
                // Log page-level errors but continue processing other pages
                error_log("Failed to process page {$page['page_id']}: " . $e->getMessage());
                $skippedCount++;
            }
        }
        
        // Log processing results but don't echo to UI
        error_log("Processed {$processedCount} pages for {$storeType} collection (skipped: {$skippedCount})");
    }
    
    /**
     * Get vector store for a specific type
     */
    private function getVectorStoreForType($storeType)
    {
        // Detect actual embedding dimensions dynamically
        $vectorDimensions = $this->detectEmbeddingDimensions();
        return TypesenseVectorStoreFactory::create($storeType, 4, $vectorDimensions);
    }

    /**
     * Get available page index store types for the task UI
     */
    public function getAvailableStores(): array
    {
        // Since we now use a unified approach, provide options that make sense to users
        return [
            'all_pages' => t('All Pages (Comprehensive - Recommended)'),
            'legal_services' => t('Legal Services Only'),
            'calculators' => t('Calculators Only'),
            'articles' => t('Articles & News Only'),
            'case_studies' => t('Case Studies Only')
        ];
    }

    public function getRelevantDocuments(string $query, int $topK = 12): array
    {
        $embeddingProvider = $this->getEmbeddingProvider();
        $queryEmbedding = $embeddingProvider->embedText($query);
        
        // USE ONLY THE COMPREHENSIVE ALL_PAGES COLLECTION
        // No fallbacks - if this fails, we need to see the error and fix it
        
        $vectorStore = $this->getVectorStoreForType('all_pages');
        
        // Perform the search
        if ($vectorStore instanceof \KatalysisProAi\CustomTypesenseVectorStore) {
            $allResults = $vectorStore->similaritySearch($queryEmbedding, $query);
        } else {
            $allResults = $vectorStore->similaritySearch($queryEmbedding);
        }
        
        // Trust Typesense's native ranking and return results
        // Typesense already handles the optimal hybrid scoring of vector + text similarity
        return array_slice($allResults, 0, $topK);
    }

    /**
     * Get the OpenAI embeddings provider instance
     */
    public function getEmbeddingProvider(): OpenAIEmbeddingsProvider
    {
        return new OpenAIEmbeddingsProvider(
            Config::get('katalysis.ai.open_ai_key'),
            'text-embedding-3-small'
        );
    }

    /**
     * Get the vector store instance (Typesense or File-based based on configuration)
     */
    public function getVectorStore(int $topK = 4): VectorStoreInterface
    {
        // Detect actual embedding dimensions dynamically
        $vectorDimensions = $this->detectEmbeddingDimensions();
        return TypesenseVectorStoreFactory::create('pages', $topK, $vectorDimensions);
    }
    
    /**
     * Detect the actual embedding dimensions from OpenAI
     */
    private function detectEmbeddingDimensions(): int
    {
        try {
            $embeddingProvider = $this->getEmbeddingProvider();
            $testEmbedding = $embeddingProvider->embedText("Test text for dimension detection");
            return count($testEmbedding);
        } catch (\Exception $e) {
            error_log("Warning: Could not detect embedding dimensions, using default 1536: " . $e->getMessage());
            return 1536; // Fallback to default
        }
    }



    /**
     * Clear a Typesense collection by deleting all documents (safer than deleting the collection)
     */
    private function clearTypesenseCollection(string $storeType): void
    {
        try {
            $apiKey = Config::get('katalysis.search.typesense_api_key', '');
            $host = Config::get('katalysis.search.typesense_host', '');
            $port = Config::get('katalysis.search.typesense_port', '443');
            $protocol = Config::get('katalysis.search.typesense_protocol', 'https');
            $collectionPrefix = Config::get('katalysis.search.typesense_collection_prefix', 'katalysis_');
            
            if (empty($apiKey) || empty($host)) {
                throw new \Exception('Typesense configuration is incomplete');
            }
            
            $client = new \Typesense\Client([
                'api_key' => $apiKey,
                'nodes' => [
                    [
                        'host' => $host,
                        'port' => $port,
                        'protocol' => $protocol,
                    ],
                ],
                'connection_timeout_seconds' => 10,
            ]);
            
            $collectionName = $collectionPrefix . $storeType;
            
            // More aggressive approach: drop and recreate the collection to ensure clean state
            try {
                // Try to delete the entire collection
                $client->collections[$collectionName]->delete();
                error_log("Successfully dropped Typesense collection: {$collectionName} - this will force schema recreation with new fields");
                
            } catch (\Typesense\Exceptions\ObjectNotFound $e) {
                error_log("Collection {$collectionName} did not exist, nothing to drop");
            } catch (\Exception $e) {
                // If we can't drop the collection, try to clear all documents
                error_log("Could not drop collection {$collectionName}, attempting to clear documents: " . $e->getMessage());
                
                try {
                    $collectionInfo = $client->collections[$collectionName]->retrieve();
                    
                    // Delete all documents using multiple filter approaches
                    $filters = [
                        'store_type:=' . $storeType,
                        'sourceType:=page',
                        '*' // Wildcard to match all documents
                    ];
                    
                    $totalDeleted = 0;
                    foreach ($filters as $filter) {
                        try {
                            $deleteResponse = $client->collections[$collectionName]->documents->delete([
                                'filter_by' => $filter
                            ]);
                            $deleted = $deleteResponse['num_deleted'] ?? 0;
                            $totalDeleted += $deleted;
                            if ($deleted > 0) {
                                error_log("Deleted {$deleted} documents with filter: {$filter}");
                            }
                        } catch (\Exception $filterError) {
                            error_log("Filter '{$filter}' failed: " . $filterError->getMessage());
                        }
                    }
                    
                    error_log("Total documents deleted from {$collectionName}: {$totalDeleted}");
                    
                } catch (\Typesense\Exceptions\ObjectNotFound $e) {
                    error_log("Collection {$collectionName} did not exist during cleanup");
                }
            }
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to clear Typesense collection {$storeType}: " . $e->getMessage());
        }
    }

    /**
     * Validate if content is suitable for processing
     */
    private function isValidContent($content): bool
    {
        return is_string($content) && !empty($content) && strlen($content) >= 50;
    }

    /**
     * Extract URL path for better matching
     */
    public function extractUrlPath($url): string
    {
        if (empty($url)) {
            return '';
        }
        
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Remove leading slash and convert to match-friendly format
        return ltrim($path, '/');
    }

    /**
     * Extract specialisms hierarchy from page data
     */
    public function extractSpecialisms($pageData): string
    {
        $specialisms = '';
        $pageId = $pageData['page_id'] ?? 'unknown';
        
        // Primary method: check for 'specialisms' topic attribute on all pages
        if (isset($pageData['page_id'])) {
            try {
                $page = Page::getByID($pageData['page_id'], 'APPROVED');
                if ($page && !$page->isError()) {
                    // Use proper Concrete CMS method for getting attribute value objects (Topics attributes)
                    $specialismsValueObj = $page->getAttributeValueObject('specialisms');
                    
                    $specialismsAttr = null;
                    if ($specialismsValueObj) {
                        // Try different methods to get the actual topic values
                        try {
                            // Method 1: getDisplayValue() - returns formatted string (preferred for Topics)
                            $displayValue = $specialismsValueObj->getDisplayValue();
                            
                            // Method 2: getPlainTextValue() - returns plain text
                            $plainValue = $specialismsValueObj->getPlainTextValue();
                            
                            // Use the most appropriate value
                            if (!empty($displayValue) && is_string($displayValue)) {
                                return $displayValue;
                            } elseif (!empty($plainValue) && is_string($plainValue)) {
                                return $plainValue;
                            } else {
                                // Fall back to processing native value
                                $specialismsAttr = $specialismsValueObj->getValue();
                            }
                        } catch (\Exception $valueException) {
                            // Try the old method as fallback
                            $specialismsAttr = $page->getCollectionAttributeValue('specialisms');
                        }
                    } else {
                        // Fallback to old method if value object not found
                        $specialismsAttr = $page->getCollectionAttributeValue('specialisms');
                    }
                    
                    if ($specialismsAttr) {
                        // Handle different attribute value types
                        if (is_array($specialismsAttr)) {
                            // If it's an array of topic objects, extract their display names
                            $topicNames = [];
                            foreach ($specialismsAttr as $topic) {
                                if (is_object($topic)) {
                                    // Topic object - get the tree node name
                                    if (method_exists($topic, 'getTreeNodeDisplayName')) {
                                        $topicNames[] = $topic->getTreeNodeDisplayName();
                                    } elseif (method_exists($topic, 'getTreeNodeName')) {
                                        $topicNames[] = $topic->getTreeNodeName();
                                    } elseif (isset($topic->treeNodeName)) {
                                        $topicNames[] = $topic->treeNodeName;
                                    }
                                } elseif (is_string($topic)) {
                                    $topicNames[] = $topic;
                                }
                            }
                            if (!empty($topicNames)) {
                                return implode(' > ', $topicNames);
                            }
                        } elseif (is_object($specialismsAttr)) {
                            // Single topic object
                            if (method_exists($specialismsAttr, 'getTreeNodeDisplayName')) {
                                return $specialismsAttr->getTreeNodeDisplayName();
                            } elseif (method_exists($specialismsAttr, 'getTreeNodeName')) {
                                return $specialismsAttr->getTreeNodeName();
                            } elseif (isset($specialismsAttr->treeNodeName)) {
                                return $specialismsAttr->treeNodeName;
                            }
                        } elseif (is_string($specialismsAttr) && !empty($specialismsAttr)) {
                            // Already a string value
                            return $specialismsAttr;
                        }
                    }
                    
                    // Fallback: try current version if approved version doesn't have the attribute
                    if (empty($specialismsAttr)) {
                        $currentPage = Page::getByID($pageData['page_id']);
                        if ($currentPage && !$currentPage->isError() && $currentPage->getCollectionID() != $page->getCollectionID()) {
                            // Try value object method first
                            $currentValueObj = $currentPage->getAttributeValueObject('specialisms');
                            if ($currentValueObj) {
                                $currentDisplay = $currentValueObj->getDisplayValue();
                                if (!empty($currentDisplay) && is_string($currentDisplay)) {
                                    return $currentDisplay;
                                }
                            }
                            
                            // Fallback to old method
                            $currentSpecialisms = $currentPage->getCollectionAttributeValue('specialisms');
                            if ($currentSpecialisms) {
                                // Process current page specialisms the same way
                                if (is_array($currentSpecialisms)) {
                                    $topicNames = [];
                                    foreach ($currentSpecialisms as $topic) {
                                        if (is_object($topic)) {
                                            if (method_exists($topic, 'getTreeNodeDisplayName')) {
                                                $topicNames[] = $topic->getTreeNodeDisplayName();
                                            } elseif (method_exists($topic, 'getTreeNodeName')) {
                                                $topicNames[] = $topic->getTreeNodeName();
                                            }
                                        } elseif (is_string($topic)) {
                                            $topicNames[] = $topic;
                                        }
                                    }
                                    if (!empty($topicNames)) {
                                        return implode(' > ', $topicNames);
                                    }
                                } elseif (is_string($currentSpecialisms)) {
                                    return $currentSpecialisms;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log errors but continue processing
                error_log("Error getting specialisms attribute for page {$pageId}: " . $e->getMessage());
            }
        }
        
        // Fallback: extract from URL structure ONLY for specific page types that need it
        $pageType = $pageData['pagetype'] ?? '';
        if (in_array($pageType, ['legal_service', 'legal_service_index', 'calculator_entry', 'guide', 'case_study', 'article'])) {
            $url = $pageData['link'] ?? '';
            $urlSpecialisms = $this->extractSpecialismsFromUrl($url);
            if (!empty($urlSpecialisms)) {
                return implode(' > ', array_filter($urlSpecialisms));
            }
        }
        
        return $specialisms;
    }

    /**
     * Extract specialisms from URL structure
     */
    private function extractSpecialismsFromUrl($url): array
    {
        $specialisms = [];
        
        if (empty($url)) {
            return $specialisms;
        }
        
        // Parse URL path and extract hierarchy
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $pathParts = array_filter(explode('/', $path));
            
            // Look for known patterns in URLs to build hierarchical specialisms
            $hierarchyMapping = [
                // Top-level categories
                'personal-injury-claim' => 'Personal Injury Claims',
                'road-accident-claims' => 'Road Accident Claims', 
                'work-accident-claims' => 'Work Accident Claims',
                'medical-negligence' => 'Medical Negligence',
                'no-win-no-fee-claims' => 'No Win No Fee Claims',
                
                // Civil Litigation & Disputes Categories
                'civil-litigation-disputes' => 'Disputes & Settlements',
                'civil-litigation' => 'Disputes & Settlements',
                'disputes' => 'Disputes & Settlements',
                'professional-negligence' => 'Professional Negligence',
                'commercial-debt-recovery' => 'Commercial Debt Recovery',
                'settlement-agreements' => 'Settlement Agreements',
                'wills-probate-disputes' => 'Wills & Probate Disputes',
                'commercial-disputes' => 'Commercial Disputes',
                'contract-disputes' => 'Contract Disputes',
                'debt-recovery' => 'Commercial Debt Recovery',
                'litigation' => 'Disputes & Settlements',
                
                // Conveyancing Categories
                'conveyancing' => 'Conveyancing',
                'residential-conveyancing' => 'Residential Conveyancing',
                'commercial-conveyancing' => 'Commercial Conveyancing',
                'property-law' => 'Conveyancing',
                
                // Family Law Categories
                'family-law' => 'Family Law',
                'divorce-law' => 'Divorce Law',
                'financial-settlements' => 'Financial Settlements',
                'prenuptial-agreements' => 'Prenuptial Agreements',
                'separation-agreements' => 'Separation Agreements',
                'clean-break-orders' => 'Clean Break Orders',
                
                // Wills & Probate Categories
                'wills-probate-estates' => 'Wills, Probate & Estates',
                'wills-probate' => 'Wills, Probate & Estates',
                'will-disputes' => 'Will Disputes',
                'contentious-probate' => 'Contentious Probate',
                'probate' => 'Wills, Probate & Estates',
                'estate-planning' => 'Estate Planning',
                
                // Specific subcategories
                'horse-riding-accidents' => 'Horse Riding Accidents',
                'car-accidents' => 'Car Accidents',
                'motorcycle-accidents' => 'Motorcycle Accidents',
                'cycling-accidents' => 'Cycling Accidents',
                'lorry-accidents' => 'Lorry/HGV Accidents',
                'pedestrian-accidents' => 'Pedestrian Accidents',
                'public-transport-accidents' => 'Public Transport Accidents',
                
                // Alternative patterns
                'personal-injury' => 'Personal Injury Claims',
                'road-accident' => 'Road Accident Claims',
                'work-accident' => 'Work Accident Claims', 
                'workplace-injury' => 'Work Accident Claims',
                'medical-malpractice' => 'Medical Negligence',
                'clinical-negligence' => 'Medical Negligence',
                
                // General patterns that might appear in case studies/articles
                'accident-compensation' => 'Accident Compensation Claims',
                'injury-claim' => 'Personal Injury Claims',
                'compensation-claim' => 'Compensation Claims',
                'legal-advice' => 'Legal Advice',
                'solicitors' => 'Legal Services',
            ];
            
            // Build proper hierarchy by analyzing URL structure
            $foundCategories = [];
            foreach ($pathParts as $part) {
                if (isset($hierarchyMapping[$part])) {
                    $foundCategories[] = $hierarchyMapping[$part];
                }
            }
            
            // Build hierarchical specialisms based on patterns
            if (!empty($foundCategories)) {
                // Process categories from most specific to least specific to build proper hierarchy
                // Reverse the array so we check most specific categories first
                $reversedCategories = array_reverse($foundCategories);
                
                foreach ($reversedCategories as $category) {
                    switch ($category) {
                        // Personal Injury subcategories
                        case 'Horse Riding Accidents':
                        case 'Car Accidents':
                        case 'Motorcycle Accidents':
                        case 'Cycling Accidents':
                        case 'Lorry/HGV Accidents':
                        case 'Pedestrian Accidents':
                        case 'Public Transport Accidents':
                            $specialisms = ['Personal Injury Claims', 'Road Accident Claims', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Road Accident Claims':
                            // Only use this if we don't have a more specific subcategory
                            if (empty($specialisms)) {
                                $specialisms = ['Personal Injury Claims', 'Road Accident Claims'];
                            }
                            break;
                        case 'Work Accident Claims':
                        case 'Medical Negligence':
                            $specialisms = ['Personal Injury Claims', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Personal Injury Claims':
                            // Only use this if we don't have anything more specific
                            if (empty($specialisms)) {
                                $specialisms = ['Personal Injury Claims'];
                            }
                            break;
                        
                        // Civil Litigation & Disputes subcategories
                        case 'Professional Negligence':
                        case 'Commercial Debt Recovery':
                        case 'Settlement Agreements':
                        case 'Wills & Probate Disputes':
                        case 'Commercial Disputes':
                        case 'Contract Disputes':
                            $specialisms = ['Disputes & Settlements', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Disputes & Settlements':
                            // Only use this if we don't have a more specific subcategory
                            if (empty($specialisms)) {
                                $specialisms = ['Disputes & Settlements'];
                            }
                            break;
                        
                        // Family Law subcategories
                        case 'Divorce Law':
                        case 'Financial Settlements':
                        case 'Prenuptial Agreements':
                        case 'Separation Agreements':
                        case 'Clean Break Orders':
                            $specialisms = ['Family Law', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Family Law':
                            if (empty($specialisms)) {
                                $specialisms = ['Family Law'];
                            }
                            break;
                        
                        // Conveyancing subcategories
                        case 'Residential Conveyancing':
                        case 'Commercial Conveyancing':
                            $specialisms = ['Conveyancing', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Conveyancing':
                            if (empty($specialisms)) {
                                $specialisms = ['Conveyancing'];
                            }
                            break;
                        
                        // Wills & Probate subcategories
                        case 'Will Disputes':
                        case 'Contentious Probate':
                        case 'Estate Planning':
                            $specialisms = ['Wills, Probate & Estates', $category];
                            break 2; // Break out of both switch and foreach
                        case 'Wills, Probate & Estates':
                            if (empty($specialisms)) {
                                $specialisms = ['Wills, Probate & Estates'];
                            }
                            break;
                        
                        default:
                            if (empty($specialisms)) {
                                $specialisms[] = $category;
                            }
                    }
                }
            }

        }
        
        return $specialisms;
    }

    /**
     * Extract parent pages for hierarchical relationships
     */
    public function extractParentPages($pageData): string
    {
        $parentPages = [];
        
        if (isset($pageData['page_id'])) {
            try {
                $page = Page::getByID($pageData['page_id'], 'APPROVED');
                if ($page && !$page->isError()) {
                    // Get parent page hierarchy
                    $parentId = $page->getCollectionParentID();
                    while ($parentId > 1) { // Stop at home page
                        $parent = Page::getByID($parentId, 'APPROVED');
                        if ($parent && !$parent->isError()) {
                            // Get parent name from version object first, then fallback to getCollectionName
                            $parentName = '';
                            $versionObject = $parent->getVersionObject();
                            if ($versionObject) {
                                $parentName = $versionObject->cvName ?: $versionObject->getVersionName();
                            }
                            if (empty($parentName)) {
                                $parentName = $parent->getCollectionName();
                            }
                            // If the approved version doesn't have a name, try the current version
                            if (empty($parentName)) {
                                $currentParent = Page::getByID($parentId);
                                if ($currentParent && !$currentParent->isError()) {
                                    $currentVersionObject = $currentParent->getVersionObject();
                                    if ($currentVersionObject) {
                                        $parentName = $currentVersionObject->cvName ?: $currentVersionObject->getVersionName();
                                    }
                                    if (empty($parentName)) {
                                        $parentName = $currentParent->getCollectionName();
                                    }
                                }
                            }
                            
                            $parentPages[] = [
                                'id' => $parent->getCollectionID(),
                                'name' => $parentName ?: 'Untitled Page',
                                'url_path' => $this->extractUrlPath($parent->getCollectionLink())
                            ];
                            $parentId = $parent->getCollectionParentID();
                        } else {
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("Error getting parent pages: " . $e->getMessage());
            }
        }
        
        // Return as JSON string for storage in metadata
        return !empty($parentPages) ? json_encode(array_reverse($parentPages)) : '';
    }

    /**
     * Get page modification date in readable format
     */
    public function getPageModifiedTime($pageData): string
    {
        if (isset($pageData['page_id'])) {
            try {
                $page = Page::getByID($pageData['page_id'], 'APPROVED');
                if ($page && !$page->isError()) {
                    $dateModified = $page->getCollectionDateLastModified();
                    if ($dateModified) {
                        // Convert to same format as indexed_date for easy comparison
                        return date('Y-m-d H:i:s', strtotime($dateModified));
                    }
                }
            } catch (\Exception $e) {
                error_log("Error getting page modification time: " . $e->getMessage());
            }
        }
        
        // Fallback to current time if we can't get the actual modification time
        return date('Y-m-d H:i:s');
    }

    /**
     * Get page collection version for debugging (approved/published version only)
     */
    public function getPageVersion($pageData): string
    {
        if (isset($pageData['page_id'])) {
            try {
                $page = Page::getByID($pageData['page_id'], 'APPROVED');
                if ($page && !$page->isError()) {
                    // Get the version ID from the approved page
                    $versionObject = $page->getVersionObject();
                    if ($versionObject) {
                        return (string)$versionObject->getVersionID();
                    }
                }
            } catch (\Exception $e) {
                error_log("Error getting approved page version: " . $e->getMessage());
            }
        }
        
        // Fallback to 1 if we can't get the approved version
        return '1';
    }
}
