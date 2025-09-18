<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace KatalysisProAi;

use KatalysisProAi\Entity\Search;
use KatalysisProAi\Search\ItemList\Pager\Manager\SearchListPagerManager;
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

class SearchList extends ItemList implements PagerProviderInterface, PaginationProviderInterface
{
    protected $isFulltextSearch = false;
    protected $autoSortColumns = ['s.id', 's.started', 's.location', 's.llm', 's.Name', 's.Email', 's.Phone', 's.launchPageTitle', 's.createdDate', 's.utmSource', 's.launchPageUrl', 's.launchPageType', 's.query', 's.resultSummary', 's.utmId', 's.utmMedium', 's.utmCampaign', 's.utmTerm', 's.utmContent', 's.placeholderMessage'];
    protected $permissionsChecker = -1;
    
    public function createQuery()
    {
        $this->query->select('s.*')
            ->from("KatalysisProSearches", "s");
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
        $this->query->andWhere('(s.`id` LIKE :keywords OR s.`started` LIKE :keywords OR s.`location` LIKE :keywords OR s.`llm` LIKE :keywords OR s.`Name` LIKE :keywords OR s.`Email` LIKE :keywords OR s.`Phone` LIKE :keywords OR s.`launchPageTitle` LIKE :keywords OR s.`createdDate` LIKE :keywords OR s.`utmSource` LIKE :keywords OR s.`launchPageUrl` LIKE :keywords OR s.`launchPageType` LIKE :keywords OR s.`query` LIKE :keywords OR s.`resultSummary` LIKE :keywords OR s.`utmId` LIKE :keywords OR s.`utmMedium` LIKE :keywords OR s.`utmCampaign` LIKE :keywords OR s.`utmTerm` LIKE :keywords OR s.`utmContent` LIKE :keywords OR s.`placeholderMessage` LIKE :keywords)');
        $this->query->setParameter('keywords', '%' . $keywords . '%');
    }
    
    /**
     * @param string $started
     */
    public function filterByStarted($started)
    {
        $this->query->andWhere('s.`started` LIKE :started');
        $this->query->setParameter('started', '%' . $started . '%');
    }
    
    /**
     * @param string $location
     */
    public function filterByLocation($location)
    {
        $this->query->andWhere('s.`location` LIKE :location');
        $this->query->setParameter('location', '%' . $location . '%');
    }
    
    /**
     * @param string $llm
     */
    public function filterByLlm($llm)
    {
        $this->query->andWhere('s.`llm` LIKE :llm');
        $this->query->setParameter('llm', '%' . $llm . '%');
    }
    
    /**
     * @param string $name
     */
    public function filterByName($name)
    {
        $this->query->andWhere('s.`Name` LIKE :name');
        $this->query->setParameter('name', '%' . $name . '%');
    }
    
    /**
     * @param string $email
     */
    public function filterByEmail($email)
    {
        $this->query->andWhere('s.`Email` LIKE :email');
        $this->query->setParameter('email', '%' . $email . '%');
    }
    
    /**
     * @param string $phone
     */
    public function filterByPhone($phone)
    {
        $this->query->andWhere('s.`Phone` LIKE :phone');
        $this->query->setParameter('phone', '%' . $phone . '%');
    }
    
    /**
     * @param string $launchPageTitle
     */
    public function filterByLaunchPageTitle($launchPageTitle)
    {
        $this->query->andWhere('s.`launchPageTitle` LIKE :launchPageTitle');
        $this->query->setParameter('launchPageTitle', '%' . $launchPageTitle . '%');
    }
    
    /**
     * @param string $createdDate
     */
    public function filterByCreatedDate($createdDate)
    {
        $this->query->andWhere('s.`createdDate` LIKE :createdDate');
        $this->query->setParameter('createdDate', '%' . $createdDate . '%');
    }
    
