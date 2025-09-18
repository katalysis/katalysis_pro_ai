<?php

/** @noinspection DuplicatedCode */

namespace KatalysisProAi;

use Concrete\Core\Application\UserInterface\ContextMenu\DropdownMenu;
use Concrete\Core\Application\UserInterface\ContextMenu\MenuInterface;
use Concrete\Core\Application\UserInterface\ContextMenu\Item\LinkItem;
use Concrete\Core\Support\Facade\Url;
use KatalysisProAi\Entity\Search;

class SearchesMenu extends DropdownMenu implements MenuInterface
{
    protected $menuAttributes = ['class' => 'ccm-popover-page-menu'];

    public function __construct(Search $search)
    {
        parent::__construct();

        // View Search Details
        $this->addItem(new LinkItem(
            Url::to('/dashboard/katalysis_pro_ai/searches/view_search', $search->getId()),
            t('View Details'),
            ['target' => '_self']
        ));

        // Delete Search
        $this->addItem(new LinkItem(
            'javascript:void(0)',
            t('Delete'),
            [
                'class' => 'ccm-delete-item',
                'data-action' => Url::to('/dashboard/katalysis_pro_ai/searches/remove', $search->getId()),
                'data-confirm-message' => t('Are you sure you want to delete this search record?'),
                'target' => '_self'
            ]
        ));
    }
}
