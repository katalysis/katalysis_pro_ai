<?php

/**
 * Sample Form Actions for AI Chat Bot
 * 
 * This file contains example form configurations that demonstrate
 * the AI-driven dynamic form system capabilities.
 */

// Example 1: Simple Contact Form
$contactFormAction = [
    'name' => 'Contact Us',
    'icon' => 'fas fa-envelope',
    'actionType' => 'form',
    'triggerInstruction' => 'User wants to contact us, get in touch, or needs help from our team',
    'responseInstruction' => 'I\'d be happy to help you get in touch with our team!',
    'formSteps' => [
        [
            'stepKey' => 'name',
            'fieldType' => 'text',
            'question' => 'What\'s your name?',
            'aiPrompt' => 'Ask for their full name in a warm, friendly way',
            'validation' => ['required' => true, 'min_length' => 2],
            'sortOrder' => 1
        ],
        [
            'stepKey' => 'email',
            'fieldType' => 'email', 
            'question' => 'What\'s your email address?',
            'aiPrompt' => 'Ask for their email so we can respond to them',
            'validation' => ['required' => true, 'email' => true],
            'sortOrder' => 2
        ],
        [
            'stepKey' => 'message',
            'fieldType' => 'textarea',
            'question' => 'What can we help you with?',
            'aiPrompt' => 'Ask what specific help they need from our team',
            'validation' => ['required' => true, 'min_length' => 10],
            'sortOrder' => 3
        ]
    ],
    'formConfig' => [
        'progressive' => true,
        'completion_message' => 'Thanks {name}! We\'ve received your message and will get back to you within 24 hours.',
        'ai_completion' => false
    ]
];

// Example 2: AI-Driven Lead Qualification Form
$leadQualificationAction = [
    'name' => 'Get Personalized Quote',
    'icon' => 'fas fa-calculator',
    'actionType' => 'dynamic_form',
    'triggerInstruction' => 'User wants pricing, a quote, cost information, or to understand our rates',
    'responseInstruction' => 'I can help you get a personalized quote based on your specific needs!',
    'formSteps' => [
        [
            'stepKey' => 'name',
            'fieldType' => 'text',
            'question' => 'First, what\'s your name?',
            'aiPrompt' => 'Get their name for personalization',
            'validation' => ['required' => true],
            'sortOrder' => 1
        ],
        [
            'stepKey' => 'company',
            'fieldType' => 'text',
            'question' => 'What company do you work for?',
            'aiPrompt' => 'Ask about their company to understand business context',
            'validation' => ['required' => true],
            'sortOrder' => 2
        ],
        [
            'stepKey' => 'company_size',
            'fieldType' => 'select',
            'question' => 'How many employees does {company} have?',
            'options' => ['1-10', '11-50', '51-200', '201-1000', '1000+'],
            'sortOrder' => 3,
            'conditionalLogic' => [
                'ai_decides' => true,
                'decision_prompt' => 'Based on company "{company}", should we ask about company size? Skip for well-known large companies like Microsoft, Google, Apple, etc.'
            ]
        ],
        [
            'stepKey' => 'budget_range',
            'fieldType' => 'select', 
            'question' => 'What\'s your approximate budget range for this project?',
            'options' => ['Under $1,000', '$1,000 - $5,000', '$5,000 - $25,000', '$25,000 - $100,000', 'Over $100,000'],
            'sortOrder' => 4,
            'conditionalLogic' => [
                'ai_decides' => true,
                'decision_prompt' => 'Should we ask about budget? Consider: company size "{company_size}", company "{company}". Only ask if they seem like a qualified prospect based on company and size.'
            ]
        ],
        [
            'stepKey' => 'timeline',
            'fieldType' => 'select',
            'question' => 'When are you looking to get started?',
            'options' => ['Immediately', 'Within 1 month', '1-3 months', '3-6 months', 'Just exploring'],
            'sortOrder' => 5,
            'conditionalLogic' => [
                'ai_decides' => true,
                'decision_prompt' => 'Based on budget "{budget_range}" and company profile, should we ask about timeline?'
            ]
        ],
        [
            'stepKey' => 'ai_generated_followup',
            'fieldType' => 'ai_generated',
            'sortOrder' => 6,
            'aiGenerationPrompt' => 'Based on: Company: {company}, Size: {company_size}, Budget: {budget_range}, Timeline: {timeline} - generate 1 additional qualifying question that would help us provide the most accurate quote.'
        ]
    ],
    'formConfig' => [
        'progressive' => true,
        'ai_completion' => true,
        'completion_prompt' => 'Based on the collected information, determine the best next action. Consider: Company size, budget, timeline, and overall qualification level.',
        'ai_decision_model' => 'claude-3-haiku'
    ]
];

