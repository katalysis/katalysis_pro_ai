<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Entity\Search\Query;
use Concrete\Core\Filesystem\Element;
use Concrete\Core\Filesystem\ElementManager;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\Http\Request;
use Concrete\Core\Page\Page;
use Concrete\Core\Search\Field\Field\KeywordsField;
use Concrete\Core\Search\Query\Modifier\AutoSortColumnRequestModifier;
use Concrete\Core\Search\Query\Modifier\ItemsPerPageRequestModifier;
use Concrete\Core\Search\Query\QueryModifier;
use Concrete\Core\Search\Result\Result;
use Concrete\Core\Search\Result\ResultFactory;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Search\Query\QueryFactory;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Validation\CSRF\Token;
use KatalysisProAi\Entity\Search as SearchEntity;
use KatalysisProAi\Entity\Search\SavedSearchesSearch;
use KatalysisProAi\Search\Searches\SearchProvider;
use Concrete\Core\User\User;

class Searches extends DashboardPageController
{
    /**
     * @var Element
     */
    protected $headerMenu;

    /**
     * @var Element
     */
    protected $headerSearch;

    /** @var ResponseFactory */
    protected $responseFactory;
    /** @var Request */
    protected $request;

    public function on_start()
    {
        parent::on_start();

        \Log::info("on_start");

        $this->responseFactory = $this->app->make(ResponseFactory::class);
        $this->request = $this->app->make(Request::class);
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     * @param SearchEntity $entry
     * @return Response
     */
    private function save($entry)
    {
        $data = $this->request->request->all();

        if ($this->validate($data)) {

            // Set Created and Updated info
            $date = date('Y-m-d H:i:s');
            $u = new User();
            $user = $u->getUserID();

            $entry->setStarted(new \DateTime($date ?? null));
            $entry->setCreatedBy($user);
            $entry->setLocation($data["location"] ?? '');
            $entry->setLlm($data["llm"] ?? '');
            $entry->setQuery($data["query"] ?? '');
            $entry->setResultSummary($data["resultSummary"] ?? '');
            $entry->setLaunchPageUrl($data["launchPageUrl"] ?? '');
            $entry->setLaunchPageType($data["launchPageType"] ?? '');
            $entry->setLaunchPageTitle($data["launchPageTitle"] ?? '');
            $entry->setName($data["Name"] ?? '');
            $entry->setEmail($data["Email"] ?? '');
            $entry->setPhone($data["Phone"] ?? '');
            $entry->setSessionId($data["sessionId"] ?? '');
            $entry->setUtmSource($data["utmSource"] ?? '');
            $entry->setUtmMedium($data["utmMedium"] ?? '');
            $entry->setUtmCampaign($data["utmCampaign"] ?? '');
            $entry->setUtmTerm($data["utmTerm"] ?? '');
            $entry->setUtmContent($data["utmContent"] ?? '');
            $entry->setUtmId($data["utmId"] ?? '');

            $this->entityManager->persist($entry);
            $this->entityManager->flush();

            return $this->responseFactory->redirect(Url::to("/dashboard/katalysis_pro_ai/searches/saved/" . $entry->getId()), Response::HTTP_TEMPORARY_REDIRECT);

        } else {

            // Changes to render errors in edit view without resetting content
            $this->flash('error', $this->error);
            $this->set("entry", $entry);
            $this->setDefaults($entry);
            $this->render("/dashboard/katalysis_pro_ai/searches/edit");
        }
    }

    private function setDefaults($entry = null)
    {
        $dateHelper = \Core::make('helper/date');

        $createdByUser = null;
        if ($entry && $entry->getCreatedBy()) {
            $createdByUser = User::getByUserID($entry->getCreatedBy());
            if ($createdByUser) {
                $this->set('createdByName', $createdByUser->getUserName());
                $this->set('createdDate', $dateHelper->formatDateTime($entry->getCreatedDate()));
            }
        }

        $this->set("entry", $entry);
        $this->render("/dashboard/katalysis_pro_ai/searches/edit");
    }

    public function removed()
    {
        $this->set("success", t('The item has been successfully removed.'));
        $this->view();
    }

    public function saved($id = null)
    {
        $this->flash('success', t('The item has been successfully updated.'));
        $factory = $this->app->make(ResponseFactory::class);
        return $factory->redirect(Url::to('/dashboard/katalysis_pro_ai/searches/edit/' . $id));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     * @param array $data
     * @return bool
     */
    public function validate($data = null)
    {
        return !$this->error->has();
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function add()
    {
        $entry = new SearchEntity();

        if ($this->token->validate("save_katalysis_searches_entity")) {
            return $this->save($entry);
        }

        $this->setDefaults($entry);
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function edit($id = null)
    {
        /** @var SearchEntity $entry */
        $entry = $this->entityManager->getRepository(SearchEntity::class)->findOneBy([
            "id" => $id
        ]);

        if ($entry instanceof SearchEntity) {
            if ($this->token->validate("save_katalysis_searches_entity")) {
                return $this->save($entry);
            }

            $this->setDefaults($entry);
        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }

    /**
     * View a specific search entry details
     * @param int $id
     */
    public function view_search($id = null)
    {
        $this->requireAsset('css', 'katalysis-ai');
        $this->requireAsset('javascript', 'katalysis-ai');

        if (!$id) {
            $this->redirect('/dashboard/katalysis_pro_ai/searches');
            return;
        }

        // Find the search by ID
        $search = $this->entityManager->find(SearchEntity::class, $id);

        if (!$search) {
            $this->error->add(t('Search not found.'));
            $this->redirect('/dashboard/katalysis_pro_ai/searches');
            return;
        }

        // Set the search for the view
        $this->set('search', $search);
        $this->set('pageTitle', t('View Search #%s', $search->getId()));
        
        // Set token helper for AJAX requests
        $this->set('token', $this->app->make('token'));

        // Render the view
        $this->render('/dashboard/katalysis_pro_ai/searches/view');
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function remove($id = null)
    {
        /** @var SearchEntity $entry */
        $entry = $this->entityManager->getRepository(SearchEntity::class)->findOneBy([
            "id" => $id
        ]);

        if ($entry instanceof SearchEntity) {
            $this->entityManager->remove($entry);
            $this->entityManager->flush();

            return $this->responseFactory->redirect(Url::to("/dashboard/katalysis_pro_ai/searches/removed"), Response::HTTP_TEMPORARY_REDIRECT);
        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }

    public function view()
    {
        \Log::info("view - list");

        $query = $this->getQueryFactory()->createQuery($this->getSearchProvider(), [
            $this->getSearchKeywordsField()
        ]);

        $result = $this->createSearchResult($query);

        $this->renderSearchResult($result);

        $this->headerSearch->getElementController()->setQuery(null);

        // Explicitly render the list template
        $this->render('/dashboard/katalysis_pro_ai/searches');
    }

    protected function getSearchProvider()
    {
        return $this->app->make(SearchProvider::class);
    }

    protected function getSearchKeywordsField()
    {
        return new KeywordsField();
    }

    protected function getQueryFactory()
    {
        return $this->app->make(QueryFactory::class);
    }

    /**
     * @param Query $query
     * @return Result
     */
    protected function createSearchResult(Query $query)
    {
        $provider = $this->getSearchProvider();
        $resultFactory = $this->app->make(ResultFactory::class);
        $queryModifier = $this->app->make(QueryModifier::class);

        $queryModifier->addModifier(new AutoSortColumnRequestModifier($provider, $this->request, Request::METHOD_GET));
        $queryModifier->addModifier(new ItemsPerPageRequestModifier($provider, $this->request, Request::METHOD_GET));
        $query = $queryModifier->process($query);

        return $resultFactory->createFromQuery($provider, $query);
    }

    protected function renderSearchResult(Result $result)
    {
        $headerMenu = $this->getHeaderMenu();
        $headerSearch = $this->getHeaderSearch();
        $headerMenu->getElementController()->setQuery($result->getQuery());
        $headerSearch->getElementController()->setQuery($result->getQuery());

        $exportArgs = [$this->getPageObject()->getCollectionPath(), 'csv_export'];
        if ($this->getAction() == 'advanced_search') {
            $exportArgs[] = 'advanced_search';
        }
        $exportURL = $this->app->make('url/resolver/path')->resolve($exportArgs);
        $query = \Concrete\Core\Url\Url::createFromServer($_SERVER)->getQuery();
        $exportURL = $exportURL->setQuery($query);
        $headerMenu->getElementController()->setExportURL($exportURL);

        $this->set('result', $result);
        $this->set('headerMenu', $headerMenu);
        $this->set('headerSearch', $headerSearch);
    }

    protected function getHeaderMenu()
    {
        if (!isset($this->headerMenu)) {
            $elementManager = $this->app->make(ElementManager::class);

            $this->headerMenu = $elementManager->get('searches/header/menu', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }

        return $this->headerMenu;
    }

    protected function getHeaderSearch()
    {
        if (!isset($this->headerSearch)) {
            $elementManager = $this->app->make(ElementManager::class);

            $this->headerSearch = $elementManager->get('searches/header/search', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }

        return $this->headerSearch;
    }
}
