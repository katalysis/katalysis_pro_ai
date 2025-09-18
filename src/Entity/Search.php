<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace KatalysisProAi\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="`KatalysisProSearches`")
 */
class Search
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="`id`", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var datetime
     * @ORM\Column(name="`started`", type="datetime", nullable=true)
     */
    protected $started;

    /**
     * @var string
     * @ORM\Column(name="`location`", type="string", nullable=true)
     */
    protected $location = '';

    /**
     * @var string
     * @ORM\Column(name="`llm`", type="string", nullable=true)
     */
    protected $llm = '';

    /**
     * @var integer
     * @ORM\Column(name="`createdBy`", type="integer", nullable=true)
     */
    protected $createdBy;

    /**
     * @var datetime
     * @ORM\Column(name="`createdDate`", type="datetime", nullable=true)
     */
    protected $createdDate;

    /**
     * @var string
     * @ORM\Column(name="`launchPageUrl`", type="string", nullable=true)
     */
    protected $launchPageUrl = '';

    /**
     * @var string
     * @ORM\Column(name="`launchPageType`", type="string", nullable=true)
     */
    protected $launchPageType = '';

    /**
     * @var string
     * @ORM\Column(name="`launchPageTitle`", type="string", nullable=true)
     */
    protected $launchPageTitle = '';

    /**
     * @var string
     * @ORM\Column(name="`query`", type="text", nullable=true)
     */
    protected $query = '';

    /**
     * @var string
     * @ORM\Column(name="`resultSummary`", type="text", nullable=true)
     */
    protected $resultSummary = '';

    /**
     * @var string
     * @ORM\Column(name="`utmId`", type="string", nullable=true)
     */
    protected $utmId = '';

    /**
     * @var string
     * @ORM\Column(name="`utmSource`", type="string", nullable=true)
     */
    protected $utmSource = '';

    /**
     * @var string
     * @ORM\Column(name="`utmMedium`", type="string", nullable=true)
     */
    protected $utmMedium = '';

    /**
     * @var string
     * @ORM\Column(name="`utmCampaign`", type="string", nullable=true)
     */
    protected $utmCampaign = '';

    /**
     * @var string
     * @ORM\Column(name="`utmTerm`", type="string", nullable=true)
     */
    protected $utmTerm = '';

    /**
     * @var string
     * @ORM\Column(name="`utmContent`", type="string", nullable=true)
     */
    protected $utmContent = '';

    /**
     * @var string
     * @ORM\Column(name="`Name`", type="string", nullable=true)
     */
    protected $Name = '';

    /**
     * @var string
     * @ORM\Column(name="`Email`", type="string", nullable=true)
     */
    protected $Email = '';

    /**
     * @var string
     * @ORM\Column(name="`Phone`", type="string", nullable=true)
     */
    protected $Phone = '';

    /**
     * @var string
     * @ORM\Column(name="`sessionId`", type="string", nullable=true)
     */
    protected $sessionId = '';

    /**
     * @var string
     * @ORM\Column(name="`placeholderMessage`", type="text", nullable=true)
     */
    protected $placeholderMessage = null;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return datetime
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getLlm()
    {
        return $this->llm;
    }

    /**
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return datetime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @return string
     */
    public function getLaunchPageUrl()
    {
        return $this->launchPageUrl;
    }

    /**
     * @return string
     */
    public function getLaunchPageType()
    {
        return $this->launchPageType;
    }

    /**
     * @return string
     */
    public function getLaunchPageTitle()
    {
        return $this->launchPageTitle;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getResultSummary()
    {
        return $this->resultSummary;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function getUtmId()
    {
        return $this->utmId;
    }

    /**
     * @return string
     */
    public function getUtmSource()
    {
        return $this->utmSource;
    }

    /**
     * @return string
     */
    public function getUtmMedium()
    {
        return $this->utmMedium;
    }

    /**
     * @return string
     */
    public function getUtmCampaign()
    {
        return $this->utmCampaign;
    }

    /**
     * @return string
     */
    public function getUtmTerm()
    {
        return $this->utmTerm;
    }

    /**
     * @return string
     */
    public function getUtmContent()
    {
        return $this->utmContent;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->Phone;
    }

    /**
     * @return string
     */
    public function getPlaceholderMessage()
    {
        return $this->placeholderMessage;
    }

    /**
     * @param integer $id
     * @return Search
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param datetime $started
     * @return Search
     */
    public function setStarted($started)
    {
        $this->started = $started;
        return $this;
    }

    /**
     * @param string $location
     * @return Search
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @param string $llm
     * @return Search
     */
    public function setLlm($llm)
    {
        $this->llm = $llm;
        return $this;
    }

    /**
     * @param integer $createdBy
     * @return Search
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @param datetime $createdDate
     * @return Search
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    /**
     * @param string $launchPageUrl
     * @return Search
     */
    public function setLaunchPageUrl($launchPageUrl)
    {
        $this->launchPageUrl = $launchPageUrl;
        return $this;
    }

    /**
     * @param string $launchPageType
     * @return Search
     */
    public function setLaunchPageType($launchPageType)
    {
        $this->launchPageType = $launchPageType;
        return $this;
    }

    /**
     * @param string $launchPageTitle
     * @return Search
     */
    public function setLaunchPageTitle($launchPageTitle)
    {
        $this->launchPageTitle = $launchPageTitle;
        return $this;
    }

    /**
     * @param string $query
     * @return Search
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $resultSummary
     * @return Search
     */
    public function setResultSummary($resultSummary)
    {
        $this->resultSummary = $resultSummary;
        return $this;
    }

    /**
     * @param string $sessionId
     * @return Search
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @param string $utmId
     * @return Search
     */
    public function setUtmId($utmId)
    {
        $this->utmId = $utmId;
        return $this;
    }

    /**
     * @param string $utmSource
     * @return Search
     */
    public function setUtmSource($utmSource)
    {
        $this->utmSource = $utmSource;
        return $this;
    }

    /**
     * @param string $utmMedium
     * @return Search
     */
    public function setUtmMedium($utmMedium)
    {
        $this->utmMedium = $utmMedium;
        return $this;
    }

    /**
     * @param string $utmCampaign
     * @return Search
     */
    public function setUtmCampaign($utmCampaign)
    {
        $this->utmCampaign = $utmCampaign;
        return $this;
    }

    /**
     * @param string $utmTerm
     * @return Search
     */
    public function setUtmTerm($utmTerm)
    {
        $this->utmTerm = $utmTerm;
        return $this;
    }

    /**
     * @param string $utmContent
     * @return Search
     */
    public function setUtmContent($utmContent)
    {
        $this->utmContent = $utmContent;
        return $this;
    }

    /**
     * @param string $Name
     * @return Search
     */
    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    /**
     * @param string $Email
     * @return Search
     */
    public function setEmail($Email)
    {
        $this->Email = $Email;
        return $this;
    }

    /**
     * @param string $Phone
     * @return Search
     */
    public function setPhone($Phone)
    {
        $this->Phone = $Phone;
        return $this;
    }

    /**
     * @param string $placeholderMessage
     * @return Search
     */
    public function setPlaceholderMessage($placeholderMessage)
    {
        $this->placeholderMessage = $placeholderMessage;
        return $this;
    }

    /**
     * Display formatted started date
     * @return string
     */
    public function getDisplayStarted()
    {
        return $this->started ? $this->started->format('d/m/Y H:i') : '';
    }

    /**
     * Display formatted created date
     * @return string
     */
    public function getDisplayCreatedDate()
    {
        return $this->createdDate ? $this->createdDate->format('d/m/Y H:i') : '';
    }

    /**
     * Get truncated query for display
     * @param int $length
     * @return string
     */
    public function getTruncatedQuery($length = 100)
    {
        return strlen($this->query) > $length ? substr($this->query, 0, $length) . '...' : $this->query;
    }

    /**
     * Get truncated result summary for display
     * @param int $length
     * @return string
     */
    public function getTruncatedResultSummary($length = 150)
    {
        return strlen($this->resultSummary) > $length ? substr($this->resultSummary, 0, $length) . '...' : $this->resultSummary;
    }
}
