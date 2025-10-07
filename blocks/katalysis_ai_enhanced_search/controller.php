<?php
namespace Concrete\Package\KatalysisProAi\Block\KatalysisAiEnhancedSearch;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Http\ResponseFactory;
use Config;
use Core;
use Database;
use KatalysisProAi\RagAgent;
use KatalysisProAi\PageIndexService;
use KatalysisProAi\KatalysisProIndexService;
use KatalysisProAi\AiAgent;

class Controller extends BlockController
{
    protected $btTable = 'btKatalysisAiEnhancedSearch';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 600;
    protected $btWrapperClass = 'ccm-ui';

    public function getBlockTypeDescription()
    {
        return t("Enhanced AI-powered search with comprehensive response generation");
    }

    public function getBlockTypeName()
    {
        return t("Katalysis AI Enhanced Search");
    }

    public function view()
    {
        $this->requireAsset('css', 'katalysis-ai');
        
        // Pass configuration to the view
        $this->set('blockId', $this->bID);
        $this->set('displayMode', $this->displayMode ?: 'inline');
        $this->set('resultsPageId', $this->resultsPageId ?: 0);
        $this->set('placeholder', $this->searchPlaceholder ?: t('Search our knowledge base...'));
        $this->set('buttonText', $this->searchButtonText ?: t('Search'));
        $this->set('maxResults', $this->maxResults ?: 5);
        $this->set('showSpecialists', $this->showSpecialists);
        $this->set('showReviews', $this->showReviews);
        $this->set('showPlaces', $this->showPlaces);
        $this->set('enableDebug', $this->enableDebug);
    }

    public function add()
    {
        $this->edit();
    }

    public function edit()
    {
        // Get available pages for results display
        $pages = [];
        $site = \Core::make('site')->getSite();
        $home = $site->getSiteHomePageObject();
        $this->loadPagesRecursive($home, $pages);
        
        $this->set('pages', $pages);
        $this->set('displayMode', $this->displayMode ?: 'inline');
        $this->set('resultsPageId', $this->resultsPageId ?: 0);
        $this->set('searchPlaceholder', $this->searchPlaceholder ?: t('Search our knowledge base...'));
        $this->set('searchButtonText', $this->searchButtonText ?: t('Search'));
        $this->set('maxResults', $this->maxResults ?: 5);
        $this->set('showSpecialists', $this->showSpecialists ?? true);
        $this->set('showReviews', $this->showReviews ?? true);
        $this->set('showPlaces', $this->showPlaces ?? true);
        $this->set('enableDebug', $this->enableDebug ?? false);
    }

    private function loadPagesRecursive($page, &$pages, $level = 0)
    {
        if ($level > 3) return;
        
        $indent = str_repeat('— ', $level);
        $pages[$page->getCollectionID()] = $indent . $page->getCollectionName();
        
        $children = $page->getCollectionChildren();
        foreach ($children as $child) {
            if (!$child->isSystemPage()) {
                $this->loadPagesRecursive($child, $pages, $level + 1);
            }
        }
    }

    public function save($args)
    {
        $args['displayMode'] = $args['displayMode'] ?? 'inline';
        $args['resultsPageId'] = (int)($args['resultsPageId'] ?? 0);
        $args['searchPlaceholder'] = trim($args['searchPlaceholder'] ?? '');
        $args['searchButtonText'] = trim($args['searchButtonText'] ?? '');
        $args['maxResults'] = max(1, min(20, (int)($args['maxResults'] ?? 5)));
        $args['showSpecialists'] = !empty($args['showSpecialists']);
        $args['showReviews'] = !empty($args['showReviews']);
        $args['showPlaces'] = !empty($args['showPlaces']);
        $args['enableDebug'] = !empty($args['enableDebug']);

        parent::save($args);
    }

    /**
     * Perform AI-powered search with comprehensive response generation
     * This consolidates logic previously in the dashboard controller
     */
    public function action_search()
    {
        $query = $this->request->request->get('query');
        $blockId = $this->request->request->get('block_id');

        if (empty($query)) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => 'Query is required'
            ]);
        }

        try {
            // Get RagAgent singleton instance
            $rag = RagAgent::getInstance('search_' . $blockId);
            
            // Perform vector search
            $pageIndexService = new PageIndexService();
            $documents = $pageIndexService->getRelevantDocuments($query, $this->maxResults ?? 5);

            // Generate AI response using RAG
            $aiResponse = $rag->answer($query);

            // Get specialists if enabled
            $specialists = [];
            if ($this->showSpecialists) {
                $specialists = $this->getRelevantSpecialists($query);
            }

            // Get reviews if enabled
            $reviews = [];
            if ($this->showReviews) {
                $reviews = $this->getRelevantReviews($query);
            }

            // Get places if enabled
            $places = [];
            if ($this->showPlaces) {
                $places = $this->getRelevantPlaces($query);
            }

            return $this->app->make(ResponseFactory::class)->json([
                'success' => true,
                'ai_response' => $aiResponse,
                'documents' => array_map(function($doc) {
                    return [
                        'title' => $doc->metadata['collection_name'] ?? 'Unknown',
                        'url' => $doc->metadata['url'] ?? '#',
                        'content' => substr($doc->content, 0, 200) . '...'
                    ];
                }, $documents),
                'specialists' => $specialists,
                'reviews' => $reviews,
                'places' => $places
            ]);

        } catch (\Exception $e) {
            return $this->app->make(ResponseFactory::class)->json([
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getRelevantSpecialists($query, $limit = 3)
    {
        try {
            $katalysisProService = new KatalysisProIndexService();
            $documents = $katalysisProService->searchPeople($query, $limit);
            
            return array_map(function($doc) {
                return [
                    'name' => $doc->metadata['name'] ?? 'Unknown',
                    'job_title' => $doc->metadata['job_title'] ?? '',
                    'page' => $doc->metadata['page'] ?? '#',
                    'short_biography' => $doc->metadata['short_biography'] ?? ''
                ];
            }, $documents);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getRelevantReviews($query, $limit = 3)
    {
        try {
            $katalysisProService = new KatalysisProIndexService();
            $documents = $katalysisProService->searchReviews($query, $limit);
            
            return array_map(function($doc) {
                return [
                    'reviewer_name' => $doc->metadata['reviewer_name'] ?? 'Anonymous',
                    'content' => $doc->content,
                    'rating' => $doc->metadata['rating'] ?? 5
                ];
            }, $documents);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getRelevantPlaces($query, $limit = 3)
    {
        try {
            $katalysisProService = new KatalysisProIndexService();
            $documents = $katalysisProService->searchPlaces($query, $limit);
            
            return array_map(function($doc) {
                return [
                    'name' => $doc->metadata['name'] ?? 'Unknown',
                    'address' => $doc->metadata['address'] ?? '',
                    'town' => $doc->metadata['town'] ?? '',
                    'postcode' => $doc->metadata['postcode'] ?? ''
                ];
            }, $documents);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSearchTitle()
    {
        return t('Enhanced AI Search');
    }
}
