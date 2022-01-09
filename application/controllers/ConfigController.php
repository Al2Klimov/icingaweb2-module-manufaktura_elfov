<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\CommonController;
use Icinga\Module\Manufaktura_elfov\Forms\BackendForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    use CommonController;

    public function indexAction(): void
    {
        $this->assertPermission('config/modules');

        $form = new BackendForm;
        $form->setIniConfig($this->Config())->handleRequest();

        $this->setupTabs($this->view, $this->Module()->getConfigTabs(), 'config');
        $this->view->form = $form;
    }
}
