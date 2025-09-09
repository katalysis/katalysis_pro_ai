<?php


/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

namespace KatalysisProAi;

use KatalysisProAi\Entity\Chat;
use KatalysisProAi\Search\ItemList\Pager\Manager\ChatListPagerManager;
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

class ChatList extends ItemList implements PagerProviderInterface, PaginationProviderInterface
{
    protected $isFulltextSearch = false;
    protected $autoSortColumns = ['c.id', 'c.started', 'c.location', 'c.llm', 'c.Name', 'c.Email', 'c.Phone', 'c.launchPageTitle', 'c.createdDate', 'c.utmSource', 'c.launchPageUrl', 'c.launchPageType', 'c.firstMessage', 'c.lastMessage', 'c.utmId', 'c.utmMedium', 'c.utmCampaign', 'c.utmTerm', 'c.utmContent', 'c.welcomeMessage'];
    protected $permissionsChecker = -1;
    
    public function createQuery()
    {
        $this->query->select('c.*')
            ->from("KatalysisProChats", "c");
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
        $this->query->andWhere('(c.`id` LIKE :keywords OR c.`started` LIKE :keywords OR c.`location` LIKE :keywords OR c.`llm` LIKE :keywords OR c.`Name` LIKE :keywords OR c.`Email` LIKE :keywords OR c.`Phone` LIKE :keywords OR c.`launchPageTitle` LIKE :keywords OR c.`createdDate` LIKE :keywords OR c.`utmSource` LIKE :keywords OR c.`launchPageUrl` LIKE :keywords OR c.`launchPageType` LIKE :keywords OR c.`firstMessage` LIKE :keywords OR c.`lastMessage` LIKE :keywords OR c.`utmId` LIKE :keywords OR c.`utmMedium` LIKE :keywords OR c.`utmCampaign` LIKE :keywords OR c.`utmTerm` LIKE :keywords OR c.`utmContent` LIKE :keywords OR c.`welcomeMessage` LIKE :keywords)');
        $this->query->setParameter('keywords', '%' . $keywords . '%');
    }
    
    
    /**
     * @param string $started
     */
    public function filterByStarted($started)
    {
        $this->query->andWhere('c.`started` LIKE :started');
        $this->query->setParameter('started', '%' . $started . '%');
    }
    
    /**
     * @param string $location
     */
    public function filterByLocation($location)
    {
        $this->query->andWhere('c.`location` LIKE :location');
        $this->query->setParameter('location', '%' . $location . '%');
    }
    
    /**
     * @param string $llm
     */
    public function filterByLlm($llm)
    {
        $this->query->andWhere('c.`llm` LIKE :llm');
        $this->query->setParameter('llm', '%' . $llm . '%');
    }
    
    /**
     * @param string $name
     */
    public function filterByName($name)
    {
        $this->query->andWhere('c.`Name` LIKE :name');
        $this->query->setParameter('name', '%' . $name . '%');
    }
    
    /**
     * @param string $email
     */
    public function filterByEmail($email)
    {
        $this->query->andWhere('c.`Email` LIKE :email');
        $this->query->setParameter('email', '%' . $email . '%');
    }
    
    /**
     * @param string $phone
     */
    public function filterByPhone($phone)
    {
        $this->query->andWhere('c.`Phone` LIKE :phone');
        $this->query->setParameter('phone', '%' . $phone . '%');
    }
    
    /**
     * @param string $launchPageTitle
     */
    public function filterByLaunchPageTitle($launchPageTitle)
    {
        $this->query->andWhere('c.`launchPageTitle` LIKE :launchPageTitle');
        $this->query->setParameter('launchPageTitle', '%' . $launchPageTitle . '%');
    }
    
    /**
     * @param string $createdDate
     */
    public function filterByCreatedDate($createdDate)
    {
        $this->query->andWhere('c.`createdDate` LIKE :createdDate');
        $this->query->setParameter('createdDate', '%' . $createdDate . '%');
    }
    
    /**
     * @param string $utmSource
     */
    public function filterByUtmSource($utmSource)
    {
        $this->query->andWhere('c.`utmSource` LIKE :utmSource');
        $this->query->setParameter('utmSource', '%' . $utmSource . '%');
    }

    /**
     * @param string $launchPageUrl
     */
    public function filterByLaunchPageUrl($launchPageUrl)
    {
        $this->query->andWhere('c.`launchPageUrl` LIKE :launchPageUrl');
        $this->query->setParameter('launchPageUrl', '%' . $launchPageUrl . '%');
    }

    /**
     * @param string $launchPageType
     */
    public function filterByLaunchPageType($launchPageType)
    {
        $this->query->andWhere('c.`launchPageType` LIKE :launchPageType');
        $this->query->setParameter('launchPageType', '%' . $launchPageType . '%');
    }

    /**
     * @param string $firstMessage
     */
    public function filterByFirstMessage($firstMessage)
    {
        $this->query->andWhere('c.`firstMessage` LIKE :firstMessage');
        $this->query->setParameter('firstMessage', '%' . $firstMessage . '%');
    }

    /**
     * @param string $lastMessage
     */
    public function filterByLastMessage($lastMessage)
    {
        $this->query->andWhere('c.`lastMessage` LIKE :lastMessage');
        $this->query->setParameter('lastMessage', '%' . $lastMessage . '%');
    }

    /**
     * @param string $utmId
     */
    public function filterByUtmId($utmId)
    {
        $this->query->andWhere('c.`utmId` LIKE :utmId');
        $this->query->setParameter('utmId', '%' . $utmId . '%');
    }

    /**
     * @param string $utmMedium
     */
    public function filterByUtmMedium($utmMedium)
    {
        $this->query->andWhere('c.`utmMedium` LIKE :utmMedium');
        $this->query->setParameter('utmMedium', '%' . $utmMedium . '%');
    }

    /**
     * @param string $utmCampaign
     */
    public function filterByUtmCampaign($utmCampaign)
    {
        $this->query->andWhere('c.`utmCampaign` LIKE :utmCampaign');
        $this->query->setParameter('utmCampaign', '%' . $utmCampaign . '%');
    }

    /**
     * @param string $utmTerm
     */
    public function filterByUtmTerm($utmTerm)
    {
        $this->query->andWhere('c.`utmTerm` LIKE :utmTerm');
        $this->query->setParameter('utmTerm', '%' . $utmTerm . '%');
    }

    /**
     * @param string $utmContent
     */
    public function filterByUtmContent($utmContent)
    {
        $this->query->andWhere('c.`utmContent` LIKE :utmContent');
        $this->query->setParameter('utmContent', '%' . $utmContent . '%');
    }
    
    /**
    * @param array $queryRow
    * @return Chat
    */
    public function getResult($queryRow)
    {
        $app = Application::getFacadeApplication();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $app->make(EntityManagerInterface::class);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $entityManager->getRepository(Chat::class)->findOneBy(["id" => $queryRow["id"]]);
    }
    
    public function getTotalResults()
    {
        if ($this->permissionsChecker === -1) {
            return $this->deliverQueryObject()
                ->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct c.id)')
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn();
            }
        
        return -1; // unknown
    }
    
    public function getPagerManager()
    {
        return new ChatListPagerManager($this);
    }
    
    public function getPagerVariableFactory()
    {
        return new VariableFactory($this, $this->getSearchRequest());
    }
    
    public function getPaginationAdapter()
    {
        return new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            $query->resetQueryParts(['groupBy', 'orderBy'])
                ->select('count(distinct c.id)')
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
        
        $permissionKey = Key::getByHandle("read_katalysis_chats");
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
