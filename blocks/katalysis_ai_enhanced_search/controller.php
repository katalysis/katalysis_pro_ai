<?php
/**
 * Enhanced AI Search Block Controller
 * 
 * Architecture:
 * - Phase 1: Fast Typesense search with AI categorization (~600ms)
 * - Phase 2: Async AI response generation (~4s background)
 * 
 * Performance Benefits:
 * - 87% improvement in perceived performance (600ms vs 7s initial load)
 * - Non-blocking AI response generation
 * - Progressive search result display
 * 
 * Features:
 * - Multi-dimensional search: Pages, specialists, reviews, places
 * - AI-powered content categorization and specialist matching
 * - Configurable response sections via dashboard
 * - Comprehensive debug analytics
 * - CSRF protection and security validation
 */
namespace Concrete\Package\KatalysisProAi\Block\KatalysisAiEnhancedSearch;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\Support\Facade\Config;
use Typesense\Client as TypesenseClient;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Tree\Type\Topic as TopicTree;
use Concrete\Package\KatalysisPro\Src\KatalysisPro\People\PeopleList;
use Concrete\Package\KatalysisPro\Src\KatalysisPro\Reviews\ReviewList;
use Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\PlaceList;
use KatalysisProAi\Entity\Search;
use Concrete\Core\Support\Facade\Database;
use KatalysisProAi\ActionService;
use Concrete\Core\Support\Facade\Application;
class Controller extends BlockController
{


    protected $btTable = 'btKatalysisAiEnhancedSearch';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 500;
    protected $btHandle = 'katalysis_ai_enhanced_search';

    // Block settings
    public $searchPlaceholder = 'How can we help you today?';
    public $displayMode = 'inline';
    public $resultsPageId = 0;
    public $searchButtonText = 'Search';

    public function getBlockTypeDescription()
    {
        return t("AI-powered legal search with Typesense backend and structured responses");
    }

    public function getBlockTypeName()
    {
        return t("Enhanced AI Search");
    }

    /**
     * View method - sets up variables for the template and handles display
     */
    public function view()
    {
        // Set template variables for the search form
        $this->set('searchPlaceholder', $this->searchPlaceholder ?: 'How can we help you today?');
        $this->set('displayMode', $this->displayMode ?: 'inline');
        $this->set('resultsPageId', $this->resultsPageId ?: 0);
        $this->set('searchButtonText', $this->searchButtonText ?: 'Search');
        $this->set('buttonText', $this->searchButtonText ?: 'Search'); // For backward compatibility
        $this->set('enableTyping', false); // Removed feature, set to false

        // Add block ID for debugging
        $this->set('blockID', $this->bID);

        // Get debug panel setting from config
        $enableDebugPanel = Config::get('katalysis.search.enable_debug_panel', false);
        $this->set('enableDebugPanel', $enableDebugPanel);

        // Get block path for assets
        $urlHelper = Application::getFacadeApplication()->make('helper/concrete/urls');
        $blockType = BlockType::getByHandle('katalysis_ai_enhanced_search');
        if ($blockType) {
            $assetsURL = $urlHelper->getBlockTypeAssetsURL($blockType   );
            // $assetsURL will be a URL like /application/blocks/your_block_handle
        }

        // Load CSS and JS assets using proper Concrete CMS methods
        $this->addHeaderItem('<link rel="stylesheet" type="text/css" href="' . $assetsURL . '/css/search.css">');
        $this->addFooterItem('<script src="' . $assetsURL . '/js/search.js"></script>');
    }

    public function add()
    {
        $this->set('searchPlaceholder', $this->searchPlaceholder);
        $this->set('displayMode', $this->displayMode);
        $this->set('resultsPageId', $this->resultsPageId);
        $this->set('searchButtonText', $this->searchButtonText);
        $this->setPageList();
    }

    public function edit()
    {
        $this->set('searchPlaceholder', $this->searchPlaceholder);
        $this->set('displayMode', $this->displayMode);
        $this->set('resultsPageId', $this->resultsPageId);
        $this->set('searchButtonText', $this->searchButtonText);
        $this->setPageList();
    }

    private function setPageList()
    {
        $pageList = new \Concrete\Core\Page\PageList();
        $pageList->filterByPageTypeHandle('page');
        $pageList->sortByCollectionIDAscending();
        $pages = [0 => t('Choose Page')];
        
        foreach ($pageList->getResults() as $page) {
            $pages[$page->getCollectionID()] = $page->getCollectionName();
        }
        
        $this->set('pages', $pages);
    }

    public function save($args)
    {
        $args['searchPlaceholder'] = trim($args['searchPlaceholder'] ?? 'How can we help you today?');
        $args['displayMode'] = $args['displayMode'] ?? 'inline';
        $args['resultsPageId'] = intval($args['resultsPageId'] ?? 0);
        $args['searchButtonText'] = trim($args['searchButtonText'] ?? 'Search');
        parent::save($args);
    }





