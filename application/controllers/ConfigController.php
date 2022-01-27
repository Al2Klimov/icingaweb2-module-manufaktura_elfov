<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\CommonController;
use Icinga\Module\Manufaktura_elfov\Forms\BackendForm;
use Icinga\Module\Manufaktura_elfov\Forms\NotificationsForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    use CommonController;

    public function indexAction(): void
    {
        $this->assertPermission('config/modules');

        $backendForm = new BackendForm;
        $notificationsForm = new NotificationsForm;

        $backendForm->setIniConfig($this->Config())->handleRequest();
        $notificationsForm->setIniConfig($this->Config())->handleRequest();

        $this->setupTabs($this->view, $this->Module()->getConfigTabs(), 'config');
        $this->view->forms = [$backendForm, $notificationsForm];
    }
}
