<?php
namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard\KatalysisProAi;

use Concrete\Core\Page\Controller\DashboardPageController;
use Config;


class Settings extends DashboardPageController
{
    public function view()
    {

        // Set AI configuration variables
        $this->set('open_ai_key', (string) Config::get('katalysis.ai.open_ai_key'));
        $this->set('open_ai_model', (string) Config::get('katalysis.ai.open_ai_model'));
        $this->set('anthropic_key', (string) Config::get('katalysis.ai.anthropic_key'));
        $this->set('anthropic_model', (string) Config::get('katalysis.ai.anthropic_model'));
        $this->set('ollama_key', (string) Config::get('katalysis.ai.ollama_key'));
        $this->set('ollama_url', (string) Config::get('katalysis.ai.ollama_url'));
        $this->set('ollama_model', (string) Config::get('katalysis.ai.ollama_model'));

        // Set Typesense configuration variables
        $this->set('useTypesense', Config::get('katalysis.search.use_typesense', false));
        $this->set('typesenseApiKey', Config::get('katalysis.search.typesense_api_key', ''));
        $this->set('typesenseHost', Config::get('katalysis.search.typesense_host', ''));
        $this->set('typesensePort', Config::get('katalysis.search.typesense_port', '443'));
        $this->set('typesenseProtocol', Config::get('katalysis.search.typesense_protocol', 'https'));
        $this->set('typesenseCollectionPrefix', Config::get('katalysis.search.typesense_collection_prefix', 'katalysis_'));
       
    }


    public function save()
    {
        if (!$this->token->validate('save_settings')) {
            $this->error->add($this->token->getErrorMessage());
            return;
        }

        $data = $this->request->request->all();

        Config::save('katalysis.ai.open_ai_key', (string) $this->post('open_ai_key'));
        Config::save('katalysis.ai.open_ai_model', (string) $this->post('open_ai_model'));
        Config::save('katalysis.ai.anthropic_key', (string) $this->post('anthropic_key'));
        Config::save('katalysis.ai.anthropic_model', (string) $this->post('anthropic_model'));
        Config::save('katalysis.ai.ollama_key', (string) $this->post('ollama_key'));
        Config::save('katalysis.ai.ollama_url', (string) $this->post('ollama_url'));
        Config::save('katalysis.ai.ollama_model', (string) $this->post('ollama_model'));

        // Save Typesense configuration
        Config::save('katalysis.search.use_typesense', !empty($data['use_typesense']));
        Config::save('katalysis.search.typesense_api_key', $data['typesense_api_key'] ?? '');
        Config::save('katalysis.search.typesense_host', $data['typesense_host'] ?? '');
        Config::save('katalysis.search.typesense_port', $data['typesense_port'] ?? '443');
        Config::save('katalysis.search.typesense_protocol', $data['typesense_protocol'] ?? 'https');
        Config::save('katalysis.search.typesense_collection_prefix', $data['typesense_collection_prefix'] ?? 'katalysis_');

        $this->flash('success', t('Settings saved successfully.'));
        $this->redirect('/dashboard/katalysis_pro_ai/settings');
    }
    
    
}
