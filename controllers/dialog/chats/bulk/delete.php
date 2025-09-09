<?php
namespace Concrete\Package\KatalysisProAi\Controller\Dialog\Chats\Bulk;
defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Controller\Backend\UserInterface as BackendInterfaceController;
use Concrete\Core\Application\EditResponse;
use Concrete\Core\Support\Facade\Url;
use KatalysisProAi\Entity\Chat;

class Delete extends BackendInterfaceController
{
    protected $viewPath = '/dialogs/chats/bulk/delete';
    protected $chats = [];
    protected $canEdit = false;

    public function view()
    {
        // Debug: Log what's in the request
        $requestData = $this->request->request->all();
        $queryData = $this->request->query->all();
        
        // For debugging, let's see what we get
        error_log('Bulk Delete Dialog - Request Data: ' . print_r($requestData, true));
        error_log('Bulk Delete Dialog - Query Data: ' . print_r($queryData, true));
        
        $this->populateChats();
        $this->set('chats', $this->chats);
        
        // Also set debug info for the view
        $this->set('debug_request', $requestData);
        $this->set('debug_query', $queryData);
    }

    public function submit()
    {
        $r = new EditResponse();
        if (!$this->validateAction()) {
            $r->setError(new \Exception(t('Invalid Token')));
            $r->outputJSON();
            \Core::shutdown();
        }

        // Ensure chats are populated
        $this->populateChats();

        $count = 0;
        if (count($this->chats) > 0) {
            try {
                $entityManager = $this->app->make('database/orm')->entityManager();
                
                foreach ($this->chats as $chat) {
                    if ($this->canPerformOperationOnObject($chat)) {
                        $entityManager->remove($chat);
                        ++$count;
                    }
                }
                $entityManager->flush();
            } catch (\Exception $e) {
                // Log the error for debugging
                error_log('Bulk Delete Error: ' . $e->getMessage());
                $r->setError(new \Exception(t('Error deleting chats: %s', $e->getMessage())));
                $r->outputJSON();
                \Core::shutdown();
            }
        }

        $r->setMessage(t2('%s chat deleted', '%s chats deleted', $count));
        $r->setTitle(t('Chats Deleted'));
        $r->setRedirectURL(Url::to('/dashboard/katalysis_pro_ai/chats'));
        $r->outputJSON();
    }

    protected function canAccess()
    {
        // Allow access to the dialog - actual permission check happens on submit
        return true;
    }

    protected function populateChats()
    {
        $sh = $this->app->make('helper/security');
        $entityManager = $this->app->make('database/orm')->entityManager();
        
        // Debug: Check what's in the request
        $requestData = $this->request->request->all();
        $queryData = $this->request->query->all();
        
        // Try different ways the bulk action might pass the selected items
        $selectedItems = null;
        
        // Check if items are in POST data
        if ($this->request->request->has('item') && is_array($this->request->request->get('item'))) {
            $selectedItems = $this->request->request->get('item');
        }
        // Check if items are in GET data
        elseif ($this->request->query->has('item') && is_array($this->request->query->get('item'))) {
            $selectedItems = $this->request->query->get('item');
        }
        // Check if items are in the bulk action data
        elseif ($this->request->request->has('bulk_action_data')) {
            $bulkData = $this->request->request->get('bulk_action_data');
            if (is_array($bulkData) && isset($bulkData['item'])) {
                $selectedItems = $bulkData['item'];
            }
        }
        
        if ($selectedItems && is_array($selectedItems)) {
            $this->chats = [];
            foreach ($selectedItems as $chatId) {
                $chat = $entityManager->find(Chat::class, $sh->sanitizeInt($chatId));
                if ($chat instanceof Chat) {
                    if ($this->canPerformOperationOnObject($chat)) {
                        $this->chats[] = $chat;
                    }
                }
            }
        }

        if (count($this->chats) > 0) {
            $this->canEdit = true;
        } else {
            $this->canEdit = false;
        }

        return $this->canEdit;
    }

    protected function canPerformOperationOnObject($object)
    {
        // For now, allow deletion if user is logged in
        // You can add more specific permission checks here if needed
        $u = $this->app->make('Concrete\Core\User\User');
        return $u->isRegistered();
    }
} 