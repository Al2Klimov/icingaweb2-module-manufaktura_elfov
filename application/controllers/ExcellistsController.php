<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\ExcelLists;
use Icinga\Module\Manufaktura_elfov\Forms\ExcelListForm;
use Icinga\Web\Controller;

class ExcellistsController extends Controller
{
    public function indexAction(): void
    {
        $this->assertPermission('config/modules');

        $this->view->lists = $this->getRepo()->select(['uuid', 'display_name'])->order('display_name');
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('excellists');
    }

    public function createAction(): void
    {
        $this->assertPermission('config/modules');

        $form = $this->newForm()->add();
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('New');
    }

    public function editAction(): void
    {
        $this->assertPermission('config/modules');

        $form = $this->newForm()->edit($this->params->getRequired('uuid'));
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('Edit');
    }

    public function removeAction(): void
    {
        $this->assertPermission('config/modules');

        $form = $this->newForm()->remove($this->params->getRequired('uuid'));
        $form->handleRequest();

        $this->view->form = $form;
        $this->view->title = $this->translate('Remove');
    }

    private function getRepo(): ExcelLists
    {
        $ds = $this->Config('excel_lists');
        $ds->getConfigObject()->setKeyColumn('uuid');

        return new ExcelLists($ds);
    }

    private function newForm(): ExcelListForm
    {
        return (new ExcelListForm)->setRepository($this->getRepo())->setRedirectUrl('manufaktura_elfov/excellists');
    }
}
