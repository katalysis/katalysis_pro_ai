<?php
/**
 * REMOTE SITE: https://dev36.katalysis.net
 * This is deployed code - test via CMS frontend search block, not direct API calls
 */
namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Http\ResponseFactory;
use Config;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Type\Type as PageType;
use Concrete\Core\Attribute\Key\CollectionKey;
use PageList;
use Core;
use Database;
use KatalysisProAi\RagAgent;
use \NeuronAI\Chat\Messages\UserMessage;
use \NeuronAI\Chat\Messages\SystemMessage;
use \NeuronAI\Providers\OpenAI\OpenAI;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\People\PeopleList;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\Reviews\ReviewList;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\PlaceList;
use \Concrete\Package\KatalysisPro\Src\KatalysisPro\Places\Place;
use \Concrete\Core\Tree\Type\Topic as TopicTree;
use KatalysisProAi\ActionService;

class SearchSettings extends DashboardPageController
{
    public function view()
    {
        // Get current settings from config
        $config = $this->app->make('config');
        $maxResults = $config->get('katalysis.search.max_results', 8);
        $resultLength = $config->get('katalysis.search.result_length', 'medium');
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
        
        // Get AI document selection settings
        $useAISelection = Config::get('katalysis.search.use_ai_document_selection', false);
        $maxSelectedDocuments = Config::get('katalysis.search.max_selected_documents', 6);
        $candidateDocumentsCount = Config::get('katalysis.search.candidate_documents_count', 15);
        
        // Get advanced AI prompts (new section-based system)
        $responseSections = Config::get('katalysis.search.response_sections', '');
        $responseGuidelines = Config::get('katalysis.search.response_guidelines', '');
        
        // If no sections exist, initialize with defaults
        if (empty($responseSections)) {
            $responseSections = json_encode($this->getDefaultSections());
            $responseGuidelines = $this->getDefaultGuidelines();
        }
        
        // Legacy support - check for old format and suggest migration
        $hasOldFormat = !empty(Config::get('katalysis.search.response_format_instructions', ''));
        
        // Pass section data to view
        $this->set('responseSections', $responseSections);
        $this->set('responseGuidelines', $responseGuidelines);
        $this->set('hasOldFormat', $hasOldFormat);
        
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
        $this->set('useAISelection', $useAISelection);
        $this->set('maxSelectedDocuments', $maxSelectedDocuments);
        $this->set('candidateDocumentsCount', $candidateDocumentsCount);
        $this->set('knownFalsePositives', $knownFalsePositives);
        $this->set('enableDebugPanel', $enableDebugPanel);
        $this->set('searchStats', $searchStats);
        $this->set('popularTerms', $popularTerms);
        
        // Set default values for comparison (new section-based system)
        $this->set('defaultResponseSections', json_encode($this->getDefaultSections()));
        $this->set('defaultResponseGuidelines', $this->getDefaultGuidelines());
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
               "    \"specialism_id\": \"number or null (extract the ID number from specialisms list if service matches, e.g. if query matches 'Personal Injury (ID: 123)', return 123)\",\n" .
               "    \"urgency\": \"string (low, medium, high)\",\n" .
               "    \"location_mentioned\": \"string or null\",\n" .
               "    \"key_phrases\": [\"array of important phrases from query\"]\n" .
               "  },\n" .
               "  \"response\": \"string (structured response using format below)\"\n" .
               "}\n" .
               "```\n\n";
    }

    /**
     * Get response format instructions with flexible section management
     * Uses JSON-based section definitions for full administrative control
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
        
        // Check for old text-based format and migrate to sections
        $oldFormat = Config::get('katalysis.search.response_format_instructions', '');
        if (!empty($oldFormat)) {
            $migratedSections = $this->migrateOldFormatToSections($oldFormat);
            if (!empty($migratedSections)) {
                // Save migrated sections
                Config::save('katalysis.search.response_sections', json_encode($migratedSections));
                Config::save('katalysis.search.response_guidelines', $this->extractGuidelinesFromOldFormat($oldFormat));
                // Clear old format
                Config::save('katalysis.search.response_format_instructions', '');
                return $this->buildResponseInstructionsFromSections($migratedSections, $this->extractGuidelinesFromOldFormat($oldFormat));
            }
        }
        
        // Force use of default sections if nothing exists
        $defaultSections = $this->getDefaultSections();
        $defaultGuidelines = $this->getDefaultGuidelines();
        
        // Save defaults to config to ensure they persist
        Config::save('katalysis.search.response_sections', json_encode($defaultSections));
        Config::save('katalysis.search.response_guidelines', $defaultGuidelines);
        Config::save('katalysis.search.response_format_instructions', ''); // Clear any old format
        
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
        
        // Add strict section enforcement - return to text format that works
        $instructions .= "\n\nCRITICAL FORMATTING REQUIREMENTS:";
        $instructions .= "\n- Use EXACTLY the {$enabledSections} sections shown above";
        $instructions .= "\n- Each section must follow the specified format";
        $instructions .= "\n- MANDATORY: Each section must contain exactly the specified number of sentences - never more, never less";
        $instructions .= "\n- If a section requires 2 sentences, write exactly 2 complete sentences, not 1 or 3";
        $instructions .= "\n- Do NOT add sections like 'Related Services', 'Additional Information', or any others";
        $instructions .= "\n- Follow the sentence count for each section precisely";
        $instructions .= "\n- Use clear section headings as specified";
        $instructions .= "\n- Keep content concise and professional";
        $instructions .= "\n\nSENTENCE COUNT ENFORCEMENT:";
        $instructions .= "\n- COUNT YOUR SENTENCES: Before finalizing each section, literally count the sentences to ensure you have the exact required number";
        $instructions .= "\n- SENTENCE DEFINITION: A sentence ends with a period (.), exclamation mark (!), or question mark (?)";
        $instructions .= "\n- NO EXCEPTIONS: Even if you have more information to share, stick to the exact sentence count specified";
        $instructions .= "\n- QUALITY OVER QUANTITY: Make each sentence count by including the most important information";
        
        
        return $instructions;
    }
    
    /**
     * Migrate old format to section-based system
     */
    private function migrateOldFormatToSections($oldFormat)
    {
        $sections = [];
        
        // Extract sections from old format using regex
        if (preg_match_all('/^([A-Z][A-Z\s]+):\s*\[([^\]]+)\]/m', $oldFormat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sectionName = trim($match[1]);
                $description = trim($match[2]);
                
                // Skip "RELATED SERVICES" during migration
                if (strtoupper($sectionName) === 'RELATED SERVICES') {
                    continue;
                }
                
                $sections[] = [
                    'name' => $sectionName,
                    'description' => $description,
                    'enabled' => true,
                    'show_heading' => true,
                    'sentence_count' => 2
                ];
            }
        }
        
        // If no sections found, return default sections
        if (empty($sections)) {
            return $this->getDefaultSections();
        }
        
        return $sections;
    }
    
    /**
     * Extract guidelines from old format
     */
    private function extractGuidelinesFromOldFormat($oldFormat)
    {
        // Extract everything after "RESPONSE GUIDELINES:"
        if (preg_match('/RESPONSE GUIDELINES:\s*\n(.+)$/s', $oldFormat, $matches)) {
            return trim($matches[1]);
        }
        
        return $this->getDefaultGuidelines();
    }
    
    /**
     * Get default response sections
     */
    private function getDefaultSections()
    {
        return [
            [
                'name' => 'DIRECT ANSWER',
                'description' => 'Direct answer to their specific question or need',
                'enabled' => true,
                'show_heading' => false,
                'sentence_count' => 2
            ],
            [
                'name' => 'OUR CAPABILITIES',
                'description' => 'How our expertise specifically helps',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2
            ],
            [
                'name' => 'WHY CHOOSE US',
                'description' => 'Benefits of choosing our firm, unique value proposition',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2
            ],
            [
                'name' => 'PRACTICAL GUIDANCE',
                'description' => 'Next steps, what to prepare, or actions to take',
                'enabled' => true,
                'show_heading' => true,
                'sentence_count' => 2
            ]
        ];
    }
    