// Example 3: Demo Request Form with Smart Routing
$demoRequestAction = [
    'name' => 'Schedule Demo',
    'icon' => 'fas fa-calendar-alt',
    'actionType' => 'dynamic_form',
    'triggerInstruction' => 'User wants to see a demo, product demonstration, or wants to see how our solution works',
    'responseInstruction' => 'I\'d love to show you how our solution works! Let me get some details to set up the perfect demo for you.',
    'formSteps' => [
        [
            'stepKey' => 'name',
            'fieldType' => 'text',
            'question' => 'What\'s your name?',
            'validation' => ['required' => true],
            'sortOrder' => 1
        ],
        [
            'stepKey' => 'email',
            'fieldType' => 'email',
            'question' => 'What\'s your email address?',
            'validation' => ['required' => true, 'email' => true],
            'sortOrder' => 2
        ],
        [
            'stepKey' => 'role',
            'fieldType' => 'select',
            'question' => 'What\'s your role at your company?',
            'options' => ['CEO/Founder', 'CTO/Technical Lead', 'Marketing Manager', 'Sales Manager', 'Operations Manager', 'Other'],
            'sortOrder' => 3
        ],
        [
            'stepKey' => 'team_size',
            'fieldType' => 'select',
            'question' => 'How large is your team?',
            'options' => ['Just me', '2-5 people', '6-20 people', '21-100 people', '100+ people'],
            'sortOrder' => 4,
            'conditionalLogic' => [
                'ai_decides' => true,
                'decision_prompt' => 'Based on role "{role}", should we ask about team size? Skip for individual contributors, ask for managers/leaders.'
            ]
        ],
        [
            'stepKey' => 'specific_interest',
            'fieldType' => 'ai_generated',
            'sortOrder' => 5,
            'aiGenerationPrompt' => 'Based on their role ({role}) and team size ({team_size}), generate a specific question about what aspect of our product they\'re most interested in seeing during the demo.'
        ]
    ],
    'formConfig' => [
        'progressive' => true,
        'ai_completion' => true,
        'completion_prompt' => 'Based on role, team size, and interests, should this lead get: enterprise_demo (for large teams/senior roles), standard_demo (for small-medium teams), or self_service_trial (for individual contributors)?'
    ]
];

// Example 4: Support Request Form
$supportRequestAction = [
    'name' => 'Get Help',
    'icon' => 'fas fa-life-ring',
    'actionType' => 'form',
    'triggerInstruction' => 'User has a problem, needs help, has an issue, or something isn\'t working',
    'responseInstruction' => 'I\'m here to help! Let me get some details so I can assist you or connect you with the right person.',
    'formSteps' => [
        [
            'stepKey' => 'issue_type',
            'fieldType' => 'select',
            'question' => 'What type of issue are you experiencing?',
            'options' => ['Technical Problem', 'Account/Billing Question', 'Feature Request', 'General Question', 'Bug Report'],
            'validation' => ['required' => true],
            'sortOrder' => 1
        ],
        [
            'stepKey' => 'urgency',
            'fieldType' => 'select',
            'question' => 'How urgent is this issue?',
            'options' => ['Critical - System Down', 'High - Major Impact', 'Medium - Some Impact', 'Low - Minor Issue'],
            'validation' => ['required' => true],
            'sortOrder' => 2,
            'conditionalLogic' => [
                'show_if' => ['field' => 'issue_type', 'equals' => 'Technical Problem']
            ]
        ],
        [
            'stepKey' => 'description',
            'fieldType' => 'textarea',
            'question' => 'Please describe the {issue_type} in detail:',
            'aiPrompt' => 'Ask them to describe their specific issue so we can help effectively',
            'validation' => ['required' => true, 'min_length' => 20],
            'sortOrder' => 3
        ],
        [
            'stepKey' => 'contact_email',
            'fieldType' => 'email',
            'question' => 'What\'s the best email to reach you at?',
            'validation' => ['required' => true, 'email' => true],
            'sortOrder' => 4
        ]
    ],
    'formConfig' => [
        'progressive' => true,
        'completion_message' => 'Thank you! I\'ve submitted your {issue_type} request. Our support team will get back to you shortly.',
        'ai_completion' => false
    ]
];

/**
 * Function to create these sample actions in the database
 * Call this from a migration or admin interface
 */
function createSampleFormActions($entityManager) 
{
    $sampleActions = [
        $contactFormAction,
        $leadQualificationAction, 
        $demoRequestAction,
        $supportRequestAction
    ];
    
    foreach ($sampleActions as $actionData) {
        $action = new \KatalysisProAi\Entity\Action();
        
        $action->setName($actionData['name']);
        $action->setIcon($actionData['icon']);
        $action->setActionType($actionData['actionType']);
        $action->setTriggerInstruction($actionData['triggerInstruction']);
        $action->setResponseInstruction($actionData['responseInstruction']);
        $action->setFormSteps(json_encode($actionData['formSteps']));
        $action->setFormConfig(json_encode($actionData['formConfig']));
        $action->setCreatedBy(1); // Admin user
        $action->setCreatedDate(new \DateTime());
        
        $entityManager->persist($action);
    }
    
    $entityManager->flush();
    
    return "Created " . count($sampleActions) . " sample form actions";
}

?>