<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace KatalysisProAi\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="`KatalysisProChats`")
 */
class Chat
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
     * @ORM\Column(name="`firstMessage`", type="text", nullable=true)
     */
    protected $firstMessage = '';

    /**
     * @var string
     * @ORM\Column(name="`lastMessage`", type="text", nullable=true)
     */
    protected $lastMessage = '';

    /**
     * @var string
     * @ORM\Column(name="`completeChatHistory`", type="text", nullable=true)
     */
    protected $completeChatHistory = '';

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
     * @var integer
     * @ORM\Column(name="`userMessageCount`", type="integer", nullable=true)
     */
    protected $userMessageCount = 0;

    /**
     * @var string
     * @ORM\Column(name="`submittedInfo`", type="text", nullable=true)
     */
    protected $submittedInfo = '';

    /**
     * @var string
     * @ORM\Column(name="`activeFormState`", type="text", nullable=true)
     */
    protected $activeFormState = '';

    /**
     * @var string
     * @ORM\Column(name="`welcomeMessage`", type="text", nullable=true)
     */
    protected $welcomeMessage = null;
    
    
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
    public function getFirstMessage()
    {
        return $this->firstMessage;
    }

    /**
     * @return string
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    /**
     * @return string
     */
    public function getCompleteChatHistory()
    {
        return $this->completeChatHistory;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return integer
     */
    public function getUserMessageCount()
    {
        return $this->userMessageCount;
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
    public function getSubmittedInfo()
    {
        return $this->submittedInfo;
    }

    /**
     * @return string
     */
    public function getActiveFormState()
    {
        return $this->activeFormState;
    }

    /**
     * @return string
     */
    public function getWelcomeMessage()
    {
        return $this->welcomeMessage;
    }

    
    /**
     * @param integer $id
     * @return Chat
     */
    public function setId($id)
    {
        $this->id = $id;
         return $this;
    }

    /**
     * @param datetime $started
     * @return Chat
     */
    public function setStarted($started)
    {
        $this->started = $started;
         return $this;
    }

    /**
     * @param string $location
     * @return Chat
     */
    public function setLocation($location)
    {
        $this->location = $location;
         return $this;
    }

    /**
     * @param string $llm
     * @return Chat
     */
    public function setLlm($llm)
    {
        $this->llm = $llm;
         return $this;
    }

    /**
     * @param integer $createdBy
     * @return Chat
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
         return $this;
    }

    /**
     * @param datetime $createdDate
     * @return Chat
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
         return $this;
    }

    /**
     * @param string $launchPageUrl
     * @return Chat
     */
    public function setLaunchPageUrl($launchPageUrl)
    {
        $this->launchPageUrl = $launchPageUrl;
         return $this;
    }

    /**
     * @param string $launchPageType
     * @return Chat
     */
    public function setLaunchPageType($launchPageType)
    {
        $this->launchPageType = $launchPageType;
         return $this;
    }

    /**
     * @param string $launchPageTitle
     * @return Chat
     */
    public function setLaunchPageTitle($launchPageTitle)
    {
        $this->launchPageTitle = $launchPageTitle;
         return $this;
    }

    /**
     * @param string $firstMessage
     * @return Chat
     */
    public function setFirstMessage($firstMessage)
    {
        $this->firstMessage = $firstMessage;
         return $this;
    }

    /**
     * @param string $lastMessage
     * @return Chat
     */
    public function setLastMessage($lastMessage)
    {
        $this->lastMessage = $lastMessage;
         return $this;
    }

    /**
     * @param string $completeChatHistory
     * @return Chat
     */
    public function setCompleteChatHistory($completeChatHistory)
    {
        $this->completeChatHistory = $completeChatHistory;
         return $this;
    }

    /**
     * @param string $sessionId
     * @return Chat
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
         return $this;
    }

    /**
     * @param integer $userMessageCount
     * @return Chat
     */
    public function setUserMessageCount($userMessageCount)
    {
        $this->userMessageCount = $userMessageCount;
         return $this;
    }

    /**
     * @param string $utmId
     * @return Chat
     */
    public function setUtmId($utmId)
    {
        $this->utmId = $utmId;
         return $this;
    }

    /**
     * @param string $utmSource
     * @return Chat
     */
    public function setUtmSource($utmSource)
    {
        $this->utmSource = $utmSource;
         return $this;
    }

    /**
     * @param string $utmMedium
     * @return Chat
     */
    public function setUtmMedium($utmMedium)
    {
        $this->utmMedium = $utmMedium;
         return $this;
    }

    /**
     * @param string $utmCampaign
     * @return Chat
     */
    public function setUtmCampaign($utmCampaign)
    {
        $this->utmCampaign = $utmCampaign;
         return $this;
    }

    /**
     * @param string $utmTerm
     * @return Chat
     */
    public function setUtmTerm($utmTerm)
    {
        $this->utmTerm = $utmTerm;
         return $this;
    }

    /**
     * @param string $utmContent
     * @return Chat
     */
    public function setUtmContent($utmContent)
    {
        $this->utmContent = $utmContent;
         return $this;
    }

    /**
     * @param string $Name
     * @return Chat
     */
    public function setName($Name)
    {
        $this->Name = $Name;
         return $this;
    }

    /**
     * @param string $Email
     * @return Chat
     */
    public function setEmail($Email)
    {
        $this->Email = $Email;
         return $this;
    }

    /**
     * @param string $Phone
     * @return Chat
     */
    public function setPhone($Phone)
    {
        $this->Phone = $Phone;
         return $this;
    }

    /**
     * @param string $submittedInfo
     * @return Chat
     */
    public function setSubmittedInfo($submittedInfo)
    {
        $this->submittedInfo = $submittedInfo;
         return $this;
    }

    /**
     * @param string $activeFormState
     * @return Chat
     */
    public function setActiveFormState($activeFormState)
    {
        $this->activeFormState = $activeFormState;
         return $this;
    }

    /**
     * @param string $welcomeMessage
     * @return Chat
     */
    public function setWelcomeMessage($welcomeMessage)
    {
        $this->welcomeMessage = $welcomeMessage;
         return $this;
    }

    public function getDisplayStarted()
    {
        return $this->started->format('d/m/Y H:i');
    }

    public function getDisplayCreatedDate()
    {
        return $this->createdDate->format('d/m/Y H:i');
    }

    
}
