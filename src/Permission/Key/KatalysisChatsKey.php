<?php

namespace KatalysisProAi\Permission\Key;

use Concrete\Core\Permission\Key\Key as PermissionKey;

class KatalysisChatsKey extends PermissionKey
{
    public function validate()
    {
        $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
        $u = $app->make(\Concrete\Core\User\User::class);
        if ($u->isSuperUser()) {
            return true;
        }
        $pae = $this->getPermissionAccessObject();
        if (is_object($pae)) {
            $valid = $pae->validate();
        } else {
            $valid = false;
        }

        return $valid;
    }
} 