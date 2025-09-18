<?php
/**
 * REMOTE SITE: https://dev36.katalysis.net
 * This is deployed code - test via CMS frontend search block, not direct API calls
 */
namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Page\Page;
use Concrete\Core\Attribute\Key\CollectionKey;
use PageList;
use Core;
use Database;
use KatalysisProAi\RagAgent;
use \NeuronAI\Chat\Messages\UserMessage;
use Config;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\People\PeopleList;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\Reviews\ReviewList;
use \Concrete\Core\Tree\Type\Topic as TopicTree;

class SearchSettings extends DashboardPageController
{
    public function view()
    {
        // Get current settings from config
        $maxResults = Config::get('katalysis.search.max_results', 8);
        $resultLength = Config::get('katalysis.search.result_length', 'medium');
        $includePageLinks = Config::get('katalysis.search.include_page_links', true);
        $showSnippets = Config::get('katalysis.search.show_snippets', true);
        
        // Get specialists settings (AI-driven, no manual prompts needed)
        $enableSpecialists = Config::get('katalysis.search.enable_specialists', true);
        $maxSpecialists = Config::get('katalysis.search.max_specialists', 3);

        // Get reviews settings (AI-driven, no manual prompts needed)
        $enableReviews = Config::get('katalysis.search.enable_reviews', true);
        $maxReviews = Config::get('katalysis.search.max_reviews', 3);
        
        // Get places settings
        $enablePlaces = Config::get('katalysis.search.enable_places', true);
        $maxPlaces = Config::get('katalysis.search.max_places', 3);
        
        // Get advanced AI prompts (split into hardcoded structure + editable response format)
        $responseFormatInstructions = Config::get('katalysis.search.response_format_instructions', $this->getDefaultResponseFormatInstructions());
        
        // Get known false positives
        $knownFalsePositives = Config::get('katalysis.search.known_false_positives', $this->getDefaultKnownFalsePositives());

        // Get debug panel setting
        $enableDebugPanel = Config::get('katalysis.search.enable_debug_panel', false);

        // Get search statistics
        $searchStats = [
            'total_searches' => $this->getSearchCount('all'),
            'today_searches' => $this->getSearchCount('today'),
            'this_week' => $this->getSearchCount('week'),
            'this_month' => $this->getSearchCount('month')
        ];

        // Get popular search terms
        $popularTerms = $this->getPopularSearchTerms();

        // Set view variables
        $this->set('maxResults', $maxResults);
        $this->set('resultLength', $resultLength);
        $this->set('includePageLinks', $includePageLinks);
        $this->set('showSnippets', $showSnippets);
        $this->set('enableSpecialists', $enableSpecialists);
        $this->set('maxSpecialists', $maxSpecialists);  
        $this->set('enableReviews', $enableReviews);
        $this->set('maxReviews', $maxReviews);
        $this->set('responseFormatInstructions', $responseFormatInstructions);
        $this->set('knownFalsePositives', $knownFalsePositives);
        $this->set('enableDebugPanel', $enableDebugPanel);
        $this->set('searchStats', $searchStats);
        $this->set('popularTerms', $popularTerms);
        
        // Set default values for comparison (only for AI-configurable prompts)
        $this->set('defaultResponseFormatInstructions', $this->getDefaultResponseFormatInstructions());
        $this->set('defaultKnownFalsePositives', $this->getDefaultKnownFalsePositives());
        
        // Set default values for statistics (since we removed the display)
        $this->set('searchesToday', 0);
        $this->set('searchesThisMonth', 0);
        $this->set('popularTerms', []);
    }

    /**
     * Get hardcoded intent analysis structure (not user-editable)
     */
    private function getIntentAnalysisStructure(): string
    {
        return "TASK: Analyze this query and provide a comprehensive structured response. Return JSON with both intent analysis AND detailed response:\n\n" .
               "```json\n" .
               "{\n" .
               "  \"intent\": {\n" .
               "    \"intent_type\": \"string (information, help, contact, booking, pricing, comparison, complaint, urgent, question)\",\n" .
               "    \"confidence\": \"number (0.0-1.0)\",\n" .
               "    \"service_area\": \"string or null (specific service mentioned)\",\n" .
               "    \"specialism_id\": \"number or null (matching specialism ID if applicable)\",\n" .
               "    \"urgency\": \"string (low, medium, high)\",\n" .
               "    \"location_mentioned\": \"string or null\",\n" .
               "    \"key_phrases\": [\"array of important phrases from query\"]\n" .
               "  },\n" .
               "  \"response\": \"string (structured response using format below)\"\n" .
               "}\n" .
               "```\n\n";
    }

    /**
     * Get default response format instructions (user-editable)
     */
    private function getDefaultResponseFormatInstructions(): string
    {
        return "RESPONSE STRUCTURE - Use this exact format:\n" .
               "DIRECT ANSWER: [Direct answer to their specific question or need]\n" .
               "RELATED SERVICES: [Additional relevant services we offer]\n" .
               "OUR CAPABILITIES: [How our expertise specifically helps]\n" .
               "PRACTICAL GUIDANCE: [Next steps, what to prepare, or actions to take]\n" .
               "WHY CHOOSE US: [Benefits of choosing our firm, unique value proposition]\n\n" .
               "RESPONSE GUIDELINES:\n" .
               "- Use professional, reassuring, and confident tone\n" .
               "- Be specific about our legal services and expertise\n" .
               "- Include practical next steps and actionable advice\n" .
               "- Highlight our unique strengths and experience\n" .
               "- Each section should be 1-2 sentences, clear and informative\n" .
               "- End with a call to action encouraging contact";
    }

    /**
     * Get default intent analysis prompt (combines structure + response format)
     */
    private function getDefaultIntentAnalysisPrompt(): string
    {
        return $this->getIntentAnalysisStructure() . $this->getDefaultResponseFormatInstructions();
    }

    /**
     * Get full intent analysis prompt (combines hardcoded structure + user-editable format)
     */
    private function getFullIntentAnalysisPrompt(string $userResponseFormat): string
    {
        return $this->getIntentAnalysisStructure() . $userResponseFormat;
    }

    /**
     * Get default known false positives
     */
    private function getDefaultKnownFalsePositives(): string
    {
        return json_encode([
            ['query' => 'crash', 'false' => 'crush'],
            ['query' => 'car', 'false' => 'care'],
            ['query' => 'accident', 'false' => 'incident']
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get default response format instructions via AJAX
     */
    public function get_default_response_format_instructions()
    {
        if (!$this->token->validate('save_search_settings')) {
            return new JsonResponse(['error' => $this->token->getErrorMessage()], 400);
        }
        
        return new JsonResponse([
            'instructions' => $this->getDefaultResponseFormatInstructions()
        ]);
    }

    /**
     * Get default known false positives via AJAX
     */
    public function get_default_known_false_positives()
    {
        if (!$this->token->validate('save_search_settings')) {
            return new JsonResponse(['error' => $this->token->getErrorMessage()], 400);
        }
        
        return new JsonResponse([
            'patterns' => $this->getDefaultKnownFalsePositives()
        ]);
    }

    public function save()
    {
        if (!$this->token->validate('save_search_settings')) {
            $this->error->add($this->token->getErrorMessage());
            return;
        }

        $data = $this->request->request->all();

        // Save basic settings
        Config::save('katalysis.search.max_results', (int)($data['max_results'] ?? 8));
        Config::save('katalysis.search.result_length', $data['result_length'] ?? 'medium');
        Config::save('katalysis.search.include_page_links', !empty($data['include_page_links']));
        Config::save('katalysis.search.show_snippets', !empty($data['show_snippets']));

        // Save AI-driven specialists and reviews settings (no manual prompts needed)
        Config::save('katalysis.search.enable_specialists', !empty($data['enable_specialists']));
        Config::save('katalysis.search.max_specialists', (int)($data['max_specialists'] ?? 3));
        Config::save('katalysis.search.enable_reviews', !empty($data['enable_reviews']));
        Config::save('katalysis.search.max_reviews', (int)($data['max_reviews'] ?? 3));

        // Save advanced AI prompts
        Config::save('katalysis.search.response_format_instructions', $data['response_format_instructions'] ?? '');
        
        // Save known false positives (validate JSON format)
        $knownFalsePositivesJson = $data['known_false_positives'] ?? '';
        if (!empty($knownFalsePositivesJson)) {
            $decoded = json_decode($knownFalsePositivesJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                Config::save('katalysis.search.known_false_positives', $knownFalsePositivesJson);
            } else {
                $this->error->add(t('Known false positives must be valid JSON format.'));
            }
        } else {
            Config::save('katalysis.search.known_false_positives', $this->getDefaultKnownFalsePositives());
        }

        // Save debug panel setting
        Config::save('katalysis.search.enable_debug_panel', !empty($data['enable_debug_panel']));

        $this->flash('success', t('Search settings saved successfully.'));
        $this->redirect('/dashboard/katalysis_pro_ai/search_settings');
    }

    private function getSearchCount($period)
    {
        // Placeholder - implement actual search count logic
        return 0;
    }

    private function getPopularSearchTerms()
    {
        // Placeholder - implement popular terms logic
        return [];
    }

    public function perform_search()
    {
        // Handle both POST and GET parameters for Concrete CMS compatibility
        $query = trim($this->request->request->get('query', '') ?: $this->request->query->get('query', '') ?: $_POST['query'] ?? $_GET['query'] ?? '');
        $blockId = (int)($this->request->request->get('block_id', 0) ?: $this->request->query->get('block_id', 0) ?: $_POST['block_id'] ?? $_GET['block_id'] ?? 0);
        
        
        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => t('Please enter a search query')
            ]);
        }

        try {
            // Get search settings
            $maxResults = Config::get('katalysis.search.max_results', 8);
            $resultLength = Config::get('katalysis.search.result_length', 'medium');
            $includePageLinks = Config::get('katalysis.search.include_page_links', true);
            $showSnippets = Config::get('katalysis.search.show_snippets', true);
            
            // Start timing for performance measurement
            $startTime = microtime(true);
            
            // Get RAG agent
            $ragAgent = new RagAgent();
            $ragAgent->setApp($this->app);    
            
            // Get specialisms for intent analysis
            $allSpecialisms = $this->getSpecialisms();
            if (empty($allSpecialisms)) {
                $specialismsList = "No specific specialisms available";
            } else {
                $specialismsList = "Available legal specialisms: " . implode(', ', array_column($allSpecialisms, 'treeNodeName'));
            }
            
            // Get configurable intent analysis prompt
            $intentAnalysisPrompt = Config::get('katalysis.search.intent_analysis_prompt', $this->getDefaultIntentAnalysisPrompt());
            
            // COMBINED PROMPT: Intent analysis + response generation in single AI call
            $combinedPrompt = "LEGAL QUERY: \"$query\"\n\n" .
                "$specialismsList\n\n" .
                $intentAnalysisPrompt;
            
            error_log("OPTIMIZED: Using combined intent+response prompt");
            
            // Single AI call for both intent and response
            $combinedStartTime = microtime(true);
            $combinedResponse = $ragAgent->answer(new UserMessage($combinedPrompt));
            $combinedContent = $combinedResponse->getContent();
            $combinedTime = round((microtime(true) - $combinedStartTime) * 1000, 2);
            
            // Parse the combined response
            $intent = null;
            $aiResponse = '';
            
            // Clean up the response - remove markdown code blocks if present
            $cleanedContent = $combinedContent;
            if (preg_match('/```json\s*(.*?)\s*```/s', $cleanedContent, $matches)) {
                $cleanedContent = trim($matches[1]);
            } else {
                // Remove any other markdown code block markers
                $cleanedContent = preg_replace('/```[a-z]*\s*|\s*```/', '', $cleanedContent);
                $cleanedContent = trim($cleanedContent);
            }
            
            // Try to parse as JSON first
            $jsonData = json_decode($cleanedContent, true);
            if ($jsonData && isset($jsonData['intent']) && isset($jsonData['response'])) {
                $intent = $jsonData['intent'];
                $aiResponse = $jsonData['response'];
                
                // Find the most specific specialism ID that matches
                $specialismId = null;
                if (!empty($intent['service_area']) && $intent['service_area'] !== 'null') {
                    $specialismId = $this->findBestMatchingSpecialism($intent['service_area'], $query, $allSpecialisms);
                    if ($specialismId) {
                        $matchedSpecialism = null;
                        foreach ($allSpecialisms as $specialism) {
                            if ($specialism['treeNodeID'] == $specialismId) {
                                $matchedSpecialism = $specialism;
                                break;
                            }
                        }
                    }
                }
                
                // Enhance intent with additional fields
                $intent['specialism_id'] = $specialismId;
                $intent['complexity'] = 'moderate';
                $intent['suggested_specialist_count'] = 3;
                $intent['suggested_office_focus'] = 'nearest';
                $intent['review_type_needed'] = 'general';
                
                error_log("Combined intent+response completed in {$combinedTime}ms");
                
            } else {
                // Fallback: treat entire response as AI response and create basic intent
                $aiResponse = $combinedContent;
                $intent = [
                    'intent_type' => 'information',
                    'service_area' => null,
                    'specialism_id' => null,
                    'location_mentioned' => null,
                    'person_name' => null,
                    'urgency_level' => 'medium',
                    'complexity' => 'moderate',
                    'suggested_specialist_count' => 3,
                    'suggested_office_focus' => 'nearest',
                    'review_type_needed' => 'general'
                ];
            }
            
            // Get relevant pages separately for the pages section with more results
            $ragResults = [];
            $ragStartTime = microtime(true);
            try {
                // Use PageIndexService directly to get more documents (15 instead of default 4)
                $pageIndexService = new \KatalysisProAi\PageIndexService();
                $ragResults = $pageIndexService->getRelevantDocuments($query, 15); // Get 15 documents for AI selection
                
                // Process RAG documents and prioritize legal service pages
                $ragProcessResult = $this->processRagDocuments($ragResults, $query, $intent);
                $ragResults = $ragProcessResult['documents'] ?? $ragProcessResult; // Handle both array and direct return
                $ragDebugInfo = $ragProcessResult['debug'] ?? [];
                
            } catch (\Exception $docError) {
                error_log('Document retrieval for pages failed: ' . $docError->getMessage());
                // Fallback to ragAgent method
                try {
                    $ragResults = $ragAgent->retrieveDocuments(new UserMessage($query));
                    error_log("Fallback: Retrieved " . count($ragResults) . " documents via ragAgent");
                    $ragProcessResult = $this->processRagDocuments($ragResults, $query, $intent);
                    $ragResults = $ragProcessResult['documents'] ?? $ragProcessResult;
                    $ragDebugInfo = $ragProcessResult['debug'] ?? [];
                } catch (\Exception $fallbackError) {
                    error_log('Fallback document retrieval also failed: ' . $fallbackError->getMessage());
                }
            }
            $ragTime = round((microtime(true) - $ragStartTime) * 1000, 2);
            error_log("TIMING: RAG document retrieval and processing completed in {$ragTime}ms");
            
            // PHASE 3: Targeted data retrieval based on intent
            $dataStartTime = microtime(true);
            
            // Check if specialists are enabled
            $enableSpecialists = Config::get('katalysis.search.enable_specialists', true);
            $specialists = $enableSpecialists ? $this->getTargetedSpecialists($query, $intent) : [];
            $specialistsTime = round((microtime(true) - $dataStartTime) * 1000, 2);
            
            $reviewsStartTime = microtime(true);
            // Check if reviews are enabled
            $enableReviews = Config::get('katalysis.search.enable_reviews', true);
            $reviews = $enableReviews ? $this->getTargetedReviews($query, $intent) : [];
            $reviewsTime = round((microtime(true) - $reviewsStartTime) * 1000, 2);
            
            $placesStartTime = microtime(true);
            $places = $this->getTargetedPlaces($query, $intent);
            $placesTime = round((microtime(true) - $placesStartTime) * 1000, 2);
            error_log("Places search completed in {$placesTime}ms");
            
            $pages = $this->formatSearchResults($ragResults, $includePageLinks, $showSnippets);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            error_log("Search completed in {$processingTime}ms. Intent: {$intent['intent_type']}, Service: " . ($intent['service_area'] ?? 'null'));
            
            $results = [
                'success' => true,
                'query' => $query,
                'response' => $aiResponse,
                'intent' => $intent,
                'pages' => $pages,
                'specialists' => $specialists,
                'reviews' => $reviews,
                'places' => $places,
                'processing_time' => $processingTime,
                'debug' => [
                    'intent_analysis' => $intent,
                    'processing_time_ms' => $processingTime,
                    'optimization_strategy' => $this->getOptimizationStrategy($intent),
                    'query_classification' => $this->getQueryClassification($intent),
                    'approach' => 'Combined intent+response (Optimized)',
                    'performance_breakdown' => [
                        'combined_ai_call_ms' => $combinedTime,
                        'rag_documents_ms' => $ragTime,
                        'specialists_search_ms' => $specialistsTime,
                        'reviews_search_ms' => $reviewsTime,
                        'places_search_ms' => $placesTime,
                        'total_ms' => $processingTime,
                        'ai_percentage' => round(($combinedTime / $processingTime) * 100, 1),
                        'optimization_notes' => 'Single AI call replaces separate intent analysis and query building'
                    ],
                    'document_selection' => $ragDebugInfo
                ]
            ];
            
            // Log the search with comprehensive results
            $this->logSearch($query, $blockId, $aiResponse, $intent, $results);
            
            return $this->app->make(ResponseFactory::class)->json($results);
            
        } catch (\Exception $e) {
            error_log('Search error: ' . $e->getMessage());
            error_log('Search error trace: ' . $e->getTraceAsString());
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    public function ask_ai()
    {
        // Alias for perform_search to maintain compatibility with existing routes
        return $this->perform_search();
    }
    
    /**
     * Async endpoint for loading specialists
     */
    public function load_specialists()
    {
        // Check if specialists are enabled
        $enableSpecialists = Config::get('katalysis.search.enable_specialists', true);
        if (!$enableSpecialists) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'specialists' => [],
                'processing_time' => 0
            ]);
        }
        
