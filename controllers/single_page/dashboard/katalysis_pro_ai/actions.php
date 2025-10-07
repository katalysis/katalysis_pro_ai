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
use KatalysisProAi\Entity\Action as ActionEntity;
use KatalysisProAi\Entity\Search\SavedActionsSearch;
use KatalysisProAi\Search\Actions\SearchProvider;
use Concrete\Core\User\User;

use Concrete\Core\Html\Service\FontAwesomeIcon;


class Actions extends DashboardPageController
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
     * @param ActionEntity $entry
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

            $entry->setCreatedDate(new \DateTime($date ?? null));
            $entry->setCreatedBy($user);
            $entry->setName($data["name"]);
            $entry->setIcon($data["icon"]);
            $entry->setTriggerInstruction($data["triggerInstruction"]);
            $entry->setResponseInstruction($data["responseInstruction"]);
            $entry->setActionType($data["actionType"] ?? 'basic');
            $entry->setFormSteps($data["formSteps"] ?? '');
            $entry->setShowImmediately($data["showImmediately"] ?? false);
            $entry->setEnabled($data["enabled"] ?? false);

            $this->entityManager->persist($entry);
            $this->entityManager->flush();
            
            $this->flash('success', t('Action saved successfully.'));
            $this->setDefaults($entry);
            $this->render("/dashboard/katalysis_pro_ai/actions/edit");

        } else {

            // Set error message and render with current data
            $this->flash('error', $this->error);
            $this->setDefaults($entry);
        }
    }
    
    private function setDefaults($entry = null)
    {
        $dateHelper = \Core::make('helper/date');
        
        if ($entry instanceof ActionEntity) {
            $this->set('name', $entry->getName());
            $this->set('icon', $entry->getIcon());
            $this->set('triggerInstruction', $entry->getTriggerInstruction());
            $this->set('responseInstruction', $entry->getResponseInstruction());
            $this->set('actionType', $entry->getActionType() ?: 'basic');
            $this->set('formSteps', $entry->getFormSteps() ?: '');
            $this->set('showImmediately', $entry->getShowImmediately());
            $this->set('enabled', $entry->getEnabled());
            $this->set('createdBy', $entry->getCreatedBy());
            $this->set('createdDate', $entry->getCreatedDate() ? $dateHelper->formatDateTime($entry->getCreatedDate()) : '');
            
            // Set user names for display
            $createdByUser = User::getByUserID($entry->getCreatedBy());
            if ($createdByUser) {
                $this->set('createdByName', $createdByUser->getUserName());
            } else {
                $this->set('createdByName', 'Unknown');
            }
        } else {
            $this->set('name', '');
            $this->set('icon', '');
            $this->set('triggerInstruction', '');
            $this->set('responseInstruction', '');
            $this->set('actionType', 'basic');
            $this->set('formSteps', '');
            $this->set('showImmediately', false);
            $this->set('enabled', false);
            $this->set('createdBy', '');
            $this->set('createdDate', '');
            $this->set('createdByName', '');
        }
        
        $this->set("entry", $entry);
    }
    
    private function validate($data)
    {
        $this->error = null;
        
        if (empty($data["name"])) {
            $this->error = t("Name is required");
            return false;
        }
        
        if (empty($data["icon"])) {
            $this->error = t("Icon is required");
            return false;
        }
        
        if (empty($data["triggerInstruction"])) {
            $this->error = t("Trigger Instruction is required");
            return false;
        }
        
        if (empty($data["responseInstruction"])) {
            $this->error = t("Response Instruction is required");
            return false;
        }
        
        return true;
    }
    
    public function add()
    {
        $entry = new ActionEntity();
        
        if ($this->token->validate("save_katalysis_actions_entity")) {
            return $this->save($entry);
        }
        
        $this->setDefaults($entry);
        $this->render("/dashboard/katalysis_pro_ai/actions/edit");
    }
    
    public function edit($id = null)
    {
        /** @var ActionEntity $entry */
        $entry = $this->entityManager->getRepository(ActionEntity::class)->findOneBy([
            "id" => $id
        ]);
        
        if ($entry instanceof ActionEntity) {
            if ($this->token->validate("save_katalysis_actions_entity")) {
                return $this->save($entry);
            }
            
            $this->setDefaults($entry);
            $this->render("/dashboard/katalysis_pro_ai/actions/edit");
        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }

    
    
    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function remove($id = null)
    {
        /** @var ActionEntity $entry */
        $entry = $this->entityManager->getRepository(ActionEntity::class)->findOneBy([
            "id" => $id
        ]);
        
        if ($entry instanceof ActionEntity) {
            $this->entityManager->remove($entry);
            $this->entityManager->flush();
            
            return $this->responseFactory->redirect(Url::to("/dashboard/katalysis_pro_ai/actions/removed"), Response::HTTP_TEMPORARY_REDIRECT);
        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }
    
    public function view()
    {
        \Log::info("view");

        $query = $this->getQueryFactory()->createQuery($this->getSearchProvider(), [
            $this->getSearchKeywordsField()
        ]);
        
        $result = $this->createSearchResult($query);

        
        $this->renderSearchResult($result);

        
        $this->headerSearch->getElementController()->setQuery(null);
    }
    
    protected function getHeaderMenu()
    {
        if (!isset($this->headerMenu)) {
            /** @var ElementManager $elementManager */
            $elementManager = $this->app->make(ElementManager::class);
            $this->headerMenu = $elementManager->get('actions/header/menu', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }
        
        return $this->headerMenu;
    }
    
    protected function getHeaderSearch()
    {
        if (!isset($this->headerSearch)) {
            /** @var ElementManager $elementManager */
            $elementManager = $this->app->make(ElementManager::class);
            $this->headerSearch = $elementManager->get('actions/header/search', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }
        
        return $this->headerSearch;
    }
    
    protected function getSearchProvider()
    {
        return $this->app->make(SearchProvider::class);
    }
    
    protected function getSearchKeywordsField()
    {
        $keywords = null;
        
        if ($this->request->query->has('keywords')) {
            $keywords = $this->request->query->get('keywords');
        }
        
        return new KeywordsField($keywords);
    }
    
    protected function getQueryFactory()
    {
        return $this->app->make(QueryFactory::class);
    }
    
    protected function createSearchResult(Query $query)
    {
        $provider = $this->app->make(SearchProvider::class);
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
        
        $this->setThemeViewTemplate('full.php');
    }
    
    public function getSearchResultFromQuery(Query $query)
    {
        $provider = $this->getSearchProvider();
        $resultFactory = $this->app->make(ResultFactory::class);
        return $resultFactory->createFromQuery($provider, $query);
    }
    
    public function getCurrentSearchBaseURL()
    {
        return Url::to('/ccm/system/search/actions/current');
    }
    
    public function getSavedSearchEntity()
    {
        $em = $this->app->make('Doctrine\ORM\EntityManager');
        if (is_object($em)) {
            return $em->getRepository(SavedActionsSearch::class);
        }
        
        return null;
    }
    
    public function getSearchPresets()
    {
        $em = $this->app->make('Doctrine\ORM\EntityManager');
        if (is_object($em)) {
            return $em->getRepository(SavedActionsSearch::class)->findAll();
        }
    }
    
    public function advanced_search()
    {
        $query = $this->getQueryFactory()->createFromAdvancedSearchRequest(
            $this->getSearchProvider(), $this->request, Request::METHOD_GET
        );
        
        if ($query) {
            $result = $this->createSearchResult($query);
            $this->renderSearchResult($result);
        }
        
        $this->headerSearch->getElementController()->setQuery(null);
    }
    
    public function preset($presetID = null)
    {
        if ($presetID) {
            $em = $this->app->make('Doctrine\ORM\EntityManager');
            $preset = $em->find(SavedActionsSearch::class, $presetID);
            
            if ($preset) {
                $query = $preset->getQuery();
                if ($query) {
                    $result = $this->createSearchResult($query);
                    $this->renderSearchResult($result);
                }
            }
        }
        
        $this->headerSearch->getElementController()->setQuery(null);
    }
    
    public function removed()
    {
        $this->set('message', t('Action removed successfully.'));
    }
    
    public function saved($id = null)
    {
        if ($id) {
            $entry = $this->entityManager->getRepository(ActionEntity::class)->findOneBy([
                "id" => $id
            ]);
            
            if ($entry instanceof ActionEntity) {
                $this->set('entry', $entry);
                $this->set('message', t('Action saved successfully.'));
            }
        }
    }
    
    public function save_form_steps()
    {
        if (!$this->token->validate('save_form_steps')) {
            return new Response('Invalid token', 403);
        }
        
        $actionId = $this->request->request->get('action_id');
        $formSteps = $this->request->request->get('form_steps');
        
        if (!$actionId || !$formSteps) {
            return new Response('Missing parameters', 400);
        }
        
        try {
            /** @var ActionEntity $action */
            $action = $this->entityManager->getRepository(ActionEntity::class)->findOneBy([
                "id" => $actionId
            ]);
            
            if (!$action) {
                return new Response('Action not found', 404);
            }
            
            // Validate JSON
            $decoded = json_decode($formSteps, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new Response('Invalid JSON', 400);
            }
            
            // Update the form steps
            $action->setFormSteps($formSteps);
            
            $this->entityManager->persist($action);
            $this->entityManager->flush();
            
            return new Response('Form steps saved successfully', 200);
            
        } catch (\Exception $e) {
            error_log('Error saving form steps: ' . $e->getMessage());
            return new Response('Error saving form steps: ' . $e->getMessage(), 500);
        }
    }
} 