<?php

namespace Concrete\Package\KatalysisProAi\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;

class KatalysisProAi extends DashboardPageController
{
    public function view()
    {
        return $this->buildRedirectToFirstAccessibleChildPage();
    }
}
