<?php

namespace Concrete\Package\KatalysisProAi\Block\KatalysisAiChatbot;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Page\Page;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Support\Facade\Application;

class Controller extends BlockController
{
    protected $btTable = "btKatalysisAiChatBot";
    protected $btInterfaceWidth = 400;
    protected $btInterfaceHeight = 500;
    protected $btCacheBlockRecord = false;
    protected $btCacheBlockOutput = false;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btWrapperClass = "ccm-ui";
    protected $btDefaultSet = "katalysis";

    public function getBlockTypeName()
    {
        return t("Katalysis AI Chatbot");
    }

    public function getBlockTypeDescription()
    {
        return t("Adds an AI-powered chatbot to your page");
    }

    public function add()
    {
        $this->set("primaryColor", "#7749F8");
        $this->set("primaryDarkColor", "#4D2DA5");
        $this->set("secondaryColor", "#6c757d");
        $this->set("successColor", "#28a745");
        $this->set("lightColor", "#ffffff");
        $this->set("darkColor", "#333333");
        $this->set("borderColor", "#e9ecef");
        $this->set("shadowColor", "rgba(0,0,0,0.1)");
        $this->set("hoverBgColor", "rgba(255,255,255,0.2)");
    }

    public function edit()
    {
        // Load existing values from the block record
        $this->set("primaryColor", $this->primaryColor ?? "#7749F8");
        $this->set("primaryDarkColor", $this->primaryDarkColor ?? "#4D2DA5");
        $this->set("secondaryColor", $this->secondaryColor ?? "#6c757d");
        $this->set("successColor", $this->successColor ?? "#28a745");
        $this->set("lightColor", $this->lightColor ?? "#ffffff");
        $this->set("darkColor", $this->darkColor ?? "#333333");
        $this->set("borderColor", $this->borderColor ?? "#e9ecef");
        $this->set("shadowColor", $this->shadowColor ?? "rgba(0,0,0,0.1)");
        $this->set("hoverBgColor", $this->hoverBgColor ?? "rgba(255,255,255,0.2)");
    }

    public function save($args)
    {
        $args["primaryColor"] = $args["primaryColor"] ?? "#7749F8";
        $args["primaryDarkColor"] = $args["primaryDarkColor"] ?? "#4D2DA5";
        $args["secondaryColor"] = $args["secondaryColor"] ?? "#6c757d";
        $args["successColor"] = $args["successColor"] ?? "#28a745";
        $args["lightColor"] = $args["lightColor"] ?? "#ffffff";
        $args["darkColor"] = $args["darkColor"] ?? "#333333";
        $args["borderColor"] = $args["borderColor"] ?? "#e9ecef";
        $args["shadowColor"] = $args["shadowColor"] ?? "rgba(0,0,0,0.1)";
        $args["hoverBgColor"] = $args["hoverBgColor"] ?? "rgba(255,255,255,0.2)";
        
        parent::save($args);
    }

    public function view()
    {
        $app = Application::getFacadeApplication();
        $config = $app->make(Repository::class);
        
        // Require form handling assets
        $this->requireAsset('javascript', 'katalysis-ai-chat-forms');
        
        // Generate CSRF token for API requests
        $token = $app->make('token');
        $this->set('token', $token);
        $this->set('csrfToken', $token->generate('ai.settings'));
        
        // Get AI configuration
        $this->set('openaiKey', $config->get('katalysis.ai.open_ai_key'));
        $this->set('openaiModel', $config->get('katalysis.ai.open_ai_model'));
        
        // Get current page context
        $page = Page::getCurrentPage();
        if ($page) {
            $this->set('pageTitle', $page->getCollectionName());
            $this->set('pageUrl', $page->getCollectionLink());
            $this->set('pageType', $this->getPageType($page));
            $this->set('isEditMode', $page->isEditMode());
        }
        
        // Get welcome message prompt from settings
        $this->set('welcomePrompt', $config->get('katalysis.aichatbot.welcome_message_prompt', ''));
    }

    private function getPageType($page)
    {
        // Determine page type based on page template or attributes
        $template = $page->getPageTemplateHandle();
        
        if (strpos($template, "service") !== false) {
            return "service";
        } elseif (strpos($template, "article") !== false) {
            return "article";
        } elseif (strpos($template, "contact") !== false) {
            return "contact";
        } elseif (strpos($template, "about") !== false) {
            return "about";
        } else {
            return "page";
        }
    }

    public function getSearchableContent()
    {
        $content = "";
        return $content;
    }
}
