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
use KatalysisProAi\Entity\Chat as ChatEntity;
use KatalysisProAi\Entity\Search\SavedChatsSearch;
use KatalysisProAi\Search\Chats\SearchProvider;
use Concrete\Core\User\User;

class Chats extends DashboardPageController
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
     * @param ChatEntity $entry
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
            $entry->setLocation($data["location"]);
            $entry->setLlm($data["llm"]);

            $this->entityManager->persist($entry);
            $this->entityManager->flush();

            return $this->responseFactory->redirect(Url::to("/dashboard/katalysis_pro_ai/chats/saved/" . $entry->getId()), Response::HTTP_TEMPORARY_REDIRECT);

        } else {

            // Changes to render errors in edit view without resetting content
            $this->flash('error', $this->error);
            $this->set("entry", $entry);
            $this->setDefaults($entry);
            $this->render("/dashboard/katalysis_pro_ai/chats/edit");
        }
    }

    private function setDefaults($entry = null)
    {
        $dateHelper = \Core::make('helper/date');

        $createdByUser = null;
        $createdByUser = User::getByUserID($entry->getCreatedBy());
        if ($createdByUser) {
            $this->set('createdByName', $createdByUser->getUserName());
            $this->set('createdDate', $dateHelper->formatDateTime($entry->getCreatedDate()));
        }

        $this->set("entry", $entry);
        $this->render("/dashboard/katalysis_pro_ai/chats/edit");
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
        return $factory->redirect(Url::to('/dashboard/katalysis_pro_ai/chats/edit/' . $id));
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
        $entry = new ChatEntity();

        if ($this->token->validate("save_katalysis_chats_entity")) {
            return $this->save($entry);
        }

        $this->setDefaults($entry);
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function edit($id = null)
    {
        /** @var ChatEntity $entry */
        $entry = $this->entityManager->getRepository(ChatEntity::class)->findOneBy([
            "id" => $id
        ]);

        if ($entry instanceof ChatEntity) {
            if ($this->token->validate("save_katalysis_chats_entity")) {
                return $this->save($entry);
            }

            $this->setDefaults($entry);
        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }

    /**
     * View a specific chat entry details
     * @param int $id
     */
    public function view_chat($id = null)
    {

        $this->requireAsset('css', 'katalysis-ai');
        $this->requireAsset('javascript', 'katalysis-ai');

        if (!$id) {
            $this->redirect('/dashboard/katalysis_pro_ai/chats');
            return;
        }

        // Find the chat by ID
        $chat = $this->entityManager->find(ChatEntity::class, $id);

        if (!$chat) {
            $this->error->add(t('Chat not found.'));
            $this->redirect('/dashboard/katalysis_pro_ai/chats');
            return;
        }

        // Set the chat for the view
        $this->set('chat', $chat);
        $this->set('pageTitle', t('View Chat #%s', $chat->getId()));
        
        // Set token helper for AJAX requests
        $this->set('token', $this->app->make('token'));

        // Render the view
        $this->render('/dashboard/katalysis_pro_ai/chats/view');
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function remove($id = null)
    {
        /** @var ChatEntity $entry */
        $entry = $this->entityManager->getRepository(ChatEntity::class)->findOneBy([
            "id" => $id
        ]);

        if ($entry instanceof ChatEntity) {
            $this->entityManager->remove($entry);
            $this->entityManager->flush();

            return $this->responseFactory->redirect(Url::to("/dashboard/katalysis_pro_ai/chats/removed"), Response::HTTP_TEMPORARY_REDIRECT);
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
        $this->render('/dashboard/katalysis_pro_ai/chats');
    }

    protected function getHeaderMenu()
    {
        if (!isset($this->headerMenu)) {
            /** @var ElementManager $elementManager */
            $elementManager = $this->app->make(ElementManager::class);
            $this->headerMenu = $elementManager->get('chats/header/menu', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }

        return $this->headerMenu;
    }

    protected function getHeaderSearch()
    {
        if (!isset($this->headerSearch)) {
            /** @var ElementManager $elementManager */
            $elementManager = $this->app->make(ElementManager::class);
            $this->headerSearch = $elementManager->get('chats/header/search', Page::getCurrentPage(), [], 'katalysis_pro_ai');
        }

        return $this->headerSearch;
    }

    /**
     * @return QueryFactory
     */
    protected function getQueryFactory()
    {
        return $this->app->make(QueryFactory::class);
    }

    /**
     * @return SearchProvider
     */
    protected function getSearchProvider()
    {
        return $this->app->make(SearchProvider::class);
    }

    /**
     * @param Result $result
     */
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

    /**
     * @param Query $query
     * @return Result
     */
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

    protected function getSearchKeywordsField()
    {
        $keywords = null;

        if ($this->request->query->has('keywords')) {
            $keywords = $this->request->query->get('keywords');
        }

        return new KeywordsField($keywords);
    }

    public function advanced_search()
    {
        $query = $this->getQueryFactory()->createFromAdvancedSearchRequest(
            $this->getSearchProvider(),
            $this->request,
            Request::METHOD_GET
        );

        $result = $this->createSearchResult($query);

        $this->renderSearchResult($result);
    }

    public function preset($presetID = null)
    {
        if ($presetID) {
            $preset = $this->entityManager->find(SavedChatsSearch::class, $presetID);

            if ($preset) {
                $query = $this->getQueryFactory()->createFromSavedSearch($preset);
                $result = $this->createSearchResult($query);
                $this->renderSearchResult($result);

                return;
            }
        }

        $this->view();
    }


    public function chatNotification($chatId)
    {
        $entry = $this->entityManager->getRepository(ChatEntity::class)->findOneBy([
            "id" => $chatId
        ]);

        if ($entry instanceof ChatEntity) {

            $recipientemails = trim(Config::get('katalysis.pro_ai.notification_recipient_emails'));
            foreach (explode(',', $recipientemails) as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mailService = Loader::helper('mail');
                    $mailService->from(trim(Config::get('katalysis.pro_ai.sender_from_email')), Config::get('katalysis.pro_ai.sender_from_name'));
                    $mailService->to(trim($email));
                    $mailService->addParameter('chatId', $chatId);
                    $mailService->load('chat_notification', 'katalysis_pro_ai');
                    $mailService->sendMail();
                    unset($mailService);
                }
            }

        } else {
            $this->responseFactory->notFound(null)->send();
            $this->app->shutdown();
        }
    }

}