        $query = trim($this->request->request->get('query', ''));
        $intent = $this->request->request->get('intent', []);
        
        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => t('Query required')
            ]);
        }
        
        try {
            $startTime = microtime(true);
            
            // Parse intent if it's a JSON string
            if (is_string($intent)) {
                if ($intent === 'undefined' || $intent === 'null' || empty($intent)) {
                    $intent = [];
                } else {
                    $intent = json_decode($intent, true) ?: [];
                }
            }
            
            // Ensure intent is an array with defaults
            if (!is_array($intent)) {
                $intent = [];
            }
            
            $specialists = $this->getTargetedSpecialists($query, $intent);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'specialists' => $specialists,
                'processing_time' => $processingTime
            ]);
            
        } catch (\Exception $e) {
            error_log('Async specialists loading error: ' . $e->getMessage());
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'specialists' => [['error' => 'Specialist search failed: ' . $e->getMessage()]],
                'error' => 'Specialist search failed'
            ]);
        }
    }
    
    /**
     * Async endpoint for loading reviews
     */
    public function load_reviews()
    {
        // Check if reviews are enabled
        $enableReviews = Config::get('katalysis.search.enable_reviews', true);
        if (!$enableReviews) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'reviews' => [],
                'processing_time' => 0
            ]);
        }
        
        $query = trim($this->request->request->get('query', ''));
        $intent = $this->request->request->get('intent', []);
        
        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => t('Query required')
            ]);
        }
        
        try {
            $startTime = microtime(true);
            
            // Parse intent if it's a JSON string
            if (is_string($intent)) {
                if ($intent === 'undefined' || $intent === 'null' || empty($intent)) {
                    $intent = [];
                } else {
                    $intent = json_decode($intent, true) ?: [];
                }
            }
            
            // Ensure intent is an array with defaults
            if (!is_array($intent)) {
                $intent = [];
            }
            
            $reviews = $this->getTargetedReviews($query, $intent);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'reviews' => $reviews,
                'processing_time' => $processingTime
            ]);
            
        } catch (\Exception $e) {
            error_log('Async reviews loading error: ' . $e->getMessage());
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'reviews' => [['error' => 'Review search failed: ' . $e->getMessage()]],
                'error' => 'Review search failed'
            ]);
        }
    }
    
    /**
     * Async endpoint for loading places
     */
    public function load_places()
    {
        $query = trim($this->request->request->get('query', ''));
        $intent = $this->request->request->get('intent', []);
        
        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => t('Query required')
            ]);
        }
        
        try {
            $startTime = microtime(true);
            
            // Parse intent if it's a JSON string
            if (is_string($intent)) {
                if ($intent === 'undefined' || $intent === 'null' || empty($intent)) {
                    $intent = [];
                } else {
                    $intent = json_decode($intent, true) ?: [];
                }
            }
            
            // Ensure intent is an array with defaults
            if (!is_array($intent)) {
                $intent = [];
            }
            
            $places = $this->getTargetedPlaces($query, $intent);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'places' => $places,
                'processing_time' => $processingTime
            ]);
            
        } catch (\Exception $e) {
            error_log('Async places loading error: ' . $e->getMessage());
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'places' => [['error' => 'Places search failed: ' . $e->getMessage()]],
                'error' => 'Places search failed'
            ]);
        }
    }
    
    /**
     * Async endpoint for loading pages
     */
    public function load_pages()
    {
        $query = trim($this->request->request->get('query', ''));
        
        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => t('Query required')
            ]);
        }
        
        try {
            $startTime = microtime(true);
            
            // Get search settings
            $includePageLinks = Config::get('katalysis.search.include_page_links', true);
            $showSnippets = Config::get('katalysis.search.show_snippets', true);
            
            // Get RAG results
            $ragAgent = new RagAgent();
            $ragAgent->setApp($this->app);
            
            $ragResults = [];
            try {
                // Use the RAG agent to get document results - use correct method signature
                $ragResults = $ragAgent->retrieveDocuments(new UserMessage($query));
                
                // Process RAG documents if we got results
                if (!empty($ragResults)) {
                    $ragResults = $this->processRagDocuments($ragResults, $query, []);
                }
            } catch (\Exception $docError) {
                error_log('RAG retrieveDocuments failed: ' . $docError->getMessage());
                // Try alternative method if available
                try {
                    $pageIndexService = new \KatalysisProAi\PageIndexService();
                    $ragResults = $pageIndexService->getRelevantDocuments($query, 12);
                    
                    if (!empty($ragResults)) {
                        $ragResults = $this->processRagDocuments($ragResults, $query, []);
                    }
                } catch (\Exception $fallbackError) {
                    error_log('Both RAG methods failed: ' . $fallbackError->getMessage());
                    $ragResults = [];
                }
            }
            
            $pages = $this->formatSearchResults($ragResults, $includePageLinks, $showSnippets);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'pages' => $pages,
                'processing_time' => $processingTime
            ]);
            
        } catch (\Exception $e) {
            error_log('Async pages loading error: ' . $e->getMessage());
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'pages' => [],
                'error' => 'Page loading failed'
            ]);
        }
    }

    private function formatSearchResults($ragResults, $includePageLinks, $showSnippets)
    {
        $formatted = [];
        
        foreach ($ragResults as $result) {
            try {
                $title = 'Relevant Page';
                $content = '';
                $url = '';
                $type = 'general';
                
                if (is_array($result)) {
                    // Enhanced result from our enhancePageResults method
                    $title = $result['title'] ?? $title;
                    $content = $result['content'] ?? '';
                    $url = $result['url'] ?? '';
                    $type = $result['type'] ?? 'general';
                } elseif (is_object($result)) {
                    // Original RAG Document objects
                    if (method_exists($result, 'getContent')) {
                        $content = $result->getContent();
                    } elseif (method_exists($result, '__toString')) {
                        $content = (string)$result;
                    }
                    
                    if (method_exists($result, 'getMetadata')) {
                        $metadata = $result->getMetadata();
                        $title = $metadata['title'] ?? $title;
                        $url = $metadata['url'] ?? $url;
                    }
                }
                
                $item = [
                    'title' => $title,
                    'type' => $type,
                    'score' => 1.0
                ];
                
                if ($includePageLinks && !empty($url)) {
                    $item['url'] = $url;
                }
                
                if ($showSnippets && !empty($content)) {
                    $item['snippet'] = $this->createContentSnippet($content, 200);
                }
                
                $formatted[] = $item;
                
            } catch (\Exception $e) {
                error_log("Error formatting search result: " . $e->getMessage());
                continue;
            }
        }
        
        return $formatted;
    }

    private function createContentSnippet($content, $maxLength = 200)
    {
        $content = strip_tags($content);
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        $snippet = substr($content, 0, $maxLength);
        $lastSpace = strrpos($snippet, ' ');
        
        if ($lastSpace !== false) {
            $snippet = substr($snippet, 0, $lastSpace);
        }
        
        return $snippet . '...';
    }

    private function processRagDocuments($ragResults, $query, $intent = [])
    {
        try {
            // OPTIMIZED: Fast scoring-based document selection (no AI calls, no semantic filtering)
            $processedResults = [];
            $seenUrls = []; // Track URLs to prevent duplicates

            // Prepare candidate documents for fast scoring-based selection
            $candidateDocs = [];
            foreach ($ragResults as $result) {
                if (is_object($result)) {
                    // Extract URL from metadata
                    $url = $result->metadata['url'] ?? '';
                    if (empty($url)) {
                        continue; // Skip documents without URLs
                    }

                    // Skip if we've already seen this URL
                    if (in_array($url, $seenUrls)) {
                        continue;
                    }

                    $title = $result->sourceName ?? 'Relevant Page';
                    $content = $result->content ?? '';
                    $score = $result->score ?? 0;
                    $pageType = $result->metadata['pagetype'] ?? '';

                    // Apply page type scoring boost to prioritize certain content types
                    $boostedScore = $this->applyPageTypeBoost($score, $pageType);

                    // Only include documents with reasonable relevance scores (after boost)
                    if ($boostedScore >= 0.3) {
                        $candidateDocs[] = [
                            'title' => $title,
                            'url' => $url,
                            'content' => $content,
                            'score' => $boostedScore, // Use boosted score
                            'original_score' => $score, // Keep original for debugging
                            'page_type' => $pageType,
                            'boost_applied' => $boostedScore > $score ? round(($boostedScore - $score), 3) : 0
                        ];
                        $seenUrls[] = $url;
                    }
                }
            }

            // FAST SELECTION: Use deterministic scoring instead of AI calls
            if (!empty($candidateDocs)) {
                // Sort by boosted score and ensure content type diversity
                usort($candidateDocs, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                error_log("FAST DOCUMENT SELECTION: Starting with " . count($candidateDocs) . " candidate documents");
                
                // Log score boost summary
                $boostedDocs = array_filter($candidateDocs, function($doc) { return $doc['boost_applied'] > 0; });
                if (!empty($boostedDocs)) {
                    error_log("SCORE BOOSTS APPLIED: " . count($boostedDocs) . " documents received boosts");
                    foreach ($boostedDocs as $doc) {
                        error_log("BOOST: {$doc['page_type']} '{$doc['title']}' - Original: {$doc['original_score']}, Boosted: {$doc['score']} (+{$doc['boost_applied']})");
                    }
                }
                
                // FAST SEMANTIC FILTER: Remove obvious phonetic false positives without AI
                $candidateDocs = $this->fastSemanticFilter($candidateDocs, $query);
                error_log("FAST SEMANTIC FILTER: Reduced to " . count($candidateDocs) . " semantically relevant documents");
                
                // FAST SELECTION: Take top 7 documents by score, ensuring page type diversity
                $selectionResult = $this->getFastBalancedSelection($candidateDocs, 7);
                $selectedDocs = $selectionResult['selected'];
                $enhancedCandidates = $selectionResult['candidates']; // This includes parent lookup additions
                
                error_log("ENHANCED CANDIDATES: Final candidate list has " . count($enhancedCandidates) . " documents (after parent lookup)");
                
                // Create results from selected documents
                foreach ($selectedDocs as $index => $doc) {
                    $processedResults[] = [
                        'title' => $doc['title'],
                        'content' => $this->truncateContent($doc['content'], 150),
                        'url' => $doc['url'],
                        'type' => $doc['page_type'] ?: 'general',
                        'score' => $doc['score'],
                        'badge' => ucfirst(str_replace('_', ' ', $doc['page_type'] ?: 'Page')),
                        'ai_selected' => true,
                        'ai_order' => $index + 1,
                        'selection_reason' => $doc['selection_reason'] ?? 'Adaptive fast scoring'
                    ];
                }
                
                error_log("FAST SELECTION COMPLETE: Selected " . count($processedResults) . " documents in " . (microtime(true) - ($fastStart ?? microtime(true))) . "ms");
            }

            // FAST SUPPLEMENT: Add articles and case studies based on specialism (if available)
            $supplementaryDebugInfo = [];
            if (!empty($intent['specialism_id'])) {
                $supplementStartTime = microtime(true);
                $supplementResult = $this->getArticlesAndCaseStudiesBySpecialism($intent['specialism_id'], $query, 2, 2); // Keep final display at 2+2=4
                $supplementTime = round((microtime(true) - $supplementStartTime) * 1000, 2);
                
                // Handle both old format (array of docs) and new format (array with debug info)
                if (isset($supplementResult['documents'])) {
                    $supplementaryDocs = $supplementResult['documents'];
                    $supplementaryDebugInfo = $supplementResult['debug'] ?? [];
                } else {
                    $supplementaryDocs = $supplementResult; // Backwards compatibility
                }
                
                if (!empty($supplementaryDocs)) {
                    error_log("FAST SUPPLEMENT: Adding " . count($supplementaryDocs) . " supplementary documents in {$supplementTime}ms");
                    $processedResults = array_merge($processedResults, $supplementaryDocs);
                }
            }

            return [
                'documents' => $processedResults,
                'debug' => [
                    'total_candidate_docs' => count($enhancedCandidates ?? $candidateDocs),
                    'ai_selected_count' => count($processedResults),
                    'selection_method' => 'Fast scoring with parent page index lookup',
                    'score_threshold' => 0.3,
                    'max_candidates_processed' => count($enhancedCandidates ?? $candidateDocs),
                    'page_type_distribution' => $this->getPageTypeDistribution($enhancedCandidates ?? $candidateDocs),
                    'selected_type_distribution' => $this->getPageTypeDistribution($processedResults),
                    'candidate_documents' => array_map(function($doc) {
                        return [
                            'title' => $doc['title'],
                            'url' => $doc['url'],
                            'score' => round($doc['score'], 3),
                            'original_score' => round($doc['original_score'] ?? $doc['score'], 3),
                            'boost_applied' => $doc['boost_applied'] ?? 0,
                            'page_type' => $doc['page_type'],
                            'specialisms' => $doc['specialisms'] ?? $doc['service_section'] ?? '',
                            'source' => $doc['source'] ?? 'vector_search',
                            'found_via_parent' => $doc['found_via_parent'] ?? false,
                            'content_preview' => substr(strip_tags($doc['content']), 0, 100) . '...'
                        ];
                    }, $enhancedCandidates ?? $candidateDocs),
                    'selected_documents' => array_map(function($doc) {
                        return [
                            'title' => $doc['title'],
                            'url' => $doc['url'],
                            'score' => $doc['score'],
                            'page_type' => $doc['type'] ?? $doc['page_type'] ?? 'unknown',
                            'ai_selected' => $doc['ai_selected'] ?? true,
                            'ai_order' => $doc['ai_order'] ?? null,
                            'selection_reason' => $doc['selection_reason'] ?? 'Fast scoring-based selection'
                        ];
                    }, $processedResults),
                    'supplementary_content' => $supplementaryDebugInfo // Add supplementary debug info
                ]
            ];
            
        } catch (\Exception $e) {
            error_log('Error processing RAG documents: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            $fallbackResult = $this->fallbackRagProcessing($ragResults); // Return fallback results
            return [
                'documents' => $fallbackResult,
                'debug' => [
                    'total_candidate_docs' => count($ragResults),
                    'ai_selected_count' => count($fallbackResult),
                    'selection_method' => 'Fallback processing (AI selection failed)',
                    'score_threshold' => 'N/A',
                    'max_candidates_processed' => 0,
                    'error_message' => $e->getMessage()
                ]
            ];
        }
    }
    
    private function fallbackRagProcessing($ragResults)
    {
        $fallbackResults = [];
        $seenUrls = []; // Prevent duplicates in fallback too
        
        foreach ($ragResults as $result) {
            $title = 'Relevant Page';
            $content = '';
            $url = '';
            
            if (is_object($result)) {
                if (isset($result->sourceName)) {
                    $title = $result->sourceName;
                }
                if (isset($result->content)) {
                    $content = $this->truncateContent($result->content, 150);
                }
                if (isset($result->metadata['url'])) {
                    $url = $result->metadata['url'];
                }
            }
            
            // Skip duplicates even in fallback
            if (!empty($url) && in_array($url, $seenUrls)) {
                continue;
            }
            
            if (!empty($url)) {
                $seenUrls[] = $url;
            }
            
            $fallbackResults[] = [
                'title' => $title,
                'content' => $content,
                'url' => $url,
                'type' => 'general',
                'priority' => 5,
                'score' => 0.5, // Default score for fallback
                'badge' => 'Page'
            ];
        }
        
        // Increase fallback limit to match our enhanced target (5-6 results)
        return array_slice($fallbackResults, 0, 8);
    }
    
    /**
     * Fast balanced selection without AI calls - prioritize by score and ensure content diversity
     */
    private function getFastBalancedSelection($candidateDocs, $maxResults = 7)
    {
        $selected = [];
        $typeCount = [];
        
        // First, analyze what content types are actually available
        $availableTypes = [];
        foreach ($candidateDocs as $doc) {
            $type = $doc['page_type'] ?: 'unknown';
            $availableTypes[$type] = ($availableTypes[$type] ?? 0) + 1;
        }
        
        // SPECIAL CASE: If we have legal_service pages but no legal_service_index, 
        // find existing index pages by looking up parent pages
        if (isset($availableTypes['legal_service']) && !isset($availableTypes['legal_service_index'])) {
            error_log("PARENT LOOKUP: Starting search for legal_service_index pages via parent lookup");
            $existingIndexPages = $this->findLegalServiceIndexPages($candidateDocs);
            if (!empty($existingIndexPages)) {
                // Add found index pages to candidate documents
                $candidateDocs = array_merge($existingIndexPages, $candidateDocs);
                $availableTypes['legal_service_index'] = count($existingIndexPages);
                error_log("FOUND INDEX PAGES: Located " . count($existingIndexPages) . " existing legal_service_index pages via parent lookup");
            } else {
                error_log("PARENT LOOKUP: No legal_service_index pages found via parent lookup");
            }
        } else {
            if (!isset($availableTypes['legal_service'])) {
                error_log("PARENT LOOKUP: Skipped - no legal_service pages in candidates");
            }
            if (isset($availableTypes['legal_service_index'])) {
                error_log("PARENT LOOKUP: Skipped - legal_service_index pages already found in vector search");
            }
        }
        
        // Define priority content type requirements (back to original fixed requirements)
        $requiredTypes = [
            'legal_service_index' => 1,  // At least 1 index page
            'legal_service' => 2,        // At least 2 service pages  
            'blog_entry' => 1,           // At least 1 blog/article
            'article' => 1,              // At least 1 article
            'calculator_entry' => 1,     // At least 1 calculator
            'case_study' => 1,           // At least 1 case study
            'guide' => 1                 // At least 1 guide
        ];
        
        // First pass: Ensure required types are included (highest scoring of each type)
        foreach ($requiredTypes as $requiredType => $minCount) {
            $found = 0;
            foreach ($candidateDocs as $index => $doc) {
                if (count($selected) >= $maxResults) break;
                
                if ($doc['page_type'] === $requiredType && $found < $minCount && !in_array($index, $selected)) {
                    $selected[] = $index;
                    $typeCount[$requiredType] = ($typeCount[$requiredType] ?? 0) + 1;
                    $found++;
                }
            }
        }
        
        // Second pass: Fill remaining slots with highest scoring documents (any type)
        foreach ($candidateDocs as $index => $doc) {
            if (count($selected) >= $maxResults) break;
            
            if (!in_array($index, $selected)) {
                $selected[] = $index;
                $type = $doc['page_type'] ?: 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
        }
        
        // Return selected documents with selection reasoning
        $selectedDocs = [];
        foreach ($selected as $index) {
            if (isset($candidateDocs[$index])) {
                $doc = $candidateDocs[$index];
                $type = $doc['page_type'] ?: 'unknown';
                
                // Add selection reasoning
                if (isset($requiredTypes[$type])) {
                    if ($doc['found_via_parent'] ?? false) {
                        $doc['selection_reason'] = 'Found existing legal_service_index via parent lookup';
                    } else {
                        $doc['selection_reason'] = 'Required content type selection';
                    }
                } else {
                    $doc['selection_reason'] = 'Additional high-scoring content';
                }
                
                $selectedDocs[] = $doc;
            }
        }
        
        return [
            'selected' => $selectedDocs,
            'candidates' => $candidateDocs  // Enhanced candidates including parent lookup
        ];
    }
    
    /**
     * Find existing legal_service_index pages from parent pages of legal_service pages
     */
    private function findLegalServiceIndexPages($candidateDocs)
    {
        $indexPages = [];
        $foundIndexPageIds = [];
        
        try {
            error_log("PARENT LOOKUP: Using Concrete CMS Page objects");
            
            $legalServiceCount = 0;
            
            // Extract legal_service pages and find their parent legal_service_index pages
            foreach ($candidateDocs as $doc) {
                if ($doc['page_type'] === 'legal_service') {
                    $legalServiceCount++;
                    error_log("PARENT LOOKUP: Processing legal_service page: " . $doc['title'] . " URL: " . $doc['url']);
                    
                    // Extract page ID from URL
                    $pageId = $this->extractPageIdFromUrl($doc['url']);
                    error_log("PARENT LOOKUP: Extracted page ID: " . ($pageId ?: 'NULL') . " from URL: " . $doc['url']);
                    
                    if ($pageId) {
                        // Get the page object
                        $page = \Concrete\Core\Page\Page::getByID($pageId);
                        
                        if ($page && !$page->isError()) {
                            // Get parent page using proper CMS method
                            $parentPageId = $page->getCollectionParentID();
                            error_log("PARENT LOOKUP: Parent ID for page {$pageId}: " . ($parentPageId ?: 'NULL'));
                            
                            if ($parentPageId && $parentPageId > 1 && !in_array($parentPageId, $foundIndexPageIds)) {
                                // Get parent page object
                                $parentPage = \Concrete\Core\Page\Page::getByID($parentPageId);
                                
                                if ($parentPage && !$parentPage->isError()) {
                                    // Check if parent page is a legal_service_index page type
                                    $pageType = $parentPage->getPageTypeObject();
                                    $parentPageType = $pageType ? $pageType->getPageTypeHandle() : null;
                                    
                                    error_log("PARENT LOOKUP: Parent page {$parentPageId} has type: " . ($parentPageType ?: 'NULL'));
                                    
                                    if ($parentPageType === 'legal_service_index') {
                                        $foundIndexPageIds[] = $parentPageId;
                                        
                                        // Get page details
                                        $title = $parentPage->getCollectionName();
                                        $description = $parentPage->getCollectionDescription();
                                        $handle = $parentPage->getCollectionHandle();
                                        
                                        // Create full URL
                                        $fullUrl = $parentPage->getCollectionLink();
                                        
                                        // Use description or title for content
                                        $content = $description ?: $title;
                                        if ($description && $title !== $description) {
                                            $content = $title . ' - ' . $description;
                                        }
                                        
                                        $indexPages[] = [
                                            'title' => $title,
                                            'content' => $content,
                                            'url' => $fullUrl,
                                            'page_type' => 'legal_service_index',
                                            'score' => 0.85, // High score to ensure inclusion
                                            'original_score' => 0.85,
                                            'boost_applied' => 0,
                                            'found_via_parent' => true,
                                            'parent_page_id' => $parentPageId,
                                            'source' => 'parent_page_lookup'
                                        ];
                                        
                                        error_log("FOUND INDEX PAGE: {$title} (ID: {$parentPageId}) as parent of legal_service page {$pageId}");
                                    }
                                } else {
                                    error_log("PARENT LOOKUP: Failed to get parent page object for ID {$parentPageId}");
                                }
                            } else {
                                if (!$parentPageId) {
                                    error_log("PARENT LOOKUP: No parent found for page {$pageId}");
                                } elseif ($parentPageId <= 1) {
                                    error_log("PARENT LOOKUP: Parent {$parentPageId} is root level for page {$pageId}");
                                } elseif (in_array($parentPageId, $foundIndexPageIds)) {
                                    error_log("PARENT LOOKUP: Parent {$parentPageId} already processed for page {$pageId}");
                                }
                            }
                        } else {
                            error_log("PARENT LOOKUP: Failed to get page object for ID {$pageId}");
                        }
                    }
                }
            }
            
            error_log("PARENT LOOKUP: Processed {$legalServiceCount} legal_service pages, found " . count($indexPages) . " index pages");
            
            // Sort by score (highest first)
            usort($indexPages, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
        } catch (\Exception $e) {
            error_log("PARENT LOOKUP ERROR: " . $e->getMessage());
            error_log("PARENT LOOKUP TRACE: " . $e->getTraceAsString());
        }
        
        return $indexPages;
    }
    
    /**
     * Extract page ID from a URL using Concrete CMS Page methods
     */
    private function extractPageIdFromUrl($url)
    {
        try {
            error_log("URL EXTRACTION: Processing URL: " . $url);
            
            // Remove domain and get path
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                error_log("URL EXTRACTION: Failed to parse URL path from: " . $url);
                return null;
            }
            
            error_log("URL EXTRACTION: Extracted path: " . $path);
            
            // Use Concrete CMS Page::getByPath() method
            $page = \Concrete\Core\Page\Page::getByPath($path);
            
            if ($page && !$page->isError()) {
                $pageId = $page->getCollectionID();
                error_log("URL EXTRACTION: Found page ID {$pageId} by path: " . $path);
                return (int)$pageId;
            }
            
            error_log("URL EXTRACTION: No page found by path, trying segments");
            
            // If not found by full path, try segments
            $segments = explode('/', trim($path, '/'));
            if (!empty($segments)) {
                $lastSegment = end($segments);
                error_log("URL EXTRACTION: Trying last segment: " . $lastSegment);
                
                // Try getByHandle for the last segment
                $page = \Concrete\Core\Page\Page::getByHandle($lastSegment);
                if ($page && !$page->isError()) {
                    $pageId = $page->getCollectionID();
                    error_log("URL EXTRACTION: Found page ID {$pageId} by handle: " . $lastSegment);
                    return (int)$pageId;
                }
            }
            
            error_log("URL EXTRACTION: No page found for URL: " . $url);
            return null;
            
        } catch (\Exception $e) {
            error_log("URL EXTRACTION ERROR: " . $e->getMessage() . " for URL: " . $url);
            return null;
        }
    }
    
    /**
     * Fast semantic filter to remove obvious phonetic false positives without AI calls
     */
    private function fastSemanticFilter($candidateDocs, $query)
    {
        if (empty($candidateDocs) || empty($query)) {
            return $candidateDocs;
        }
        
        $queryLower = strtolower(trim($query));
        $queryWords = preg_split('/\s+/', $queryLower);
        $filteredDocs = [];
        
        foreach ($candidateDocs as $doc) {
            $titleLower = strtolower($doc['title']);
            $shouldInclude = true;
            
            // Check for obvious phonetic false positives
            foreach ($queryWords as $queryWord) {
                // Skip very short words
                if (strlen($queryWord) < 4) continue;
                
                // Get words from document title
                $titleWords = preg_split('/\s+/', $titleLower);
                
                foreach ($titleWords as $titleWord) {
                    // Calculate Levenshtein distance
                    $distance = levenshtein($queryWord, $titleWord);
                    
                    // If words are very similar (1-2 character difference) but not exact matches
                    if ($distance > 0 && $distance <= 2 && strlen($titleWord) >= 4) {
                        // Check if the exact query word appears anywhere in title or content
                        $docContent = strtolower($doc['content'] ?? '');
                        if (strpos($titleLower, $queryWord) === false && strpos($docContent, $queryWord) === false) {
                            // Get configurable known false positive patterns
                            $knownFalsePositivesJson = Config::get('katalysis.search.known_false_positives', $this->getDefaultKnownFalsePositives());
                            $knownFalsePositives = json_decode($knownFalsePositivesJson, true) ?: [];
                            
                            $isKnownFalsePositive = false;
                            foreach ($knownFalsePositives as $pattern) {
                                if ($queryWord === $pattern['query'] && $titleWord === $pattern['false']) {
                                    $isKnownFalsePositive = true;
                                    break;
                                }
                            }
                            
                            // For known false positives, ALWAYS reject regardless of score
                            // For other potential false positives, use stricter threshold
                            if ($isKnownFalsePositive) {
                                $shouldInclude = false;
                                error_log("FAST FILTER: Rejecting '{$doc['title']}' - KNOWN false positive: '{$queryWord}' vs '{$titleWord}' (distance: {$distance}, score: {$doc['score']}) - ALWAYS REJECTED");
                                break 2; // Break out of both loops
                            } elseif ($doc['score'] < 0.75) { // Raised threshold for other cases
                                $shouldInclude = false;
                                error_log("FAST FILTER: Rejecting '{$doc['title']}' - potential false positive: '{$queryWord}' vs '{$titleWord}' (distance: {$distance}, score: {$doc['score']})");
                                break 2; // Break out of both loops
                            }
                        }
                    }
                }
            }
            
            if ($shouldInclude) {
                $filteredDocs[] = $doc;
            }
        }
        
        return $filteredDocs;
    }

    private function truncateContent($content, $maxLength = 150)
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }
        
        return substr($content, 0, $maxLength - 3) . '...';
    }

    private function getArticlesAndCaseStudiesBySpecialism($specialismId, $query, $maxArticles = 2, $maxCaseStudies = 2)
    {
        try {
            error_log("SPECIALISM SUPPLEMENT: Getting articles and case studies for specialism ID: " . $specialismId);
            
            $db = Database::get();
            $supplementaryDocs = [];
            
            // Get Specialisms attribute key
            $ak = CollectionKey::getByHandle('specialisms');
            if (!is_object($ak)) {
                error_log("SPECIALISM SUPPLEMENT: 'specialisms' attribute key not found");
                return [];
            }
            
            // Process articles with query-based prioritization
            $articles = $this->getAndPrioritizeContentBySpecialism('article', $specialismId, $query, 15, $ak); // Get more to ensure good selection
            $caseStudies = $this->getAndPrioritizeContentBySpecialism('case_study', $specialismId, $query, 15, $ak); // Get more to ensure good selection
            
            // Combine articles and case studies, then sort by relevance score to get top 10 overall
            $allSupplementaryContent = [];
            
            // Add articles to combined list
            foreach ($articles as $article) {
                $allSupplementaryContent[] = [
                    'title' => $article['title'],
                    'url' => $article['url'],
                    'snippet' => $article['snippet'],
                    'score' => $article['relevance_score'],
                    'page_type' => 'article',
                    'selection_reason' => $article['selection_reason'],
                    'relevance_score' => $article['relevance_score']
                ];
            }
            
            // Add case studies to combined list
            foreach ($caseStudies as $caseStudy) {
                $allSupplementaryContent[] = [
                    'title' => $caseStudy['title'],
                    'url' => $caseStudy['url'],
                    'snippet' => $caseStudy['snippet'],
                    'score' => $caseStudy['relevance_score'],
                    'page_type' => 'case_study',
                    'selection_reason' => $caseStudy['selection_reason'],
                    'relevance_score' => $caseStudy['relevance_score']
                ];
            }
            
            // Sort combined content by relevance score (highest first)
            usort($allSupplementaryContent, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            // Take top 10 overall for debug display
            $top10ForDebug = array_slice($allSupplementaryContent, 0, 10);
            
            // Take the specified number for main display (ensuring we maintain type balance)
            $topArticles = [];
            $topCaseStudies = [];
            $articleCount = 0;
            $caseStudyCount = 0;
            
            foreach ($allSupplementaryContent as $item) {
                if ($item['page_type'] === 'article' && $articleCount < $maxArticles) {
                    $topArticles[] = $item;
                    $articleCount++;
                } elseif ($item['page_type'] === 'case_study' && $caseStudyCount < $maxCaseStudies) {
                    $topCaseStudies[] = $item;
                    $caseStudyCount++;
                }
                
                // Stop when we have enough of both types
                if ($articleCount >= $maxArticles && $caseStudyCount >= $maxCaseStudies) {
                    break;
                }
            }
            
            // Log debug information showing top 10 overall
            error_log("QUERY PRIORITIZATION DEBUG: Top 10 combined articles and case studies by score:");
            foreach ($top10ForDebug as $i => $item) {
                error_log("  " . ($i + 1) . ". '{$item['title']}' ({$item['page_type']}) - Score: {$item['relevance_score']} - {$item['selection_reason']}");
            }
            
            // Convert selected items to supplementary docs format for main display
            foreach ($topArticles as $article) {
                $supplementaryDocs[] = [
                    'title' => $article['title'],
                    'url' => $article['url'],
                    'snippet' => $article['snippet'],
                    'score' => $article['relevance_score'],
                    'page_type' => 'article',
                    'content_source' => 'specialism_supplement',
                    'badge' => 'Article',
                    'ai_selected' => false,
                    'selection_reason' => $article['selection_reason']
                ];
            }
            
            foreach ($topCaseStudies as $caseStudy) {
                $supplementaryDocs[] = [
                    'title' => $caseStudy['title'],
                    'url' => $caseStudy['url'],
                    'snippet' => $caseStudy['snippet'],
                    'score' => $caseStudy['relevance_score'],
                    'page_type' => 'case_study',
                    'content_source' => 'specialism_supplement',
                    'badge' => 'Case Study',
                    'ai_selected' => false,
                    'selection_reason' => $caseStudy['selection_reason']
                ];
            }
            
            error_log("SPECIALISM SUPPLEMENT: Selected " . count($topArticles) . " articles and " . count($topCaseStudies) . " case studies based on query relevance");
            
            // Prepare debug info using the top 10 combined items we already calculated
            $allSupplementaryForDebug = [];
            
            // Convert the top 10 combined items for debug display
            foreach ($top10ForDebug as $item) {
                $allSupplementaryForDebug[] = [
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'score' => $item['relevance_score'],
                    'page_type' => $item['page_type'],
                    'selection_reason' => $item['selection_reason']
                ];
            }
            
            return [
                'documents' => $supplementaryDocs,
                'debug' => [
                    'total_articles_found' => count($articles),
                    'total_case_studies_found' => count($caseStudies),
                    'displayed_articles' => count($topArticles),
                    'displayed_case_studies' => count($topCaseStudies),
                    'all_content_debug' => $allSupplementaryForDebug // For debug display - shows top 10 combined
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("SPECIALISM SUPPLEMENT: Error getting articles and case studies: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get and prioritize content (articles/case studies) by specialism with query-based relevance scoring
     */
    private function getAndPrioritizeContentBySpecialism($pageType, $specialismId, $query, $maxResults, $attributeKey)
    {
        try {
            error_log("QUERY PRIORITIZATION: Processing {$pageType} content for query: '{$query}'");
            
            // Create PageList to get content by specialism
            $pageList = new PageList();
            $pageList->filterByPageTypeID($this->getPageTypeID($pageType));
            $attributeKey->getController()->filterByAttribute($pageList, $specialismId);
            $pageList->sortByPublicDateDescending();
            $pageList->setItemsPerPage($maxResults);
            $pages = $pageList->getResults();
            
            error_log("QUERY PRIORITIZATION: Found " . count($pages) . " {$pageType} pages for specialism {$specialismId}");
            
            $scoredContent = [];
            $queryWords = $this->extractQueryWords($query);
            
            foreach ($pages as $page) {
                $title = $page->getCollectionName();
                $description = $page->getCollectionDescription() ?: '';
                $url = $page->getCollectionPath();
                
                // Calculate query-based relevance score
                $relevanceScore = $this->calculateContentRelevanceScore($title, $description, $queryWords, $page->getCollectionDatePublic());
                
                $scoredContent[] = [
                    'title' => $title,
                    'url' => $url,
                    'snippet' => $this->truncateContent($description, 150),
                    'relevance_score' => $relevanceScore,
                    'publication_date' => $page->getCollectionDatePublic(),
                    'selection_reason' => $this->getSelectionReason($relevanceScore, $queryWords, $title, $description)
                ];
                
                error_log("QUERY PRIORITIZATION: {$pageType} '{$title}' scored {$relevanceScore}");
            }
            
            // Sort by relevance score (highest first)
            usort($scoredContent, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            error_log("QUERY PRIORITIZATION: Sorted " . count($scoredContent) . " {$pageType} items by relevance");
            
            return $scoredContent;
            
        } catch (\Exception $e) {
            error_log("Error prioritizing {$pageType} content: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract meaningful words from query for matching
     */
    private function extractQueryWords($query)
    {
        // Convert to lowercase and split into words
        $words = preg_split('/\s+/', strtolower(trim($query)));
        
        // Remove common stop words and short words
        $stopWords = ['a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'will', 'with', 'i', 'my', 'me', 'we', 'our', 'us'];
        
        $meaningfulWords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        error_log("QUERY WORDS: Extracted meaningful words: " . implode(', ', $meaningfulWords));
        
        return array_values($meaningfulWords); // Re-index array
    }
    
    /**
     * Calculate content relevance score based on query matches
     */
    private function calculateContentRelevanceScore($title, $description, $queryWords, $publicationDate)
    {
        $score = 0.3; // Base score for being in the right specialism
        
        $titleLower = strtolower($title);
        $descriptionLower = strtolower($description);
        $allContent = $titleLower . ' ' . $descriptionLower;
        
        $exactMatches = 0;
        $partialMatches = 0;
        
        foreach ($queryWords as $word) {
            $wordLower = strtolower($word);
            
            // Skip very short words (less than 3 characters) as they're usually not meaningful
            if (strlen($wordLower) < 3) {
                continue;
            }
            
            // Title matches (highest weight) - exact word boundaries preferred
            if (preg_match('/\b' . preg_quote($wordLower, '/') . '\b/', $titleLower)) {
                $score += 0.25;
                $exactMatches++;
                error_log("SCORING: Exact title match for '$word' in '$title'");
            } elseif (strpos($titleLower, $wordLower) !== false) {
                $score += 0.15; // Partial title match
                $partialMatches++;
                error_log("SCORING: Partial title match for '$word' in '$title'");
            }
            
            // Description matches (medium weight)
            if (preg_match('/\b' . preg_quote($wordLower, '/') . '\b/', $descriptionLower)) {
                $score += 0.12;
                $exactMatches++;
                error_log("SCORING: Exact description match for '$word'");
            } elseif (strpos($descriptionLower, $wordLower) !== false) {
                $score += 0.08; // Partial description match
                $partialMatches++;
                error_log("SCORING: Partial description match for '$word'");
            }
        }
        
        // Exact phrase matching (higher bonus for multi-word queries)
        $queryPhrase = strtolower(implode(' ', $queryWords));
        if (strlen($queryPhrase) > 5) {
            if (strpos($allContent, $queryPhrase) !== false) {
                $score += 0.25; // Increased bonus for exact phrase match
                error_log("SCORING: Exact phrase match for '$queryPhrase'");
            }
        }
        
        // Multiple exact match bonus (progressive scoring)
        if ($exactMatches > 1) {
            $score += ($exactMatches - 1) * 0.1;
            error_log("SCORING: Multiple exact match bonus: " . (($exactMatches - 1) * 0.1));
        }
        
        // Coverage bonus - reward content that matches more of the query terms
        $totalQueryWords = count(array_filter($queryWords, function($word) { return strlen($word) >= 3; }));
        if ($totalQueryWords > 0) {
            $coverage = ($exactMatches + ($partialMatches * 0.5)) / $totalQueryWords;
            $coverageBonus = $coverage * 0.15; // Up to 0.15 bonus for full coverage
            $score += $coverageBonus;
            error_log("SCORING: Coverage bonus: $coverageBonus (coverage: $coverage)");
        }
        
        // Recency bonus (newer content gets slight boost)
        if ($publicationDate) {
            $timestamp = is_object($publicationDate) ? $publicationDate->getTimestamp() : strtotime($publicationDate);
            if ($timestamp) {
                $monthsOld = (time() - $timestamp) / (30 * 24 * 60 * 60);
                if ($monthsOld < 6) {
                    $score += 0.05;
                    error_log("SCORING: Recent content bonus (< 6 months)");
                } elseif ($monthsOld < 12) {
                    $score += 0.03;
                    error_log("SCORING: Moderate recency bonus (< 12 months)");
                }
            }
        }
        
        // Title length penalty (favor concise, focused titles)
        if (strlen($title) > 80) {
            $score -= 0.02;
            error_log("SCORING: Long title penalty");
        }
        
        // Cap the score at 0.95 to ensure variation in results
        return min($score, 0.95);
    }
    
    /**
     * Generate selection reason based on matching criteria
     */
    private function getSelectionReason($score, $queryWords, $title, $description)
    {
        if ($score > 0.8) {
            return 'Excellent query relevance + specialism match';
        } elseif ($score > 0.65) {
            return 'Very good query relevance + specialism match';
        } elseif ($score > 0.5) {
            return 'Good query relevance + specialism match';
        } elseif ($score > 0.4) {
            return 'Moderate query relevance + specialism match';
        } else {
            return 'Basic specialism match';
        }
    }
    
    private function getPageTypeID($handle)
    {
        try {
            $db = Database::get();
            $ptID = $db->GetOne("SELECT ptID FROM PageTypes WHERE ptHandle = ?", [$handle]);
            return $ptID ? (int)$ptID : 0;
        } catch (\Exception $e) {
            error_log("Error getting page type ID for '{$handle}': " . $e->getMessage());
            return 0;
        }
    }

    private function findBestMatchingSpecialism($serviceArea, $query, $allSpecialisms)
    {
        try {
            error_log("SPECIALISM MATCHING: AI selected service area: '{$serviceArea}' for query: '{$query}'");
            error_log("SPECIALISM MATCHING: Available specialisms: " . implode(', ', array_column($allSpecialisms, 'treeNodeName')));
            
            // First, try exact match (prefer child specialisms over parent ones)
            $exactMatches = [];
            foreach ($allSpecialisms as $specialism) {
                if (strcasecmp($specialism['treeNodeName'], $serviceArea) === 0) {
                    $exactMatches[] = $specialism;
                }
            }
            
            if (!empty($exactMatches)) {
                // If we have multiple exact matches, prefer child specialisms (those with a parent)
                $childMatches = array_filter($exactMatches, function($s) {
                    return !empty($s['parentName']);
                });
                
                $bestMatch = !empty($childMatches) ? $childMatches[0] : $exactMatches[0];
                error_log("SPECIALISM MATCHING: Found exact match for '{$serviceArea}' - '{$bestMatch['treeNodeName']}' (ID: {$bestMatch['treeNodeID']})");
                return $bestMatch['treeNodeID'];
            }
            
            // Second, try partial matches (prefer child specialisms)
            $partialMatches = [];
            foreach ($allSpecialisms as $specialism) {
                if (stripos($specialism['treeNodeName'], $serviceArea) !== false || 
                    stripos($serviceArea, $specialism['treeNodeName']) !== false) {
                    $partialMatches[] = $specialism;
                }
            }
            
            if (!empty($partialMatches)) {
                // Prefer child specialisms over parent ones
                $childMatches = array_filter($partialMatches, function($s) {
                    return !empty($s['parentName']);
                });
                
                $bestMatch = !empty($childMatches) ? $childMatches[0] : $partialMatches[0];
                error_log("SPECIALISM MATCHING: Found partial match: '{$bestMatch['treeNodeName']}' (ID: {$bestMatch['treeNodeID']}) for '{$serviceArea}'");
                return $bestMatch['treeNodeID'];
            }
            
            // Third, try semantic matching for known child categories (fallback to parent)
            $semanticMatches = [
                'road accident' => 'Injury Claims',
                'car accident' => 'Injury Claims', 
                'vehicle accident' => 'Injury Claims',
                'traffic accident' => 'Injury Claims',
                'rta' => 'Injury Claims',
                'motorbike accident' => 'Injury Claims',
                'motorcycle accident' => 'Injury Claims',
                'pedestrian accident' => 'Injury Claims',
                'work accident' => 'Injury Claims',
                'workplace accident' => 'Injury Claims',
                'serious injury' => 'Injury Claims'
            ];
            
            $serviceAreaLower = strtolower($serviceArea);
            if (isset($semanticMatches[$serviceAreaLower])) {
                $parentSpecialism = $semanticMatches[$serviceAreaLower];
                foreach ($allSpecialisms as $specialism) {
                    if (strcasecmp($specialism['treeNodeName'], $parentSpecialism) === 0) {
                        error_log("SPECIALISM MATCHING: Found semantic parent match: '{$specialism['treeNodeName']}' (ID: {$specialism['treeNodeID']}) for child '{$serviceArea}'");
                        return $specialism['treeNodeID'];
                    }
                }
            }
            
            error_log("SPECIALISM MATCHING: No match found for '{$serviceArea}'");
            return null;
            
        } catch (\Exception $e) {
            error_log("Error in findBestMatchingSpecialism: " . $e->getMessage());
            return null;
        }
    }
    
    private function getSpecialistRecommendations($query, $intent = [])
    {
        try {
            // OPTIMIZATION: Use specialism_id directly if available (like reviews optimization)
            if (($intent['intent_type'] ?? null) === 'service' && !empty($intent['specialism_id'] ?? null)) {
                error_log("Fast specialists search using specialism ID: " . $intent['specialism_id']);
                $specialists = $this->getSpecialistsByService($intent, 3);
                if (!empty($specialists)) {
                    return $specialists;
                }
            }
            
            // For service-specific queries without specialism ID, use service area
            if (($intent['intent_type'] ?? null) === 'service' && !empty($intent['service_area'] ?? null)) {
                error_log("Specialists search using service area: " . $intent['service_area']);
                $specialists = $this->getSpecialistsByService($intent['service_area'], 3);
                if (!empty($specialists)) {
                    return $specialists;
                }
            }
            
            // No AI vector search fallback - return empty if no specialism match
            error_log("No specialism ID or service area found - no specialists returned");
            return [];
            
        } catch (\Exception $e) {
            error_log('Error getting specialist recommendations: ' . $e->getMessage());
            return [];
        }
    }
    


    
    /**
     * Get office information for a specialist
     */
    private function getSpecialistOfficeInfo($specialistId): array
    {
        try {
            // Try to get specialist's location information from Person object
            $personObj = \Concrete\Package\KatalysisPro\Src\KatalysisPro\People\Person::getByID($specialistId);
            if ($personObj) {
                $places = $personObj->getPlaces($specialistId);
                if (!empty($places) && is_array($places)) {
                    $place = $places[0]; // Use first associated place
                    return [
                        'name' => $place['name'] ?? '',
                        'address' => trim(($place['address1'] ?? '') . ' ' . ($place['address2'] ?? '')),
                        'town' => $place['town'] ?? '',
                        'county' => $place['county'] ?? '',
                        'postcode' => $place['postcode'] ?? '',
                        'phone' => $place['phone'] ?? '',
                        'email' => $place['email'] ?? ''
                    ];
                }
            }
            
            // Fallback: Try to get office info from database
            $db = Database::get();
            $placeData = $db->GetRow("
                SELECT kp.name, kp.address1, kp.address2, kp.town, kp.county, kp.postcode, kp.phone, kp.email
                FROM KatalysisPlaces kp
                INNER JOIN KatalysisPeoplePlaces kpp ON kp.pID = kpp.placeID
                WHERE kpp.personID = ? 
                AND kp.active = 1
                LIMIT 1
            ", [$specialistId]);
            
            if ($placeData) {
                return [
                    'name' => $placeData['name'] ?? '',
                    'address' => trim(($placeData['address1'] ?? '') . ' ' . ($placeData['address2'] ?? '')),
                    'town' => $placeData['town'] ?? '',
                    'county' => $placeData['county'] ?? '',
                    'postcode' => $placeData['postcode'] ?? '',
                    'phone' => $placeData['phone'] ?? '',
                    'email' => $placeData['email'] ?? ''
                ];
            }
            
        } catch (\Exception $e) {
            error_log("Error getting office info for specialist $specialistId: " . $e->getMessage());
        }
        
        return []; // Return empty array if no office found
    }
    

    
    /**
     * Extract location name from search query for distance calculation
     */
    private function extractLocationFromQuery($query): ?string
    {
        $query = strtolower($query);
        
        // Look for "in [location]" or "near [location]" patterns
        if (preg_match('/\b(?:in|near)\s+([a-z\s]+)$/i', $query, $matches)) {
            return trim($matches[1]);
        }
        
        // Look for location names directly in the query
        $locations = [
            'llangollen', 'wrexham', 'chester', 'rhyl', 'shotton', 'colwyn bay',
            'ellesmere port', 'wallasey', 'bangor', 'caernarfon', 'prestatyn',
            'mold', 'ruthin', 'denbigh', 'flint', 'holywell', 'buckley'
        ];
        
        foreach ($locations as $location) {
            if (strpos($query, $location) !== false) {
                return $location;
            }
        }
        
        return null;
    }
    

    

    
    /**
     * Calculate location match score for a specialist
     */
    private function calculateLocationMatch($specialist, $locationKeywords): float
    {
        $score = 0;
        
        // Try to get specialist's location information from Person object
        try {
            $personObj = \Concrete\Package\KatalysisPro\Src\KatalysisPro\People\Person::getByID($specialist['sID']);
            if ($personObj) {
                $places = $personObj->getPlaces($specialist['sID']);
                if (!empty($places)) {
                    foreach ($places as $place) {
                        $placeName = strtolower($place['name'] ?? '');
                        
                        // Check if any location keyword matches this place
                        foreach ($locationKeywords as $keyword) {
                            if (strpos($placeName, strtolower($keyword)) !== false) {
                                $score += 3; // Strong location match
                                error_log("Location match: {$specialist['name']} works in {$placeName}, matches keyword: {$keyword}");
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Error getting location info for specialist {$specialist['sID']}: " . $e->getMessage());
        }
        
        return $score;
    }
    
    private function mapJobTitleToExpertise($jobTitle, $department = '')
    {
        $jobTitle = strtolower($jobTitle ?: '');
        $department = strtolower($department ?: '');
        
        if (strpos($jobTitle, 'conveyancing') !== false || strpos($department, 'conveyancing') !== false) {
            return 'Conveyancing & Property Law';
        } elseif (strpos($jobTitle, 'family') !== false || strpos($department, 'family') !== false) {
            return 'Family Law';
        } elseif (strpos($jobTitle, 'personal injury') !== false || strpos($jobTitle, 'medical negligence') !== false) {
            return 'Personal Injury & Medical Negligence';
        } elseif (strpos($jobTitle, 'employment') !== false || strpos($department, 'employment') !== false) {
            return 'Employment Law';
        } elseif (strpos($jobTitle, 'litigation') !== false || strpos($department, 'litigation') !== false) {
            return 'Litigation & Disputes';
        } elseif (strpos($jobTitle, 'probate') !== false || strpos($jobTitle, 'wills') !== false) {
            return 'Wills & Probate';
        } elseif (strpos($jobTitle, 'director') !== false || strpos($jobTitle, 'managing') !== false || strpos($jobTitle, 'head') !== false) {
            return 'Senior Legal Practice';
        } else {
            return 'General Practice';
        }
    }
    
    private function findSpecialismsByQuery($query)
    {
        try {
            // Simple keyword matching with specialisms from TreeNodes
            $db = Database::get();
            $keywords = explode(' ', strtolower($query));
            $likeConditions = [];
            $params = [];
            
            foreach ($keywords as $i => $keyword) {
                if (strlen($keyword) > 2) { // Only search for words longer than 2 characters
                    $likeConditions[] = "LOWER(TreeNodes.treeNodeName) LIKE ?";
                    $params[] = '%' . $keyword . '%';
                }
            }
            
            if (empty($likeConditions)) {
                return [];
            }
            
            $sql = "SELECT DISTINCT TreeNodes.treeNodeID FROM TreeNodes WHERE " . implode(' OR ', $likeConditions);
            $results = $db->GetAll($sql, $params);
            
            return array_column($results, 'treeNodeID');
            
        } catch (\Exception $e) {
            error_log('Error finding specialisms by query: ' . $e->getMessage());
            return [];
        }
    }

    private function getRelevantReviews($query)
    {
        try {
            error_log("Starting AI-powered review search with query: " . $query);
            
            // Try AI vector search first
            try {
                $vectorBuilder = new \KatalysisProAi\KatalysisProIndexService();
                $reviews = $vectorBuilder->searchReviews($query, 3);
                
                if (!empty($reviews)) {
                    error_log("Found " . count($reviews) . " reviews using AI vector search");
                    return $reviews;
                }
            } catch (\Exception $e) {
                error_log("Vector search error for reviews: " . $e->getMessage());
            }
            
            // Fallback to error if vector search fails
            return [['error' => 'Review search failed - AI vector search unavailable']];
            
        } catch (\Exception $e) {
            error_log('Error getting relevant reviews: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }
    

    

    
    private function getPlaceRecommendations($query)
    {
        try {
            // Try AI vector search first
            try {
                $vectorBuilder = new \KatalysisProAi\KatalysisProIndexService();
                $places = $vectorBuilder->searchPlaces($query, 3);
                
                if (!empty($places)) {
                    return $places;
                }
            } catch (\Exception $e) {
                error_log("Vector search error for places: " . $e->getMessage());
            }
            
            // Fallback: return error if vector search fails
            return [['error' => 'Place search unavailable']];
            
        } catch (\Exception $e) {
            error_log('Error getting place recommendations: ' . $e->getMessage());
            return [['error' => 'Place search failed: ' . $e->getMessage()]];
        }
    }
    

    
    private function logSearch($query, $blockId, $aiResponse = '', $intent = [], $fullResults = [])
    {
        try {
            // Create Search entity record for comprehensive tracking
            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $search = new \KatalysisProAi\Entity\Search();
            
            // Core search information
            $search->setQuery($query);
            $search->setStarted(new \DateTime());
            $search->setCreatedDate(new \DateTime());
            
            // Create comprehensive result summary including intent analysis and AI response
            $resultSummary = [
                'ai_response' => $aiResponse,
                'intent_analysis' => $intent,
                'processing_summary' => [
                    'query' => $query,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'processing_time_ms' => $fullResults['processing_time'] ?? 0,
                    'optimization_approach' => $fullResults['debug']['approach'] ?? 'standard',
                ]
            ];
            
            // Add counts and summaries if available
            if (!empty($fullResults)) {
                $resultSummary['results_summary'] = [
                    'pages_found' => count($fullResults['pages'] ?? []),
                    'specialists_found' => count($fullResults['specialists'] ?? []),
                    'reviews_found' => count($fullResults['reviews'] ?? []),
                    'places_found' => count($fullResults['places'] ?? []),
                ];
                
                // Add debug information if available
                if (!empty($fullResults['debug'])) {
                    $resultSummary['debug_summary'] = [
                        'intent_type' => $fullResults['debug']['intent_analysis']['intent_type'] ?? 'unknown',
                        'service_area' => $fullResults['debug']['intent_analysis']['service_area'] ?? 'none',
                        'confidence' => $fullResults['debug']['intent_analysis']['confidence'] ?? 0,
                        'processing_time_ms' => $fullResults['debug']['processing_time_ms'] ?? 0,
                    ];
                }
            }
            
            // Store as JSON in resultSummary field
            $search->setResultSummary(json_encode($resultSummary, JSON_PRETTY_PRINT));
            
            // Page context information
            $launchPageUrl = $this->request->request->get('launch_page_url', '');
            $launchPageTitle = $this->request->request->get('launch_page_title', '');
            $launchPageType = $this->request->request->get('launch_page_type', '');
            
            if ($launchPageUrl) {
                $search->setLaunchPageUrl($launchPageUrl);
            }
            if ($launchPageTitle) {
                $search->setLaunchPageTitle($launchPageTitle);
            }
            if ($launchPageType) {
                $search->setLaunchPageType($launchPageType);
            }
            
            // UTM tracking parameters
            $search->setUtmSource($this->request->request->get('utm_source', ''));
            $search->setUtmMedium($this->request->request->get('utm_medium', ''));
            $search->setUtmCampaign($this->request->request->get('utm_campaign', ''));
            $search->setUtmTerm($this->request->request->get('utm_term', ''));
            $search->setUtmContent($this->request->request->get('utm_content', ''));
            
            // Session and user tracking
            $user = new \Concrete\Core\User\User();
            if ($user->isRegistered()) {
                $search->setCreatedBy($user->getUserID());
            }
            
            // Session ID and technical information
            $search->setSessionId(session_id());
            $search->setLocation($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            
            // LLM model information (could be enhanced based on config)
            $search->setLlm('OpenAI GPT-4'); // Default model
            
            // Save the search entity
            $entityManager->persist($search);
            $entityManager->flush();
            
            error_log("Search logged to database: ID {$search->getId()}, Query: {$query}, Response Length: " . strlen($aiResponse));
            
        } catch (\Exception $e) {
            error_log('Database search logging failed: ' . $e->getMessage());
            
            // Fallback to file-based logging if database logging fails
            try {
                $logDir = DIR_APPLICATION . '/files/katalysis_search_logs';
                
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $logFile = $logDir . '/searches_' . date('Y-m') . '.log';
                $logEntry = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'query' => $query,
                    'block_id' => $blockId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'ai_response_length' => strlen($aiResponse),
                    'intent_type' => $intent['intent_type'] ?? 'unknown',
                    'error' => 'DB logging failed: ' . $e->getMessage()
                ];
                
                file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
            } catch (\Exception $fileLogException) {
                error_log('File fallback logging also failed: ' . $fileLogException->getMessage());
            }
        }
    }

    public function getPageTitle()
    {
        return t('Search Settings');
    }
    
    /**
     * Get specialisms from the Specialisms topic tree using TopicTree::getByName()
     * Returns only the actual specialisms, not all TreeNodes
     */
    private function getSpecialisms(): array
    {
        try {
            $db = Database::get();
            
            // Get the Specialisms tree using TopicTree::getByName()
            $specialismsTree = TopicTree::getByName('Specialisms');
            if (!$specialismsTree) {
                error_log("WARNING: Specialisms tree not found by name");
                return [];
            }
            
            $specialismsTreeId = $specialismsTree->getRootTreeNodeID();
            error_log("Found Specialisms tree with root ID: " . $specialismsTreeId);
            
            // Get ALL nodes in the Specialisms tree (including children) by checking the tree path
            // This will give us both parent and child specialisms like "Injury Claims" and "Road Accident"
            $specialisms = $db->GetAll("
                SELECT tn.treeNodeID, tn.treeNodeName, tn.treeNodeParentID,
                       parent.treeNodeName as parentName
                FROM TreeNodes tn
                LEFT JOIN TreeNodes parent ON tn.treeNodeParentID = parent.treeNodeID
                WHERE tn.treeID = (SELECT treeID FROM TreeNodes WHERE treeNodeID = ?)
                AND tn.treeNodeID != ?
                ORDER BY tn.treeNodeParentID, tn.treeNodeName
            ", [$specialismsTreeId, $specialismsTreeId]);
            
            if (empty($specialisms)) {
                error_log("WARNING: No specialisms found in Specialisms tree (root ID: " . $specialismsTreeId . ")");
                return [];
            }
            
            // Log the full hierarchy for debugging
            $topLevel = array_filter($specialisms, function($s) use ($specialismsTreeId) { 
                return $s['treeNodeParentID'] == $specialismsTreeId; 
            });
            $children = array_filter($specialisms, function($s) use ($specialismsTreeId) { 
                return $s['treeNodeParentID'] != $specialismsTreeId; 
            });
            
            error_log("Found " . count($specialisms) . " total specialisms (" . count($topLevel) . " top-level, " . count($children) . " children)");
            error_log("Top-level specialisms: " . implode(', ', array_column($topLevel, 'treeNodeName')));
            if (!empty($children)) {
                foreach ($children as $child) {
                    error_log("Child specialism: '{$child['treeNodeName']}' (parent: {$child['parentName']}, ID: {$child['treeNodeID']})");
                }
            }
            
            return $specialisms;
            
        } catch (\Exception $e) {
            error_log("Error getting specialisms from tree: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build optimized query based on intent analysis
     */
    private function buildIntentBasedQuery($query, $intent): string
    {
        // Use comprehensive prompts for detailed AI responses
        switch ($intent['intent_type']) {
            case 'service':
                return "Based on the search query '{$query}', provide a comprehensive explanation that includes:

1. DIRECT ANSWER: Answer the specific question or query asked
2. RELATED SERVICES: Detail all relevant legal services, expertise areas, and specializations we offer that relate to this query
3. OUR CAPABILITIES: Explain our firm's specific experience, qualifications, and track record in these areas
4. PRACTICAL GUIDANCE: Provide helpful information, next steps, or considerations related to this topic
5. WHY CHOOSE US: Highlight what makes our approach or expertise distinctive in this area

Please structure your response to be informative and comprehensive, helping the user understand both the answer to their query and the full scope of how we can assist them in this area of law. Use a professional but accessible tone.

Query: {$query}";
                
            case 'location':
                $location = $intent['location_mentioned'] ?? 'your area';
                return "Based on the search query '{$query}' and location '{$location}', provide a comprehensive response that includes:

1. DIRECT ANSWER: Address the specific location-related query
2. OUR OFFICES: Detail our office locations, services available, and accessibility in {$location}
3. LOCAL EXPERTISE: Explain our experience and knowledge of local legal matters and regulations
4. CONTACT INFORMATION: Provide relevant contact details, addresses, and directions
5. WHY CHOOSE OUR LOCAL SERVICES: Highlight the benefits of our local presence and community connections

Use a professional but welcoming tone that demonstrates our local knowledge and accessibility.

Query: {$query}";
                
            case 'situation':
                return "Based on the search query '{$query}', provide a comprehensive response that includes:

1. DIRECT ANSWER: Address the specific legal situation or concern
2. IMMEDIATE GUIDANCE: Explain what the person should do right now and any urgent considerations
3. OUR SERVICES: Detail how our legal services can help with this specific situation
4. PROCESS OVERVIEW: Outline the typical legal process, timeline, and what to expect
5. NEXT STEPS: Provide clear guidance on how to proceed and contact us for assistance

Use a reassuring and professional tone that demonstrates our expertise while being accessible to non-lawyers.

Query: {$query}";
                
            default: // information
                return "Based on the search query '{$query}', provide a comprehensive explanation that includes:

1. DIRECT ANSWER: Answer the specific question or query asked
2. RELATED INFORMATION: Provide relevant context, background, and related legal concepts
3. OUR EXPERTISE: Explain our firm's knowledge and experience in this area
4. PRACTICAL GUIDANCE: Offer helpful information, considerations, or next steps
5. HOW WE CAN HELP: Detail the specific ways our legal services can assist with related matters

Please structure your response to be informative and comprehensive, helping the user understand the topic and how we can assist them. Use a professional but accessible tone.

Query: {$query}";
        }
    }
    
    /**
     * Get targeted specialists based on intent using specialism topics
     */
    private function getTargetedSpecialists($query, $intent): array
    {
        try {
            $maxSpecialists = $intent['suggested_specialist_count'] ?? 3;
            
            // For person-specific queries, search by name first
            if (($intent['intent_type'] ?? null) === 'person' && !empty($intent['person_name'] ?? null)) {
                error_log("Using person-based specialist search for: " . $intent['person_name']);
                $personResults = $this->getSpecialistsByName($intent['person_name'], $maxSpecialists);
                if (!empty($personResults)) {
                    return $personResults;
                }
                // If no exact name match, fall through to other search methods
                error_log("No exact person match found for '{$intent['person_name']}', trying other methods");
            }
            
            // For service-specific queries, use specialism-based filtering
            if (($intent['intent_type'] ?? null) === 'service' && !empty($intent['service_area'] ?? null)) {
                error_log("Using specialism-based specialist search for service: " . $intent['service_area']);
                // FIXED: Pass the full intent object instead of just the service area string
                return $this->getSpecialistsByService($intent, $maxSpecialists);
            }
            
            // For location queries, prioritize local specialists
            if (($intent['intent_type'] ?? null) === 'location' && ($intent['location_mentioned'] ?? null)) {
                return $this->getSpecialistsByLocation($intent['location_mentioned'], $maxSpecialists);
            }
            
            // For urgent situations, get most experienced specialists
            if (($intent['urgency_level'] ?? null) === 'high') {
                return $this->getSeniorSpecialists($maxSpecialists);
            }
            
            // Default: try specialism-based search with the full intent object
            error_log("Using specialism-based search for general query: " . $query);
            $specialismResults = $this->getSpecialistsByService($intent, $maxSpecialists);
            
            if (!empty($specialismResults)) {
                return $specialismResults;
            }
            
            // Return error instead of fallback
            return [['error' => 'No specialist search strategy could be applied for this query']];
            
        } catch (\Exception $e) {
            error_log("Targeted specialist search error: " . $e->getMessage());
            return [['error' => 'Specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get targeted reviews based on intent using specialism topics
     */
    private function getTargetedReviews($query, $intent): array
    {
        try {
            // OPTIMIZATION: Use specialism_id directly if available (much faster)
            if (($intent['intent_type'] ?? null) === 'service' && !empty($intent['specialism_id'] ?? null)) {
                $serviceArea = $intent['service_area'] ?? '';
                return $this->getReviewsBySpecialismId($intent['specialism_id'], $serviceArea);
            }
            
            // For other queries without specialism_id, return general high-rating reviews as fallback
            return $this->getGeneralHighRatingReviews();
            
        } catch (\Exception $e) {
            error_log("Targeted review search error: " . $e->getMessage());
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get targeted places based on intent
     */
    private function getTargetedPlaces($query, $intent): array
    {
        try {
            // OPTIMIZATION: Only search for places when location is relevant to the query
            
            // For explicit location queries, prioritize specific location
            if (($intent['intent_type'] ?? null) === 'location' && ($intent['location_mentioned'] ?? null)) {
                error_log("Location-specific query detected: " . $intent['location_mentioned']);
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // For service queries that explicitly mention a location
            if (($intent['intent_type'] ?? null) === 'service' && ($intent['location_mentioned'] ?? null)) {
                error_log("Service query with location context: " . $intent['location_mentioned']);
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // For urgent situations that mention a location, get nearest offices
            if (($intent['urgency_level'] ?? null) === 'high' && ($intent['location_mentioned'] ?? null)) {
                error_log("Urgent query with location: " . $intent['location_mentioned']);
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // NEW: Skip places entirely if no location mentioned and not a location query
            if (empty($intent['location_mentioned'] ?? null) && ($intent['intent_type'] ?? null) !== 'location') {
                error_log("No location mentioned in non-location query - skipping places search");
                return [];
            }
            
            // Fallback: if somehow we get here, return empty array
            error_log("Places search skipped - no relevant location context found");
            return [];
            
        } catch (\Exception $e) {
            error_log("Targeted places search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get specialists filtered by service area using proper Katalysis Pro PeopleList class
     */
    private function getSpecialistsByService($intentOrServiceArea, $maxResults = 3): array
    {
        try {
            error_log("=== USING KATALYSIS PRO PEOPLELIST CLASS ===");
            
            // Handle both intent object and legacy string input
            if (is_array($intentOrServiceArea)) {
                $serviceArea = $intentOrServiceArea['service_area'] ?? 'unknown';
                $specialismId = $intentOrServiceArea['specialism_id'] ?? null;
                error_log("Service Area: '$serviceArea'");
                error_log("Specialism ID from intent: " . ($specialismId ? $specialismId : 'null'));
            } else {
                // Legacy string input
                $serviceArea = $intentOrServiceArea;
                $specialismId = null;
                error_log("Service Area: '$serviceArea'");
                error_log("Legacy string input - no specialism ID available");
            }
            
            error_log("Max Results: $maxResults");
            
            // Use specialism_id from intent if available (much faster)
            if ($specialismId) {
                error_log("Using identified specialism ID: $specialismId for fast filtering");
                
                // Direct specialism-based search using proper filterBySpecialisms method
                $peopleList = new PeopleList();
                $peopleList->filterByActive();
                $peopleList->filterBySpecialisms([$specialismId]);
                $peopleList->limitResults($maxResults);
                
                $results = $peopleList->getResults();
                error_log("PeopleList with specialism ID returned " . count($results) . " specialists");
                
                if (!empty($results)) {
                    return $this->formatPeopleListResults($results);
                } else {
                    error_log("No specialists found for specialism ID $specialismId");
                    
                    // SMART FALLBACK: Try parent topic if child topic has no specialists
                    $parentSpecialismId = $this->getParentSpecialismId($specialismId);
                    if ($parentSpecialismId && $parentSpecialismId !== $specialismId) {
                        error_log("Trying parent specialism ID: $parentSpecialismId");
                        
                        $parentPeopleList = new PeopleList();
                        $parentPeopleList->filterByActive();
                        $parentPeopleList->filterBySpecialisms([$parentSpecialismId]);
                        $parentPeopleList->limitResults($maxResults);
                        
                        $parentResults = $parentPeopleList->getResults();
                        error_log("Parent specialism ID $parentSpecialismId returned " . count($parentResults) . " specialists");
                        
                        if (!empty($parentResults)) {
                            error_log("SUCCESS: Found specialists using parent specialism");
                            return $this->formatPeopleListResults($parentResults);
                        }
                    }
                    
                    // DEBUG: Check if there are ANY specialism associations
                    $db = Database::get();
                    $specialismAssociations = $db->GetOne("SELECT COUNT(*) FROM KatalysisPeopleSpecialism WHERE specialismID = ?", [$specialismId]);
                    error_log("Total people linked to specialism $specialismId: $specialismAssociations");
                    
                    // FAST FAIL: No specialists in child or parent topic
                    error_log("Fast fail - no specialists found for specialism $specialismId or its parent");
                    return [];
                }
            }
            
            // FAST FAIL: No specialism ID means no results  
            error_log("No specialism ID available - returning empty");
            return [];
            
        } catch (\Exception $e) {
            error_log("PeopleList specialist search error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return [['error' => 'Specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get specialists by name for person-specific searches
     */
    private function getSpecialistsByName($personName, $maxResults = 3): array
    {
        try {
            error_log("Searching for person by name: " . $personName);
            
            $db = Database::get();
            
            // Clean and prepare the search name
            $cleanName = trim($personName);
            $nameWords = explode(' ', $cleanName);
            
            // Build flexible name search query
            $nameConditions = [];
            $params = [];
            
            // Search by full name (exact match)
            $nameConditions[] = "LOWER(p.name) LIKE ?";
            $params[] = '%' . strtolower($cleanName) . '%';
            
            // Search by individual words in name
            foreach ($nameWords as $word) {
                if (strlen(trim($word)) > 2) { // Only search meaningful words
                    $nameConditions[] = "LOWER(p.name) LIKE ?";
                    $params[] = '%' . strtolower(trim($word)) . '%';
                }
            }
            
            // Also check if it might be searching by job title + name combination
            $nameConditions[] = "LOWER(CONCAT(p.jobTitle, ' ', p.name)) LIKE ?";
            $params[] = '%' . strtolower($cleanName) . '%';
            
            $sql = "SELECT p.sID, p.name, p.jobTitle, p.email, p.phone, p.featured, p.shortBiography
                    FROM KatalysisPeople p
                    WHERE p.active = 1 
                    AND (" . implode(' OR ', $nameConditions) . ")
                    ORDER BY 
                        CASE WHEN LOWER(p.name) = ? THEN 1 ELSE 2 END,  -- Exact name match first
                        p.featured DESC,
                        CHAR_LENGTH(p.name) ASC,  -- Shorter names first (likely more specific)
                        p.name ASC
                    LIMIT " . (int)$maxResults;
            
            // Add exact match parameter for ordering
            $params[] = strtolower($cleanName);
            
            $specialists = $db->GetAll($sql, $params);
            
            if (empty($specialists)) {
                error_log("No specialists found for name: " . $personName);
                return [];
            }
            
            error_log("Found " . count($specialists) . " specialists matching name: " . $personName);
            return $this->formatSpecialistResults($specialists);
            
        } catch (\Exception $e) {
            error_log("Name-based specialist search error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return [['error' => 'Person search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get specialists by location preference
     */
    private function getSpecialistsByLocation($location, $maxResults = 1): array
    {
        try {
            // Get specialists associated with offices in or near the specified location
            $db = Database::get();
            
            $sql = "SELECT DISTINCT p.sID, p.name, p.jobTitle, p.email, p.phone, p.featured
                    FROM KatalysisPeople p
                    INNER JOIN KatalysisPeoplePlaces pp ON p.sID = pp.personID
                    INNER JOIN KatalysisPlaces pl ON pp.placeID = pl.pID
                    WHERE p.active = 1 AND pl.active = 1
                    AND (LOWER(pl.name) LIKE ? OR LOWER(pl.town) LIKE ? OR LOWER(pl.county) LIKE ?)
                    ORDER BY p.featured DESC, p.jobTitle LIKE '%director%' DESC
                    LIMIT " . (int)$maxResults;
            
            $locationPattern = '%' . strtolower($location) . '%';
            $specialists = $db->GetAll($sql, [$locationPattern, $locationPattern, $locationPattern]);
            
            if (empty($specialists)) {
                // Fallback to senior specialists if no location match
                return $this->getSeniorSpecialists($maxResults);
            }
            
            return $this->formatSpecialistResults($specialists);
            
        } catch (\Exception $e) {
            error_log("Location-specific specialist search error: " . $e->getMessage());
            return $this->getSeniorSpecialists($maxResults);
        }
    }
    
    /**
     * Get senior/experienced specialists for urgent situations
     */
    private function getSeniorSpecialists($maxResults = 1): array
    {
        try {
            $db = Database::get();
            
            $sql = "SELECT sID, name, jobTitle, email, phone, featured
                    FROM KatalysisPeople 
                    WHERE active = 1
                    ORDER BY 
                        featured DESC,
                        CASE 
                            WHEN jobTitle LIKE '%director%' THEN 3
                            WHEN jobTitle LIKE '%head%' THEN 2  
                            WHEN jobTitle LIKE '%senior%' THEN 1
                            ELSE 0
                        END DESC
                    LIMIT ?";
            
            $specialists = $db->GetAll($sql, [$maxResults]);
            
            return $this->formatSpecialistResults($specialists);
            
        } catch (\Exception $e) {
            error_log("Senior specialist search error: " . $e->getMessage());
            return [['error' => 'Senior specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Format specialist results consistently
     */
    private function formatSpecialistResults($specialists): array
    {
        $results = [];
        foreach ($specialists as $specialist) {
            $officeInfo = $this->getSpecialistOfficeInfo($specialist['sID']);
            
            $results[] = [
                'id' => $specialist['sID'],
                'name' => $specialist['name'],
                'title' => $specialist['jobTitle'] ?: 'Specialist',
                'expertise' => $this->mapJobTitleToExpertise($specialist['jobTitle']),
                'contact' => $specialist['email'] ?: $specialist['phone'] ?: 'Contact Available',
                'email' => $specialist['email'],
                'phone' => $specialist['phone'],
                'featured' => (bool)$specialist['featured'],
                'office' => $officeInfo,
                'relevance_score' => 8, // High score for targeted results
                'optimized_match' => true
            ];
        }
        
        return $results;
    }
    



    /**
     * Format PeopleList results for API response
     */
    private function formatPeopleListResults($results): array
    {
        $formattedResults = [];
        foreach ($results as $person) {
            error_log("Processing person: " . $person->name . " (ID: " . $person->sID . ", Job: " . $person->jobTitle . ")");
            
            // Get specialisms/topics for this person
            $specialisms = $person->getSpecialisms($person->sID);
            error_log("Person specialisms: " . json_encode($specialisms));
            
            $expertise = !empty($specialisms) ? 
                implode(', ', array_column($specialisms, 'treeNodeName')) : 
                $this->mapJobTitleToExpertise($person->jobTitle);
            
            error_log("Final mapped expertise: " . $expertise);
            
            $formattedResults[] = [
                'id' => $person->sID,
                'name' => $person->name,
                'title' => $person->jobTitle ?: 'Specialist',
                'expertise' => $expertise,
                'contact' => $person->email ?: $person->phone ?: 'Contact Available',
                'email' => $person->email,
                'phone' => $person->phone,
                'featured' => (bool)$person->featured,
                'relevance_score' => 9, // High score for specialism-matched results
                'sort_order' => $person->sortOrder,
                'specialism_match' => true
            ];
        }
        
        error_log("SPECIALIST SEARCH COMPLETE: Returning " . count($formattedResults) . " PeopleList specialists");
        return $formattedResults;
    }
    
    /**
     * Get service-specific reviews using specialism topics
     */
    /**
     * FAST: Get reviews directly by specialism ID (no AI needed)
     */
    private function getReviewsBySpecialismId($specialismId, $serviceArea = ''): array
    {
        try {
            // Direct database query using ReviewList with specialism filtering
            $reviewList = new ReviewList();
            $reviewList->filterByActive();
            $reviewList->filterBySpecialisms([$specialismId]); // Use filterBySpecialisms for KatalysisReviewSpecialism table
            $reviewList->limitResults(3);
            
            $results = $reviewList->getResults();
            
            if (empty($results)) {
                // Check if any reviews are linked to this specialism and total active reviews
                $db = Database::get();
                $reviewSpecialismCount = $db->GetOne("SELECT COUNT(*) FROM KatalysisReviewSpecialism WHERE specialismID = ?", [$specialismId]);
                $totalActiveReviews = $db->GetOne("SELECT COUNT(*) FROM KatalysisReviews WHERE active = 1");
                
                // If no reviews for this specialism but reviews exist, return general high-rating reviews
                if ($totalActiveReviews > 0 && $reviewSpecialismCount == 0) {
                    return $this->getGeneralHighRatingReviews();
                }
                
                return [['error' => 'No reviews found for this specialism']];
            }
            
            // Format results for API response
            $formattedResults = [];
            foreach ($results as $review) {
                $formattedResults[] = [
                    'id' => $review->sID,
                    'client_name' => $review->author ?: 'Anonymous',
                    'organization' => $review->organization ?: '',
                    'rating' => (int)($review->rating ?: 5),
                    'review' => $review->extract ?: $review->review ?: 'Excellent service',
                    'source' => $review->source ?: 'Client Review',
                    'featured' => (bool)$review->featured,
                    'relevance_score' => 10, // Perfect score for direct specialism match
                    'specialism_match' => true
                ];
            }
            
            return $formattedResults;
            
        } catch (\Exception $e) {
            error_log("Fast review search error: " . $e->getMessage());
            // Return error message instead of fallback
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }

    /**
     * Get outcome-focused reviews for urgent situations
     */
    private function getOutcomeFocusedReviews(): array
    {
        try {
            $db = Database::get();
            
            // Look for reviews mentioning successful outcomes
            $sql = "SELECT sID, author, organization, rating, extract, review, source, featured 
                    FROM KatalysisReviews 
                    WHERE active = 1 
                    AND (LOWER(review) LIKE '%successful%' OR LOWER(review) LIKE '%won%' OR 
                         LOWER(review) LIKE '%achieved%' OR LOWER(review) LIKE '%resolved%' OR
                         LOWER(extract) LIKE '%successful%' OR LOWER(extract) LIKE '%won%')
                    ORDER BY rating DESC, featured DESC 
                    LIMIT 3";
            
            $reviews = $db->GetAll($sql);
            
            if (empty($reviews)) {
                return $this->getGeneralHighRatingReviews();
            }
            
            return $this->formatReviewResults($reviews);
            
        } catch (\Exception $e) {
            error_log("Outcome-focused review search error: " . $e->getMessage());
            return $this->getGeneralHighRatingReviews();
        }
    }
    
    /**
     * Get general high-rating reviews
     */
    private function getGeneralHighRatingReviews(): array
    {
        try {
            $db = Database::get();
            
            $sql = "SELECT sID, author, organization, rating, extract, review, source, featured 
                    FROM KatalysisReviews 
                    WHERE active = 1 AND rating >= 4
                    ORDER BY rating DESC, featured DESC 
                    LIMIT 3";
            
            $reviews = $db->GetAll($sql);
            
            return $this->formatReviewResults($reviews);
            
        } catch (\Exception $e) {
            error_log("General review search error: " . $e->getMessage());
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Format review results consistently
     */
    private function formatReviewResults($reviews): array
    {
        $results = [];
        foreach ($reviews as $review) {
            $results[] = [
                'id' => $review['sID'],
                'client_name' => $review['author'],
                'organization' => $review['organization'],
                'rating' => (int)($review['rating'] ?: 5),
                'review' => $review['extract'] ?: $review['review'] ?: 'Excellent service',
                'source' => $review['source'] ?: 'Client Review',
                'featured' => (bool)$review['featured'],
                'relevance_score' => 8, // High score for targeted results
                'optimized_match' => true
            ];
        }
        
        return $results;
    }
    
    /**
     * Get places by specific location
     */
    private function getPlacesByLocation($location): array
    {
        try {
            $db = Database::get();
            
            $sql = "SELECT sID, name, address1, address2, town, county, postcode, phone 
                    FROM KatalysisPlaces 
                    WHERE active = 1 
                    AND (LOWER(name) LIKE ? OR LOWER(town) LIKE ? OR LOWER(county) LIKE ?)
                    ORDER BY name 
                    LIMIT 3";
            
            $locationPattern = '%' . strtolower($location) . '%';
            $places = $db->GetAll($sql, [$locationPattern, $locationPattern, $locationPattern]);
            
            if (empty($places)) {
                return $this->getNearestOffices();
            }
            
            return $this->formatPlaceResults($places, true);
            
        } catch (\Exception $e) {
            error_log("Location-specific places search error: " . $e->getMessage());
            return $this->getNearestOffices();
        }
    }
    
    /**
     * Get offices capable of handling specific services
     */
    private function getServiceCapableOffices($serviceArea): array
    {
        try {
            // For now, return main offices as all can handle most services
            // This could be enhanced with service-office mapping
            return $this->getNearestOffices();
            
        } catch (\Exception $e) {
            error_log("Service-capable offices search error: " . $e->getMessage());
            return [['error' => 'Office search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get nearest/main offices
     */
    private function getNearestOffices(): array
    {
        try {
            $db = Database::get();
            
            $sql = "SELECT sID, name, address1, address2, town, county, postcode, phone 
                    FROM KatalysisPlaces 
                    WHERE active = 1
                    ORDER BY 
                        CASE 
                            WHEN LOWER(name) LIKE '%main%' THEN 1
                            WHEN LOWER(name) LIKE '%head%' THEN 2
                            ELSE 3
                        END,
                        name 
                    LIMIT 3";
            
            $places = $db->GetAll($sql);
            
            return $this->formatPlaceResults($places, false);
            
        } catch (\Exception $e) {
            error_log("Nearest offices search error: " . $e->getMessage());
            return [['error' => 'Nearest offices search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Format place results consistently
     */
    private function formatPlaceResults($places, $isLocationSpecific = false): array
    {
        $results = [];
        foreach ($places as $place) {
            $address = trim(($place['address1'] ?? '') . ' ' . ($place['address2'] ?? ''));
            if (!empty($place['town'])) {
                $address .= ($address ? ', ' : '') . $place['town'];
            }
            if (!empty($place['county'])) {
                $address .= ($address ? ', ' : '') . $place['county'];
            }
            if (!empty($place['postcode'])) {
                $address .= ($address ? ' ' : '') . $place['postcode'];
            }
            
            $results[] = [
                'id' => $place['sID'],
                'name' => $place['name'],
                'address' => $address,
                'phone' => $place['phone'] ?? '',
                'services' => [],
                'featured' => false,
                'relevance_score' => $isLocationSpecific ? 9 : 7,
                'optimized_match' => true
            ];
        }
        
        return $results;
    }
    
    /**
     * Get reviews by content keywords when no specialism links exist
     */
    private function getReviewsByContent($serviceArea): array
    {
        try {
            error_log("=== CONTENT-BASED REVIEW SEARCH ===");
            error_log("Searching reviews by content for: $serviceArea");
            
            $db = Database::get();
            
            // Get service-specific keywords
            $keywords = $this->getServiceKeywords($serviceArea);
            error_log("Using keywords: " . implode(', ', $keywords));
            
            if (empty($keywords)) {
                error_log("No keywords found for service area: $serviceArea");
                return [['error' => 'No keywords available for content search']];
            }
            
            // Build SQL query to search review content
            $keywordConditions = [];
            $params = [];
            
            foreach ($keywords as $keyword) {
                $keywordConditions[] = "(LOWER(extract) LIKE ? OR LOWER(review) LIKE ?)";
                $params[] = '%' . strtolower($keyword) . '%';
                $params[] = '%' . strtolower($keyword) . '%';
            }
            
            $sql = "SELECT sID, author, organization, rating, extract, review, source, featured 
                   FROM KatalysisReviews 
                   WHERE active = 1 AND (" . implode(' OR ', $keywordConditions) . ")
                   ORDER BY rating DESC, featured DESC 
                   LIMIT 3";
            
            $reviews = $db->GetAll($sql, $params);
            error_log("Content search found " . count($reviews) . " reviews");
            
            if (empty($reviews)) {
                error_log("No content-based reviews found for: $serviceArea");
                return [['error' => 'No reviews found containing relevant keywords']];
            }
            
            // Format results
            $formattedResults = [];
            foreach ($reviews as $row) {
                $formattedResults[] = [
                    'id' => $row['sID'],
                    'client_name' => $row['author'] ?: 'Anonymous',
                    'organization' => $row['organization'] ?: '',
                    'rating' => (int)($row['rating'] ?: 5),
                    'review' => $row['extract'] ?: $row['review'] ?: 'Excellent service',
                    'source' => $row['source'] ?: 'Client Review',
                    'featured' => (bool)$row['featured'],
                    'relevance_score' => 7, // Good score for content match
                    'content_match' => true
                ];
            }
            
            error_log("CONTENT-BASED SEARCH COMPLETE: Returning " . count($formattedResults) . " reviews");
            return $formattedResults;
            
        } catch (\Exception $e) {
            error_log("Content-based review search error: " . $e->getMessage());
            return [['error' => 'Content search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get service keywords for filtering
     */
    private function getServiceKeywords($serviceArea): array
    {
        $serviceMap = [
            'conveyancing' => ['conveyancing', 'property', 'purchase', 'sale'],
            'family' => ['family', 'divorce', 'custody', 'matrimonial'],
            'personal injury' => ['personal injury', 'accident', 'compensation', 'medical negligence', 'injury'],
            'employment' => ['employment', 'workplace', 'tribunal', 'dismissal'],
            'wills' => ['wills', 'probate', 'estate', 'inheritance'],
            'probate' => ['wills', 'probate', 'estate', 'inheritance'],
            'litigation' => ['litigation', 'dispute', 'court', 'legal action'],
            'injury' => ['personal injury', 'accident', 'compensation', 'medical negligence', 'injury']
        ];
        
        $serviceArea = strtolower($serviceArea ?: '');
        
        // Direct match first
        foreach ($serviceMap as $area => $keywords) {
            if ($serviceArea === $area || strpos($serviceArea, $area) !== false) {
                error_log("Service keyword match found: '$serviceArea' matches '$area' - keywords: " . implode(', ', $keywords));
                return $keywords;
            }
        }
        
        // Partial match for compound terms
        foreach ($serviceMap as $area => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($serviceArea, $keyword) !== false) {
                    error_log("Partial service keyword match: '$serviceArea' contains '$keyword' - using keywords: " . implode(', ', $keywords));
                    return $keywords;
                }
            }
        }
        
        error_log("No service keyword match for: '$serviceArea' - using fallback");
        // Return general keywords if no specific match
        return [$serviceArea]; // Use the service area itself as keyword
    }
    
    /**
     * Get optimization strategy description for debug panel
     */
    private function getOptimizationStrategy($intent): array
    {
        $strategy = [
            'type' => $intent['intent_type'],
            'description' => '',
            'specialist_strategy' => '',
            'places_strategy' => '',
            'reviews_strategy' => ''
        ];
        
        switch ($intent['intent_type']) {
            case 'service':
                $strategy['description'] = 'Specialism-based optimization: Using AI-identified specialisms and CMS Topics';
                $strategy['specialist_strategy'] = 'Specialism matching via database relationships: ' . ($intent['service_area'] ?? 'general');
                $strategy['places_strategy'] = 'Main offices (all services available)';
                $strategy['reviews_strategy'] = 'Specialism-linked testimonials via KatalysisReviewTopic';
                break;
                
            case 'location':
                $strategy['description'] = 'Location-based optimization: Geographic matching priority';
                $strategy['specialist_strategy'] = 'Location-based filtering: ' . ($intent['location_mentioned'] ?? 'general');
                $strategy['places_strategy'] = 'Geographic matching with exact location priority';
                $strategy['reviews_strategy'] = 'General high-rating testimonials';
                break;
                
            case 'person':
                $strategy['description'] = 'Person-specific optimization: Name-based specialist search';
                $strategy['specialist_strategy'] = 'Name matching with flexible search: ' . ($intent['person_name'] ?? 'unknown');
                $strategy['places_strategy'] = 'Associated office locations for found specialist';
                $strategy['reviews_strategy'] = 'Specialist-specific or practice area testimonials';
                break;
                
            case 'situation':
                $strategy['description'] = 'Situation-urgent optimization: Experience and accessibility focus';
                $strategy['specialist_strategy'] = 'Senior/experienced practitioners for urgent matters';
                $strategy['places_strategy'] = 'Nearest offices for consultation convenience';
                $strategy['reviews_strategy'] = 'Outcome-focused testimonials';
                break;
                
            default: // information
                $strategy['description'] = 'Information-comprehensive optimization: Educational focus';
                $strategy['specialist_strategy'] = 'Practice area expert for follow-up';
                $strategy['places_strategy'] = 'All main offices (process location-independent)';
                $strategy['reviews_strategy'] = 'Educational value testimonials';
                break;
        }
        
        return $strategy;
    }
    
    /**
     * Get query classification summary for debug panel
     */
    private function getQueryClassification($intent): array
    {
        return [
            'primary_intent' => $intent['intent_type'],
            'service_detected' => $intent['service_area'] ?? 'None',
            'location_detected' => $intent['location_mentioned'] ?? 'None',
            'person_detected' => $intent['person_name'] ?? 'None',
            'urgency_assessment' => $intent['urgency_level'],
            'complexity_rating' => $intent['complexity'],
            'suggested_contacts' => $intent['suggested_specialist_count'],
            'office_focus' => $intent['suggested_office_focus'],
            'review_type' => $intent['review_type_needed']
        ];
    }

    /**
     * Apply page type scoring boost to prioritize certain content types
     */
    private function applyPageTypeBoost($originalScore, $pageType): float
    {
        // Define page type boost multipliers
        $boostMap = [
            'legal_service' => 1.3,           // +30% for legal service pages
            'legal_service_index' => 1.4,    // +40% for legal service index pages  
            'blog_entry' => 1.15,            // +15% for blog posts
            'case_study' => 1.2,             // +20% for case studies
            'news' => 1.1,                   // +10% for news articles
            'page' => 1.0,                   // No boost for general pages
            '' => 1.0                        // No boost for unknown types
        ];

        $multiplier = $boostMap[$pageType] ?? 1.0;
        $boostedScore = $originalScore * $multiplier;
        
        // Cap the maximum score at 1.0 to maintain score integrity
        return min($boostedScore, 1.0);
    }

    /**
     * Get default link selection rules for AI document selection
     */
    private function getDefaultLinkSelectionRules(): string
    {
        return "You are selecting the most relevant links for a user's search query. Provide a balanced mix of content types to give comprehensive coverage.

Selection Criteria (in order of priority):
1. **Direct Relevance**: Choose documents whose titles/content directly answer the user's question
2. **Semantic Accuracy**: REJECT documents with phonetically similar but semantically different terms
   - AVOID words that sound similar but have different meanings (e.g. 'crush' and 'crash')
   - Verify documents are topically relevant, not just lexically similar
   - ONLY select documents that genuinely help answer the user's query
3. **Content Type Balance**: Ensure a diverse mix of page types when possible:
   - MUST include at least 1 legal_service_index page if available (these are category overview pages)
   - MUST include 2-3 legal_service pages (specific service detail pages)
   - MUST include at least 1 calculator_entry if available (tools and calculators)
   - MUST include at least 1 guide_entry if available (guides)
3. **Service Matching**: If the user asks about a specific service, prioritize pages about that service
4. **Comprehensive Coverage**: Balance specific service pages with overview/index pages and supporting content
5. **Quality Over Quantity**: Prefer 6-8 high-quality, diverse links over fewer similar pages

Selection Rules:
- Select 6-8 links for comprehensive coverage (minimum 6, maximum 8)
- REQUIRED: Include at least 1 legal_service_index if any exist in the list
- REQUIRED: Include at least 1 calculator_entry if any exist in the list
- REQUIRED: Include at least 1 guide if any exist in the list
- Prioritize 3-4 legal_service or mlegal_service_index pages as the core content
- Don't select location pages unless location is mentioned in the question
- CRITICAL: Return numbers in order of importance (most important document first)
- CRITICAL: Double-check semantic relevance - avoid phonetic false positives
- Consider both relevance scores AND content type balance when determining order
- If no documents are truly relevant, return 'none'";
    }

    /**
     * Get essential link selection instructions that are always appended
     */
    private function getEssentialLinkSelectionInstructions(): string
    {
        return "

RESPONSE FORMAT REQUIREMENTS:
• You must respond with ONLY numbers separated by commas (e.g., '1,3,4,6,7') or 'none' if no links are relevant
• Do not include any other text, explanations, or formatting
• Do not use bullet points, dashes, or any other characters
• Maximum 7 numbers total
• Numbers must correspond to the document numbers listed above
• IMPORTANT: List the numbers in order of importance (most important first)

EXAMPLES OF CORRECT RESPONSES:
- '5,1,3,7,2' (document 5 is most important, then 1, then 3, etc.)
- '2,4,6' (document 2 is most important, then 4, then 6)
- 'none' (no documents are relevant)
- '1,9,4,6,7,3' (6 documents in order of importance)

EXAMPLES OF INCORRECT RESPONSES:
- 'I think documents 1 and 3 would be helpful'
- '1, 3' (with spaces)
- 'Documents 1 and 3'
- '1. and 3.' (with periods)";
    }

    /**
     * Get balanced content selection ensuring diverse page types
     */
    private function getBalancedContentSelection($candidateDocs, $maxResults = 7)
    {
        $selected = [];
        $selectedIndices = [];
        
        // Sort by score first to prioritize quality
        $sortedDocs = $candidateDocs;
        usort($sortedDocs, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Create index mapping for original array
        $indexMap = [];
        foreach ($candidateDocs as $originalIndex => $doc) {
            $indexMap[$doc['title'] . '|' . $doc['url']] = $originalIndex;
        }
        
        // Priority content types to ensure inclusion
        $requiredTypes = [
            'legal_service_index' => 1,  // At least 1 index page
            'legal_service' => 3,        // At least 3 service pages (increased from 2)
            'blog_entry' => 1,           // At least 1 blog/article
            'article' => 1,              // At least 1 article (in addition to blog_entry)
            'calculator_entry' => 1,     // At least 1 calculator
            'case_study' => 1            // At least 1 case study
        ];
        
        $typeCount = [];
        
        // First pass: Ensure required types are included
        foreach ($requiredTypes as $requiredType => $minCount) {
            $found = 0;
            foreach ($sortedDocs as $doc) {
                if (count($selectedIndices) >= $maxResults) break;
                
                if ($doc['page_type'] === $requiredType && $found < $minCount) {
                    $key = $doc['title'] . '|' . $doc['url'];
                    if (isset($indexMap[$key])) {
                        $selectedIndices[] = $indexMap[$key];
                        $typeCount[$requiredType] = ($typeCount[$requiredType] ?? 0) + 1;
                        $found++;
                    }
                }
            }
        }
        
        // Second pass: Fill remaining slots with highest scoring documents (any type)
        foreach ($sortedDocs as $doc) {
            if (count($selectedIndices) >= $maxResults) break;
            
            $key = $doc['title'] . '|' . $doc['url'];
            if (isset($indexMap[$key]) && !in_array($indexMap[$key], $selectedIndices)) {
                $selectedIndices[] = $indexMap[$key];
                $type = $doc['page_type'] ?: 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
        }
        
        // Log the balanced selection
        error_log("BALANCED SELECTION: Selected " . count($selectedIndices) . " documents with types: " . json_encode($typeCount));
        
        return $selectedIndices;
    }

    /**
     * Get page type distribution for debugging
     */
    private function getPageTypeDistribution($documents)
    {
        $distribution = [];
        foreach ($documents as $doc) {
            $type = $doc['page_type'] ?? $doc['type'] ?? 'unknown';
            $distribution[$type] = ($distribution[$type] ?? 0) + 1;
        }
        return $distribution;
    }

    /**
     * Extract search terms from query for content matching
     */
    private function extractSearchTermsFromQuery($query)
    {
        // Remove common words and extract meaningful terms
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'what', 'when', 'where', 'who', 'how', 'can', 'could', 'should', 'would', 'need', 'want', 'help', 'about', 'following', 'after', 'before', 'during', 'while'];
        
        $terms = preg_split('/\s+/', strtolower(trim($query)));
        $meaningfulTerms = array_filter($terms, function($term) use ($commonWords) {
            return strlen($term) > 2 && !in_array($term, $commonWords);
        });
        
        return array_values($meaningfulTerms);
    }

    /**
     * Get parent specialism ID for a given specialism ID (optimized)
     */
    private function getParentSpecialismId($specialismId)
    {
        try {
            // Use cached specialisms data instead of direct database call
            $allSpecialisms = $this->getSpecialisms();
            
            foreach ($allSpecialisms as $specialism) {
                if ($specialism['treeNodeID'] == $specialismId) {
                    $parentId = $specialism['treeNodeParentID'];
                    
                    if ($parentId && $parentId > 0) {
                        // Verify the parent is also a valid specialism (not the root tree)
                        foreach ($allSpecialisms as $potentialParent) {
                            if ($potentialParent['treeNodeID'] == $parentId) {
                                error_log("Found parent specialism: '{$potentialParent['treeNodeName']}' (ID: $parentId) for child '{$specialism['treeNodeName']}' (ID: $specialismId)");
                                return $parentId;
                            }
                        }
                    }
                    break;
                }
            }
            
            error_log("No valid parent specialism found for ID $specialismId");
            return null;
            
        } catch (\Exception $e) {
            error_log("Error getting parent specialism ID: " . $e->getMessage());
            return null;
        }
    }
}
