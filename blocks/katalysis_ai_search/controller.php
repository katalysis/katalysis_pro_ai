<?php
namespace Concrete\Package\KatalysisProAi\Block\KatalysisAiSearch;

use Concrete\Core\Block\BlockController;
use Core;

class Controller extends BlockController
{
    protected $btTable = 'btKatalysisAiSearch';
    protected $btInterfaceWidth = 500;
    protected $btInterfaceHeight = 600;
    protected $btWrapperClass = 'ccm-ui';

    public function getBlockTypeDescription()
    {
        return t("AI-powered search with intelligent results and recommendations");
    }

    public function getBlockTypeName()
    {
        return t("Katalysis AI Search");
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
        $this->set('showSpecialists', $this->showSpecialists);
        $this->set('showReviews', $this->showReviews);
    }

    private function loadPagesRecursive($page, &$pages, $level = 0)
    {
        if ($level > 3) return; // Prevent too deep recursion
        
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

        parent::save($args);
    }



    public function getSearchTitle()
    {
        return t('AI Search');
    }
}
