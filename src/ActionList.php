<?php


/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace KatalysisProAi;

use KatalysisProAi\Entity\Action;
use KatalysisProAi\Search\ItemList\Pager\Manager\ActionListPagerManager;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Search\ItemList\Pager\PagerProviderInterface;
use Concrete\Core\Search\ItemList\Pager\QueryString\VariableFactory;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Permission\Key\Key;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Closure;

class ActionList extends ItemList implements PagerProviderInterface, PaginationProviderInterface
{
    protected $isFulltextSearch = false;
    protected $autoSortColumns = ['a.id', 'a.name', 'a.icon', 'a.triggerInstruction', 'a.responseInstruction', 'a.createdBy', 'a.createdDate'];
    protected $permissionsChecker = -1;
    
    public function createQuery()
    {
        $this->query->select('a.*')
            ->from("KatalysisProActions", "a");
    }
    
    public function finalizeQuery(QueryBuilder $query)
    {
        return $query;
    }
    
    /**
     * @param string $keywords
     */
    public function filterByKeywords($keywords)
    {
        $this->query->andWhere('(a.`id` LIKE :keywords OR a.`name` LIKE :keywords OR a.`icon` LIKE :keywords OR a.`triggerInstruction` LIKE :keywords OR a.`responseInstruction` LIKE :keywords OR a.`createdBy` LIKE :keywords OR a.`createdDate` LIKE :keywords)');
        $this->query->setParameter('keywords', '%' . $keywords . '%');
    }
    
    
    /**
     * @param string $name
     */
    public function filterByName($name)
    {
        $this->query->andWhere('a.`name` LIKE :name');
        $this->query->setParameter('name', '%' . $name . '%');
    }
    
    /**
     * @param string $icon
     */
    public function filterByIcon($icon)
    {
        $this->query->andWhere('a.`icon` LIKE :icon');
        $this->query->setParameter('icon', '%' . $icon . '%');
    }
    
    /**
     * @param string $triggerInstruction
     */
    public function filterByTriggerInstruction($triggerInstruction)
    {
        $this->query->andWhere('a.`triggerInstruction` LIKE :triggerInstruction');
        $this->query->setParameter('triggerInstruction', '%' . $triggerInstruction . '%');
    }

    /**
     * @param string $responseInstruction
     */
    public function filterByResponseInstruction($responseInstruction)
    {
        $this->query->andWhere('a.`responseInstruction` LIKE :responseInstruction');
        $this->query->setParameter('responseInstruction', '%' . $responseInstruction . '%');
    }
    
    /**
     * @param string $createdBy
     */
    public function filterByCreatedBy($createdBy)
    {
        $this->query->andWhere('a.`createdBy` LIKE :createdBy');
        $this->query->setParameter('createdBy', '%' . $createdBy . '%');
    }
    
    /**
     * @param string $createdDate
     */
    public function filterByCreatedDate($createdDate)
    {
        $this->query->andWhere('a.`createdDate` LIKE :createdDate');
        $this->query->setParameter('createdDate', '%' . $createdDate . '%');
    }
    
    /**
    * @param array $queryRow
    * @return Action
    */
    public function getResult($queryRow)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $entityManager->getRepository(Action::class)->findOneBy(["id" => $queryRow["id"]]);
    }
    
    public function getTotalResults()
    {
        if ($this->permissionsChecker === -1) {
            return $this->deliverQueryObject()
                ->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct a.id)')
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn();
            }
        
        return -1; // unknown
    }
    
    public function getPagerManager()
    {
        return new ActionListPagerManager($this);
    }
    
    public function getPagerVariableFactory()
    {
        return new VariableFactory($this, $this->getSearchRequest());
    }
    
    public function getPaginationAdapter()
    {
        return new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            $query->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct a.id)')
                ->setMaxResults(1);
        });
    }
    
    public function checkPermissions($mixed)
    {
        if (isset($this->permissionsChecker)) {
            if ($this->permissionsChecker === -1) {
                return true;
            }
            
            /** @noinspection PhpParamsInspection */
            return call_user_func_array($this->permissionsChecker, [$mixed]);
        }
        
        $permissionKey = Key::getByHandle("read_katalysis_actions");
        return $permissionKey->validate();
    }
    
    public function setPermissionsChecker(Closure $checker = null)
    {
        $this->permissionsChecker = $checker;
    }
    
    public function ignorePermissions()
    {
        $this->permissionsChecker = -1;
    }
    
    public function getPermissionsChecker()
    {
        return $this->permissionsChecker;
    }
    
    public function enablePermissions()
    {
        unset($this->permissionsChecker);
    }
    
    public function isFulltextSearch()
    {
        return $this->isFulltextSearch;
    }
} 