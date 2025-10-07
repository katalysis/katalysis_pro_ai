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
        
        // REMOVED: max_results, result_length, include_page_links, show_snippets - not used by Enhanced AI Search
        // KEPT: enable_places, max_places - now used by Enhanced AI Search (updated block)
        // KEPT: candidate_documents_count - used for AI context document selection
        // KEPT: known_false_positives - for future implementation
        
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

        // Get specialism-matched content settings
        $maxArticlesCaseStudies = Config::get('katalysis.search.max_articles_case_studies', 4);
        
        // Get basic display settings that are shown in the form
        $maxResults = Config::get('katalysis.search.max_results', 8);
        $includePageLinks = Config::get('katalysis.search.include_page_links', true);
        $showSnippets = Config::get('katalysis.search.show_snippets', true);

        // Set view variables - FIXED: Added missing form variables
        $this->set('maxResults', $maxResults);
        $this->set('includePageLinks', $includePageLinks);
        $this->set('showSnippets', $showSnippets);
        $this->set('enableSpecialists', $enableSpecialists);
        $this->set('maxSpecialists', $maxSpecialists);  
        $this->set('enableReviews', $enableReviews);
        $this->set('maxReviews', $maxReviews);
        $this->set('enablePlaces', $enablePlaces);
        $this->set('maxPlaces', $maxPlaces);
        $this->set('useAISelection', $useAISelection);
        $this->set('maxSelectedDocuments', $maxSelectedDocuments);
        $this->set('candidateDocumentsCount', $candidateDocumentsCount);
        $this->set('knownFalsePositives', $knownFalsePositives);
        $this->set('enableDebugPanel', $enableDebugPanel);
        $this->set('maxArticlesCaseStudies', $maxArticlesCaseStudies);
        
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

        // Save basic settings - FIXED: Now properly saving all displayed settings
        Config::save('katalysis.search.max_results', (int)($data['max_results'] ?? 8));
        Config::save('katalysis.search.include_page_links', !empty($data['include_page_links']));
        Config::save('katalysis.search.show_snippets', !empty($data['show_snippets']));

        // Save AI-driven specialists and reviews settings (no manual prompts needed)
        Config::save('katalysis.search.enable_specialists', !empty($data['enable_specialists']));
        Config::save('katalysis.search.max_specialists', (int)($data['max_specialists'] ?? 3));
        Config::save('katalysis.search.enable_reviews', !empty($data['enable_reviews']));
        Config::save('katalysis.search.max_reviews', (int)($data['max_reviews'] ?? 3));
        
        // Save places settings - FIXED: Added missing places settings save functionality
        Config::save('katalysis.search.enable_places', !empty($data['enable_places']));
        Config::save('katalysis.search.max_places', (int)($data['max_places'] ?? 3));

        // Save AI document selection settings
        Config::save('katalysis.search.use_ai_document_selection', !empty($data['use_ai_document_selection']));
        Config::save('katalysis.search.max_selected_documents', (int)($data['max_selected_documents'] ?? 6));
        Config::save('katalysis.search.candidate_documents_count', (int)($data['candidate_documents_count'] ?? 15));
        
        // Save specialism-matched content settings
        Config::save('katalysis.search.max_articles_case_studies', (int)($data['max_articles_case_studies'] ?? 4));

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
                
                // Return raw RAG results - let the frontend handle processing
                if (empty($ragResults)) {
                    // Fallback to RAG agent if PageIndexService fails
                    $ragResults = $ragAgent->retrieveDocuments(new UserMessage($query));
                }
            } catch (\Exception $docError) {
                $ragResults = [];
            }
            
            // Simple formatting for basic page results
            $pages = $this->simpleFormatResults($ragResults, $includePageLinks, $showSnippets);
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

    /**
     * Simple formatting for basic page results from search_settings controller
     */
    private function simpleFormatResults($ragResults, $includePageLinks = true, $showSnippets = true)
    {
        $formattedResults = [];
        $seenUrls = [];
        
        foreach ($ragResults as $result) {
            $title = 'Relevant Page';
            $content = '';
            $url = '';
            
            if (is_object($result)) {
                if (isset($result->sourceName)) {
                    $title = $result->sourceName;
                }
                if (isset($result->content)) {
                    $content = $this->simpleContentTruncate($result->content, 150);
                }
                if (isset($result->metadata['url'])) {
                    $url = $result->metadata['url'];
                }
            }
            
            // Skip duplicates
            if (!empty($url) && in_array($url, $seenUrls)) {
                continue;
            }
            
            if (!empty($url)) {
                $seenUrls[] = $url;
            }
            
            $formattedResults[] = [
                'id' => $url ?: uniqid(),
                'title' => $title,
                'snippet' => $showSnippets ? $content : '',
                'url' => $includePageLinks ? $url : '',
                'type' => 'page',
                'score' => 0.5,
                'badge' => 'Page'
            ];
        }
        
        return array_slice($formattedResults, 0, 8);
    }
    
    /**
     * Simple content truncation for search_settings controller
     */
    private function simpleContentTruncate($content, $length = 150)
    {
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }
    
    /**
     * Map job title to expertise area
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
            return 'Commercial & Business Law';
        }
        
        return 'Legal Services';
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
    

}
