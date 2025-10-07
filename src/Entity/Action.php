<?php

/** @noinspection DuplicatedCode */
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace KatalysisProAi\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="`KatalysisProActions`")
 */
class Action
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="`id`", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="`name`", type="string", nullable=true)
     */
    protected $name = '';

    /**
     * @var string
     * @ORM\Column(name="`icon`", type="string", nullable=true)
     */
    protected $icon = '';

    /**
     * @var string
     * @ORM\Column(name="`triggerInstruction`", type="text", nullable=true)
     */
    protected $triggerInstruction = '';

    /**
     * @var string
     * @ORM\Column(name="`responseInstruction`", type="text", nullable=true)
     */
    protected $responseInstruction = '';

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
     * @ORM\Column(name="`actionType`", type="string", nullable=true)
     */
    protected $actionType = 'basic';

    /**
     * @var string
     * @ORM\Column(name="`formSteps`", type="text", nullable=true)
     */
    protected $formSteps = '';

    /**
     * @var boolean
     * @ORM\Column(name="`showImmediately`", type="boolean", nullable=false, options={"default"=false})
     */
    protected $showImmediately = false;

     /**
     * @var boolean
     * @ORM\Column(name="`enabled`", type="boolean", nullable=false, options={"default"=false})
     */
    protected $enabled = false;

    
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getTriggerInstruction()
    {
        return $this->triggerInstruction;
    }

    /**
     * @return string
     */
    public function getResponseInstruction()
    {
        return $this->responseInstruction;
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
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * @return string
     */
    public function getFormSteps()
    {
        return $this->formSteps;
    }

    /**
     * @return boolean
     */
    public function getShowImmediately()
    {
        return $this->showImmediately;
    }

    /**
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    
    /**
     * @param integer $id
     * @return Action
     */
    public function setId($id)
    {
        $this->id = $id;
         return $this;
    }

    /**
     * @param string $name
     * @return Action
     */
    public function setName($name)
    {
        $this->name = $name;
         return $this;
    }

    /**
     * @param string $icon
     * @return Action
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
         return $this;
    }

    /**
     * @param string $triggerInstruction
     * @return Action
     */
    public function setTriggerInstruction($triggerInstruction)
    {
        $this->triggerInstruction = $triggerInstruction;
         return $this;
    }

    /**
     * @param string $responseInstruction
     * @return Action
     */
    public function setResponseInstruction($responseInstruction)
    {
        $this->responseInstruction = $responseInstruction;
         return $this;
    }

    /**
     * @param integer $createdBy
     * @return Action
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
         return $this;
    }

    /**
     * @param datetime $createdDate
     * @return Action
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
         return $this;
    }

    /**
     * @param string $actionType
     * @return Action
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;
         return $this;
    }

    /**
     * @param string $formSteps
     * @return Action
     */
    public function setFormSteps($formSteps)
    {
        $this->formSteps = $formSteps;
         return $this;
    }

    /**
     * @param boolean $showImmediately
     * @return Action
     */
    public function setShowImmediately($showImmediately)
    {
        $this->showImmediately = (bool)$showImmediately;
         return $this;
    }

    /**
     * @param boolean $enabled
     * @return Action
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;    
         return $this;
    }

    public function getIconHtml()
    {
        if ($this->icon) {
            return '<i class="' . h($this->icon) . '"></i>';
        }
        return '';
    }

    public function getNameAndIcon()
    {
        return $this->getIconHtml() . ' <strong class="ms-3">' . h($this->name) . '</strong>';
    }

    

    
}