    /**
     * @param string $utmSource
     */
    public function filterByUtmSource($utmSource)
    {
        $this->query->andWhere('s.`utmSource` LIKE :utmSource');
        $this->query->setParameter('utmSource', '%' . $utmSource . '%');
    }

    /**
     * @param string $launchPageUrl
     */
    public function filterByLaunchPageUrl($launchPageUrl)
    {
        $this->query->andWhere('s.`launchPageUrl` LIKE :launchPageUrl');
        $this->query->setParameter('launchPageUrl', '%' . $launchPageUrl . '%');
    }

    /**
     * @param string $launchPageType
     */
    public function filterByLaunchPageType($launchPageType)
    {
        $this->query->andWhere('s.`launchPageType` LIKE :launchPageType');
        $this->query->setParameter('launchPageType', '%' . $launchPageType . '%');
    }

    /**
     * @param string $query
     */
    public function filterByQuery($query)
    {
        $this->query->andWhere('s.`query` LIKE :searchQuery');
        $this->query->setParameter('searchQuery', '%' . $query . '%');
    }

    /**
     * @param string $resultSummary
     */
    public function filterByResultSummary($resultSummary)
    {
        $this->query->andWhere('s.`resultSummary` LIKE :resultSummary');
        $this->query->setParameter('resultSummary', '%' . $resultSummary . '%');
    }

    /**
     * @param string $utmId
     */
    public function filterByUtmId($utmId)
    {
        $this->query->andWhere('s.`utmId` LIKE :utmId');
        $this->query->setParameter('utmId', '%' . $utmId . '%');
    }

    /**
     * @param string $utmMedium
     */
    public function filterByUtmMedium($utmMedium)
    {
        $this->query->andWhere('s.`utmMedium` LIKE :utmMedium');
        $this->query->setParameter('utmMedium', '%' . $utmMedium . '%');
    }

    /**
     * @param string $utmCampaign
     */
    public function filterByUtmCampaign($utmCampaign)
    {
        $this->query->andWhere('s.`utmCampaign` LIKE :utmCampaign');
        $this->query->setParameter('utmCampaign', '%' . $utmCampaign . '%');
    }

    /**
     * @param string $utmTerm
     */
    public function filterByUtmTerm($utmTerm)
    {
        $this->query->andWhere('s.`utmTerm` LIKE :utmTerm');
        $this->query->setParameter('utmTerm', '%' . $utmTerm . '%');
    }

    /**
     * @param string $utmContent
     */
    public function filterByUtmContent($utmContent)
    {
        $this->query->andWhere('s.`utmContent` LIKE :utmContent');
        $this->query->setParameter('utmContent', '%' . $utmContent . '%');
    }

    /**
     * @param string $sessionId
     */
    public function filterBySessionId($sessionId)
    {
        $this->query->andWhere('s.`sessionId` LIKE :sessionId');
        $this->query->setParameter('sessionId', '%' . $sessionId . '%');
    }

    /**
     * @param string $placeholderMessage
     */
    public function filterByPlaceholderMessage($placeholderMessage)
    {
        $this->query->andWhere('s.`placeholderMessage` LIKE :placeholderMessage');
        $this->query->setParameter('placeholderMessage', '%' . $placeholderMessage . '%');
    }
    
    /**
    * @param array $queryRow
    * @return Search
    */
    public function getResult($queryRow)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $entityManager->getRepository(Search::class)->findOneBy(["id" => $queryRow["id"]]);
    }
    
    public function getTotalResults()
    {
        if ($this->permissionsChecker === -1) {
            return $this->deliverQueryObject()
                ->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct s.id)')
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn();
            }
        
        return -1; // unknown
    }
    
    public function getPagerManager()
    {
        return new SearchListPagerManager($this);
    }
    
    public function getPagerVariableFactory()
    {
        return new VariableFactory($this, $this->getSearchRequest());
    }
    
    public function getPaginationAdapter()
    {
        return new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            $query->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct s.id)')
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
        
        $permissionKey = Key::getByHandle("read_katalysis_searches");
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
