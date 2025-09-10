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
use KatalysisProAi\Command\Task\Controller\BuildRagIndexController;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;

class Controller extends Package
{
    protected $pkgHandle = 'katalysis_pro_ai';
    protected $appVersionRequired = '9.3';
    protected $pkgVersion = '0.1.13';
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

    );

    protected $blocks = array(
        'katalysis_ai_chat_bot'
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
		$manager->extend('build_rag_index', function ($app) {
			return new BuildRagIndexController($app);
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
        $this->installContentFile('build_rag_index.xml');
        $this->installContentFile('install_permissions.xml');

        // Create Katalysis block set if it doesn't exist
        if (!BlockTypeSet::getByHandle('katalysis')) {
            BlockTypeSet::add('katalysis', 'Katalysis', $pkg);
        }

        // Install the chatbot block
        BlockType::installBlockTypeFromPackage('katalysis_ai_chat_bot', $pkg);

        $blockType = BlockType::getByHandle('katalysis_ai_chat_bot');
        
        // Add the block to the Katalysis block set
        if ($blockType) {
            $blockSet = BlockTypeSet::getByHandle('katalysis');
            if ($blockSet) {
                $blockSet->addBlockType($blockType);
            }
        }
        
    }

    public function upgrade()
    {
		parent::upgrade();

        $this->installContentFile('install_permissions.xml');

        $pkg = Package::getByHandle("katalysis_pro_ai");

        // Create Katalysis block set if it doesn't exist
        if (!BlockTypeSet::getByHandle('katalysis')) {
            BlockTypeSet::add('katalysis', 'Katalysis', $pkg);
        }

        // Install the chatbot block
        BlockType::installBlockTypeFromPackage('katalysis_ai_chat_bot', $pkg);

        $blockType = BlockType::getByHandle('katalysis_ai_chat_bot');

        // Add the block to the Katalysis block set
        if ($blockType) {
            $blockSet = BlockTypeSet::getByHandle('katalysis');
            if ($blockSet) {
                $blockSet->addBlockType($blockType);
            }
        }
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