    /**
     * Main search endpoint - handles all search requests
     * Following the same pattern as action_like for consistency
     */
    public function action_perform_search($token = false, $bID = false)
    {
        // Validate block ID like other actions do
        if ($this->bID != $bID) {
            return false;
        }
        
        // Validate CSRF token for security
        if (!$this->app->make('token')->validate('search_action', $token)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid security token'
            ]);
            exit;
        }

        // Set proper JSON response headers
        header('Content-Type: application/json');

        $query = trim($this->request->request->get('query', '') ?: $this->request->query->get('query', ''));

        if (empty($query)) {
            echo json_encode([
                'success' => false,
                'error' => 'Please enter a search query'
            ]);
            exit;
        }

        try {
            $startTime = microtime(true);

            // Phase 1: Test Typesense connection first
            $typesenseTest = $this->testTypesenseConnection();

            if (!$typesenseTest['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Typesense connection failed: ' . $typesenseTest['error'],
                    'query' => $query,
                    'block_id' => $this->bID,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                exit;
            }

            // Phase 2: AI-powered query categorization
            $aiAnalysis = $this->performQueryCategorization($query, null);

            // Phase 3: Perform enhanced search with AI insights
            $searchResults = $this->performEnhancedSearch($query, $aiAnalysis);
            
            // Phase 4: Get targeted supporting content based on AI analysis
            $specialistsResult = $this->getTargetedSpecialists($query, $aiAnalysis);
            $specialists = $specialistsResult['specialists'] ?? [];
            $specialistsDebug = $specialistsResult['debug'] ?? ['enabled' => false];
            
            $reviews = $this->getTargetedReviews($query, $aiAnalysis);
            
            // Get places with debug information
            $placesResult = $this->getSmartPlaceSelectionWithDebug($query, $aiAnalysis);
            $places = $placesResult['places'];
            $placesDebug = $placesResult['debug'];
            
            $searchTime = round((microtime(true) - $startTime) * 1000, 2);

            // Get available places for debug display
            $locationData = $this->getLocationKeywords();
            $availablePlaces = $locationData['placeNames'];

            // Build debug information (without AI response timing)
            $debugInfo = $this->buildDebugInfo($query, $aiAnalysis, $searchResults, $searchTime, $placesDebug, $specialistsDebug, $availablePlaces);



            // Note: Search logging happens in action_generate_ai_response for complete data

            echo json_encode([
                'success' => true,
                'message' => 'Enhanced search completed successfully!',
                'query' => $query,
                'ai_analysis' => $aiAnalysis,
                'search_results' => $searchResults,
                'specialists' => $specialists,
                'reviews' => $reviews,
                'places' => $places,
                'debug_info' => $debugInfo,
                'typesense_status' => $typesenseTest,
                'performance' => ['search_time_ms' => $searchTime],
                'ai_response_loading' => true, // Indicate AI response will load separately
                'block_id' => $this->bID,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage(),
                'debug_info' => [
                    'block_id' => $this->bID ?? 'unknown',
                    'method' => 'action_search',
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            exit;
        }
    }

    /**
     * Generate AI response asynchronously after search results are displayed
     */
    public function action_generate_ai_response($token = false, $bID = false)
    {
        // Validate block ID
        if ($this->bID != $bID) {
            return false;
        }
        
        // Validate CSRF token for security
        if (!$this->app->make('token')->validate('ai_response_action', $token)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid security token'
            ]);
            exit;
        }

        // Set proper JSON response headers
        header('Content-Type: application/json');

        $query = trim($this->request->request->get('query', '') ?: $this->request->query->get('query', ''));
        $aiAnalysisJson = $this->request->request->get('ai_analysis', '');
        $searchResultsJson = $this->request->request->get('search_results', '');

        if (empty($query)) {
            echo json_encode([
                'success' => false,
                'error' => 'Query required for AI response generation'
            ]);
            exit;
        }

        try {
            $startTime = microtime(true);

            // Parse AI analysis and search results from previous search
            $aiAnalysis = json_decode($aiAnalysisJson, true) ?: [];
            $searchResults = json_decode($searchResultsJson, true) ?: [];

            // Generate AI response using search results as context
            $aiResponseResult = $this->generateAIResponse($query, $aiAnalysis, $searchResults);
            $aiResponse = $aiResponseResult['response'] ?? 'I apologize, but I encountered an error generating a response.';
            $selectedActionIds = $aiResponseResult['actions'] ?? [];
            
            // Get full action details for selected actions
            $actions = $this->getActionDetails($selectedActionIds);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Prepare result data for AI response logging
            $fullResults = [
                'search_results' => $searchResults,
                'ai_analysis' => $aiAnalysis, 
                'ai_response' => $aiResponse,
                'actions' => $actions,
                'performance' => ['ai_response_time_ms' => $responseTime]
            ];

            // Log complete search with AI response to database for analytics
            $this->logSearch($query, $this->bID, $aiResponse, $aiAnalysis, $fullResults);

            echo json_encode([
                'success' => true,
                'message' => 'AI response generated successfully!',
                'ai_response' => $aiResponse,
                'actions' => $actions,
                'performance' => ['ai_response_time_ms' => $responseTime],
                'block_id' => $this->bID,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'AI response generation failed: ' . $e->getMessage(),
                'debug_info' => [
                    'block_id' => $this->bID ?? 'unknown',
                    'method' => 'action_generate_ai_response',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            exit;
        }
    }

    /**
     * Initialize Typesense client with configuration from settings
     */
    private function initializeTypesenseClient()
    {
        // Ensure autoloader is loaded
        $autoloadPath = DIRNAME_PACKAGES . '/katalysis_pro_ai/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }

        $apiKey = Config::get('katalysis.search.typesense_api_key');
        $host = Config::get('katalysis.search.typesense_host');
        $port = Config::get('katalysis.search.typesense_port', 443);
        $protocol = Config::get('katalysis.search.typesense_protocol', 'https');

        if (empty($apiKey) || empty($host)) {
            // Fallback to hardcoded values for development
            $apiKey = 'tIxnmdmmKDGPLPpe62zxcWpEDsH1VP6z';
            $host = 'eb3npjrdx4h1iw9ap-1.a1.typesense.net';
            $port = 443;
            $protocol = 'https';
        }

        return new TypesenseClient([
            'api_key' => $apiKey,
            'nodes' => [
                [
                    'host' => $host,
                    'port' => $port,
                    'protocol' => $protocol,
                ]
            ],
            'connection_timeout_seconds' => 2,
        ]);
    }

    /**
     * Perform AI-powered query categorization with specialism, location, and people detection
     */
    private function performQueryCategorization($query, $client)
    {
        $openAiKey = Config::get('katalysis.ai.open_ai_key');

        if (empty($openAiKey)) {
            return [
                'category' => 'General', 
                'query' => $query, 
                'specialism_id' => null,
                'location_mentioned' => null,
                'person_mentioned' => null,
                'intent_type' => 'general'
            ];
        }

        try {
            // Get specialisms from TopicTree for dynamic categorization
            $allSpecialisms = $this->getSpecialisms();
            
            if (empty($allSpecialisms)) {
                error_log("SPECIALISMS ERROR: No specialisms found from TopicTree");
                return [
                    'category' => 'General', 
                    'query' => $query, 
                    'specialism_id' => null, 
                    'service_area' => null, 
                    'error' => 'No specialisms available',
                    'location_mentioned' => null,
                    'person_mentioned' => null,
                    'intent_type' => 'general'
                ];
            }
            
            // Get available places for AI context
            $locationData = $this->getLocationKeywords();
            $availablePlaces = $locationData['placeNames'];
            
            // Get available people for AI context
            $availablePeople = $this->getPeopleNames();
            
            // Debug logging (only in development)
            $debugMode = Config::get('katalysis.debug.enabled', false);
            if ($debugMode) {
                error_log("AI PERSON DETECTION DEBUG - Available people for AI: " . implode(', ', $availablePeople));
                error_log("AI PERSON DETECTION DEBUG - Query being analyzed: '$query'");
            }
            
            // Build comprehensive context for AI
            $specialismsWithIds = array_map(function($spec) {
                return $spec['treeNodeName'] . " [#" . $spec['treeNodeID'] . "]";
            }, $allSpecialisms);
            
            $availableSpecialisms = array_column($allSpecialisms, 'treeNodeName');
            
            $staffNamesForPrompt = implode(', ', array_slice($availablePeople, 0, 15)) . (count($availablePeople) > 15 ? '... and more' : '');
            
            if ($debugMode) {
                error_log("AI PERSON DETECTION DEBUG - Staff names sent to AI: $staffNamesForPrompt");
            }
            
            $prompt = "User Query: \"" . $query . "\"

AVAILABLE DATA:
Legal Specialisms: " . implode(', ', $availableSpecialisms) . "
Office Locations: " . implode(', ', array_slice($availablePlaces, 0, 10)) . (count($availablePlaces) > 10 ? '... and more' : '') . "
Staff Names: " . $staffNamesForPrompt . "

TASK: Analyze the query and extract key information. Respond with JSON in this exact format:

{
  \"specialism\": \"[exact specialism name from list or NONE]\",
  \"location\": \"[exact location name mentioned or NONE]\", 
  \"person\": \"[exact person name mentioned or NONE]\",
  \"intent_type\": \"[service|location|person|general]\",
  \"urgency\": \"[high|medium|low]\"
}

IMPORTANT MAPPINGS:
- 'house sale', 'property sale', 'selling house', 'buying house', 'conveyancing' → 'Conveyancing' 
- 'car accident', 'road accident', 'injury claim' → 'Road Accident'
- 'will', 'inheritance', 'probate' → 'Wills, Probate & Estates'
- 'dispute', 'settlement', 'legal dispute' → 'Disputes & Settlements'

INTENT TYPES:
- service: Query about legal services/specialisms
- location: Query about offices/locations 
- person: Query about specific staff members
- general: General inquiry

EXAMPLES:
\"lawyers in Cardiff\" → {\"specialism\": \"NONE\", \"location\": \"Cardiff\", \"person\": \"NONE\", \"intent_type\": \"location\", \"urgency\": \"low\"}
\"conveyancing help\" → {\"specialism\": \"Conveyancing\", \"location\": \"NONE\", \"person\": \"NONE\", \"intent_type\": \"service\", \"urgency\": \"medium\"}
\"does Paul Rossiter work there\" → {\"specialism\": \"NONE\", \"location\": \"NONE\", \"person\": \"Paul Rossiter\", \"intent_type\": \"person\", \"urgency\": \"low\"}";

            $response = $this->callOpenAI($openAiKey, 'gpt-4o-mini', $prompt);
            
            if ($debugMode) {
                error_log("AI PERSON DETECTION DEBUG - OpenAI raw response: $response");
            }

            if ($response) {
                $aiResult = json_decode($response, true);
                
                if ($debugMode) {
                    error_log("AI PERSON DETECTION DEBUG - Parsed AI result: " . json_encode($aiResult));
                }
                
                if ($aiResult && is_array($aiResult)) {
                    // Validate and clean the AI response
                    $specialism = isset($aiResult['specialism']) && $aiResult['specialism'] !== 'NONE' ? 
                        $aiResult['specialism'] : null;
                    $location = isset($aiResult['location']) && $aiResult['location'] !== 'NONE' ? 
                        $aiResult['location'] : null;
                    $person = isset($aiResult['person']) && $aiResult['person'] !== 'NONE' ? 
                        $aiResult['person'] : null;
                    $intentType = $aiResult['intent_type'] ?? 'general';
                    $urgency = $aiResult['urgency'] ?? 'medium';
                    
                    if ($debugMode) {
                        error_log("AI PERSON DETECTION DEBUG - Extracted person: " . ($person ?: 'none') . ", Intent: $intentType");
                    }
                    
                    // Validate specialism against available list
                    if ($specialism && !in_array($specialism, $availableSpecialisms)) {
                        $specialism = null;
                    }
                    
                    // Validate person against available list (fuzzy match)
                    if ($person && !empty($availablePeople)) {
                        $personMatch = $this->findPersonMatch($person, $availablePeople);
                        $person = $personMatch;
                    }
                    
                    // Validate location against available places (fuzzy match)
                    if ($location && !empty($availablePlaces)) {
                        $locationMatch = $this->findLocationMatch($location, $availablePlaces);
                        $location = $locationMatch ?: $location; // Keep original if no match
                    }
                    
                    $specialismId = $specialism ? $this->mapServiceAreaToSpecialismId($specialism) : null;
                    
                    return [
                        'category' => $specialism ?: 'General',
                        'query' => $query,
                        'specialism_id' => $specialismId,
                        'service_area' => $specialism,
                        'location_mentioned' => $location,
                        'person_mentioned' => $person,
                        'intent_type' => $intentType,
                        'urgency' => $urgency,
                        'ai_analysis_success' => true
                    ];
                }
            }

        } catch (\Exception $e) {
            error_log("AI categorization failed: " . $e->getMessage());
        }

        return [
            'category' => 'General', 
            'query' => $query, 
            'specialism_id' => null, 
            'service_area' => null,
            'location_mentioned' => null,
            'person_mentioned' => null,
            'intent_type' => 'general',
            'urgency' => 'medium',
            'ai_analysis_success' => false
        ];
    }



    /**
     * Create content snippet for search result
     */
    private function createSnippet($content, $maxLength = 200)
    {
        $cleanContent = strip_tags($content);
        return strlen($cleanContent) > $maxLength
            ? substr($cleanContent, 0, $maxLength) . '...'
            : $cleanContent;
    }

    /**
     * Get specialisms from TopicTree for AI categorization
     */
    private function getSpecialisms(): array
    {
        try {
            // Use proper Concrete CMS TopicTree API
            $specialismsTree = TopicTree::getByName('Specialisms');
            
            if (!$specialismsTree) {
                return [];
            }
            
            // Get tree ID for database query
            $treeID = $specialismsTree->getTreeID();
            
            // Use database query approach since getChildNodes() API isn't working properly
            try {
                $db = Database::get();
                
                // Get all nodes in the tree (excluding root node with NULL parent)
                $nodes = $db->fetchAll(
                    'SELECT treeNodeID, treeNodeName, treeNodeParentID FROM TreeNodes WHERE treeID = ? AND treeNodeParentID IS NOT NULL ORDER BY treeNodeName',
                    [$treeID]
                );
                
                if (count($nodes) === 0) {
                    return [];
                }
                
                $specialisms = [];
                
                // Convert database results to expected format
                foreach ($nodes as $node) {
                    $nodeName = $node['treeNodeName'];
                    
                    // Skip root node name itself
                    if ($nodeName === 'Specialisms') {
                        continue;
                    }
                    
                    $specialisms[] = [
                        'treeNodeID' => $node['treeNodeID'],
                        'treeNodeName' => $nodeName,
                        'treeNodeParentID' => $node['treeNodeParentID']
                    ];
                }
                
                return $specialisms;
                
            } catch (\Exception $e) {
                error_log("Error querying specialisms from database: " . $e->getMessage());
                return [];
            }
            
        } catch (\Exception $e) {
            error_log("Error loading specialisms TopicTree: " . $e->getMessage());
            
            // Fallback: Basic specialisms for law firms
            return [
                ['treeNodeID' => 1, 'treeNodeName' => 'Personal Injury', 'treeNodeParentID' => null],
                ['treeNodeID' => 2, 'treeNodeName' => 'Medical Negligence', 'treeNodeParentID' => null],
                ['treeNodeID' => 3, 'treeNodeName' => 'Employment Law', 'treeNodeParentID' => null],
                ['treeNodeID' => 4, 'treeNodeName' => 'Family Law', 'treeNodeParentID' => null],
                ['treeNodeID' => 5, 'treeNodeName' => 'Conveyancing', 'treeNodeParentID' => null],
                ['treeNodeID' => 6, 'treeNodeName' => 'Wills & Probate', 'treeNodeParentID' => null]
            ];
        }
    }

    /**
     * Map service area to specialism ID for targeted searches
     */
    private function mapServiceAreaToSpecialismId($serviceArea)
    {
        if (empty($serviceArea)) {
            return null;
        }
        
        try {
            // Get all available specialisms from TopicTree
            $specialisms = $this->getSpecialisms();
            
            if (empty($specialisms)) {
                return null;
            }
            
            // Convert service area to lowercase for case-insensitive matching
            $serviceAreaLower = strtolower(trim($serviceArea));
            
            // Exact name match only (case insensitive)
            foreach ($specialisms as $specialism) {
                $specialismName = $specialism['treeNodeName'] ?? '';
                if (strtolower(trim($specialismName)) === $serviceAreaLower) {
                    return $specialism['treeNodeID'];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log("Error mapping service area to specialism: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build debug information for the debug panel
     */
    private function buildDebugInfo($query, $aiAnalysis, $searchResults, $totalTime, $placesDebug = null, $specialistsDebug = null, $availablePlaces = null)
    {
        // Check if debug panel is enabled (default to false for production)
        $enableDebugPanel = Config::get('katalysis.search.enable_debug_panel', false);
        
        if (!$enableDebugPanel) {
            return ['enabled' => false];
        }

        try {
            // Get available specialisms for display
            $allSpecialisms = $this->getSpecialisms();
            $specialismsText = '';
            
            if (!empty($allSpecialisms)) {
                $specialismNames = array_column($allSpecialisms, 'treeNodeName');
                $specialismsText = implode(', ', $specialismNames);
            } else {
                $specialismsText = 'No specialisms loaded from TopicTree';
            }

            // Extract search performance from results
            $searchTime = $searchResults['performance']['search_time_ms'] ?? 0;
            
            // Calculate total results across categories
            $totalResults = $searchResults['total_hits'] ?? 0;
            
            // Build performance breakdown string
            $performanceBreakdown = sprintf(
                "Multi-Search: %.1fms | Total: %.1fms", 
                $searchTime, 
                $totalTime
            );

            // Get available people for debug display with detailed information
            $availablePeople = $this->getPeopleNames();
            $peopleText = !empty($availablePeople) ? 
                implode(', ', array_slice($availablePeople, 0, 10)) . (count($availablePeople) > 10 ? ' ... and ' . (count($availablePeople) - 10) . ' more' : '') :
                'No people loaded';

            // Get detailed people information for debugging
            $peopleDetails = $this->getPeopleWithSpecialisms();
            
            // Get available places for debug display with similar formatting to people
            $placesText = 'All offices available'; // Default text
            $placesCount = 0;
            if (!empty($availablePlaces) && is_array($availablePlaces)) {
                $placesCount = count($availablePlaces);
                // Convert place names to proper title case for display
                $formattedPlaces = array_map(function($place) {
                    return ucwords(strtolower($place));
                }, array_slice($availablePlaces, 0, 10));
                $placesText = implode(', ', $formattedPlaces) . 
                    ($placesCount > 10 ? ' ... and ' . ($placesCount - 10) . ' more' : '');
            }
            
            $debugMode = Config::get('katalysis.debug.enabled', false);
            if ($debugMode) {
                error_log("DEBUG INFO - People detailed count: " . count($peopleDetails));
                error_log("DEBUG INFO - People detailed sample: " . json_encode(array_slice($peopleDetails, 0, 2)));
                error_log("DEBUG INFO - Places count: " . $placesCount . ", sample: " . substr($placesText, 0, 100));
            }

            return [
                'enabled' => true,
                'available_specialisms' => $specialismsText,
                'available_people' => $peopleText,
                'available_people_count' => count($availablePeople),
                'people_detailed' => $peopleDetails, // Detailed people info with specialisms
                'available_places' => $placesText,
                'available_places_count' => $placesCount,
                'original_query' => $query,
                'detected_category' => ucfirst($aiAnalysis['intent_type'] ?? 'general'), // Primary Intent
                'detected_specialism' => $this->getDetectedCategoryForMatrix($aiAnalysis), // Specialism for matrix
                'enhanced_query' => $searchResults['enhanced_query'] ?? $query,
                'total_results' => $totalResults,
                'search_strategy' => 'Multi-target approach for optimal AI candidate list',
                'performance_breakdown' => $performanceBreakdown,
                'specialism_id' => $aiAnalysis['specialism_id'] ?? null,
                'ai_analysis_success' => !empty($aiAnalysis['category']) && $aiAnalysis['category'] !== 'General',
                'ai_analysis' => $aiAnalysis, // Include full AI analysis for enhanced debug display
                'places_selection' => $placesDebug ? $placesDebug : ['method' => 'none', 'selection_reason' => 'Places debug not available'],
                'specialists_selection' => $specialistsDebug ? $specialistsDebug : ['enabled' => false, 'reason' => 'Specialists debug not available']
            ];
            
        } catch (\Exception $e) {
            error_log("Debug info generation failed: " . $e->getMessage());
            return [
                'enabled' => true,
                'error' => 'Debug info generation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get detected category for clean intent-specialism matrix
     * Ensures consistent logic between backend and frontend
     */
    private function getDetectedCategoryForMatrix($aiAnalysis)
    {
        $intentType = $aiAnalysis['intent_type'] ?? 'general';
        
        switch ($intentType) {
            case 'service':
                // Service queries show the detected specialism
                return $aiAnalysis['specialism'] ?? $aiAnalysis['category'] ?? 'General';
                
            case 'person':
                // Person queries show "General" for specialism (clean matrix)
                return 'General';
                
            case 'location':
                // Location queries show "General" for specialism  
                return 'General';
                
            case 'general':
            default:
                // General queries show any detected specialism or General
                return $aiAnalysis['specialism'] ?? $aiAnalysis['category'] ?? 'General';
        }
    }

    /**
     * Test Typesense connection before attempting search
     */
    private function testTypesenseConnection()
    {
        try {
            $client = $this->initializeTypesenseClient();
            
            // Try to get collection info as a connection test
            $collections = $client->collections->retrieve();
            
            return [
                'success' => true,
                'message' => 'Typesense connection successful',
                'collections_count' => count($collections)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform enhanced search using AI categorization
     */
    private function performEnhancedSearch($query, $aiAnalysis)
    {
        try {
            $searchStartTime = microtime(true);
            $client = $this->initializeTypesenseClient();
            $totalFound = 0;
            $categories = [
                'our_services' => ['items' => [], 'count' => 0],
                'about_us' => ['items' => [], 'count' => 0],
                'legal_service_pages' => ['items' => [], 'count' => 0],
                'category_pages' => ['items' => [], 'count' => 0],
                'calculators' => ['items' => [], 'count' => 0],
                'guides' => ['items' => [], 'count' => 0],
                'articles' => ['items' => [], 'count' => 0]
            ];
            
            // Use the AI categorization result
            $relevantSpecialism = $aiAnalysis['category'] ?? 'General';
            
            // Federated Multi-Search setup (same as typesense.php)
            $federatedSearches = [];
            
            // 1. Legal Service Pages search
            $legalServiceQuery = $query;
            if (!empty($relevantSpecialism) && $relevantSpecialism !== 'General') {
                $legalServiceQuery = $relevantSpecialism . ' ' . $query;
            }
            
            $federatedSearches[] = [
                'collection' => 'katalysis_all_pages',
                'q' => $legalServiceQuery,
                'query_by' => 'specialisms,sourceName,url_path,meta_title',
                'query_by_weights' => '10,8,6,5',
                'per_page' => 2,
                'filter_by' => 'pagetype:legal_service',
                'prefix' => 'true,true,true,true',
                'group_by' => 'url_path',
                'group_limit' => 1,
                'typo_tokens_threshold' => 1,
                'num_typos' => 1
            ];
            
            // 2. Category Pages search
            $federatedSearches[] = [
                'collection' => 'katalysis_all_pages',
                'q' => $query,
                'query_by' => 'sourceName,url_path,specialisms,content',
                'query_by_weights' => '10,8,6,2',
                'per_page' => 2,
                'filter_by' => 'pagetype:legal_service_index && url_path:!~*/calculator*',
                'prefix' => 'true,true,true,false',
                'group_by' => 'url_path',
                'group_limit' => 1
            ];
            
            // 3. Calculators & Tools search
            $calcFilter = 'url_path:*calculator* || sourceName:*calculator* || sourceName:*Calculator*';
            $federatedSearches[] = [
                'collection' => 'katalysis_all_pages',
                'q' => $query,
                'query_by' => 'sourceName,url_path,specialisms,content',
                'query_by_weights' => '10,8,6,4',
                'per_page' => 2,
                'filter_by' => $calcFilter,
                'prefix' => 'true,true,true,false',
                'group_by' => 'url_path',
                'group_limit' => 1
            ];
            
            // 4. Guides & Resources search
            $federatedSearches[] = [
                'collection' => 'katalysis_all_pages',
                'q' => $query,
                'query_by' => 'sourceName,url_path,content,specialisms',
                'query_by_weights' => '10,8,6,4',
                'per_page' => 2,
                'filter_by' => 'pagetype:guide',
                'prefix' => 'true,true,false,true',
                'group_by' => 'url_path',
                'group_limit' => 1
            ];
            
            // 5. Articles & Case Studies search - Enhanced with AI analysis
            $caseStudyQuery = $query;
            if (!empty($relevantSpecialism) && $relevantSpecialism !== 'General') {
                $caseStudyQuery = $relevantSpecialism . ' ' . $query; // Use enhanced query with specialism
            }
            
            // Use configured max articles/case studies count
            $maxArticlesCaseStudies = Config::get('katalysis.search.max_articles_case_studies', 4);
            
            $federatedSearches[] = [
                'collection' => 'katalysis_all_pages',
                'q' => $caseStudyQuery,
                'query_by' => 'specialisms,sourceName,content,url_path', // Prioritize specialisms for better matching
                'query_by_weights' => '10,8,6,2', // Higher weight on specialisms
                'per_page' => $maxArticlesCaseStudies,
                'filter_by' => 'pagetype:case_study || pagetype:article || pagetype:news',
                'prefix' => 'true,true,false,false', // Enable prefix matching for specialisms and titles
                'group_by' => 'url_path',
                'group_limit' => 1,
                'typo_tokens_threshold' => 2,
                'num_typos' => 2
            ];
            
            // Execute federated multi-search
            $federatedResult = $client->multiSearch->perform([
                'searches' => $federatedSearches
            ]);
            
            // Helper function to extract hits from grouped results
            $extractHits = function($searchResult) {
                if (isset($searchResult['grouped_hits'])) {
                    $hits = [];
                    foreach ($searchResult['grouped_hits'] as $group) {
                        if (isset($group['hits'])) {
                            foreach ($group['hits'] as $hit) {
                                $hits[] = $hit;
                            }
                        }
                    }
                    return $hits;
                } else {
                    return $searchResult['hits'] ?? [];
                }
            };
            
            // Process federated results
            $categoryKeys = ['legal_service_pages', 'category_pages', 'calculators', 'guides', 'articles'];
            
            foreach ($federatedResult['results'] as $index => $result) {
                $hits = $extractHits($result);
                $categoryKey = $categoryKeys[$index] ?? 'legal_service_pages';
                
                $processedHits = array_map(function($hit) {
                    $doc = $hit['document'];
                    return [
                        'title' => $doc['sourceName'] ?? 'Untitled',
                        'url' => $doc['url_path'] ?? '',
                        'snippet' => $this->createSnippet($doc['content'] ?? ''),
                        'specialisms' => $doc['specialisms'] ?? '',
                        'page_type' => $doc['pagetype'] ?? 'unknown',
                        'score' => $hit['text_match'] ?? 0
                    ];
                }, $hits);
                
                $categories[$categoryKey]['items'] = $processedHits;
                $categories[$categoryKey]['count'] = count($processedHits);
                $totalFound += count($processedHits);
            }
            
            // Replace search results with fallback pages for general/person queries
            $intentType = $aiAnalysis['intent_type'] ?? 'general';
            if ($intentType === 'general' || $intentType === 'person') {
                $debugMode = Config::get('katalysis.debug.enabled', false);
                if ($debugMode) {
                    error_log("FALLBACK PAGES - Replacing search results with fallback pages for intent: $intentType");
                }
                
                $fallbackPages = $this->getFallbackPages();
                
                if (!empty($fallbackPages)) {
                    // Clear all existing search results
                    $totalFound = 0;
                    foreach ($categories as $key => $category) {
                        $categories[$key]['items'] = [];
                        $categories[$key]['count'] = 0;
                    }
                    
                    // Group and add fallback pages
                    $ourServicesPages = [];
                    $aboutUsPages = [];
                    
                    foreach ($fallbackPages as $page) {
                        $formattedPage = [
                            'title' => $page['title'],
                            'url' => $page['url'],
                            'snippet' => $page['description'],
                            'specialisms' => $page['category'],
                            'page_type' => 'fallback_page',
                            'score' => 1.0
                        ];
                        
                        if ($page['category'] === 'Our Services') {
                            $ourServicesPages[] = $formattedPage;
                        } else {
                            $aboutUsPages[] = $formattedPage;
                        }
                    }
                    
                    // Add Our Services to dedicated our_services category
                    if (!empty($ourServicesPages)) {
                        $categories['our_services']['items'] = $ourServicesPages;
                        $categories['our_services']['count'] = count($ourServicesPages);
                        $totalFound += count($ourServicesPages);
                    }
                    
                    // Add About Us pages to dedicated about_us category
                    if (!empty($aboutUsPages)) {
                        $categories['about_us']['items'] = $aboutUsPages;
                        $categories['about_us']['count'] = count($aboutUsPages);
                        $totalFound += count($aboutUsPages);
                    }
                    
                    if ($debugMode) {
                        error_log("FALLBACK PAGES - Replaced all content with " . count($fallbackPages) . " fallback pages (Services: " . count($ourServicesPages) . ", About: " . count($aboutUsPages) . ")");
                    }
                }
            }
            
            $searchEndTime = microtime(true);
            $searchTime = ($searchEndTime - $searchStartTime) * 1000;
            
            return [
                'success' => true,
                'detected_category' => $relevantSpecialism,
                'enhanced_query' => $legalServiceQuery,
                'total_hits' => $totalFound,
                'categories' => $categories,
                'ai_analysis' => $aiAnalysis, // Include full AI analysis with specialism_id
                'performance' => [
                    'search_time_ms' => round($searchTime, 2)
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get targeted specialists based on AI analysis
     */
    private function getTargetedSpecialists($query, $aiAnalysis): array
    {
        // Check if specialists are enabled
        $enableSpecialists = Config::get('katalysis.search.enable_specialists', true);
        if (!$enableSpecialists) {
            return ['specialists' => [], 'debug' => ['enabled' => false, 'reason' => 'Specialists disabled in config']];
        }

        try {
            $maxSpecialists = Config::get('katalysis.search.max_specialists', 3);
            $specialismId = $aiAnalysis['specialism_id'] ?? null;
            $serviceArea = $aiAnalysis['service_area'] ?? null;
            $personMentioned = $aiAnalysis['person_mentioned'] ?? null;
            $intentType = $aiAnalysis['intent_type'] ?? 'general';

            // Build debug information
            $debugInfo = [
                'enabled' => true,
                'query' => $query,
                'specialism_id' => $specialismId,
                'service_area' => $serviceArea,
                'person_mentioned' => $personMentioned,
                'intent_type' => $intentType,
                'max_specialists' => $maxSpecialists,
                'selection_method' => null,
                'fallback_used' => false,
                'results_count' => 0,
                'available_people_count' => 0,
                'priority_used' => null
            ];

            $debugMode = Config::get('katalysis.debug.enabled', false);
            if ($debugMode) {
                error_log("SPECIALISTS DEBUG - Query: '$query', SpecialismID: $specialismId, PersonMentioned: " . ($personMentioned ?: 'none') . ", IntentType: $intentType");
            }

            // PRIORITY 1: If specific person mentioned and intent is person, search for that person
            if ($personMentioned && $intentType === 'person') {
                if ($debugMode) {
                    error_log("SPECIALISTS DEBUG - PRIORITY 1: Searching for specific person: $personMentioned");
                }
                $debugInfo['priority_used'] = 1;
                $debugInfo['selection_method'] = 'person_search';
                $personResults = $this->searchForSpecificPerson($personMentioned);
                if (!empty($personResults)) {
                    if ($debugMode) {
                        error_log("SPECIALISTS DEBUG - PRIORITY 1: Found " . count($personResults) . " results for person");
                    }
                    $debugInfo['results_count'] = count($personResults);
                    return ['specialists' => $personResults, 'debug' => $debugInfo];
                }
            }

            // PRIORITY 2: Use specialism-based search for service queries
            if ($specialismId && $intentType === 'service') {
                if ($debugMode) {
                    error_log("SPECIALISTS DEBUG - PRIORITY 2: Service intent with specialismID: $specialismId");
                }
                $debugInfo['priority_used'] = 2;
                $debugInfo['selection_method'] = 'specialism_filter';
                $peopleList = new PeopleList();
                $peopleList->filterByActive();
                $peopleList->filterBySpecialisms([$specialismId]);
                $peopleList->limitResults($maxSpecialists);
                
                $results = $peopleList->getResults();
                if ($debugMode) {
                    error_log("SPECIALISTS DEBUG - PRIORITY 2: Found " . count($results) . " raw results");
                }
                $debugInfo['available_people_count'] = count($results);
                
                if (!empty($results)) {
                    $formatted = $this->formatPeopleListResults($results);
                    if ($debugMode) {
                        error_log("SPECIALISTS DEBUG - PRIORITY 2: Formatted " . count($formatted) . " specialists, returning");
                    }
                    $debugInfo['results_count'] = count($formatted);
                    return ['specialists' => $formatted, 'debug' => $debugInfo];
                } else {
                    if ($debugMode) {
                        error_log("SPECIALISTS DEBUG - PRIORITY 2: No specialists found for specialismID $specialismId, falling back to senior specialists");
                    }
                    $debugInfo['fallback_used'] = true;
                }
            }

            // PRIORITY 3: For location queries, return empty (let places handle it)
            if ($intentType === 'location') {
                error_log("SPECIALISTS DEBUG - PRIORITY 3: Location intent - returning empty array");
                $debugInfo['priority_used'] = 3;
                $debugInfo['selection_method'] = 'location_intent_skip';
                return ['specialists' => [], 'debug' => $debugInfo];
            }

            // PRIORITY 4: Fallback for service queries with no specialists OR general queries
            if ($intentType === 'service' || $intentType === 'general' || empty($intentType)) {
                error_log("SPECIALISTS DEBUG - PRIORITY 4: " . ($intentType === 'service' ? 'Service fallback' : 'General/unclassified intent') . " - using senior specialists");
                $debugInfo['priority_used'] = 4;
                $debugInfo['selection_method'] = $intentType === 'service' ? 'fallback_senior' : 'general_senior';
                $debugInfo['fallback_used'] = true;
                
                // Pass service area to help with smart fallback matching
                $seniorResults = $this->getSeniorSpecialists($maxSpecialists, $serviceArea);
                $debugInfo['results_count'] = count($seniorResults);
                $debugInfo['fallback_service_area'] = $serviceArea;
                return ['specialists' => $seniorResults, 'debug' => $debugInfo];
            }

            // If we get here, return empty array to avoid unwanted fallbacks
            error_log("SPECIALISTS DEBUG - No matching intent type: $intentType - returning empty array");
            $debugInfo['selection_method'] = 'no_match';
            return ['specialists' => [], 'debug' => $debugInfo];
            
        } catch (\Exception $e) {
            error_log("Specialist search failed: " . $e->getMessage());
            $debugInfo['selection_method'] = 'error';
            $debugInfo['error'] = $e->getMessage();
            return ['specialists' => [], 'debug' => $debugInfo];
        }
    }

    /**
     * Get targeted reviews based on AI analysis
     */
    private function getTargetedReviews($query, $aiAnalysis): array
    {
        // Check if reviews are enabled
        $enableReviews = Config::get('katalysis.search.enable_reviews', true);
        if (!$enableReviews) {
            return [];
        }

        try {
            $specialismId = $aiAnalysis['specialism_id'] ?? null;
            $serviceArea = $aiAnalysis['service_area'] ?? null;
            
            error_log("REVIEWS DEBUG - Query: '$query', SpecialismID: $specialismId, ServiceArea: $serviceArea");
            
            if ($specialismId) {
                $reviews = $this->getReviewsBySpecialismId($specialismId, $serviceArea);
                error_log("REVIEWS DEBUG - Found " . count($reviews) . " reviews for specialismID $specialismId");
                return $reviews;
            }
            
            // Fallback to featured reviews
            $featuredReviews = $this->getFeaturedReviews();
            error_log("REVIEWS DEBUG - No specialismID, showing " . count($featuredReviews) . " featured reviews");
            return $featuredReviews;
            
        } catch (\Exception $e) {
            error_log("Review search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get targeted places based on AI analysis
     */
    private function getTargetedPlaces($query, $aiAnalysis): array
    {
        // Check if places are enabled
        $enablePlaces = Config::get('katalysis.search.enable_places', true);
        if (!$enablePlaces) {
            return [];
        }
        
        try {
            // Use smart place selection based on location intent and query analysis
            return $this->getSmartPlaceSelection($query, $aiAnalysis);
            
        } catch (\Exception $e) {
            error_log("Places search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format PeopleList results for frontend display
     */
    private function formatPeopleListResults($people): array
    {
        $formatted = [];
        error_log("SPECIALISTS FORMAT DEBUG - Processing " . count($people) . " people from database");
        
        foreach ($people as $person) {
            error_log("SPECIALISTS FORMAT DEBUG - Processing person ID: " . $person->sID . ", Name: " . $person->name);
            // Get specialisms/topics for this person
            $specialisms = $person->getSpecialisms($person->sID);
            $expertise = !empty($specialisms) ? 
                implode(', ', array_column($specialisms, 'treeNodeName')) : 
                $this->mapJobTitleToExpertise($person->jobTitle);
            
            // Get image data
            $imageUrl = null;
            $imageAlt = $person->name;
            if ($person->image > 0) {
                try {
                    $thumbnail = \Concrete\Core\File\File::getByID($person->image);
                    if (is_object($thumbnail)) {
                        $imageType = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle('small_square');
                        if ($imageType) {
                            $imageUrl = $thumbnail->getThumbnailURL($imageType->getBaseVersion());
                        } else {
                            $imageUrl = $thumbnail->getURL();
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            
            // Get profile page link
            $profileUrl = null;
            if ($person->page > 0) {
                try {
                    $profilePage = \Concrete\Core\Page\Page::getByID($person->page);
                    if (is_object($profilePage) && !$profilePage->isError()) {
                        $profileUrl = $profilePage->getCollectionPath();
                    }
                } catch (\Exception $e) {
                }
            }
            
            // Get office/location data
            $personObj = new \Concrete\Package\KatalysisPro\Src\KatalysisPro\People\Person;
            $places = $personObj->getPlaces($person->sID);
            $officeInfo = null;
            if (is_array($places) && !empty($places)) {
                $officeNames = array_column($places, 'name');
                $officeTowns = array_column($places, 'town');
                $officeCounties = array_column($places, 'county');
                
                $officeInfo = [
                    'name' => implode(', ', array_filter($officeNames)),
                    'town' => implode(', ', array_filter($officeTowns)),
                    'county' => implode(', ', array_filter($officeCounties))
                ];
            }
            
            $formatted[] = [
                'id' => $person->sID,
                'name' => $person->name,
                'title' => $person->jobTitle ?: 'Specialist',
                'expertise' => $expertise,
                'contact' => $person->email ?: $person->phone ?: 'Contact Available',
                'email' => $person->email,
                'phone' => $person->phone,
                'mobile' => $person->mobile,
                'featured' => (bool)$person->featured,
                'relevance_score' => 9, // High score for specialism-matched results
                'sort_order' => $person->sortOrder,
                'specialism_match' => true,
                // Rich data for frontend rendering
                'image_url' => $imageUrl,
                'image_alt' => $imageAlt,
                'profile_url' => $profileUrl,
                'office' => $officeInfo,
                'qualification' => $person->qualification ?? null,
                'short_biography' => $person->shortBiography ?? null,
                'biography' => $person->biography ?? null
            ];
        }
        
        error_log("SPECIALISTS FORMAT DEBUG - Returning " . count($formatted) . " formatted specialists");
        return $formatted;
    }
    
    /**
     * Map job title to expertise area if no specialisms found
     */
    private function mapJobTitleToExpertise($jobTitle)
    {
        $jobTitleLower = strtolower($jobTitle ?: '');
        
        // Map common job titles to expertise areas
        if (strpos($jobTitleLower, 'conveyancing') !== false || strpos($jobTitleLower, 'property') !== false) {
            return 'Conveyancing & Property Law';
        }
        if (strpos($jobTitleLower, 'family') !== false) {
            return 'Family Law';
        }
        if (strpos($jobTitleLower, 'injury') !== false || strpos($jobTitleLower, 'personal') !== false) {
            return 'Personal Injury Claims';
        }
        if (strpos($jobTitleLower, 'probate') !== false || strpos($jobTitleLower, 'wills') !== false) {
            return 'Wills, Probate & Estates';
        }
        if (strpos($jobTitleLower, 'dispute') !== false || strpos($jobTitleLower, 'litigation') !== false) {
            return 'Disputes & Settlements';
        }
        if (strpos($jobTitleLower, 'commercial') !== false || strpos($jobTitleLower, 'business') !== false) {
            return 'Commercial Law';
        }
        
        return 'Legal Services';
    }

    /**
     * Get related specialisms for better fallback matching
     */
    private function getRelatedSpecialisms($serviceArea): array
    {
        if (empty($serviceArea)) {
            return [];
        }
        
        try {
            $allSpecialisms = $this->getSpecialisms();
            $related = [];
            $serviceAreaLower = strtolower($serviceArea);
            
            // Define related service mappings for better fallbacks
            $relatedMappings = [
                'road accident' => ['injury claims', 'serious injury', 'medical negligence', 'work accident'],
                'accident' => ['injury claims', 'serious injury', 'medical negligence', 'road accident', 'work accident'],
                'injury' => ['injury claims', 'serious injury', 'medical negligence', 'road accident', 'work accident'],
                'medical negligence' => ['injury claims', 'serious injury', 'road accident'],
                'work accident' => ['injury claims', 'serious injury', 'road accident'],
            ];
            
            // Find related specialisms
            foreach ($relatedMappings as $key => $relatedAreas) {
                if (strpos($serviceAreaLower, $key) !== false) {
                    foreach ($allSpecialisms as $specialism) {
                        $specialismLower = strtolower($specialism['treeNodeName']);
                        foreach ($relatedAreas as $relatedArea) {
                            if (strpos($specialismLower, strtolower($relatedArea)) !== false) {
                                $related[] = $specialism;
                                break;
                            }
                        }
                    }
                    break;
                }
            }
            
            return array_unique($related, SORT_REGULAR);
            
        } catch (\Exception $e) {
            error_log("Error getting related specialisms: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get senior specialists as fallback - now with smart matching for service areas
     */
    private function getSeniorSpecialists($maxResults = 3, $preferredServiceArea = null): array
    {
        try {
            // If we have a service area preference, try to find related specialists first
            if (!empty($preferredServiceArea)) {
                error_log("SPECIALISTS FALLBACK DEBUG - Looking for specialists related to: $preferredServiceArea");
                
                // Get related specialisms for accident/injury queries
                $relatedSpecialisms = $this->getRelatedSpecialisms($preferredServiceArea);
                
                if (!empty($relatedSpecialisms)) {
                    error_log("SPECIALISTS FALLBACK DEBUG - Found " . count($relatedSpecialisms) . " related specialisms: " . implode(', ', array_column($relatedSpecialisms, 'treeNodeName')));
                    
                    // Try to find specialists in related areas
                    foreach ($relatedSpecialisms as $specialism) {
                        $peopleList = new PeopleList();
                        $peopleList->filterByActive();
                        $peopleList->filterBySpecialisms([$specialism['treeNodeID']]);
                        $peopleList->limitResults($maxResults);
                        
                        $results = $peopleList->getResults();
                        if (!empty($results)) {
                            error_log("SPECIALISTS FALLBACK DEBUG - Found " . count($results) . " specialists in related area: " . $specialism['treeNodeName']);
                            return $this->formatPeopleListResults($results);
                        }
                    }
                }
            }
            
            // Final fallback to featured specialists
            error_log("SPECIALISTS FALLBACK DEBUG - Using final fallback to featured specialists");
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->featuredOnly();
            $peopleList->limitResults($maxResults);
            
            $results = $peopleList->getResults();
            return $this->formatPeopleListResults($results);
            
        } catch (\Exception $e) {
            error_log("SPECIALISTS FALLBACK ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reviews by specialism ID
     */
    private function getReviewsBySpecialismId($specialismId, $serviceArea = ''): array
    {
        try {
            $maxReviews = Config::get('katalysis.search.max_reviews', 3);
            
            error_log("REVIEWS SPECIALISM DEBUG - Searching for specialismID: $specialismId, maxReviews: $maxReviews");
            
            $reviewList = new ReviewList();
            $reviewList->filterByActive();
            $reviewList->filterBySpecialisms([$specialismId]);
            $reviewList->limitResults($maxReviews);
            
            $results = $reviewList->getResults();
            error_log("REVIEWS SPECIALISM DEBUG - Found " . count($results) . " results from ReviewList");
            
            $formatted = $this->formatReviewResults($results);
            error_log("REVIEWS SPECIALISM DEBUG - Formatted " . count($formatted) . " reviews");
            return $formatted;
            
        } catch (\Exception $e) {
            error_log("REVIEWS SPECIALISM ERROR: " . $e->getMessage());
            return $this->getFeaturedReviews();
        }
    }

    /**
     * Get featured reviews as fallback
     */
    private function getFeaturedReviews($maxResults = 3): array
    {
        try {
            error_log("REVIEWS FEATURED DEBUG - Getting featured reviews with limit: $maxResults");
            
            $reviewList = new ReviewList();
            $reviewList->filterByActive();
            $reviewList->featuredOnly();
            $reviewList->limitResults($maxResults);
            
            $results = $reviewList->getResults();
            error_log("REVIEWS FEATURED DEBUG - Found " . count($results) . " featured reviews");
            
            $formatted = $this->formatReviewResults($results);
            error_log("REVIEWS FEATURED DEBUG - Formatted " . count($formatted) . " featured reviews");
            return $formatted;
            
        } catch (\Exception $e) {
            error_log("REVIEWS FEATURED ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format review results for frontend display
     */
    private function formatReviewResults($reviews): array
    {
        $formatted = [];
        
        foreach ($reviews as $review) {
            $formatted[] = [
                'id' => $review->sID,
                'client_name' => $review->author ?: 'Anonymous',
                'name' => $review->author ?: 'Anonymous', // Alias for compatibility
                'organization' => $review->organization ?: '',
                'service' => $review->organization ?: '', // Alias for compatibility
                'rating' => (int)($review->rating ?: 5),
                'review' => $review->extract ?: $review->review ?: 'Excellent service',
                'content' => $review->extract ?: $review->review ?: 'Excellent service', // Alias for compatibility
                'extract' => $review->extract ?: '',
                'source' => $review->source ?: 'Client Review',
                'date' => $review->date ? $review->date : '',
                'url' => $review->url ?: '',
                'featured' => (bool)$review->featured,
                'relevance_score' => 10, // Perfect score for direct specialism match
                'specialism_match' => true
            ];
        }
        
        return $formatted;
    }

    /**
     * Get nearest offices
     */
    private function getNearestOffices($maxResults = null): array
    {
        try {
            // Use configured max places if not specified
            if ($maxResults === null) {
                $maxResults = Config::get('katalysis.search.max_places', 3);
            }
            
            $placeList = new PlaceList();
            $placeList->filterByActive();
            $placeList->limitResults($maxResults);
            
            $results = $placeList->getResults();
            return $this->formatPlaceResults($results);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Format place results for frontend display
     */
    private function formatPlaceResults($places, $isLocationSpecific = false, $distanceData = []): array
    {
        $results = [];
        
        foreach ($places as $place) {
            // Build complete address matching old search format
            $address = trim(($place->address1 ?? $place->address ?? '') . ' ' . ($place->address2 ?? ''));
            if (!empty($place->town)) {
                $address .= ($address ? ', ' : '') . $place->town;
            }
            if (!empty($place->county)) {
                $address .= ($address ? ', ' : '') . $place->county;
            }
            if (!empty($place->postcode)) {
                $address .= ($address ? ' ' : '') . $place->postcode;
            }
            
            // Find distance for this place if available
            $distanceText = null;
            $distanceValue = null;
            foreach ($distanceData as $distanceItem) {
                if (isset($distanceItem['place']) && $distanceItem['place']->sID === $place->sID) {
                    $distanceText = $distanceItem['distance_text'];
                    $distanceValue = $distanceItem['distance'];
                    break;
                }
            }
            
            // Get additional place data using the proper Place class
            $additionalData = [];
            if (!empty($place->sID)) {
                try {
                    $fullPlace = \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\Place::getByID($place->sID);
                    
                    if ($fullPlace) {
                        $additionalData = [
                            'email' => $fullPlace->email ?? null,
                            'fax' => null, // Not in database schema
                            'opening_hours' => $fullPlace->openingHours ?? null,
                            'parking_info' => null, // Not in database schema  
                            'accessibility' => null, // Not in database schema
                            'page_url' => null,
                            'latitude' => $fullPlace->latitude ?? null,
                            'longitude' => $fullPlace->longitude ?? null
                        ];
                        
                        // Get page URL if place has a linked page - skipped for now due to property access issues
                    }
                } catch (\Exception $e) {
                    // Continue with basic data
                }
            }
            
            $results[] = [
                'id' => $place->sID,
                'name' => $place->name ?? '',
                'address' => $address,
                'phone' => $place->phone ?? '',
                'email' => $additionalData['email'] ?? null,
                'fax' => $additionalData['fax'] ?? null,
                'opening_hours' => $additionalData['opening_hours'] ?? null,
                'parking_info' => $additionalData['parking_info'] ?? null,
                'accessibility' => $additionalData['accessibility'] ?? null,
                'page_url' => $additionalData['page_url'] ?? null,
                'latitude' => $additionalData['latitude'] ?? null,
                'longitude' => $additionalData['longitude'] ?? null,
                'services' => [], // Could be enhanced with place-service mapping
                'featured' => false,
                'relevance_score' => $isLocationSpecific ? 9 : 7, // Higher score for specific location matches
                'optimized_match' => true,
                // Distance information for display in place cards
                'distance_text' => $distanceText,
                'distance_miles' => $distanceValue,
                // Individual address components for flexible display
                'address_components' => [
                    'line1' => $place->address1 ?? $place->address ?? '',
                    'line2' => $place->address2 ?? '',
                    'town' => $place->town ?? '',
                    'county' => $place->county ?? '',
                    'postcode' => $place->postcode ?? ''
                ]
            ];
        }
        
        return $results;
    }

    /**
     * Get location keywords dynamically from PlaceList instead of hard-coded arrays
     */
    private function getLocationKeywords(): array
    {
        try {
            // Get all active places using PlaceList
            $placeList = new \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\PlaceList();
            $placeList->filterByActive();
            $places = $placeList->getResults();
            
            $locationKeywords = [];
            $placeNames = [];
            
            foreach ($places as $place) {
                // Add place name variations
                if (!empty($place->name)) {
                    $placeName = strtolower(trim($place->name));
                    if (!in_array($placeName, $placeNames)) {
                        $placeNames[] = $placeName;
                        $locationKeywords[] = $placeName;
                    }
                }
                
                // Add town names
                if (!empty($place->town)) {
                    $townName = strtolower(trim($place->town));
                    if (!in_array($townName, $locationKeywords)) {
                        $locationKeywords[] = $townName;
                        $placeNames[] = $townName;
                    }
                }
                
                // Add county names
                if (!empty($place->county)) {
                    $countyName = strtolower(trim($place->county));
                    if (!in_array($countyName, $locationKeywords)) {
                        $locationKeywords[] = $countyName;
                    }
                }
            }
            
            // Add generic location keywords
            $genericKeywords = ['office', 'offices', 'location', 'locations', 'near', 'visit'];
            $locationKeywords = array_merge($locationKeywords, $genericKeywords);
            
            return [
                'locationKeywords' => array_unique($locationKeywords),
                'placeNames' => array_unique($placeNames)
            ];
            
        } catch (\Exception $e) {
            // Fallback to basic keywords if PlaceList fails
            return [
                'locationKeywords' => ['office', 'offices', 'location', 'locations', 'near', 'visit'],
                'placeNames' => []
            ];
        }
    }

    /**
     * Get places by specific location (exact office match or area match) - returns empty array if no exact match
     */
    private function getPlacesByLocation(string $location): array
    {
        try {
            // Use PlaceList to get all active places, then filter in PHP
            $placeList = new \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\PlaceList();
            $placeList->filterByActive();
            $placeList->limitResults(10); // Get more to filter from
            
            $allPlaces = $placeList->getResults();
            
            // Filter places by location in PHP - strict matching for direct office check
            $matchingPlaces = [];
            $locationLower = strtolower($location);
            
            foreach ($allPlaces as $place) {
                $nameMatch = stripos($place->name, $location) !== false;
                $townMatch = stripos($place->town, $location) !== false;
                $countyMatch = stripos($place->county, $location) !== false;
                
                // Enhanced debug logging for all locations to see what's in the database
                error_log("LOCATION DEBUG - Searching for: '$location' | Place: '{$place->name}' | Town: '{$place->town}' | County: '{$place->county}'");
                error_log("LOCATION DEBUG - Name Match: " . ($nameMatch ? 'YES' : 'NO') . " | Town Match: " . ($townMatch ? 'YES' : 'NO') . " | County Match: " . ($countyMatch ? 'YES' : 'NO'));
                
                if ($nameMatch || $townMatch || $countyMatch) {
                    error_log("LOCATION MATCH FOUND - Adding place: {$place->name} to matching places");
                    $matchingPlaces[] = $place;
                    $maxPlaces = Config::get('katalysis.search.max_places', 3);
                    if (count($matchingPlaces) >= $maxPlaces) break; // Use configured limit
                }
            }
            
            // Return empty array if no direct matches found - don't fallback to nearest offices
            if (empty($matchingPlaces)) {
                return [];
            }
            
            return $this->formatPlaceResults($matchingPlaces, true);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Recognize location in query using AI and calculate distances to offices
     */
    private function recognizeLocationWithAI(string $query): array
    {
        try {
            // Get OpenAI configuration
            $openaiKey = \Concrete\Core\Support\Facade\Config::get('katalysis.ai.open_ai_key');
            $openaiModel = \Concrete\Core\Support\Facade\Config::get('katalysis.ai.open_ai_model') ?: 'gpt-4o-mini';
            
            if (empty($openaiKey) || $openaiKey === 'YOUR_OPENAI_API_KEY_HERE') {
                return [
                    'location_found' => false,
                    'reason' => 'OpenAI API key not configured'
                ];
            }
            
            // Create AI prompt for location recognition
            $prompt = "Analyze this search query and identify any UK city, town, or location mentioned: \"$query\"

Instructions:
1. Look for any UK location names (cities, towns, areas)
2. If you find a location, respond with JSON: {\"location_found\": true, \"location_name\": \"[exact location name]\", \"uk_location\": true}
3. If no clear UK location is found, respond with JSON: {\"location_found\": false, \"uk_location\": false}
4. Be strict - only identify clear, real UK locations

Examples:
- \"lawyers in Cardiff\" → {\"location_found\": true, \"location_name\": \"Cardiff\", \"uk_location\": true}
- \"solicitors near Birmingham\" → {\"location_found\": true, \"location_name\": \"Birmingham\", \"uk_location\": true}
- \"employment law help\" → {\"location_found\": false, \"uk_location\": false}";

            // Make OpenAI API call
            $response = $this->callOpenAI($openaiKey, $openaiModel, $prompt);
            
            if (!$response) {
                return [
                    'location_found' => false,
                    'reason' => 'AI API call failed'
                ];
            }
            
            // Parse AI response
            $locationData = json_decode($response, true);
            
            if (!$locationData || !$locationData['location_found']) {
                return [
                    'location_found' => false,
                    'reason' => 'No UK location detected by AI'
                ];
            }
            
            // First check if we have an office in this exact location
            $directMatch = $this->getPlacesByLocation($locationData['location_name']);
            
            // Enhanced debug logging for all locations
            error_log("AI LOCATION DEBUG - Searching for: '{$locationData['location_name']}', Direct matches found: " . count($directMatch));
            if (!empty($directMatch)) {
                foreach ($directMatch as $match) {
                    error_log("AI LOCATION DEBUG - Direct Match: {$match['name']}, Address: {$match['address']}");
                }
                
                return [
                    'location_found' => true,
                    'location' => $locationData['location_name'],
                    'reason' => "Found office in {$locationData['location_name']}",
                    'nearest_offices' => $directMatch,
                    'distances' => []
                ];
            } else {
                error_log("AI LOCATION DEBUG - No direct matches for '{$locationData['location_name']}' - proceeding with distance calculation");
            }
            
            // Get coordinates for the detected location
            $coordinates = $this->getLocationCoordinates($locationData['location_name']);
            
            if (!$coordinates) {
                return [
                    'location_found' => true,
                    'location' => $locationData['location_name'],
                    'reason' => "No office in {$locationData['location_name']} - showing nearest offices (coordinates not found)",
                    'nearest_offices' => $this->getNearestOffices(),
                    'distances' => []
                ];
            }
            
            // Calculate distances to all offices
            $nearestOfficesResult = $this->calculateNearestOffices($coordinates['lat'], $coordinates['lng'], $locationData['location_name']);
            
            $distanceText = !empty($nearestOfficesResult['distances']) ? 
                ' - Distances: ' . implode(', ', $nearestOfficesResult['distances']) : '';
            
            return [
                'location_found' => true,
                'location' => $locationData['location_name'],
                'coordinates' => $coordinates,
                'reason' => "No office in {$locationData['location_name']} - nearest offices by distance" . $distanceText,
                'nearest_offices' => $nearestOfficesResult['places'],
                'distances' => $nearestOfficesResult['distances'] ?? []
            ];
            
        } catch (\Exception $e) {
            error_log('AI Location Recognition Error: ' . $e->getMessage());
            return [
                'location_found' => false,
                'reason' => 'AI location recognition failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Smart place selection with debug information
     */
    private function getSmartPlaceSelectionWithDebug(string $query, array $intent): array
    {
        $debug = [
            'method' => 'none',
            'location_detected' => null,
            'places_found' => 0,
            'selection_reason' => 'No location context found'
        ];
        
        try {
            // Trust AI intent analysis first - if no location mentioned, don't show places
            $intentType = $intent['intent_type'] ?? 'general';
            $locationMentioned = $intent['location_mentioned'] ?? null;
            
            // For pure service queries with no location, return empty places
            if ($intentType === 'service' && empty($locationMentioned)) {
                $debug['method'] = 'ai_intent_service_no_location';
                $debug['selection_reason'] = 'Service intent with no location - hiding places section';
                return ['places' => [], 'debug' => $debug];
            }
            
            // For person queries with no location, return empty places
            if ($intentType === 'person' && empty($locationMentioned)) {
                $debug['method'] = 'ai_intent_person_no_location';
                $debug['selection_reason'] = 'Person intent with no location - hiding places section';
                return ['places' => [], 'debug' => $debug];
            }
            
            // For general queries with no location, return empty places
            if ($intentType === 'general' && empty($locationMentioned)) {
                $debug['method'] = 'ai_intent_general_no_location';
                $debug['selection_reason'] = 'General intent with no location - hiding places section';
                return ['places' => [], 'debug' => $debug];
            }
            
            // Only proceed with location processing if location is mentioned or intent is location
            if ($intentType === 'location' || !empty($locationMentioned)) {
                // 1. AI-powered location recognition and distance calculation
                $locationToSearch = $locationMentioned ?: $query;
                $aiLocationResult = $this->recognizeLocationWithAI($locationToSearch);
                
                if ($aiLocationResult['location_found']) {
                    $debug['method'] = 'ai_location_recognition';
                    $debug['location_detected'] = $aiLocationResult['location'];
                    $debug['selection_reason'] = $aiLocationResult['reason'];
                    
                    // Add distance information to debug
                    if (!empty($aiLocationResult['distances'])) {
                        $debug['distances_calculated'] = implode(', ', $aiLocationResult['distances']);
                    }
                    
                    if (!empty($aiLocationResult['nearest_offices'])) {
                        $debug['places_found'] = count($aiLocationResult['nearest_offices']);
                        return ['places' => $aiLocationResult['nearest_offices'], 'debug' => $debug];
                    }
                }
                
                // 2. Fallback to basic location matching if AI recognition fails
                if (!empty($locationMentioned)) {
                    $debug['method'] = 'basic_location_matching';
                    $debug['location_detected'] = $locationMentioned;
                    $debug['selection_reason'] = 'AI location detection failed - using basic matching';
                    $places = $this->getPlacesByLocation($locationMentioned);
                    $debug['places_found'] = count($places);
                    return ['places' => $places, 'debug' => $debug];
                }
            }
            
            // Default: No places if no location context found
            $debug['method'] = 'no_location_context';
            $debug['selection_reason'] = 'No location context detected in query or AI analysis - hiding places section';
            return ['places' => [], 'debug' => $debug];
            
        } catch (\Exception $e) {
            $debug['method'] = 'error';
            $debug['selection_reason'] = 'Error during place selection: ' . $e->getMessage();
            return ['places' => [], 'debug' => $debug];
        }
    }

    /**
     * Smart place selection based on query and AI intent analysis
     */
    private function getSmartPlaceSelection(string $query, array $intent): array
    {
        // Use the debug version and extract just the places
        $result = $this->getSmartPlaceSelectionWithDebug($query, $intent);
        return $result['places'];
    }

    /**
     * Get available people names for AI context
     */
    private function getPeopleNames(): array
    {
        try {
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->limitResults(50); // Get top 50 for AI context
            
            $people = $peopleList->getResults();
            $names = [];
            
            foreach ($people as $person) {
                if (!empty($person->name)) {
                    $names[] = trim($person->name);
                }
            }
            
            return array_unique($names);
            
        } catch (\Exception $e) {
            error_log("Error getting people names: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get detailed people information with specialisms for debug panel
     */
    private function getPeopleWithSpecialisms(): array
    {
        try {
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->limitResults(20); // Limit for debug display
            
            $people = $peopleList->getResults();
            error_log("PEOPLE DEBUG - Found " . count($people) . " active people");
            $detailed = [];
            
            foreach ($people as $person) {
                if (empty($person->name)) {
                    continue;
                }
                
                // Get specialisms for this person
                $specialisms = $person->getSpecialisms($person->sID);
                $specialismNames = !empty($specialisms) ? 
                    array_column($specialisms, 'treeNodeName') : 
                    ['General'];
                
                $detailed[] = [
                    'id' => $person->sID,
                    'name' => $person->name,
                    'title' => $person->jobTitle ?: 'Specialist',
                    'specialisms' => $specialismNames,
                    'featured' => (bool)$person->featured,
                    'expertise_summary' => !empty($specialisms) ? 
                        implode(', ', $specialismNames) : 
                        $this->mapJobTitleToExpertise($person->jobTitle)
                ];
            }
            
            error_log("PEOPLE DEBUG - Returning " . count($detailed) . " detailed people records");
            return $detailed;
            
        } catch (\Exception $e) {
            error_log("Error getting people with specialisms: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get fallback pages for general queries
     * Returns formatted pages for "Our Services" and "About Us" sections
     */
    private function getFallbackPages(): array
    {
        try {
            // Define fallback page IDs - TODO: Make this configurable in block settings
            $fallbackPageIds = [
                // Our Services pages (main legal service index pages)
                // These should be replaced with actual page IDs from your site
                1217, // Personal Injury
                218, // Road Accident  
                220, // Serious Injury
                155, // Work Accident
                1918, // Medical Negligence
                570, // Conveyancing
                931, // Family Law
                932, // Wills & Probate
                560, // Displutes & Settlements
                
                // About Us section pages - update these with real IDs:
                791, // Our People
                206, // Reviews & Testimonials  
                157, // News
            ];
            
            $db = Database::get();
            $fallbackPages = [];
            
            foreach ($fallbackPageIds as $pageId) {
                // Get page details
                $page = \Concrete\Core\Page\Page::getByID($pageId);
                
                if (!$page || $page->isError()) {
                    continue;
                }
                
                // Skip if page is in trash or not active
                if ($page->isInTrash() || !$page->isActive()) {
                    continue;
                }
                
                // Get page attributes
                $collectionName = $page->getCollectionName();
                $collectionDescription = $page->getCollectionDescription();
                $collectionHandle = $page->getCollectionHandle();
                
                // Generate link
                $link = \Concrete\Core\Support\Facade\Url::to($page);
                
                // Determine category based on page title and handle
                $category = $this->categorizeFallbackPage($collectionName, $collectionHandle);
                
                $fallbackPages[] = [
                    'id' => $pageId,
                    'title' => $collectionName,
                    'description' => $collectionDescription ?: 'Learn more about our ' . strtolower($collectionName) . ' services.',
                    'url' => (string) $link,
                    'handle' => $collectionHandle,
                    'category' => $category,
                    'type' => 'fallback_page'
                ];
            }
            
            error_log("FALLBACK PAGES DEBUG - Found " . count($fallbackPages) . " valid fallback pages");
            
            return $fallbackPages;
            
        } catch (\Exception $e) {
            error_log("Error getting fallback pages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Categorize fallback page based on title and handle
     */
    private function categorizeFallbackPage($title, $handle): string
    {
        $title = strtolower($title);
        $handle = strtolower($handle);
        
        // Our Services keywords
        $serviceKeywords = [
            'injury', 'accident', 'compensation', 'claims', 'conveyancing', 'property',
            'family law', 'divorce', 'wills', 'probate', 'medical negligence', 
            'serious injury', 'road accident', 'work accident', 'disputes', 'litigation',
            'legal', 'solicitor', 'law', 'service', 'help'
        ];
        
        // About Us keywords  
        $aboutKeywords = [
            'team', 'people', 'staff', 'solicitors', 'about', 'us', 'our', 
            'reviews', 'testimonials', 'client', 'feedback', 'history', 
            'awards', 'accreditation', 'news', 'updates', 'contact'
        ];
        
        // Check for service keywords first
        foreach ($serviceKeywords as $keyword) {
            if (strpos($title, $keyword) !== false || strpos($handle, $keyword) !== false) {
                return 'Our Services';
            }
        }
        
        // Check for about keywords
        foreach ($aboutKeywords as $keyword) {
            if (strpos($title, $keyword) !== false || strpos($handle, $keyword) !== false) {
                return 'About Us';
            }
        }
        
        // Default fallback based on common page patterns
        if (strpos($handle, 'service') !== false || strpos($handle, 'legal') !== false) {
            return 'Our Services';
        }
        
        return 'About Us'; // Default to About Us for ambiguous cases
    }

    /**
     * Make OpenAI API call
     */
    private function callOpenAI($apiKey, $model, $prompt): ?string
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 300,
                'temperature' => 0.1
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['choices'][0]['message']['content'])) {
                    return trim($responseData['choices'][0]['message']['content']);
                }
            }

            error_log("OpenAI API Error: HTTP $httpCode");
            return null;

        } catch (\Exception $e) {
            error_log("OpenAI API Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find person match using fuzzy matching
     */
    private function findPersonMatch($searchPerson, $availablePeople): ?string
    {
        $searchLower = strtolower($searchPerson);
        
        // First try exact match
        foreach ($availablePeople as $person) {
            if (strtolower($person) === $searchLower) {
                return $person;
            }
        }
        
        // Then try partial matches (first name, last name)
        foreach ($availablePeople as $person) {
            $personLower = strtolower($person);
            
            // Check if search contains the person's name or vice versa
            if (strpos($personLower, $searchLower) !== false || strpos($searchLower, $personLower) !== false) {
                return $person;
            }
            
            // Check individual name parts
            $searchParts = explode(' ', $searchLower);
            $personParts = explode(' ', $personLower);
            
            foreach ($searchParts as $searchPart) {
                if (strlen($searchPart) >= 3) { // Minimum 3 characters
                    foreach ($personParts as $personPart) {
                        if (strpos($personPart, $searchPart) === 0 || strpos($searchPart, $personPart) === 0) {
                            return $person;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Find location match using fuzzy matching
     */
    private function findLocationMatch($searchLocation, $availablePlaces): ?string
    {
        $searchLower = strtolower($searchLocation);
        
        // First try exact match
        foreach ($availablePlaces as $place) {
            if (strtolower($place) === $searchLower) {
                return $place;
            }
        }
        
        // Then try partial matches
        foreach ($availablePlaces as $place) {
            $placeLower = strtolower($place);
            
            if (strpos($placeLower, $searchLower) !== false || strpos($searchLower, $placeLower) !== false) {
                return $place;
            }
        }
        
        return null;
    }

    /**
     * Get coordinates for a location using a geocoding service
     */
    private function getLocationCoordinates($locationName): ?array
    {
        try {
            // Using OpenStreetMap Nominatim (free geocoding service)
            $url = 'https://nominatim.openstreetmap.org/search?format=json&countrycodes=GB&limit=1&q=' . urlencode($locationName . ', UK');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_USERAGENT, 'KatalysisProAI/1.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                    return [
                        'lat' => (float)$data[0]['lat'],
                        'lng' => (float)$data[0]['lon']
                    ];
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate nearest offices based on coordinates
     */
    private function calculateNearestOffices($lat, $lng, $locationName, $maxResults = null): array
    {
        try {
            // Use configured max places if not specified
            if ($maxResults === null) {
                $maxResults = Config::get('katalysis.search.max_places', 3);
            }
            
            $placeList = new PlaceList();
            $placeList->filterByActive();
            $allPlaces = $placeList->getResults();
            
            if (empty($allPlaces)) {
                return ['places' => [], 'distances' => []];
            }
            
            $placesWithDistances = [];
            
            foreach ($allPlaces as $place) {
                // Try to get coordinates from place data
                $placeLat = null;
                $placeLng = null;
                
                // Get full place object with coordinates if available
                try {
                    $fullPlace = \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\Place::getByID($place->sID);
                    if ($fullPlace) {
                        $placeLat = $fullPlace->latitude ?? null;
                        $placeLng = $fullPlace->longitude ?? null;
                    }
                } catch (\Exception $e) {
                    // Continue without coordinates
                }
                
                // If no coordinates available, try geocoding the place address
                if (!$placeLat || !$placeLng) {
                    $placeAddress = trim(($place->address1 ?? '') . ' ' . ($place->town ?? '') . ' ' . ($place->postcode ?? ''));
                    if ($placeAddress) {
                        $coords = $this->getLocationCoordinates($placeAddress);
                        if ($coords) {
                            $placeLat = $coords['lat'];
                            $placeLng = $coords['lng'];
                        }
                    }
                }
                
                // Calculate distance if we have coordinates
                if ($placeLat && $placeLng) {
                    $distance = $this->calculateDistance($lat, $lng, $placeLat, $placeLng);
                    $placesWithDistances[] = [
                        'place' => $place,
                        'distance' => $distance,
                        'distance_text' => number_format($distance, 1) . ' miles'
                    ];
                } else {
                    // Add with high distance if no coordinates
                    $placesWithDistances[] = [
                        'place' => $place,
                        'distance' => 9999,
                        'distance_text' => 'Distance unavailable'
                    ];
                }
            }
            
            // Sort by distance
            usort($placesWithDistances, function($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
            
            // Get nearest places
            $nearestPlaces = array_slice($placesWithDistances, 0, $maxResults);
            $places = array_map(function($item) {
                return $item['place'];
            }, $nearestPlaces);
            
            // Create distance info for debug
            $distanceInfo = [];
            foreach ($nearestPlaces as $item) {
                $distanceInfo[] = $item['place']->name . ' (' . $item['distance_text'] . ')';
            }
            
            return [
                'places' => $this->formatPlaceResults($places, true, $nearestPlaces),
                'distances' => $distanceInfo,
                'location_searched' => $locationName
            ];
            
        } catch (\Exception $e) {
            error_log("Distance calculation error: " . $e->getMessage());
            return [
                'places' => $this->getNearestOffices($maxResults),
                'distances' => [],
                'location_searched' => $locationName
            ];
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 3959; // miles (use 6371 for kilometers)
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Search for a specific person by name
     */
    private function searchForSpecificPerson($personName): array
    {
        try {
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->limitResults(10); // Get more for name matching
            
            $allPeople = $peopleList->getResults();
            $matches = [];
            
            $searchLower = strtolower(trim($personName));
            
            foreach ($allPeople as $person) {
                if (empty($person->name)) {
                    continue;
                }
                
                $personNameLower = strtolower(trim($person->name));
                
                // Exact match (highest priority)
                if ($personNameLower === $searchLower) {
                    $matches[] = ['person' => $person, 'score' => 100];
                    continue;
                }
                
                // Full name contains search or vice versa
                if (strpos($personNameLower, $searchLower) !== false || strpos($searchLower, $personNameLower) !== false) {
                    $matches[] = ['person' => $person, 'score' => 90];
                    continue;
                }
                
                // Name parts matching (first name, last name)
                $searchParts = explode(' ', $searchLower);
                $personParts = explode(' ', $personNameLower);
                
                $partMatches = 0;
                foreach ($searchParts as $searchPart) {
                    if (strlen($searchPart) >= 2) {
                        foreach ($personParts as $personPart) {
                            if (strpos($personPart, $searchPart) === 0 || strpos($searchPart, $personPart) === 0) {
                                $partMatches++;
                                break;
                            }
                        }
                    }
                }
                
                if ($partMatches > 0) {
                    $score = min(80, $partMatches * 30);
                    $matches[] = ['person' => $person, 'score' => $score];
                }
            }
            
            if (empty($matches)) {
                return [];
            }
            
            // Sort by match score
            usort($matches, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            // Get best matches (limit to 3)
            $bestMatches = array_slice($matches, 0, 3);
            $people = array_map(function($match) {
                return $match['person'];
            }, $bestMatches);
            
            return $this->formatPeopleListResults($people);
            
        } catch (\Exception $e) {
            error_log("Person search failed: " . $e->getMessage());
            return [];
        }
    }

    // ============================================================================
    // AI RESPONSE GENERATION METHODS
    // ============================================================================

    /**
     * Generate AI response using Typesense search results as context
     */
    private function generateAIResponse($query, $aiAnalysis, $searchResults)
    {
        try {
            // Get response format instructions from admin configuration
            $responseFormatInstructions = $this->getResponseFormatInstructions();
            
            // Build context from Typesense search results
            $contextDocuments = $this->buildContextFromSearchResults($searchResults);
            
            // Get available actions for the prompt
            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $actionService = new ActionService($entityManager);
            $actionsForPrompt = $actionService->getActionsForPrompt();
            
            error_log("Enhanced AI Search - Actions available for prompt: " . $actionsForPrompt);
            
            // Get specialisms context
            $allSpecialisms = $this->getSpecialisms();
            $specialismsList = $this->buildSpecialismsContext($allSpecialisms);
            
            // Build comprehensive AI prompt
            $aiPrompt = $this->buildAIResponsePrompt($query, $aiAnalysis, $specialismsList, $actionsForPrompt, $responseFormatInstructions, $contextDocuments);
            
            // Make AI call using existing infrastructure
            $aiResponse = $this->callAIForResponse($aiPrompt);
            
            // Debug log the raw AI response
            error_log("Enhanced AI Search - Raw AI Response: " . substr($aiResponse, 0, 500) . (strlen($aiResponse) > 500 ? '...' : ''));
            
            // Parse and return structured response
            return $this->parseAIResponse($aiResponse);
            
        } catch (\Exception $e) {
            error_log("AI Response Generation Error: " . $e->getMessage());
            return [
                'response' => 'I apologize, but I encountered an error while generating a response. Please try again.',
                'actions' => [],
                'intent' => $aiAnalysis
            ];
        }
    }

    /**
     * Get response format instructions from admin configuration
     */
    private function getResponseFormatInstructions(): string
    {
        // Get saved response sections (JSON format)
        $savedSections = Config::get('katalysis.search.response_sections', '');
        $responseGuidelines = Config::get('katalysis.search.response_guidelines', '');
        
        // If we have JSON-based sections, use them
        if (!empty($savedSections)) {
            $sections = json_decode($savedSections, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($sections)) {
                return $this->buildResponseInstructionsFromSections($sections, $responseGuidelines);
            }
        }
        
        // Use default sections if nothing exists
        $defaultSections = $this->getDefaultSections();
        $defaultGuidelines = $this->getDefaultGuidelines();
        
        return $this->buildResponseInstructionsFromSections($defaultSections, $defaultGuidelines);
    }

    /**
     * Build response instructions from section definitions
     */
    private function buildResponseInstructionsFromSections($sections, $guidelines = '')
    {
        $instructions = "RESPONSE STRUCTURE - Use this EXACT format:\n";
        
        $enabledSections = 0;
        $sectionInstructions = [];
        
        foreach ($sections as $section) {
            // Only include enabled sections
            if (!isset($section['enabled']) || $section['enabled'] === true) {
                $sectionName = strtoupper($section['name']);
                $description = $section['description'] ?? 'Provide relevant content';
                $showHeading = $section['show_heading'] ?? true;
                $sentenceCount = $section['sentence_count'] ?? 2;
                
                if ($showHeading) {
                    $sectionInstructions[] = "{$sectionName}: [{$description}] - MUST be exactly {$sentenceCount} sentences";
                } else {
                    $sectionInstructions[] = "[{$description}] - MUST be exactly {$sentenceCount} sentences - No heading required";
                }
                
                $enabledSections++;
            }
        }
        
        $instructions .= implode("\n", $sectionInstructions);
        
        $instructions .= "\n\nRESPONSE GUIDELINES:\n";
        
        if (!empty($guidelines)) {
            $instructions .= $guidelines;
        } else {
            $instructions .= $this->getDefaultGuidelines();
        }
        
        // Add strict section enforcement
        $instructions .= "\n\nCRITICAL FORMATTING REQUIREMENTS:";
        $instructions .= "\n- Use EXACTLY the {$enabledSections} sections shown above";
        $instructions .= "\n- Each section must follow the specified format";
        $instructions .= "\n- MANDATORY: Each section must contain exactly the specified number of sentences - never more, never less";
        $instructions .= "\n- If a section requires 2 sentences, write exactly 2 complete sentences, not 1 or 3";
        $instructions .= "\n- Do NOT add sections like 'Related Services', 'Additional Information', or any others";
        $instructions .= "\n- Follow the sentence count for each section precisely";
        $instructions .= "\n- Use clear section headings as specified";
        $instructions .= "\n- Keep content concise and professional";
        
        return $instructions;
    }

    /**
     * Get default response sections
     */
    private function getDefaultSections()
    {
        return [
            [
                'name' => 'Direct Answer',
                'description' => 'Directly answer the specific question asked',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2,
                'sort_order' => 1
            ],
            [
                'name' => 'Our Services',
                'description' => 'Detail relevant legal services and expertise areas offered',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2,
                'sort_order' => 2
            ],
            [
                'name' => 'Why Choose Us',
                'description' => 'Highlight distinctive expertise and firm advantages',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2,
                'sort_order' => 3
            ],
            [
                'name' => 'Next Steps',
                'description' => 'Provide practical guidance and recommended actions',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2,
                'sort_order' => 4
            ]
        ];
    }

    /**
     * Get default response guidelines
     */
    private function getDefaultGuidelines()
    {
        return "- Write in a professional but accessible tone\n" .
               "- Focus on practical, actionable information\n" .
               "- Highlight relevant expertise and experience\n" .
               "- Include appropriate legal disclaimers when needed\n" .
               "- Use clear, jargon-free language\n" .
               "- CRITICAL: Each section MUST contain exactly 2 complete sentences";
    }

    /**
     * Build context from Typesense search results
     */
    private function buildContextFromSearchResults($searchResults)
    {
        $contextDocuments = '';
        
        if (!empty($searchResults['categories'])) {
            $contextDocuments = "\n\nRELEVANT CONTEXT DOCUMENTS:\n";
            $documentIndex = 1;
            
            // Get configured candidate documents count for AI processing
            $candidateDocumentsCount = Config::get('katalysis.search.candidate_documents_count', 15);
            $documentsPerCategory = max(2, intval($candidateDocumentsCount / 5)); // Distribute across categories
            
            foreach ($searchResults['categories'] as $categoryName => $categoryData) {
                if (!empty($categoryData['items'])) {
                    $contextDocuments .= "\n{$categoryName} Results:\n";
                    
                    foreach (array_slice($categoryData['items'], 0, $documentsPerCategory) as $item) {
                        $title = $item['title'] ?? 'Relevant Document';
                        $snippet = $item['snippet'] ?? '';
                        
                        $contextDocuments .= "Document {$documentIndex}:\n";
                        $contextDocuments .= "Title: {$title}\n";
                        if (!empty($snippet)) {
                            $contextDocuments .= "Content: " . substr(strip_tags($snippet), 0, 500) . "...\n";
                        }
                        $contextDocuments .= "\n";
                        $documentIndex++;
                    }
                }
            }
        }
        
        return $contextDocuments;
    }

    /**
     * Build specialisms context for AI
     */
    private function buildSpecialismsContext($allSpecialisms)
    {
        if (empty($allSpecialisms)) {
            return "No specific specialisms available";
        }
        
        $specialismsWithIds = array_map(function($spec) {
            return $spec['treeNodeName'] . " [#" . $spec['treeNodeID'] . "]";
        }, $allSpecialisms);
        
        return "Available legal specialisms: " . implode(', ', $specialismsWithIds);
    }

    /**
     * Build comprehensive AI response prompt
     */
    private function buildAIResponsePrompt($query, $aiAnalysis, $specialismsList, $actionsForPrompt, $responseFormatInstructions, $contextDocuments)
    {
        $intentType = $aiAnalysis['intent_type'] ?? 'general';
        $serviceArea = $aiAnalysis['service_area'] ?? 'General';
        $locationMentioned = $aiAnalysis['location_mentioned'] ?? null;
        $personMentioned = $aiAnalysis['person_mentioned'] ?? null;
        
        $prompt = "LEGAL QUERY: \"{$query}\"\n\n";
        $prompt .= "QUERY ANALYSIS:\n";
        $prompt .= "- Intent Type: {$intentType}\n";
        $prompt .= "- Service Area: {$serviceArea}\n";
        
        if ($locationMentioned) {
            $prompt .= "- Location: {$locationMentioned}\n";
        }
        if ($personMentioned) {
            $prompt .= "- Person Mentioned: {$personMentioned}\n";
        }
        
        $prompt .= "\n{$specialismsList}\n\n";
        $prompt .= "{$actionsForPrompt}\n\n";
        
        $prompt .= "MANDATORY JSON STRUCTURE: You MUST return JSON with this exact structure. Do not omit any fields:\n";
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"intent\": {\n";
        $prompt .= "    \"intent_type\": \"general\",\n";
        $prompt .= "    \"confidence\": 0.95,\n";
        $prompt .= "    \"service_area\": \"Injury Claims\",\n";
        $prompt .= "    \"urgency\": \"high\",\n";
        $prompt .= "    \"location_mentioned\": null,\n";
        $prompt .= "    \"key_phrases\": [\"accident claim\", \"injury\"]\n";
        $prompt .= "  },\n";
        $prompt .= "  \"response\": \"Your formatted response text here\",\n";
        $prompt .= "  \"selected_actions\": [1, 4]\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        
        $prompt .= "ACTION SELECTION RULES:\n";
        $prompt .= "- ALWAYS include 'selected_actions' in your JSON response\n";
        $prompt .= "- For legal service inquiries (injury claims, family law, etc.), include relevant action IDs\n";
        $prompt .= "- Action ID 1 (Book Meeting): Use for injury claims, accidents, legal consultations\n";
        $prompt .= "- Action ID 4 (Contact Form): Use for general inquiries and follow-up questions\n";
        $prompt .= "- For accident/injury claims: ALWAYS select actions [1, 4]\n";
        $prompt .= "- If uncertain, default to action ID 4 (Contact Form)\n";
        $prompt .= "- Never leave 'selected_actions' empty for legal service queries\n\n";
        
        $prompt .= $responseFormatInstructions;
        
        if (!empty($contextDocuments)) {
            $prompt .= $contextDocuments;
        }
        
        return $prompt;
    }

    /**
     * Call AI service for response generation
     */
    private function callAIForResponse($prompt)
    {
        // Get RAG agent for AI calls
        $ragAgent = new \KatalysisProAi\RagAgent();
        $ragAgent->setApp($this->app);
        
        $provider = $ragAgent->resolveProvider();
        
        // Set system prompt to ensure JSON format compliance
        $provider->systemPrompt("You are a legal AI assistant. You MUST respond with valid JSON in the exact format specified in the user's message. Do not deviate from the JSON structure requested. CRITICAL: Each response section MUST contain exactly 2 complete sentences - never just 1 sentence. Write 2 full sentences for each section to provide comprehensive information.");
        
        $aiResponse = $provider->chat([new \NeuronAI\Chat\Messages\UserMessage($prompt)]);
        
        return $aiResponse->getContent();
    }

    /**
     * Parse AI response and extract structured data
     */
    private function parseAIResponse($aiResponseContent)
    {
        // Clean up the response - remove markdown code blocks if present
        $cleanedContent = $aiResponseContent;
        if (preg_match('/```json\s*(.*?)\s*```/s', $cleanedContent, $matches)) {
            $cleanedContent = trim($matches[1]);
        } else {
            // Remove any other markdown code block markers
            $cleanedContent = preg_replace('/```[a-z]*\s*|\s*```/', '', $cleanedContent);
            $cleanedContent = trim($cleanedContent);
        }
        
        // Try to parse as JSON
        $jsonData = json_decode($cleanedContent, true);
        if ($jsonData && isset($jsonData['response'])) {
            $response = $jsonData['response'];
            $actions = $jsonData['selected_actions'] ?? [];
            $intent = $jsonData['intent'] ?? [];
            
            // Combine response with additional content sections (OUR CAPABILITIES, WHY CHOOSE US, etc.)
            $fullResponse = $response;
            foreach ($jsonData as $key => $value) {
                if ($key !== 'response' && $key !== 'selected_actions' && $key !== 'intent' && is_string($value)) {
                    // Add sections like "OUR CAPABILITIES", "WHY CHOOSE US", etc.
                    $sectionTitle = strtoupper(str_replace('_', ' ', $key));
                    $fullResponse .= "\n\n" . $sectionTitle . ": " . $value;
                }
            }
            
            // If no actions found but this is clearly an injury/accident query, add default actions
            if (empty($actions) && $this->isInjuryOrAccidentQuery($fullResponse)) {
                $actions = [1, 4]; // Default: Book Meeting + Contact Form
                error_log("Enhanced AI Search - Auto-added default actions [1,4] for injury/accident query (JSON path)");
            }
            
            // If no actions found but this is clearly a conveyancing query, add default actions
            if (empty($actions) && $this->isConveyancingQuery($fullResponse)) {
                $actions = [1, 4]; // Default: Book Meeting + Contact Form (same as injury for now)
                error_log("Enhanced AI Search - Auto-added default actions [1,4] for conveyancing query (JSON path)");
            }
            
            error_log("Enhanced AI Search - JSON parsed successfully. Actions found: " . json_encode($actions));
            error_log("Enhanced AI Search - Full response length: " . strlen($fullResponse) . " chars");
            
            // Clean any action tags from response
            $fullResponse = preg_replace('/\[ACTIONS:[0-9,\s]+\]/', '', $fullResponse);
            $fullResponse = trim($fullResponse);
            
            return [
                'response' => $fullResponse,
                'actions' => $actions,
                'intent' => $intent
            ];
        }
        
        // Fallback: Try to extract actions from malformed response
        $extractedActions = $this->extractActionsFromMalformedResponse($cleanedContent);
        
        // If no actions found but this is clearly an injury/accident query, add default actions
        if (empty($extractedActions) && $this->isInjuryOrAccidentQuery($cleanedContent)) {
            $extractedActions = [1, 4]; // Default: Book Meeting + Contact Form
            error_log("Enhanced AI Search - Auto-added default actions [1,4] for injury/accident query");
        }
        
        // Clean any action tags from response text for display
        $cleanedResponse = preg_replace('/\[ACTIONS:[0-9,\s]+\]/', '', $cleanedContent);
        $cleanedResponse = preg_replace('/"selected_actions":\s*\[[0-9,\s]*\]\s*\}?\s*$/', '', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);
        
        // Fallback if JSON parsing fails
        return [
            'response' => $cleanedResponse,
            'actions' => $extractedActions,
            'intent' => []
        ];
    }

    /**
     * Convert action IDs to full action objects for frontend display
     */
    private function getActionDetails($selectedActionIds)
    {
        if (empty($selectedActionIds)) {
            return [];
        }
        
        try {
            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $actionService = new ActionService($entityManager);
            
            $actions = [];
            foreach ($selectedActionIds as $actionId) {
                $action = $actionService->getActionById($actionId);
                if ($action) {
                    $actions[] = [
                        'id' => $action->getId(),
                        'name' => $action->getName(),
                        'icon' => $action->getIcon(),
                        'trigger_instruction' => $action->getTriggerInstruction(),
                        'response_instruction' => $action->getResponseInstruction(),
                        // Add any other fields needed by search-actions.js
                    ];
                }
            }
            
            return $actions;
            
        } catch (\Exception $e) {
            error_log("Error retrieving action details: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if the response content indicates an injury or accident-related query
     */
    private function isInjuryOrAccidentQuery($responseContent)
    {
        $injuryKeywords = [
            'accident', 'injury', 'claim', 'compensation', 'hurt', 'injured', 
            'damages', 'liability', 'negligence', 'personal injury', 'road accident',
            'work accident', 'medical negligence', 'slip', 'fall', 'crash'
        ];
        
        $responseText = strtolower($responseContent);
        foreach ($injuryKeywords as $keyword) {
            if (strpos($responseText, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the response content indicates a conveyancing-related query
     */
    private function isConveyancingQuery($responseContent)
    {
        $conveyancingKeywords = [
            'conveyancing', 'property', 'house', 'home', 'buy', 'sell', 'purchase',
            'sale', 'moving', 'mortgage', 'remortgage', 'lease', 'freehold',
            'leasehold', 'transfer', 'equity', 'completion', 'exchange'
        ];
        
        $responseText = strtolower($responseContent);
        foreach ($conveyancingKeywords as $keyword) {
            if (strpos($responseText, strtolower($keyword)) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract actions from malformed AI responses that contain action data but aren't proper JSON
     */
    private function extractActionsFromMalformedResponse($responseText)
    {
        $actions = [];
        
        try {
            // Look for "selected_actions": [1,4] patterns
            if (preg_match('/"selected_actions":\s*\[([0-9,\s]+)\]/', $responseText, $matches)) {
                $actionIdsString = $matches[1];
                $actionIds = array_map('trim', explode(',', $actionIdsString));
                $actionIds = array_filter(array_map('intval', $actionIds));
                
                error_log("Enhanced AI Search: Extracted actions from malformed response: " . implode(',', $actionIds));
                return array_values($actionIds);
            }
            
            // Also try the older [ACTIONS:1,4] pattern as fallback
            if (preg_match('/\[ACTIONS:([0-9,\s]+)\]/', $responseText, $matches)) {
                $actionIdsString = $matches[1];
                $actionIds = array_map('trim', explode(',', $actionIdsString));
                $actionIds = array_filter(array_map('intval', $actionIds));
                
                error_log("Enhanced AI Search: Extracted actions from [ACTIONS:] pattern: " . implode(',', $actionIds));
                return array_values($actionIds);
            }
            
        } catch (\Exception $e) {
            error_log("Error extracting actions from malformed response: " . $e->getMessage());
        }
        
        return [];
    }

    /**
     * Log search activity to database for analytics and tracking
     */
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
                    'processing_time_ms' => $fullResults['performance']['search_time_ms'] ?? 0,
                    'optimization_approach' => 'Enhanced AI Search - Typesense + Async AI',
                ]
            ];
            
            // Add counts and summaries if available
            if (!empty($fullResults)) {
                $resultSummary['results_summary'] = [
                    'pages_found' => isset($fullResults['search_results']['categories']) ? 
                        array_sum(array_column($fullResults['search_results']['categories'], 'count')) : 0,
                    'specialists_found' => count($fullResults['specialists'] ?? []),
                    'reviews_found' => count($fullResults['reviews'] ?? []),
                    'places_found' => count($fullResults['places'] ?? []),
                ];
                
                // Add debug information if available
                if (!empty($fullResults['ai_analysis'])) {
                    $resultSummary['debug_summary'] = [
                        'intent_type' => $fullResults['ai_analysis']['intent_type'] ?? 'unknown',
                        'service_area' => $fullResults['ai_analysis']['service_area'] ?? 'none',
                        'confidence' => $fullResults['ai_analysis']['confidence'] ?? 0,
                        'processing_time_ms' => $fullResults['performance']['search_time_ms'] ?? 0,
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
            
            // LLM model information (Enhanced AI Search uses different approach)
            $search->setLlm('Typesense + OpenAI GPT-4 (Enhanced)');
            
            // Save the search entity
            $entityManager->persist($search);
            $entityManager->flush();
            
        } catch (\Exception $e) {
            // Fallback to file-based logging if database logging fails
            try {
                $logDir = DIR_APPLICATION . '/files/katalysis_search_logs';
                
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $logFile = $logDir . '/enhanced_searches_' . date('Y-m') . '.log';
                $logEntry = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'query' => $query,
                    'block_id' => $blockId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'ai_response_length' => strlen($aiResponse),
                    'intent_type' => $intent['intent_type'] ?? 'unknown',
                    'search_type' => 'enhanced_ai_search',
                    'error' => 'DB logging failed: ' . $e->getMessage()
                ];
                
                file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
                
            } catch (\Exception $fileError) {
                // Silent failure - don't break the search experience
                error_log("Enhanced AI Search logging failed: " . $fileError->getMessage());
            }
        }
    }
    
    /**
     * Process RAG documents for search display
     */
    private function processRagDocuments($ragResults, $query, $intent)
    {
        if (empty($ragResults)) {
            return [];
        }
        
        // Use fallback processing for now - can be enhanced later
        return $this->fallbackRagProcessing($ragResults);
    }
    
    /**
     * Fallback RAG processing for document results
     */
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
     * Format search results for API response
     */
    private function formatSearchResults($ragResults, $includePageLinks = true, $showSnippets = true)
    {
        $formattedResults = [];
        
        foreach ($ragResults as $result) {
            $formattedResult = [
                'id' => $result['url'] ?? uniqid(),
                'title' => $result['title'] ?? 'Untitled',
                'snippet' => $showSnippets ? ($result['content'] ?? '') : '',
                'url' => $includePageLinks ? ($result['url'] ?? '') : '',
                'type' => $result['type'] ?? 'page',
                'score' => $result['score'] ?? 0.5,
                'badge' => $result['badge'] ?? 'Page'
            ];
            
            $formattedResults[] = $formattedResult;
        }
        
        return $formattedResults;
    }
    
    /**
     * Truncate content to specified length
     */
    private function truncateContent($content, $length = 150)
    {
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }

}