    /**
     * Get default guidelines
     */
    private function getDefaultGuidelines()
    {
        return "- Use professional, reassuring, and confident tone\n" .
               "- Be specific about our legal services and expertise\n" .
               "- Include practical next steps and actionable advice\n" .
               "- Highlight our unique strengths and experience\n" .
               "- CRITICAL: Each section MUST contain exactly 2 complete sentences - never just 1 sentence\n" .
               "- Write 2 full sentences for each section to provide comprehensive information\n" .
               "- End with a call to action encouraging contact";
    }
    private function getDefaultResponseFormatInstructions(): string
    {
        return "RESPONSE STRUCTURE - Use this exact format:\n" .
               "DIRECT ANSWER: [Direct answer to their specific question or need]\n" .
               "OUR CAPABILITIES: [How our expertise specifically helps]\n" .
               "WHY CHOOSE US: [Benefits of choosing our firm, unique value proposition]\n" .
               "PRACTICAL GUIDANCE: [Next steps, what to prepare, or actions to take]\n\n" .
               "RESPONSE GUIDELINES:\n" .
               "- Use professional, reassuring, and confident tone\n" .
               "- Be specific about our legal services and expertise\n" .
               "- Include practical next steps and actionable advice\n" .
               "- Highlight our unique strengths and experience\n" .
               "- CRITICAL: Each section MUST contain exactly 2 complete sentences. Never use just 1 sentence.\n" .
               "- Write 2 full sentences for each section to provide comprehensive information\n" .
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
     * Debug endpoint to check current response format configuration
     */
    public function debug_response_format()
    {
        // Get current configuration
        $savedSections = Config::get('katalysis.search.response_sections', '');
        $responseGuidelines = Config::get('katalysis.search.response_guidelines', '');
        $oldFormat = Config::get('katalysis.search.response_format_instructions', '');
        
        // Get what getResponseFormatInstructions() actually returns
        $currentInstructions = $this->getResponseFormatInstructions();
        
        $debug = [
            'current_time' => date('Y-m-d H:i:s'),
            'config_check' => [
                'response_sections_exists' => !empty($savedSections),
                'response_sections_length' => strlen($savedSections),
                'response_guidelines_exists' => !empty($responseGuidelines),
                'old_format_exists' => !empty($oldFormat),
                'old_format_length' => strlen($oldFormat)
            ],
            'section_data' => [
                'raw_sections' => $savedSections,
                'parsed_sections' => !empty($savedSections) ? json_decode($savedSections, true) : null,
                'json_parse_error' => json_last_error_msg()
            ],
            'current_instructions' => $currentInstructions,
            'default_sections' => $this->getDefaultSections(),
            'default_guidelines' => $this->getDefaultGuidelines()
        ];
        
        return $this->app->make(ResponseFactory::class)->json($debug);
    }

    /**
     * Debug endpoint to check the exact prompt being sent to AI
     */
    public function debug_prompt()
    {
        try {
            // Simulate the exact same prompt construction as in perform_search
            $query = "I need help with a house sale";
            
            // Get specialisms for intent analysis
            $allSpecialisms = $this->getSpecialisms();
            if (empty($allSpecialisms)) {
                $specialismsList = "No specific specialisms available";
            } else {
                // Build specialisms list with both names and IDs for AI mapping
                $specialismsWithIds = array_map(function($spec) {
                    return $spec['treeNodeName'] . " (ID: " . $spec['treeNodeID'] . ")";
                }, $allSpecialisms);
                $specialismsList = "Available legal specialisms: " . implode(', ', $specialismsWithIds);
            }
            
            // Get configurable response format instructions with automatic updates
            $responseFormatInstructions = $this->getResponseFormatInstructions();
            
            // Get base intent analysis prompt
            $baseIntentPrompt = $this->getDefaultIntentAnalysisPrompt();
            
            // Always use the configured format (either custom or updated default)
            $intentAnalysisPrompt = $this->getFullIntentAnalysisPrompt($responseFormatInstructions);
            
            // Get available actions for the prompt
            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $actionService = new ActionService($entityManager);
            $actionsForPrompt = $actionService->getActionsForPrompt();
            
            // Use original query directly - no enhancement needed for legal content
            
            // COMBINED PROMPT: Intent analysis + response generation in single AI call
            $combinedPrompt = "LEGAL QUERY: \"$query\"\n\n" .
                "ORIGINAL QUERY: \"$query\"\n\n" .
                "$specialismsList\n\n" .
                "$actionsForPrompt\n\n" .
                $intentAnalysisPrompt;
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'debug_info' => [
                    'query' => $query,
                    'specialisms_list' => $specialismsList,
                    'actions_for_prompt' => $actionsForPrompt,
                    'response_format_instructions' => $responseFormatInstructions,
                    'full_combined_prompt' => $combinedPrompt,
                    'prompt_length' => strlen($combinedPrompt),
                    'prompt_sections' => [
                        'legal_query_section' => strlen("LEGAL QUERY: \"$query\"\n\n"),
                        'original_query_section' => strlen("ORIGINAL QUERY: \"$query\"\n\n"),
                        'specialisms_section' => strlen("$specialismsList\n\n"),
                        'actions_section' => strlen("$actionsForPrompt\n\n"),
                        'intent_prompt_section' => strlen($intentAnalysisPrompt)
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function debug_work_accident()
    {
        header('Content-Type: text/plain');
        echo "=== Work Accident Pages Debug ===\n\n";
        $this->debugWorkAccidentPages();
        exit;
    }

    /**
     * Force migration endpoint for testing
     */
    public function force_migration()
    {
        // Clear all existing config
        Config::save('katalysis.search.response_sections', '');
        Config::save('katalysis.search.response_guidelines', '');
        Config::save('katalysis.search.response_format_instructions', '');
        
        // Force regeneration with defaults
        $instructions = $this->getResponseFormatInstructions();
        
        return $this->app->make(ResponseFactory::class)->json([
            'success' => true,
            'message' => 'Migration forced - using default 4-section structure',
            'instructions' => $instructions,
            'sections_saved' => Config::get('katalysis.search.response_sections', ''),
            'guidelines_saved' => Config::get('katalysis.search.response_guidelines', '')
        ]);
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
     * Get default response sections via AJAX
     */
    public function get_default_response_sections()
    {
        if (!$this->token->validate('save_search_settings')) {
            return new JsonResponse(['error' => $this->token->getErrorMessage()], 400);
        }
        
        return new JsonResponse([
            'sections' => $this->getDefaultSections(),
            'guidelines' => $this->getDefaultGuidelines()
        ]);
    }

    /**
     * Get default response format instructions via AJAX (legacy support)
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

        // Save AI document selection settings
        Config::save('katalysis.search.use_ai_document_selection', !empty($data['use_ai_document_selection']));
        Config::save('katalysis.search.max_selected_documents', (int)($data['max_selected_documents'] ?? 6));
        Config::save('katalysis.search.candidate_documents_count', (int)($data['candidate_documents_count'] ?? 15));

        // Save AI response configuration (new section-based system)
        if (isset($data['response_sections']) && !empty($data['response_sections'])) {
            // Validate JSON format for sections
            $sections = json_decode($data['response_sections'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($sections)) {
                Config::save('katalysis.search.response_sections', $data['response_sections']);
            } else {
                $this->error->add(t('Response sections must be valid JSON format.'));
            }
        } else {
            // If no sections provided, ensure we have defaults
            Config::save('katalysis.search.response_sections', json_encode($this->getDefaultSections()));
        }
        
        // Save response guidelines separately
        Config::save('katalysis.search.response_guidelines', $data['response_guidelines'] ?? $this->getDefaultGuidelines());
        
        // Clear old format instructions when using new system
        Config::save('katalysis.search.response_format_instructions', '');
        
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
            // Get search settings from config
            $candidateDocumentsCount = Config::get('katalysis.search.candidate_documents_count', 15);
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
                // Build specialisms list with both names and IDs for AI mapping
                $specialismsWithIds = array_map(function($spec) {
                    return $spec['treeNodeName'] . " (ID: " . $spec['treeNodeID'] . ")";
                }, $allSpecialisms);
                $specialismsList = "Available legal specialisms: " . implode(', ', $specialismsWithIds);
            }
            
            // Get configurable response format instructions with automatic updates
            $responseFormatInstructions = $this->getResponseFormatInstructions();
            
            // Get base intent analysis prompt
            $baseIntentPrompt = $this->getDefaultIntentAnalysisPrompt();
            
            // Always use the configured format (either custom or updated default)
            $intentAnalysisPrompt = $this->getFullIntentAnalysisPrompt($responseFormatInstructions);
            
            // Get available actions for the prompt
            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $actionService = new ActionService($entityManager);
            $actionsForPrompt = $actionService->getActionsForPrompt();
            
            // Use original query directly - no enhancement needed for legal content
            
            // COMBINED PROMPT: Intent analysis + response generation in single AI call
            $combinedPrompt = "LEGAL QUERY: \"$query\"\n\n" .
                "ORIGINAL QUERY: \"$query\"\n\n" .
                "$specialismsList\n\n" .
                "$actionsForPrompt\n\n" .
                "ENHANCED JSON STRUCTURE: You must return JSON with this exact structure:\n" .
                "```json\n" .
                "{\n" .
                "  \"intent\": {\n" .
                "    \"intent_type\": \"string\",\n" .
                "    \"confidence\": \"number\",\n" .
                "    \"service_area\": \"string or null\",\n" .
                "    \"urgency\": \"string\",\n" .
                "    \"location_mentioned\": \"string or null\",\n" .
                "    \"key_phrases\": [\"array of phrases\"]\n" .
                "  },\n" .
                "  \"response\": \"string (your structured response)\",\n" .
                "  \"selected_actions\": [1,4] // Array of action IDs relevant to this query\n" .
                "}\n" .
                "```\n\n" .
                "ACTION SELECTION: Review the available actions and include only the IDs of actions that are directly relevant to this specific legal query in the 'selected_actions' array. If no actions are relevant, use an empty array [].\n\n" .
                $intentAnalysisPrompt;
            
            
            // OPTIMIZATION: Retrieve documents once and reuse for both AI and page display
            $documentsStartTime = microtime(true);
            $pageIndexService = new \KatalysisProAi\PageIndexService();
            
            // IMPORTANT: Use much larger topK for vector search to avoid truncating relevant results
            // The FileVectorStore algorithm truncates during search, not after, so we need to search more
            $vectorSearchTopK = max($candidateDocumentsCount * 3, 50); // At least 50, or 3x candidate count
            

            
            // VECTOR SEARCH: Use standard document retrieval for clean, unbiased results
            $ragResults = $pageIndexService->getRelevantDocuments($query, $vectorSearchTopK);
            
            // DEBUG: Log how many results we got back
            $resultCount = is_array($ragResults) ? count($ragResults) : 0;
            error_log("VECTOR SEARCH DEBUG - Retrieved $resultCount documents from vector store");
            
            // Limit to the configured number of candidate documents
            if (!empty($ragResults) && count($ragResults) > $candidateDocumentsCount) {
                $ragResults = array_slice($ragResults, 0, $candidateDocumentsCount);
                error_log("VECTOR SEARCH DEBUG - Trimmed to $candidateDocumentsCount candidates as configured");
            }
            
            // DEBUG: Log the titles of the first few results
            if (!empty($ragResults)) {
                foreach (array_slice($ragResults, 0, 5) as $i => $result) {
                    $title = is_object($result) ? ($result->sourceName ?? 'No title') : 'Invalid result';
                    $score = is_object($result) ? ($result->score ?? 0) : 0;
                    error_log("VECTOR SEARCH DEBUG - Result " . ($i + 1) . ": '$title' (Score: $score)");
                }
            }
            
            $documentRetrievalTime = round((microtime(true) - $documentsStartTime) * 1000, 2);
            
            // Build context from retrieved documents
            $contextDocuments = '';
            if (!empty($ragResults)) {
                $contextDocuments = "\n\nRELEVANT CONTEXT DOCUMENTS:\n";
                foreach (array_slice($ragResults, 0, 10) as $index => $result) { // Limit to top 10 for AI context
                    if (is_object($result)) {
                        $title = $result->sourceName ?? 'Relevant Document';
                        $content = $result->content ?? '';
                        $score = $result->score ?? 0;
                        $contextDocuments .= "Document " . ($index + 1) . " (Score: " . round($score, 3) . "):\n";
                        $contextDocuments .= "Title: " . $title . "\n";
                        $contextDocuments .= "Content: " . substr(strip_tags($content), 0, 800) . "...\n\n";
                    }
                }
            }
            
            // Enhanced combined prompt with document context
            $enhancedCombinedPrompt = $combinedPrompt . $contextDocuments;
            
            // Direct AI call with pre-retrieved documents (bypassing RAG retrieval)
            $aiStartTime = microtime(true);
            $provider = $ragAgent->resolveProvider(); // Get the AI provider directly
            
            // CRITICAL: Set system prompt to ensure JSON format compliance AND multi-sentence responses
            $provider->systemPrompt("You are a legal AI assistant. You MUST respond with valid JSON in the exact format specified in the user's message. Do not deviate from the JSON structure requested. CRITICAL: Each response section MUST contain exactly 2 complete sentences - never just 1 sentence. Write 2 full sentences for each section to provide comprehensive information.");
            
            $aiResponse = $provider->chat([new \NeuronAI\Chat\Messages\UserMessage($enhancedCombinedPrompt)]);
            $combinedContent = $aiResponse->getContent();
            $combinedTime = round((microtime(true) - $aiStartTime) * 1000, 2);
            
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
                

                
                // Extract selected actions from JSON (new optimized method)
                $selectedActionsFromJson = isset($jsonData['selected_actions']) ? $jsonData['selected_actions'] : [];
                
                // Clean any [ACTIONS:] tags from the response (fallback method)
                $aiResponse = preg_replace('/\[ACTIONS:[0-9,\s]+\]/', '', $aiResponse);
                $aiResponse = trim($aiResponse);
                

                
                // Debug section header analysis
                if (preg_match_all('/<h[1-6]>([^<]+)<\/h[1-6]>/', $aiResponse, $matches)) {
                    foreach ($matches[1] as $index => $header) {
                    }
                } else {
                }
                
                // Check for text-based section headers
                if (preg_match_all('/^([A-Z][A-Z\s]+):/m', $aiResponse, $textMatches)) {
                } else {
                }
                
                // Use AI-provided specialism_id or map from service_area
                $specialismId = $intent['specialism_id'] ?? null;
                
                // If no specialism_id but we have a service_area, try to map it
                if (empty($specialismId) && !empty($intent['service_area'])) {
                    $specialismId = $this->mapServiceAreaToSpecialismId($intent['service_area']);

                }
                
                // Enhance intent with additional fields
                if (!isset($intent['specialism_id'])) {
                    $intent['specialism_id'] = $specialismId;
                }
                

                $intent['complexity'] = 'moderate';
                $intent['suggested_specialist_count'] = 3;
                $intent['suggested_office_focus'] = 'nearest';
                $intent['review_type_needed'] = 'general';
                

                
                
            } else {
                // Fallback: treat entire response as AI response and create basic intent
                $aiResponse = $combinedContent;
                // No selected actions from JSON since it's not JSON format
                $selectedActionsFromJson = [];
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
            
            // OPTIMIZATION: Reuse documents already retrieved above (no duplicate retrieval)
            $ragStartTime = microtime(true);
            $vectorRetrievalTime = $documentRetrievalTime; // Already retrieved above
            $processingTime = 0;
            
            try {
                // OPTIMIZATION: Skip vector retrieval - documents already retrieved above
                // Using ragResults from the first retrieval (line ~707)
                
                // PHASE 2B: Document processing only (no retrieval)
                $processingStartTime = microtime(true);
                $ragProcessResult = $this->processRagDocuments($ragResults, $query, $intent);
                $ragResults = $ragProcessResult['documents'] ?? $ragProcessResult; // Handle both array and direct return
                $ragDebugInfo = $ragProcessResult['debug'] ?? [];
                $processingTime = round((microtime(true) - $processingStartTime) * 1000, 2);
                
            } catch (\Exception $docError) {
                // Fallback to ragAgent method with original query
                try {
                    $vectorStartTime = microtime(true);
                    $ragResults = $ragAgent->retrieveDocuments(new UserMessage($query));
                    $vectorRetrievalTime = round((microtime(true) - $vectorStartTime) * 1000, 2);
                    
                    $processingStartTime = microtime(true);
                    $ragProcessResult = $this->processRagDocuments($ragResults, $query, $intent);
                    $ragResults = $ragProcessResult['documents'] ?? $ragProcessResult;
                    $ragDebugInfo = $ragProcessResult['debug'] ?? [];
                    $processingTime = round((microtime(true) - $processingStartTime) * 1000, 2);
                } catch (\Exception $fallbackError) {
                }
            }
            $ragTime = round((microtime(true) - $ragStartTime) * 1000, 2);
            
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
            
            // Get available actions - filter based on AI selection
            $actionsStartTime = microtime(true);
            // Parse selected action IDs - try JSON method first, then fallback to parsing
            $selectedActionIds = isset($selectedActionsFromJson) ? $selectedActionsFromJson : $this->parseSelectedActions($aiResponse);
            

            
            if (!empty($selectedActionIds)) {
                // Filter actions based on AI selection
                $actions = [];
                foreach ($selectedActionIds as $actionId) {
                    $action = $actionService->getActionById($actionId);
                    if ($action) {
                        $actions[] = [
                            'id' => $action->getId(),
                            'name' => $action->getName(),
                            'icon' => $action->getIcon(),
                            'triggerInstruction' => $action->getTriggerInstruction(),
                            'responseInstruction' => $action->getResponseInstruction()
                        ];
                    } else {
                    }
                }
            } else {
                // No actions selected by AI - use fallback logic
                $allActions = $actionService->getAllActions();
                
                // TEMPORARY FALLBACK: If no actions selected, show the most generic ones
                $actions = [];
                if (!empty($allActions)) {
                    foreach ($allActions as $action) {
                        // Show Contact Form (ID 4) and Book Meeting (ID 1) as fallback
                        if (in_array($action->getId(), [1, 4])) {
                            $actions[] = [
                                'id' => $action->getId(),
                                'name' => $action->getName(),
                                'icon' => $action->getIcon(),
                                'triggerInstruction' => $action->getTriggerInstruction(),
                                'responseInstruction' => $action->getResponseInstruction()
                            ];
                        }
                    }
                }
            }
            $actionsTime = round((microtime(true) - $actionsStartTime) * 1000, 2);
            
            $pages = $this->formatSearchResults($ragResults, $includePageLinks, $showSnippets);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            
            // Get configured sections for frontend parsing
            $configuredSections = [];
            $savedSections = Config::get('katalysis.search.response_sections', '');
            if (!empty($savedSections)) {
                $sectionsData = json_decode($savedSections, true);
                if (is_array($sectionsData)) {
                    foreach ($sectionsData as $section) {
                        if (!empty($section['enabled']) && !empty($section['name'])) {
                            $configuredSections[] = strtoupper($section['name']) . ':';
                        }
                    }
                }
            }
            
            $results = [
                'success' => true,
                'query' => $query,
                'response' => $aiResponse,
                'intent' => $intent,
                'pages' => $pages,
                'specialists' => $specialists,
                'reviews' => $reviews,
                'places' => $places,
                'actions' => $actions,
                'configured_sections' => $configuredSections,
                'processing_time' => $processingTime,
                'debug' => [
                    'intent_analysis' => $intent,
                    'processing_time_ms' => $processingTime,
                    'query_classification' => $this->getQueryClassification($intent),
                    'approach' => 'Combined intent+response (Fully Optimized - Single Document Retrieval)',
                    'performance_breakdown' => [
                        // CHRONOLOGICAL ORDER: Reordered to match actual execution flow
                        'document_retrieval_ms' => round($documentRetrievalTime, 2),
                        'document_processing_ms' => round($ragTime - $documentRetrievalTime, 2), // Processing time only (excluding retrieval)
                        'combined_ai_call_ms' => round($combinedTime, 2),
                        'supporting_content_ms' => round($specialistsTime + $reviewsTime + $placesTime + $actionsTime, 2),
                        'rag_detail' => [
                            'vector_retrieval_ms' => round($documentRetrievalTime, 2), // Actual retrieval time from first phase
                            'candidate_preparation_ms' => round($ragDebugInfo['processing_time_breakdown']['candidate_preparation_ms'] ?? 0, 2),
                            'document_selection_ms' => round($ragDebugInfo['processing_time_breakdown']['document_selection_ms'] ?? 0, 2),
                            'result_creation_ms' => round($ragDebugInfo['processing_time_breakdown']['result_creation_ms'] ?? 0, 2),
                            'ai_selection_used' => $ragDebugInfo['ai_selection_enabled'] ?? false,
                            'selection_method' => $ragDebugInfo['selection_method'] ?? 'unknown'
                        ],
                        'breakdown_detail' => [
                            'specialists_search_ms' => round($specialistsTime, 2),
                            'reviews_search_ms' => round($reviewsTime, 2),
                            'places_search_ms' => round($placesTime, 2),
                            'actions_retrieval_ms' => round($actionsTime, 2)
                        ],
                        'total_ms' => $processingTime,
                        'ai_percentage' => round(($combinedTime / $processingTime) * 100, 1),
                        'optimization_notes' => 'Single document retrieval used for both AI response and page display. ' . 
                                               ($ragDebugInfo['ai_selection_enabled'] ?? false ? 'AI document selection enabled.' : 'Fast algorithmic selection used.')
                    ],
                    'document_selection' => $ragDebugInfo
                ]
            ];
            
            // Log the search with comprehensive results
            $this->logSearch($query, $blockId, $aiResponse, $intent, $results);
            
            return $this->app->make(ResponseFactory::class)->json($results);
            
        } catch (\Exception $e) {
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
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'places' => [['error' => 'Places search failed: ' . $e->getMessage()]],
                'error' => 'Places search failed'
            ]);
        }
    }
    
    /**
     * Get action details for frontend (AJAX endpoint)
     */
    public function get_action_details($actionId = null)
    {
        try {
            if (!$actionId) {
                return $this->app->make(ResponseFactory::class)->json([
                    'success' => false,
                    'error' => 'Action ID required'
                ]);
            }

            $db = Database::get();
            $entityManager = $db->getEntityManager();
            $actionService = new ActionService($entityManager);
            $action = $actionService->getActionById((int)$actionId);

            if (!$action) {
                return $this->app->make(ResponseFactory::class)->json([
                    'success' => false,
                    'error' => 'Action not found'
                ]);
            }

            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'action' => [
                    'id' => $action->getId(),
                    'name' => $action->getName(),
                    'icon' => $action->getIcon(),
                    'triggerInstruction' => $action->getTriggerInstruction(),
                    'responseInstruction' => $action->getResponseInstruction(),
                    'actionType' => $action->getActionType(),
                    'formSteps' => $action->getFormSteps(),
                    'showImmediately' => $action->getShowImmediately()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => 'Failed to get action details'
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
            $candidateDocumentsCount = Config::get('katalysis.search.candidate_documents_count', 15);
            $includePageLinks = Config::get('katalysis.search.include_page_links', true);
            $showSnippets = Config::get('katalysis.search.show_snippets', true);
            
            // Get RAG results
            $ragAgent = new RagAgent();
            $ragAgent->setApp($this->app);
            
            $ragResults = [];
            try {
                // IMPROVED: Use PageIndexService directly for better control over document count
                $pageIndexService = new \KatalysisProAi\PageIndexService();
                
                // Use larger topK for vector search to ensure we don't miss relevant documents
                $vectorSearchTopK = max($candidateDocumentsCount * 3, 50);
                $ragResults = $pageIndexService->getRelevantDocuments($query, $vectorSearchTopK);
                
                // TEMPORARILY DISABLED: Show all documents for debugging
                /*if (!empty($ragResults) && count($ragResults) > $candidateDocumentsCount) {
                    $ragResults = array_slice($ragResults, 0, $candidateDocumentsCount);
                }*/
                
                // Process RAG documents if we got results
                if (!empty($ragResults)) {
                    $ragResults = $this->processRagDocuments($ragResults, $query, []);
                } else {
                    // Fallback to RAG agent if PageIndexService fails
                    $ragResults = $ragAgent->retrieveDocuments(new UserMessage($query));
                    
                    if (!empty($ragResults)) {
                        $ragResults = $this->processRagDocuments($ragResults, $query, []);
                    }
                }
            } catch (\Exception $docError) {
                $ragResults = [];
            }
            
            $pages = $this->formatSearchResults($ragResults, $includePageLinks, $showSnippets);
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'pages' => $pages,
                'processing_time' => $processingTime
            ]);
            
        } catch (\Exception $e) {
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
                    // Result from our enhancePageResults method
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
        $overallStartTime = microtime(true);
        
        try {
            // Phase 1: Candidate preparation and filtering
            $candidateStartTime = microtime(true);
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
                    $pageTypeBoostedScore = $this->applyPageTypeBoost($score, $pageType);
                    
                    // Apply query keyword matching boost to prioritize pages with matching keywords
                    $finalBoostedScore = $this->applyQueryKeywordBoost($pageTypeBoostedScore, $title, $query);

                    // Only include documents with reasonable relevance scores (after boost)
                    // TESTING: Reduced threshold to 0.1 to debug Work Accident page issue
                    if ($finalBoostedScore >= 0.1) {
                        $candidateDocs[] = [
                            'title' => $title,
                            'url' => $url,
                            'content' => $content,
                            'score' => $finalBoostedScore, // Use final boosted score
                            'original_score' => $score, // Keep original for debugging
                            'page_type_boosted_score' => $pageTypeBoostedScore, // Score after page type boost
                            'page_type' => $pageType,
                            'page_type_boost' => $pageTypeBoostedScore > $score ? round(($pageTypeBoostedScore - $score), 3) : 0,
                            'keyword_boost' => $finalBoostedScore > $pageTypeBoostedScore ? round(($finalBoostedScore - $pageTypeBoostedScore), 3) : 0,
                            'parent_child_boost' => 0, // Will be calculated later
                            'total_boost' => $finalBoostedScore > $score ? round(($finalBoostedScore - $score), 3) : 0
                        ];
                        $seenUrls[] = $url;
                    }
                }
            }

            // Apply parent-child relationship boosts before final selection
            if (!empty($candidateDocs)) {
                $candidateDocs = $this->applyParentChildBoosts($candidateDocs);
            }

            // FAST SELECTION: Use deterministic scoring instead of AI calls
            if (!empty($candidateDocs)) {
                // Sort by boosted score and ensure content type diversity
                usort($candidateDocs, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                
                // Log score boost summary
                $boostedDocs = array_filter($candidateDocs, function($doc) { return $doc['boost_applied'] > 0; });
                if (!empty($boostedDocs)) {
                    foreach ($boostedDocs as $doc) {
                    }
                }
                
                $candidateTime = round((microtime(true) - $candidateStartTime) * 1000, 2);
                
                // Phase 2: Document selection (AI or algorithmic)
                $selectionStartTime = microtime(true);
                $useAISelection = Config::get('katalysis.search.use_ai_document_selection', false); // Default to false for now
                $maxDocuments = Config::get('katalysis.search.max_results', 8); // Use unified max_results for both AI and algorithmic selection
                
                if ($useAISelection && count($candidateDocs) >= 3) {
                    // AI-POWERED SELECTION: Use AI to intelligently select documents
                    $selectionResult = $this->getAIDocumentSelection($candidateDocs, $query, $intent, $maxDocuments);
                    $selectedDocs = $selectionResult['selected'];
                    $enhancedCandidates = $selectionResult['candidates'];
                    $selectionMethod = $selectionResult['selection_method'] ?? 'AI-powered selection';
                    $selectionReasoning = $selectionResult['ai_reasoning'] ?? 'AI selection completed';
                } else {
                    // ALGORITHMIC SELECTION: Use fast algorithmic selection (original fallback method)
                    if ($useAISelection) {
                    } else {
                    }
                    $algorithmicMaxDocs = $maxDocuments; // Use same limit for both AI and algorithmic selection
                    $selectionResult = $this->getFastBalancedSelection($candidateDocs, $algorithmicMaxDocs, $intent);
                    $selectedDocs = $selectionResult['selected'];
                    $enhancedCandidates = $selectionResult['candidates'];
                    $selectionMethod = 'Fast algorithmic selection';
                    $selectionReasoning = 'Algorithmic scoring with content type diversity and specialism awareness';
                }
                
                $selectionTime = round((microtime(true) - $selectionStartTime) * 1000, 2);
                $totalProcessingTime = round((microtime(true) - $overallStartTime) * 1000, 2);
                
                
                // Phase 3: Create results from selected documents
                $resultCreationStartTime = microtime(true);
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
                    $processedResults = array_merge($processedResults, $supplementaryDocs);
                }
            }

            $resultCreationTime = round((microtime(true) - $resultCreationStartTime) * 1000, 2);
            $finalProcessingTime = round((microtime(true) - $overallStartTime) * 1000, 2);

            return [
                'documents' => $processedResults,
                'debug' => [
                    // Timing breakdown
                    'processing_time_breakdown' => [
                        'candidate_preparation_ms' => $candidateTime,
                        'document_selection_ms' => $selectionTime,
                        'result_creation_ms' => $resultCreationTime,
                        'total_processing_ms' => $finalProcessingTime
                    ],
                    // Selection details  
                    'total_candidate_docs' => count($enhancedCandidates ?? $candidateDocs),
                    'ai_selected_count' => count($processedResults),
                    'selection_method' => $selectionMethod ?? 'Fast scoring with parent page index lookup',
                    'selection_reasoning' => $selectionReasoning ?? 'Standard algorithmic selection',
                    'ai_selection_enabled' => $useAISelection ?? false,
                    'score_threshold' => 0.1,
                    'max_candidates_processed' => count($enhancedCandidates ?? $candidateDocs),
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
     * AI-powered document selection - uses AI to intelligently select most relevant documents
     */
    private function getAIDocumentSelection($candidateDocs, $query, $intent = [], $maxResults = 6)
    {
        $startTime = microtime(true);
        
        try {
            if (empty($candidateDocs)) {
                return ['selected' => [], 'candidates' => []];
            }
            
            // Prepare candidate documents for AI evaluation
            $candidateList = [];
            foreach ($candidateDocs as $index => $doc) {
                $candidateList[] = [
                    'index' => $index,
                    'title' => $doc['title'],
                    'content_snippet' => substr($doc['content'], 0, 200) . '...',
                    'page_type' => $doc['page_type'] ?: 'general',
                    'score' => $doc['score'],
                    'url' => $doc['url']
                ];
            }
            
            // Build AI selection prompt
            $selectionRules = $this->getDefaultLinkSelectionRules();
            $candidatesJson = json_encode($candidateList, JSON_PRETTY_PRINT);
            
            $aiPrompt = "USER QUERY: \"$query\"\n\n";
            $aiPrompt .= "CANDIDATE DOCUMENTS:\n$candidatesJson\n\n";
            $aiPrompt .= "$selectionRules\n\n";
            $aiPrompt .= "TASK: Select the $maxResults most relevant documents from the candidates above. ";
            $aiPrompt .= "Return a JSON array with the indices of selected documents in order of importance (most important first).\n\n";
            $aiPrompt .= "Response format: {\"selected_indices\": [0, 3, 7, 2, 5, 1], \"reasoning\": \"Brief explanation of selection\"}\n";
            $aiPrompt .= "CRITICAL: Only return valid JSON with the exact format shown above.";
            
            // Use a simple AI provider for document selection (not RAG)
            $provider = new \NeuronAI\Providers\OpenAI\OpenAI(
                \Concrete\Core\Support\Facade\Config::get('katalysis.ai.open_ai_key'),
                \Concrete\Core\Support\Facade\Config::get('katalysis.ai.open_ai_model')
            );
            
            $aiResponse = $provider->chat([
                new \NeuronAI\Chat\Messages\UserMessage("SYSTEM: You are an expert document relevance analyzer. Select the most relevant documents for the user's query.\n\nUSER REQUEST:\n" . $aiPrompt)
            ]);
            
            $responseContent = $aiResponse->getContent();
            
            // Parse AI response
            $selectionData = json_decode($responseContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON response from AI: " . json_last_error_msg());
            }
            
            if (!isset($selectionData['selected_indices']) || !is_array($selectionData['selected_indices'])) {
                throw new \Exception("AI response missing selected_indices array");
            }
            
            $selectedIndices = $selectionData['selected_indices'];
            $reasoning = $selectionData['reasoning'] ?? 'AI selection without specific reasoning';
            
            // Build selected documents
            $selectedDocs = [];
            foreach ($selectedIndices as $order => $index) {
                if (isset($candidateDocs[$index])) {
                    $doc = $candidateDocs[$index];
                    $doc['ai_order'] = $order + 1;
                    $doc['selection_reason'] = "AI selected (#" . ($order + 1) . "): " . $reasoning;
                    $selectedDocs[] = $doc;
                    
                    if (count($selectedDocs) >= $maxResults) {
                        break; // Ensure we don't exceed maxResults
                    }
                }
            }
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'selected' => $selectedDocs,
                'candidates' => $candidateDocs,
                'ai_reasoning' => $reasoning,
                'processing_time_ms' => $processingTime,
                'selection_method' => 'AI-powered intelligent selection'
            ];
            
        } catch (\Exception $e) {
            
            // Fallback to algorithmic selection
            return $this->getFastBalancedSelection($candidateDocs, min($maxResults, 3), null);
        }
    }
    
    /**
     * Fast balanced selection without AI calls - prioritize by score and ensure content diversity
     */
    private function getFastBalancedSelection($candidateDocs, $maxResults = 2, $intent = null)
    {
        // PERFORMANCE OPTIMIZATION: Filter out content types handled by separate backend queries
        // NOTE: Removed 'calculator_entry' to allow calculators to appear in search results
        $excludedTypes = ['article', 'case_study', 'guide', 'blog_entry'];
        $filteredDocs = [];
        
        foreach ($candidateDocs as $doc) {
            $pageType = $doc['page_type'] ?: 'unknown';
            if (!in_array($pageType, $excludedTypes)) {
                $filteredDocs[] = $doc;
            }
        }
        
        
        // Use filtered documents for selection
        $candidateDocs = $filteredDocs;
        
        // SPECIALISM-AWARE BOOSTING: If we have specialism context, boost matching pages
        if ($intent && isset($intent['specialism_id'])) {
            $specialismId = $intent['specialism_id'];
            $serviceArea = $intent['service_area'] ?? '';
            
            foreach ($candidateDocs as $index => &$doc) {
                $title = strtolower($doc['title']);
                
                if ($serviceArea) {
                    // General service area matching for all specialisms
                    $serviceAreaLower = strtolower($serviceArea);
                    
                    // Boost pages that match the detected service area by title
                    if ($doc['page_type'] === 'legal_service_index' && strpos($title, $serviceAreaLower) !== false) {
                        $doc['score'] += 0.5; // Major boost for main service area page
                        $doc['specialism_boost'] = true;
                    }
                }
            }
            
            // Re-sort candidates by score after boosting
            usort($candidateDocs, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
        }
        
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
            $existingIndexPages = $this->findLegalServiceIndexPages($candidateDocs);
            if (!empty($existingIndexPages)) {
                // Add found index pages to candidate documents
                $candidateDocs = array_merge($existingIndexPages, $candidateDocs);
                $availableTypes['legal_service_index'] = count($existingIndexPages);
            } else {
            }
        } else {
            if (!isset($availableTypes['legal_service'])) {
            }
            if (isset($availableTypes['legal_service_index'])) {
            }
        }
        
        // Define priority content type requirements - HIGHLY OPTIMIZED FOR SPEED
        // Focus ONLY on the most essential content: 1 index + 1 service page
        $requiredTypes = [
            'legal_service_index' => 1,  // At least 1 index page (ESSENTIAL for navigation)
            'legal_service' => 1,        // At least 1 service page (CORE CONTENT)
            // All other content types excluded for maximum AI performance
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
            'candidates' => $candidateDocs  // Candidates including parent lookup
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
            
            $legalServiceCount = 0;
            
            // Extract legal_service pages and find their parent legal_service_index pages
            foreach ($candidateDocs as $doc) {
                if ($doc['page_type'] === 'legal_service') {
                    $legalServiceCount++;
                    
                    // Extract page ID from URL
                    $pageId = $this->extractPageIdFromUrl($doc['url']);
                    
                    if ($pageId) {
                        // Get the page object
                        $page = \Concrete\Core\Page\Page::getByID($pageId);
                        
                        if ($page && !$page->isError()) {
                            // Get parent page using proper CMS method
                            $parentPageId = $page->getCollectionParentID();
                            
                            if ($parentPageId && $parentPageId > 1 && !in_array($parentPageId, $foundIndexPageIds)) {
                                // Get parent page object
                                $parentPage = \Concrete\Core\Page\Page::getByID($parentPageId);
                                
                                if ($parentPage && !$parentPage->isError()) {
                                    // Check if parent page is a legal_service_index page type
                                    $pageType = $parentPage->getPageTypeObject();
                                    $parentPageType = $pageType ? $pageType->getPageTypeHandle() : null;
                                    
                                    
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
                                        
                                    }
                                } else {
                                }
                            } else {
                                if (!$parentPageId) {
                                } elseif ($parentPageId <= 1) {
                                } elseif (in_array($parentPageId, $foundIndexPageIds)) {
                                }
                            }
                        } else {
                        }
                    }
                }
            }
            
            
            // Sort by score (highest first)
            usort($indexPages, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
        } catch (\Exception $e) {
        }
        
        return $indexPages;
    }
    
    /**
     * Extract page ID from a URL using Concrete CMS Page methods
     */
    private function extractPageIdFromUrl($url)
    {
        try {
            
            // Remove domain and get path
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                return null;
            }
            
            
            // Use Concrete CMS Page::getByPath() method
            $page = \Concrete\Core\Page\Page::getByPath($path);
            
            if ($page && !$page->isError()) {
                $pageId = $page->getCollectionID();
                return (int)$pageId;
            }
            
            
            // If not found by full path, try segments
            $segments = explode('/', trim($path, '/'));
            if (!empty($segments)) {
                $lastSegment = end($segments);
                
                // Try getByHandle for the last segment
                $page = \Concrete\Core\Page\Page::getByHandle($lastSegment);
                if ($page && !$page->isError()) {
                    $pageId = $page->getCollectionID();
                    return (int)$pageId;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            return null;
        }
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
            
            $db = Database::get();
            $supplementaryDocs = [];
            
            // Get Specialisms attribute key
            $ak = CollectionKey::getByHandle('specialisms');
            if (!is_object($ak)) {
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
                    'content' => $article['content'] ?? $article['snippet'], // Prefer full content over snippet
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
                    'content' => $caseStudy['content'] ?? $caseStudy['snippet'], // Prefer full content over snippet
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
            foreach ($top10ForDebug as $i => $item) {
            }
            
            // Convert selected items to supplementary docs format for main display
            foreach ($topArticles as $article) {
                $supplementaryDocs[] = [
                    'title' => $article['title'],
                    'url' => $article['url'],
                    'content' => $article['content'], // Use full content instead of snippet
                    'snippet' => $article['snippet'], // Keep snippet for compatibility
                    'score' => $article['relevance_score'],
                    'type' => 'article', // Use 'type' field for consistency with other results
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
                    'content' => $caseStudy['content'], // Use full content instead of snippet
                    'snippet' => $caseStudy['snippet'], // Keep snippet for compatibility
                    'score' => $caseStudy['relevance_score'],
                    'type' => 'case_study', // Use 'type' field for consistency with other results
                    'page_type' => 'case_study',
                    'content_source' => 'specialism_supplement',
                    'badge' => 'Case Study',
                    'ai_selected' => false,
                    'selection_reason' => $caseStudy['selection_reason']
                ];
            }
            
            
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
            return [];
        }
    }
    
    /**
     * Get and prioritize content (articles/case studies) by specialism with query-based relevance scoring
     */
    private function getAndPrioritizeContentBySpecialism($pageType, $specialismId, $query, $maxResults, $attributeKey)
    {
        try {
            
            // Create PageList to get content by specialism
            $pageList = new PageList();
            $pageTypeObj = PageType::getByHandle($pageType);
            if ($pageTypeObj) {
                $pageList->filterByPageTypeID($pageTypeObj->getPageTypeID());
            }
            $attributeKey->getController()->filterByAttribute($pageList, $specialismId);
            $pageList->sortByPublicDateDescending();
            $pageList->setItemsPerPage($maxResults);
            $pages = $pageList->getResults();
            
            
            $scoredContent = [];
            $queryWords = $this->extractQueryWords($query);
            
            foreach ($pages as $page) {
                $title = $page->getCollectionName();
                $description = $page->getCollectionDescription() ?: '';
                $url = $page->getCollectionPath();
                
                // Get page type information
                $pageTypeObj = $page->getPageTypeObject();
                $pageTypeHandle = $pageTypeObj ? $pageTypeObj->getPageTypeHandle() : '';
                
                // Extract more comprehensive content for better descriptions
                $fullContent = $description;
                
                // Try to get meta description if collection description is empty
                if (empty($fullContent)) {
                    $metaDescription = $page->getAttribute('meta_description');
                    if (!empty($metaDescription)) {
                        $fullContent = $metaDescription;
                    }
                }
                
                // As a last resort, try to get content from page blocks (if still empty)
                if (empty($fullContent)) {
                    try {
                        // Get the main area content
                        $mainArea = $page->getArea('Main');
                        if ($mainArea && method_exists($mainArea, 'getAreaDisplayName')) {
                            $blocks = $mainArea->getAreaBlocksArray($page);
                            $textContent = '';
                            foreach ($blocks as $block) {
                                if ($block->getBlockTypeHandle() === 'content') {
                                    $controller = $block->getController();
                                    if (method_exists($controller, 'getContent')) {
                                        $textContent .= strip_tags($controller->getContent()) . ' ';
                                    }
                                }
                            }
                            if (!empty($textContent)) {
                                $fullContent = trim($textContent);
                            }
                        }
                    } catch (\Exception $blockError) {
                    }
                }
                
                // Calculate query-based relevance score
                $relevanceScore = $this->calculateContentRelevanceScore($title, $fullContent, $queryWords, $page->getCollectionDatePublic());
                
                $scoredContent[] = [
                    'title' => $title,
                    'url' => $url,
                    'content' => $fullContent, // Full content for frontend use
                    'snippet' => $this->truncateContent($fullContent, 150), // Truncated for compatibility
                    'page_type' => $pageType, // Use the passed page type
                    'page_type_handle' => $pageTypeHandle, // Store the actual CMS page type handle
                    'relevance_score' => $relevanceScore,
                    'publication_date' => $page->getCollectionDatePublic(),
                    'selection_reason' => $this->getSelectionReason($relevanceScore, $queryWords, $title, $fullContent)
                ];
                
            }
            
            // Sort by relevance score (highest first)
            usort($scoredContent, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });
            
            
            return $scoredContent;
            
        } catch (\Exception $e) {
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
            } elseif (strpos($titleLower, $wordLower) !== false) {
                $score += 0.15; // Partial title match
                $partialMatches++;
            }
            
            // Description matches (medium weight)
            if (preg_match('/\b' . preg_quote($wordLower, '/') . '\b/', $descriptionLower)) {
                $score += 0.12;
                $exactMatches++;
            } elseif (strpos($descriptionLower, $wordLower) !== false) {
                $score += 0.08; // Partial description match
                $partialMatches++;
            }
        }
        
        // Exact phrase matching (higher bonus for multi-word queries)
        $queryPhrase = strtolower(implode(' ', $queryWords));
        if (strlen($queryPhrase) > 5) {
            if (strpos($allContent, $queryPhrase) !== false) {
                $score += 0.25; // Increased bonus for exact phrase match
            }
        }
        
        // Multiple exact match bonus (progressive scoring)
        if ($exactMatches > 1) {
            $score += ($exactMatches - 1) * 0.1;
        }
        
        // Coverage bonus - reward content that matches more of the query terms
        $totalQueryWords = count(array_filter($queryWords, function($word) { return strlen($word) >= 3; }));
        if ($totalQueryWords > 0) {
            $coverage = ($exactMatches + ($partialMatches * 0.5)) / $totalQueryWords;
            $coverageBonus = $coverage * 0.15; // Up to 0.15 bonus for full coverage
            $score += $coverageBonus;
        }
        
        // Recency bonus (newer content gets slight boost)
        if ($publicationDate) {
            $timestamp = is_object($publicationDate) ? $publicationDate->getTimestamp() : strtotime($publicationDate);
            if ($timestamp) {
                $monthsOld = (time() - $timestamp) / (30 * 24 * 60 * 60);
                if ($monthsOld < 6) {
                    $score += 0.05;
                } elseif ($monthsOld < 12) {
                    $score += 0.03;
                }
            }
        }
        
        // Title length penalty (favor concise, focused titles)
        if (strlen($title) > 80) {
            $score -= 0.02;
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

    /**
     * Get location keywords dynamically from PlaceList instead of hard-coded arrays
     */
    private function getLocationKeywords(): array
    {
        try {
            // Get all active places using PlaceList
            $placeList = new PlaceList();
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
            
            
        } catch (\Exception $e) {
            
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
            }
        }
    }

    public function getPageTitle()
    {
        return t('Search Settings');
    }
    
    /**
     * Get specialisms from the Specialisms topic tree using Concrete CMS TopicTree methods
     * Returns only the actual specialisms, not all TreeNodes
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
                // Only include actual specialisms (nodes that could be leaf nodes or have meaningful names)
                foreach ($nodes as $node) {
                    $nodeName = $node['treeNodeName'];
                    
                    // Skip root node name itself
                    if ($nodeName === 'Specialisms') {
                        continue;
                    }
                    
                    $specialisms[] = [
                        'treeNodeID' => $node['treeNodeID'],
                        'treeNodeName' => $nodeName,
                        'treeNodeParentID' => $node['treeNodeParentID'],
                        'parentName' => null // We'll determine parent relationships if needed
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
            // This ensures the feature continues to work even if TopicTree has issues
            return [
                ['treeNodeID' => 1, 'treeNodeName' => 'Personal Injury', 'treeNodeParentID' => null],
                ['treeNodeID' => 2, 'treeNodeName' => 'Medical Negligence', 'treeNodeParentID' => null],
                ['treeNodeID' => 3, 'treeNodeName' => 'Employment Law', 'treeNodeParentID' => null],
                ['treeNodeID' => 4, 'treeNodeName' => 'Family Law', 'treeNodeParentID' => null],
                ['treeNodeID' => 5, 'treeNodeName' => 'Conveyancing', 'treeNodeParentID' => null],
                ['treeNodeID' => 6, 'treeNodeName' => 'Wills & Probate', 'treeNodeParentID' => null],
                ['treeNodeID' => 7, 'treeNodeName' => 'Commercial Law', 'treeNodeParentID' => null],
                ['treeNodeID' => 8, 'treeNodeName' => 'Litigation', 'treeNodeParentID' => null]
            ];
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
                $personResults = $this->getSpecialistsByName($intent['person_name'], $maxSpecialists);
                if (!empty($personResults)) {
                    return $personResults;
                }
                // If no exact name match, fall through to other search methods
            }
            
            // PRIORITY 1: Always try specialism-based search first if we have service area or specialism_id
            if (!empty($intent['service_area'] ?? null) || !empty($intent['specialism_id'] ?? null)) {
                $specialismResults = $this->getSpecialistsByService($intent, $maxSpecialists);
                if (!empty($specialismResults)) {
                    return $specialismResults;
                }
            }
            
            // PRIORITY 2: For location queries, prioritize local specialists (only if no specialism match)
            if (($intent['intent_type'] ?? null) === 'location' && ($intent['location_mentioned'] ?? null)) {
                return $this->getSpecialistsByLocation($intent['location_mentioned'], $maxSpecialists);
            }
            
            // PRIORITY 3: Location-only search (only if no service area/specialism was identified)
            if (empty($intent['service_area'] ?? null) && empty($intent['specialism_id'] ?? null)) {
                // Check if query contains location words using dynamic PlaceList data
                $locationData = $this->getLocationKeywords();
                $locationKeywords = $locationData['locationKeywords'];
                $placeNames = $locationData['placeNames'];
                $queryLower = strtolower($query);
                
                foreach ($locationKeywords as $keyword) {
                    if (strpos($queryLower, $keyword) !== false) {
                        
                        // If it's a specific place name, search by that location
                        foreach ($placeNames as $placeName) {
                            if (strpos($queryLower, $placeName) !== false) {
                                return $this->getSpecialistsByLocation($placeName, $maxSpecialists);
                            }
                        }
                        
                        // If location mentioned in intent, use that
                        if (!empty($intent['location_mentioned'])) {
                            return $this->getSpecialistsByLocation($intent['location_mentioned'], $maxSpecialists);
                        }
                        
                        break; // Found location keyword but no specific place
                    }
                }
            }
            
            // PRIORITY 4: For urgent situations, get most experienced specialists
            if (($intent['urgency'] ?? null) === 'high') {
                return $this->getSeniorSpecialists($maxSpecialists);
            }
            
            // FINAL FALLBACK: No specialist search strategy could be applied for this query
            return [['error' => 'No matching specialists found for the specified criteria']];
            
        } catch (\Exception $e) {
            return [['error' => 'Specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get targeted reviews based on intent using specialism topics
     */
    private function getTargetedReviews($query, $intent): array
    {
        try {
            // ENHANCED: Use specialism_id for service AND help queries (much faster)
            $intentType = $intent['intent_type'] ?? null;
            $specialismId = $intent['specialism_id'] ?? null;
            
            if (in_array($intentType, ['service', 'help', 'information']) && !empty($specialismId)) {
                $serviceArea = $intent['service_area'] ?? '';
                return $this->getReviewsBySpecialismId($specialismId, $serviceArea);
            }
            
            // For other queries without specialism_id, return featured reviews as fallback
            return $this->getFeaturedReviews();
            
        } catch (\Exception $e) {
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get targeted places based on intent
     */
    private function getTargetedPlaces($query, $intent): array
    {
        try {
            
            // For explicit location queries, prioritize specific location
            if (($intent['intent_type'] ?? null) === 'location' && ($intent['location_mentioned'] ?? null)) {
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // For service queries that explicitly mention a location
            if (($intent['intent_type'] ?? null) === 'service' && ($intent['location_mentioned'] ?? null)) {
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // For urgent situations that mention a location, get nearest offices
            if (($intent['urgency'] ?? null) === 'high' && ($intent['location_mentioned'] ?? null)) {
                return $this->getPlacesByLocation($intent['location_mentioned']);
            }
            
            // ENHANCED: Check if query contains location words using dynamic PlaceList data
            $locationData = $this->getLocationKeywords();
            $locationKeywords = $locationData['locationKeywords'];
            $placeNames = $locationData['placeNames'];
            $queryLower = strtolower($query);
            
            foreach ($locationKeywords as $keyword) {
                if (strpos($queryLower, $keyword) !== false) {
                    
                    // If it's a specific place name, search by that location
                    foreach ($placeNames as $placeName) {
                        if (strpos($queryLower, $placeName) !== false) {
                            return $this->getPlacesByLocation($placeName);
                        }
                    }
                    
                    // General location query - show main offices
                    return $this->getNearestOffices();
                }
            }
            
            // FALLBACK: For location intent type without specific location mentioned
            if (($intent['intent_type'] ?? null) === 'location') {
                return $this->getNearestOffices();
            }
            
            // Skip places if no location context found
            return [];
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get specialists filtered by service area using proper Katalysis Pro PeopleList class
     */
    private function getSpecialistsByService($intentOrServiceArea, $maxResults = 3): array
    {
        try {
            
            // Handle both intent object and legacy string input
            if (is_array($intentOrServiceArea)) {
                $serviceArea = $intentOrServiceArea['service_area'] ?? 'unknown';
                $specialismId = $intentOrServiceArea['specialism_id'] ?? null;
            } else {
                // Legacy string input
                $serviceArea = $intentOrServiceArea;
                $specialismId = null;
            }
            
            
            // Use specialism_id from intent if available (much faster)
            if ($specialismId) {
                
                // Direct specialism-based search using proper filterBySpecialisms method
                $peopleList = new PeopleList();
                $peopleList->filterByActive();
                $peopleList->filterBySpecialisms([$specialismId]);
                $peopleList->limitResults($maxResults * 2); // Get more results for location sorting
                
                $results = $peopleList->getResults();
                
                if (!empty($results)) {
                    // Apply location-based prioritization if location is mentioned in intent
                    $locationMentioned = $intentOrServiceArea['location_mentioned'] ?? null;
                    if ($locationMentioned && count($results) > 1) {
                        $results = $this->prioritizeSpecialistsByLocation($results, $locationMentioned, $maxResults);
                    } else {
                        // Just limit to the requested number of results
                        $results = array_slice($results, 0, $maxResults);
                    }
                    return $this->formatPeopleListResults($results);
                } else {
                    
                    // SMART FALLBACK: Try parent topic if child topic has no specialists
                    $parentSpecialismId = $this->getParentSpecialismId($specialismId);
                    if ($parentSpecialismId && $parentSpecialismId !== $specialismId) {
                        
                        $parentPeopleList = new PeopleList();
                        $parentPeopleList->filterByActive();
                        $parentPeopleList->filterBySpecialisms([$parentSpecialismId]);
                        $parentPeopleList->limitResults($maxResults * 2); // Get more for location sorting
                        
                        $parentResults = $parentPeopleList->getResults();
                        
                        if (!empty($parentResults)) {
                            // Apply location-based prioritization for parent results too
                            $locationMentioned = $intentOrServiceArea['location_mentioned'] ?? null;
                            if ($locationMentioned && count($parentResults) > 1) {
                                $parentResults = $this->prioritizeSpecialistsByLocation($parentResults, $locationMentioned, $maxResults);
                            } else {
                                $parentResults = array_slice($parentResults, 0, $maxResults);
                            }
                            return $this->formatPeopleListResults($parentResults);
                        }
                    }
                    

                    $db = Database::get();
                    $specialismAssociations = $db->GetOne("SELECT COUNT(*) FROM KatalysisPeopleSpecialism WHERE specialismID = ?", [$specialismId]);
                    
                    // FAST FAIL: No specialists in child or parent topic
                    return [];
                }
            }
            
            // FAST FAIL: No specialism ID means no results  
            return [];
            
        } catch (\Exception $e) {
            return [['error' => 'Specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get specialists by name for person-specific searches
     */
    private function getSpecialistsByName($personName, $maxResults = 3): array
    {
        try {
            
            // Use PeopleList to get all active people, then filter by name in PHP
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->limitResults(20); // Get more to filter from
            
            $allPeople = $peopleList->getResults();
            
            // Filter people by name in PHP with flexible matching
            $cleanName = trim(strtolower($personName));
            $nameWords = explode(' ', $cleanName);
            $matchingPeople = [];
            
            foreach ($allPeople as $person) {
                $personNameLower = strtolower($person->name);
                $jobTitleNameLower = strtolower($person->jobTitle . ' ' . $person->name);
                
                // Check various matching conditions
                $exactMatch = $personNameLower === $cleanName;
                $containsFullName = strpos($personNameLower, $cleanName) !== false;
                $containsJobTitle = strpos($jobTitleNameLower, $cleanName) !== false;
                
                // Check if all name words are present
                $allWordsMatch = true;
                foreach ($nameWords as $word) {
                    $word = trim($word);
                    if (strlen($word) > 2 && strpos($personNameLower, $word) === false) {
                        $allWordsMatch = false;
                        break;
                    }
                }
                
                if ($exactMatch || $containsFullName || $containsJobTitle || $allWordsMatch) {
                    // Add priority score for sorting
                    $priority = 0;
                    if ($exactMatch) $priority = 1;
                    elseif ($containsFullName) $priority = 2;
                    elseif ($allWordsMatch) $priority = 3;
                    else $priority = 4;
                    
                    $matchingPeople[] = [
                        'person' => $person,
                        'priority' => $priority,
                        'featured' => $person->featured ?? 0
                    ];
                }
            }
            
            if (empty($matchingPeople)) {
                return [];
            }
            
            // Sort by priority, then featured, then name length
            usort($matchingPeople, function($a, $b) {
                if ($a['priority'] !== $b['priority']) {
                    return $a['priority'] - $b['priority'];
                }
                if ($a['featured'] !== $b['featured']) {
                    return $b['featured'] - $a['featured'];
                }
                return strlen($a['person']->name) - strlen($b['person']->name);
            });
            
            // Extract just the Person objects and limit results
            $specialists = array_slice(array_column($matchingPeople, 'person'), 0, $maxResults);
            
            return $this->formatPeopleListResults($specialists);
            
        } catch (\Exception $e) {
            return [['error' => 'Person search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get specialists by location preference
     */
    private function getSpecialistsByLocation($location, $maxResults = 3): array
    {
        try {
            
            // First, find places that match the location
            $placeList = new PlaceList();
            $placeList->filterByActive();
            $placeList->limitResults(10); // Get more to filter from
            
            $allPlaces = $placeList->getResults();
            
            // Filter places by location in PHP (same logic as getPlacesByLocation)
            $matchingPlaces = [];
            $locationLower = strtolower($location);
            
            foreach ($allPlaces as $place) {
                $nameMatch = stripos($place->name, $location) !== false;
                $townMatch = stripos($place->town, $location) !== false;
                $countyMatch = stripos($place->county, $location) !== false;
                
                if ($nameMatch || $townMatch || $countyMatch) {
                    $matchingPlaces[] = $place->sID;
                }
            }
            
            
            if (empty($matchingPlaces)) {
                return $this->getSeniorSpecialists($maxResults);
            }
            
            // Now get people from those places using PeopleList
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->filterByPlaces($matchingPlaces);
            $peopleList->limitResults($maxResults);
            
            $specialists = $peopleList->getResults();
            
            if (empty($specialists)) {
                return $this->getSeniorSpecialists($maxResults);
            }
            
            // Use formatPeopleListResults for rich data including images and profile links
            return $this->formatPeopleListResults($specialists);
            
        } catch (\Exception $e) {
            return $this->getSeniorSpecialists($maxResults);
        }
    }
    
    /**
     * Get senior/experienced specialists for urgent situations
     */
    private function getSeniorSpecialists($maxResults = 1): array
    {
        try {
            // Use PeopleList to get all active people, then sort by seniority in PHP
            $peopleList = new PeopleList();
            $peopleList->filterByActive();
            $peopleList->limitResults(20); // Get more to sort from
            
            $allPeople = $peopleList->getResults();
            
            // Sort people by seniority in PHP
            $seniorityScores = [];
            foreach ($allPeople as $person) {
                $jobTitleLower = strtolower($person->jobTitle);
                $score = 0;
                
                // Score based on job title seniority
                if (strpos($jobTitleLower, 'director') !== false) $score = 3;
                elseif (strpos($jobTitleLower, 'head') !== false) $score = 2;
                elseif (strpos($jobTitleLower, 'senior') !== false) $score = 1;
                
                $seniorityScores[] = [
                    'person' => $person,
                    'score' => $score,
                    'featured' => $person->featured ?? 0
                ];
            }
            
            // Sort by featured status first, then seniority score
            usort($seniorityScores, function($a, $b) {
                if ($a['featured'] !== $b['featured']) {
                    return $b['featured'] - $a['featured']; // Featured first
                }
                return $b['score'] - $a['score']; // Higher seniority score first
            });
            
            // Extract just the Person objects and limit results
            $specialists = array_slice(array_column($seniorityScores, 'person'), 0, $maxResults);
            
            return $this->formatPeopleListResults($specialists);
            
        } catch (\Exception $e) {
            return [['error' => 'Senior specialist search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Prioritize specialists by location match when multiple specialists exist for the same specialism
     */
    private function prioritizeSpecialistsByLocation($specialists, $locationMentioned, $maxResults)
    {
        if (empty($specialists) || empty($locationMentioned)) {
            return array_slice($specialists, 0, $maxResults);
        }
        
        $locationScored = [];
        $locationLower = strtolower($locationMentioned);
        
        foreach ($specialists as $person) {
            $score = 0;
            
            // Check if specialist's office locations match the mentioned location
            $offices = $person->officelocations ?? [];
            if (!empty($offices)) {
                foreach ($offices as $office) {
                    $officeName = strtolower($office->oName ?? '');
                    if (strpos($officeName, $locationLower) !== false || 
                        strpos($locationLower, $officeName) !== false) {
                        $score += 10; // High priority for office location match
                    }
                }
            }
            
            // Also check if person's name or bio contains location references
            $personName = strtolower($person->pName ?? '');
            $personBio = strtolower($person->biographicalInformation ?? '');
            if (strpos($personBio, $locationLower) !== false) {
                $score += 5; // Medium priority for bio mention
            }
            
            $locationScored[] = [
                'person' => $person,
                'location_score' => $score
            ];
        }
        
        // Sort by location score (descending), then preserve original order
        usort($locationScored, function($a, $b) {
            return $b['location_score'] - $a['location_score'];
        });
        
        // Extract the sorted persons and limit results
        $sortedSpecialists = array_column($locationScored, 'person');
        return array_slice($sortedSpecialists, 0, $maxResults);
    }
    
    /**
     * Format specialist results consistently
     */
    /**
     * Format PeopleList results for API response
     */
    private function formatPeopleListResults($results): array
    {
        $formattedResults = [];
        $seenPersonIds = []; // Track person IDs to prevent duplicates
        
        foreach ($results as $person) {
            // Skip if we've already processed this person
            if (in_array($person->sID, $seenPersonIds)) {
                continue;
            }
            
            // Mark this person as seen
            $seenPersonIds[] = $person->sID;
            
            
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
                            // Fallback to original image if thumbnail type doesn't exist
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
            
            $formattedResults[] = [
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
                
                // If no reviews for this specialism but reviews exist, return featured reviews
                if ($totalActiveReviews > 0 && $reviewSpecialismCount == 0) {
                    return $this->getFeaturedReviews();
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
            // Return error message instead of fallback
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }


    
    /**
     * Get general high-rating reviews using ReviewList
     */
    private function getFeaturedReviews(): array
    {
        try {
            // Use ReviewList for proper ORM approach
            $reviewList = new ReviewList();
            $reviewList->filterByActive();
            
            // Filter for high ratings (4+) by modifying the query directly
            $reviewList->getQueryObject()->andWhere('rating >= 4');
            
            // Order by featured first, then rating (overriding default sortOrder)
            $reviewList->getQueryObject()->orderBy('featured', 'DESC')->addOrderBy('rating', 'DESC');
            
            $reviewList->limitResults(3);
            $results = $reviewList->getResults();
            
            if (empty($results)) {
                return [['error' => 'No high-rating reviews found']];
            }
            
            // Format for API response using Review objects
            $formattedResults = [];
            foreach ($results as $reviewObj) {
                $formattedResults[] = [
                    'id' => $reviewObj->sID,
                    'client_name' => $reviewObj->author ?: 'Anonymous',
                    'organization' => $reviewObj->organization ?: '',
                    'rating' => (int)($reviewObj->rating ?: 5),
                    'review' => $reviewObj->extract ?: $reviewObj->review ?: 'Excellent service',
                    'source' => $reviewObj->source ?: 'Client Review',
                    'featured' => (bool)$reviewObj->featured,
                    'relevance_score' => 8, // High score for featured/high-rating reviews
                    'featured_match' => true
                ];
            }
            
            return $formattedResults;
            
        } catch (\Exception $e) {
            return [['error' => 'Review search failed: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get places by specific location
     */
    private function getPlacesByLocation($location): array
    {
        try {
            // Use PlaceList to get all active places, then filter in PHP
            $placeList = new PlaceList();
            $placeList->filterByActive();
            $placeList->limitResults(10); // Get more to filter from
            
            $allPlaces = $placeList->getResults();
            
            // Filter places by location in PHP
            $matchingPlaces = [];
            $locationLower = strtolower($location);
            
            foreach ($allPlaces as $place) {
                $nameMatch = stripos($place->name, $location) !== false;
                $townMatch = stripos($place->town, $location) !== false;
                $countyMatch = stripos($place->county, $location) !== false;
                
                if ($nameMatch || $townMatch || $countyMatch) {
                    $matchingPlaces[] = $place;
                    if (count($matchingPlaces) >= 3) break; // Limit to 3
                }
            }
            
            
            if (empty($matchingPlaces)) {
                return $this->getNearestOffices();
            }
            
            // Convert Place objects to array format
            $placesArray = [];
            foreach ($matchingPlaces as $place) {
                $placesArray[] = [
                    'sID' => $place->sID,
                    'name' => $place->name,
                    'address1' => $place->address1,
                    'address2' => $place->address2,
                    'town' => $place->town,
                    'county' => $place->county,
                    'postcode' => $place->postcode,
                    'phone' => $place->phone
                ];
            }
            
            $formattedPlaces = $this->formatPlaceResults($placesArray, true);
            
            return $formattedPlaces;
            
        } catch (\Exception $e) {
            return $this->getNearestOffices();
        }
    }
    
    /**
     * Get nearest/main offices
     */
    private function getNearestOffices(): array
    {
        try {
            // Use PlaceList to get active places, then sort by priority in PHP
            $placeList = new PlaceList();
            $placeList->filterByActive();
            $placeList->limitResults(10); // Get more to sort from
            
            $allPlaces = $placeList->getResults();
            
            // Sort places to prioritize main/head offices
            $sortedPlaces = [];
            $mainOffices = [];
            $headOffices = [];
            $otherOffices = [];
            
            foreach ($allPlaces as $place) {
                $nameLower = strtolower($place->name);
                if (strpos($nameLower, 'main') !== false) {
                    $mainOffices[] = $place;
                } elseif (strpos($nameLower, 'head') !== false) {
                    $headOffices[] = $place;
                } else {
                    $otherOffices[] = $place;
                }
            }
            
            // Combine in priority order and limit to 3
            $sortedPlaces = array_merge($mainOffices, $headOffices, $otherOffices);
            $topPlaces = array_slice($sortedPlaces, 0, 3);
            
            // Convert Place objects to array format
            $placesArray = [];
            foreach ($topPlaces as $place) {
                $placesArray[] = [
                    'sID' => $place->sID,
                    'name' => $place->name,
                    'address1' => $place->address1,
                    'address2' => $place->address2,
                    'town' => $place->town,
                    'county' => $place->county,
                    'postcode' => $place->postcode,
                    'phone' => $place->phone
                ];
            }
            
            return $this->formatPlaceResults($placesArray, false);
            
        } catch (\Exception $e) {
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
            // Build complete address
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
            
            // Get additional place data using the proper Place class
            $additionalData = [];
            if (!empty($place['sID'])) {
                try {
                    $fullPlace = Place::getByID($place['sID']);
                    
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
                        
                        // Get page URL if place has a linked page
                        if (!empty($fullPlace->page)) {
                            try {
                                $placePage = \Concrete\Core\Page\Page::getByID($fullPlace->page);
                                if (is_object($placePage) && !$placePage->isError()) {
                                    $additionalData['page_url'] = $placePage->getCollectionPath();
                                }
                            } catch (\Exception $e) {
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
            
            $results[] = [
                'id' => $place['sID'],
                'name' => $place['name'],
                'address' => $address,
                'phone' => $place['phone'] ?? '',
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
                'relevance_score' => $isLocationSpecific ? 9 : 7,
                'optimized_match' => true,
                // Individual address components for flexible display
                'address_components' => [
                    'line1' => $place['address1'] ?? '',
                    'line2' => $place['address2'] ?? '',
                    'town' => $place['town'] ?? '',
                    'county' => $place['county'] ?? '',
                    'postcode' => $place['postcode'] ?? ''
                ]
            ];
        }
        
        return $results;
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
            'urgency_assessment' => $intent['urgency'] ?? 'unknown',
            'complexity_rating' => $intent['complexity'],
            'suggested_contacts' => $intent['suggested_specialist_count'],
            'office_focus' => $intent['suggested_office_focus'],
            'review_type' => $intent['review_type_needed']
        ];
    }
    
    /**
     * Debug method: Search for Work Accident related pages in vector store
     */
    public function debugWorkAccidentPages()
    {
        try {
            $pageIndexService = new \KatalysisProAi\PageIndexService();
            
            // Test different search terms
            $searchTerms = [
                'Work Accident',
                'Work Accidents', 
                'Workplace Accident',
                'Accident at Work',
                'Industrial Accident',
                'Industrial Accidents'
            ];
            
            $results = [];
            
            foreach ($searchTerms as $term) {
                echo "=== Searching for: '$term' ===\n";
                // Use larger topK for comprehensive testing
                $documents = $pageIndexService->getRelevantDocuments($term, 100);
                
                $results[$term] = [];
                foreach ($documents as $i => $doc) {
                    if (is_object($doc)) {
                        $title = $doc->sourceName ?? 'Unknown';
                        $score = $doc->score ?? 0;
                        $pageType = $doc->metadata['pagetype'] ?? 'unknown';
                        $url = $doc->metadata['url'] ?? 'no-url';
                        
                        // Only show relevant results (score > 0.3)
                        if ($score > 0.3) {
                            $results[$term][] = [
                                'rank' => $i + 1,
                                'title' => $title,
                                'score' => round($score, 4),
                                'page_type' => $pageType,
                                'url' => $url,
                                'content_preview' => substr($doc->content ?? '', 0, 200)
                            ];
                            
                            echo sprintf(
                                "#%d: %s (Score: %.4f, Type: %s)\n    URL: %s\n    Preview: %s...\n\n",
                                $i + 1,
                                $title,
                                $score,
                                $pageType,
                                $url,
                                substr(strip_tags($doc->content ?? ''), 0, 150)
                            );
                        }
                    }
                }
                
                if (empty($results[$term])) {
                    echo "No relevant results found (score > 0.3)\n\n";
                }
            }
            
            return $results;
            
        } catch (\Exception $e) {
            echo "Error in debug: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * Apply page type scoring boost to prioritize certain content types
     */
    private function applyPageTypeBoost($originalScore, $pageType): float
    {
        // Define page type boost multipliers - TEMPORARILY DISABLED (all set to 1.0)
        $boostMap = [
            'legal_service' => 1.0,           // Temporarily disabled - was 1.35
            'legal_service_index' => 1.0,    // Temporarily disabled - was 1.4  
            'calculator_entry' => 1.0,       // Temporarily disabled - was 1.35
            'blog_entry' => 1.0,             // Temporarily disabled - was 1.15
            'case_study' => 1.0,             // Temporarily disabled - was 1.2
            'news' => 1.0,                   // Temporarily disabled - was 1.1
            'page' => 1.0,                   // No boost for general pages
            '' => 1.0                        // No boost for unknown types
        ];

        $multiplier = $boostMap[$pageType] ?? 1.0;
        $boostedScore = $originalScore * $multiplier;
        
        // Cap the maximum score at 1.0 to maintain score integrity
        return min($boostedScore, 1.0);
    }

    private function applyQueryKeywordBoost($originalScore, $pageTitle, $query): float
    {
        // Extract key terms from the query (remove common words)
        $commonWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'can', 'may', 'might', 'must'];
        $queryWords = preg_split('/\s+/', strtolower(trim($query)));
        $significantWords = array_filter($queryWords, function($word) use ($commonWords) {
            return !in_array($word, $commonWords) && strlen($word) > 2;
        });
        
        $titleLower = strtolower($pageTitle);
        $matchCount = 0;
        $totalWords = count($significantWords);
        
        if ($totalWords === 0) {
            return $originalScore; // No significant words to match
        }
        
        // Count how many query words appear in the title
        foreach ($significantWords as $word) {
            if (strpos($titleLower, $word) !== false) {
                $matchCount++;
            }
        }
        
        // Calculate boost based on percentage of query words found in title
        $matchRatio = $matchCount / $totalWords;
        
        // Apply boost: 0% match = no boost, 100% match = +25% boost, graduated in between - TEMPORARILY DISABLED
        // $boostMultiplier = 1.0 + ($matchRatio * 0.25); // Max 25% boost for perfect matches - TEMPORARILY DISABLED
        
        // $boostedScore = $originalScore * $boostMultiplier; - TEMPORARILY DISABLED
        
        // Cap the maximum score at 1.0 to maintain score integrity
        return $originalScore; // TEMPORARILY DISABLED - return original score without boost
    }

    private function applyParentChildBoosts($candidateDocs): array
    {
        // Create URL to title mapping for quick lookup
        $urlToTitle = [];
        $titleToCandidate = [];
        foreach ($candidateDocs as $index => $doc) {
            $urlToTitle[$doc['url']] = $doc['title'];
            $titleToCandidate[$doc['title']] = $index;
        }
        
        // Define parent-child relationships based on URL patterns and common naming
        $parentChildMappings = [
            // Work-related injury hierarchy
            'Work Accident' => ['Eye Injury At Work', 'Head Injury at Work', 'Back Injury at Work', 'Falls At Work'],
            'Industrial Accidents' => ['Work Accident', 'Factory Accidents', 'Warehouse Accidents', 'Office Accidents'],
            
            // Injury type hierarchies  
            'Serious Injury' => ['Eye Injury', 'Head Injury', 'Back Injury', 'Multiple & Major Injuries'],
            'Eye Injury' => ['Eye Injury At Work'],
            
            // Service area hierarchies
            'Personal Injury' => ['Work Accident', 'Industrial Accidents', 'Serious Injury'],
        ];
        
        // Apply parent boosts based on high-scoring children
        foreach ($candidateDocs as $index => $candidate) {
            $candidateTitle = $candidate['title'];
            
            // Check if this candidate is a parent of any high-scoring pages
            if (isset($parentChildMappings[$candidateTitle])) {
                $childTitles = $parentChildMappings[$candidateTitle];
                $highestChildScore = 0;
                $foundHighScoringChild = false;
                
                foreach ($childTitles as $childTitle) {
                    if (isset($titleToCandidate[$childTitle])) {
                        $childIndex = $titleToCandidate[$childTitle];
                        $childScore = $candidateDocs[$childIndex]['score'];
                        
                        // If child has high relevance (>0.8), boost parent
                        if ($childScore > 0.8) {
                            $highestChildScore = max($highestChildScore, $childScore);
                            $foundHighScoringChild = true;
                        }
                    }
                }
                
                // Apply parent boost: +15% of the highest child's score
                if ($foundHighScoringChild) {
                    $parentBoost = $highestChildScore * 0.15; // 15% boost based on child's score
                    $originalScore = $candidateDocs[$index]['score'];
                    $newScore = min($originalScore + $parentBoost, 1.0); // Cap at 1.0
                    
                    $candidateDocs[$index]['score'] = $newScore;
                    $candidateDocs[$index]['parent_child_boost'] = round($parentBoost, 3);
                    $candidateDocs[$index]['total_boost'] = round($newScore - $candidateDocs[$index]['original_score'], 3);
                } else {
                    $candidateDocs[$index]['parent_child_boost'] = 0;
                }
            } else {
                $candidateDocs[$index]['parent_child_boost'] = 0;
            }
        }
        
        return $candidateDocs;
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
                                return $parentId;
                            }
                        }
                    }
                    break;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse selected action IDs from AI response [ACTIONS:1,2,3] format
     */
    private function parseSelectedActions($aiResponse): array
    {
        $selectedActions = [];
        
        if (preg_match('/\[ACTIONS:([0-9,\s]+)\]/', $aiResponse, $matches)) {
            $actionIds = explode(',', $matches[1]);
            foreach ($actionIds as $id) {
                $id = trim($id);
                if (is_numeric($id)) {
                    $selectedActions[] = (int)$id;
                }
            }
        } else {
        }
        
        return $selectedActions;
    }
    
    /**
     * Map service area string to specialism ID using exact matching against TopicTree specialisms
     * Only uses exact matches to ensure portability across different sites with different specialism structures
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



}
