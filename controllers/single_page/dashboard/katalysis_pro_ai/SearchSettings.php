<?php
namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Concrete\Core\Page\Controller\DashboardPageController;
use Config;
use Core;

class SearchSettings extends DashboardPageController
{
    public function view()
    {
        // Get current settings from config
        $searchResultPrompt = Config::get('katalysis.search.result_prompt', 
            'Based on the search query "{search_query}", provide a comprehensive response that includes:

1. A detailed overview addressing the search topic
2. Key insights and important information
3. Relevant resources and recommendations
4. Actionable next steps if applicable

Format the response in a clear, professional manner suitable for {site_name} visitors.');

        $maxResults = Config::get('katalysis.search.max_results', 8);
        $resultLength = Config::get('katalysis.search.result_length', 'medium');
        $includePageLinks = Config::get('katalysis.search.include_page_links', true);
        $showSnippets = Config::get('katalysis.search.show_snippets', true);
        
        // Specialist settings
        $enableSpecialists = Config::get('katalysis.search.enable_specialists', true);
        $specialistsPrompt = Config::get('katalysis.search.specialists_prompt',
            'Based on the search query, identify team members or specialists who have expertise in this area. Consider their background, experience, and relevant skills.');
        $maxSpecialists = Config::get('katalysis.search.max_specialists', 3);
        
        // Reviews settings
        $enableReviews = Config::get('katalysis.search.enable_reviews', true);
        $reviewsPrompt = Config::get('katalysis.search.reviews_prompt',
            'Find and display customer reviews or testimonials that are relevant to the search topic. Focus on reviews that demonstrate expertise and successful outcomes.');
        $maxReviews = Config::get('katalysis.search.max_reviews', 3);

        // Get search statistics (placeholder for now)
        $searchesToday = $this->getSearchCount('today');
        $searchesThisMonth = $this->getSearchCount('month');
        $popularTerms = $this->getPopularSearchTerms();

        $this->set('searchResultPrompt', $searchResultPrompt);
        $this->set('maxResults', $maxResults);
        $this->set('resultLength', $resultLength);
        $this->set('includePageLinks', $includePageLinks);
        $this->set('showSnippets', $showSnippets);
        $this->set('enableSpecialists', $enableSpecialists);
        $this->set('specialistsPrompt', $specialistsPrompt);
        $this->set('maxSpecialists', $maxSpecialists);
        $this->set('enableReviews', $enableReviews);
        $this->set('reviewsPrompt', $reviewsPrompt);
        $this->set('maxReviews', $maxReviews);
        $this->set('searchesToday', $searchesToday);
        $this->set('searchesThisMonth', $searchesThisMonth);
        $this->set('popularTerms', $popularTerms);
    }

    public function save()
    {
        if (!$this->token->validate('save_search_settings')) {
            $this->error->add($this->token->getErrorMessage());
            return;
        }

        $data = $this->request->request->all();

        // Save search settings
        Config::save('katalysis.search.result_prompt', $data['search_result_prompt'] ?? '');
        Config::save('katalysis.search.max_results', (int)($data['max_results'] ?? 8));
        Config::save('katalysis.search.result_length', $data['result_length'] ?? 'medium');
        Config::save('katalysis.search.include_page_links', !empty($data['include_page_links']));
        Config::save('katalysis.search.show_snippets', !empty($data['show_snippets']));
        
        // Save specialist settings
        Config::save('katalysis.search.enable_specialists', !empty($data['enable_specialists']));
        Config::save('katalysis.search.specialists_prompt', $data['specialists_prompt'] ?? '');
        Config::save('katalysis.search.max_specialists', (int)($data['max_specialists'] ?? 3));
        
        // Save reviews settings
        Config::save('katalysis.search.enable_reviews', !empty($data['enable_reviews']));
        Config::save('katalysis.search.reviews_prompt', $data['reviews_prompt'] ?? '');
        Config::save('katalysis.search.max_reviews', (int)($data['max_reviews'] ?? 3));

        $this->flash('success', t('Search settings saved successfully.'));
        $this->redirect('/dashboard/katalysis_pro_ai/search_settings');
    }

    private function getSearchCount($period)
    {
        // Placeholder implementation - would integrate with actual search logging
        // This could query a search_logs table or file-based logs
        return 0;
    }

    private function getPopularSearchTerms()
    {
        // Placeholder implementation - would return array of popular search terms
        // Format: ['term' => count, 'another term' => count]
        return [];
    }

    public function getPageTitle()
    {
        return t('Search Settings');
    }
}
