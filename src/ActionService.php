<?php

namespace KatalysisProAi;

use Doctrine\ORM\EntityManagerInterface;
use KatalysisProAi\Entity\Action;

class ActionService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get all available actions
     */
    public function getAllActions(): array
    {
        $repository = $this->entityManager->getRepository(Action::class);
        return $repository->findAll();
    }

    /**
     * Get actions formatted for AI prompt
     */
    public function getActionsForPrompt(): string
    {
        $actions = $this->getAllActions();
        
        if (empty($actions)) {
            return "No action buttons are currently available.";
        }

        $actionList = "Available Action Buttons:\n";
        foreach ($actions as $action) {
            $actionList .= sprintf(
                "- %s (ID: %d, Icon: %s)\n  Trigger: %s\n  Response: %s\n\n",
                $action->getName(),
                $action->getId(),
                $action->getIcon(),
                $action->getTriggerInstruction(),
                $action->getResponseInstruction()
            );
        }

        return $actionList;
    }

    /**
     * Get actions as JSON for frontend
     */
    public function getActionsForFrontend(): array
    {
        $actions = $this->getAllActions();
        $formattedActions = [];

        foreach ($actions as $action) {
            $formattedActions[] = [
                'id' => $action->getId(),
                'name' => $action->getName(),
                'icon' => $action->getIcon(),
                'triggerInstruction' => $action->getTriggerInstruction(),
                'responseInstruction' => $action->getResponseInstruction()
            ];
        }

        return $formattedActions;
    }

    /**
     * Find action by ID
     */
    public function getActionById(int $id): ?Action
    {
        $repository = $this->entityManager->getRepository(Action::class);
        return $repository->find($id);
    }
} 