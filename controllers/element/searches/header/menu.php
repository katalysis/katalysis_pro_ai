<?php

/**
 *
 * This file was build with the Entity Designer add-on.
 *
 * https://www.concrete5.org/marketplace/addons/entity-designer
 *
 */

/** @noinspection DuplicatedCode */

namespace Concrete\Package\KatalysisProAi\Controller\Element\Searches\Header;

use Concrete\Core\Controller\ElementController;
use Concrete\Core\Entity\Search\Query;
use KatalysisProAi\Search\Searches\SearchProvider;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Utility\Service\Url;
use Concrete\Core\Validation\CSRF\Token;

class Menu extends ElementController
{
    protected $pkgHandle = "katalysis_pro_ai";
    
    public function __construct(SearchProvider $searchProvider)    {        parent::__construct();        $this->searchProvider = $searchProvider;    }    
    public function getElement()
    {
        return "searches/header/menu";
    }
    
    public function setQuery(Query $query = null): void
    {
        $this->query = $query;
    }
    
    /**
    * @param mixed $exportURL
    */
    public function setExportURL($exportURL): void
    {
        $this->exportURL = $exportURL;
    }
    
    public function view()
    {
        $itemsPerPage = (isset($this->query)) ? $this->query->getItemsPerPage() : $this->searchProvider->getItemsPerPage();
        $this->set('itemsPerPage', $itemsPerPage);
        $this->set('itemsPerPageOptions', $this->searchProvider->getItemsPerPageOptions());
        $this->set('form', $this->app->make(Form::class));
        $this->set('token', $this->app->make(Token::class));
        $this->set('urlHelper', $this->app->make(Url::class));
        $this->set('exportURL', $this->exportURL);
    }
}
