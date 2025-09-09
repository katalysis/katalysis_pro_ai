<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace KatalysisProAi;

use Concrete\Core\Application\UserInterface\ContextMenu\DropdownMenu;
use Concrete\Core\Application\UserInterface\ContextMenu\Item\LinkItem;
use Concrete\Core\Support\Facade\Url;
use KatalysisProAi\Entity\Chat;

class ChatsMenu extends DropdownMenu
{
    protected $menuAttributes = ['class' => 'ccm-popover-page-menu'];
    
    public function __construct(Chat $chat)
    {
        parent::__construct();
        
        $this->addItem(
            new LinkItem(
                (string)Url::to("/dashboard/katalysis_pro_ai/chats/view_chat", $chat->getId()),
                t('View')
            )
        );
        
        $this->addItem(
            new LinkItem(
                (string)Url::to("/dashboard/katalysis_pro_ai/chats/remove", $chat->getId()),
                t('Remove')
            )
        );
    }
}
