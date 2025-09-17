<?php 
namespace Concrete\Package\KatalysisProAi;

use Config;
use Page;
use Concrete\Core\Package\Package;
use SinglePage;
use View;
use AssetList;
use Asset;
use Concrete\Core\Command\Task\Manager as TaskManager;
use KatalysisProAi\Command\Task\Controller\BuildPageIndexController;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;

class Controller extends Package
{
    protected $pkgHandle = 'katalysis_pro_ai';
    protected $appVersionRequired = '9.3';
    protected $pkgVersion = '0.1.25';
        protected $pkgAutoloaderRegistries = [
        'src' => 'KatalysisProAi'
    ];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$packageDependencies
     */
    protected $packageDependencies = [
        'katalysis_neuron_ai' => true,
    ];

    protected $single_pages = array(

        '/dashboard/katalysis_pro_ai' => array(
            'cName' => 'Katalysis Pro AI'
        ),
        '/dashboard/katalysis_pro_ai/chats' => array(
            'cName' => 'Chats'
        ),
        '/dashboard/katalysis_pro_ai/actions' => array(
            'cName' => 'Actions'
        ),
        '/dashboard/katalysis_pro_ai/chat_bot_settings' => array(
            'cName' => 'Chat Bot Settings'
        ),
        '/dashboard/katalysis_pro_ai/search_settings' => array(
            'cName' => 'Search Settings'
        ),

    );

    protected $blocks = array(
        'katalysis_ai_chat_bot',
        'katalysis_ai_search'
    );

    public function getPackageName()
    {
        return t("Katalysis Pro AI");
    }

    public function getPackageDescription()
    {
        return t("Adds AI Chatbot and Search to Katalysis Pro");
    }

    public function on_start()
    {
        $this->setupAutoloader();

        $entityDesignerServiceProvider = $this->app->make(\KatalysisProAi\EntityDesignerServiceProvider::class);
        $entityDesignerServiceProvider->register();

        // Register the chats search service provider
        $chatsServiceProvider = $this->app->make(\KatalysisProAi\ChatsServiceProvider::class);
        $chatsServiceProvider->register();

        $version = $this->getPackageVersion();

        $al = AssetList::getInstance();
        $al->register('css', 'katalysis-ai', 'css/katalysis-ai.css', ['version' => $version, 'position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'katalysis-ai-chat-forms', 'js/chat-forms.js', ['version' => $version, 'position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        
        $manager = $this->app->make(TaskManager::class);
		$manager->extend('build_page_index', function ($app) {
			return new BuildPageIndexController($app);
		});
		$manager->extend('build_katalysis_pro_index', function ($app) {
			return new \KatalysisProAi\Command\Task\Controller\BuildKatalysisProIndexController($app);
		});
    }

    private function setupAutoloader()
    {
        if (file_exists($this->getPackagePath() . '/vendor')) {
            require_once $this->getPackagePath() . '/vendor/autoload.php';
        }
    }

    public function install()
    {
        $this->setupAutoloader();

        $pkg = parent::install();

        $this->installPages($pkg);
        $this->installContentFile('build_page_index.xml');
        $this->installContentFile('build_katalysis_pro_index.xml');
        $this->installContentFile('install_permissions.xml');

        // Create Katalysis block set if it doesn't exist
        if (!BlockTypeSet::getByHandle('katalysis')) {
            BlockTypeSet::add('katalysis', 'Katalysis', $pkg);
        }

        // Install the chatbot block
        BlockType::installBlockTypeFromPackage('katalysis_ai_chat_bot', $pkg);
        
        // Install the search block
        BlockType::installBlockTypeFromPackage('katalysis_ai_search', $pkg);

        $chatBotType = BlockType::getByHandle('katalysis_ai_chat_bot');
        $searchType = BlockType::getByHandle('katalysis_ai_search');
        
        // Add the blocks to the Katalysis block set
        $blockSet = BlockTypeSet::getByHandle('katalysis');
        if ($blockSet) {
            if ($chatBotType) {
                $blockSet->addBlockType($chatBotType);
            }
            if ($searchType) {
                $blockSet->addBlockType($searchType);
            }
        }
        
    }

    public function upgrade()
    {
		parent::upgrade();

        // Install new task definition
        $this->installContentFile('build_page_index.xml');
    }


    /**
     * @param Package $pkg
     * @return void
     */
    protected function installPages($pkg)
    {
        foreach ($this->single_pages as $path => $value) {
            if (!is_array($value)) {
                $path = $value;
                $value = array();
            }
            $page = Page::getByPath($path);
            if (!$page || $page->isError()) {
                $single_page = SinglePage::add($path, $pkg);

                if ($value) {
                    $single_page->update($value);
                }
            }
        }
    }

}
