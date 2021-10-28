<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\Forms\BackendForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission('config/modules');

        $form = new BackendForm;
        $form->setIniConfig($this->Config())->handleRequest();

        $this->view->tabs = $this->Module()->getConfigTabs()->activate('config');
        $this->view->form = $form;
    }
}
