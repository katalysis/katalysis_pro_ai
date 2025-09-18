<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace KatalysisProAi\Search\ItemList\Pager\Manager;

use KatalysisProAi\Entity\Search;
use KatalysisProAi\Search\Searches\ColumnSet\Available;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use Concrete\Core\Search\ItemList\Pager\Manager\AbstractPagerManager;
use Doctrine\ORM\EntityManagerInterface;
use Concrete\Core\Support\Facade\Application;

class SearchListPagerManager extends AbstractPagerManager
{
    /** 
     * @param Search $search
     * @return int 
     */
    public function getCursorStartValue($search)
    {
        return $search->getId();
    }
    
    public function getCursorObject($cursor)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        return $entityManager->getRepository(Search::class)->findOneBy(["id" => $cursor]);
    }
    
    public function getAvailableColumnSet()
    {
        return new Available();
    }
    
    public function sortListByCursor(PagerProviderInterface $itemList, $direction)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $itemList->getQueryObject()->addOrderBy('s.id', $direction);
    }
}
