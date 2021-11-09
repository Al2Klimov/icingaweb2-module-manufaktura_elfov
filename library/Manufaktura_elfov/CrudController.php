<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Module\Manufaktura_elfov\Forms\RepoForm;
use Icinga\Web\Controller;

abstract class CrudController extends Controller
{
    abstract protected function getTab(): string;

    abstract protected function getRepo(): IniRepo;

    abstract protected function newForm(): RepoForm;

    public function indexAction(): void
    {
        $this->assertPermission('config/modules');

        $this->view->items = $this->getRepo()->select(['uuid', 'display_name'])->order('display_name');
        $this->view->tabs = $this->Module()->getConfigTabs()->activate($this->getTab());
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
}
