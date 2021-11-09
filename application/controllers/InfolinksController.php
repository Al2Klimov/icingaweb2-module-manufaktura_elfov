<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\CrudController;
use Icinga\Module\Manufaktura_elfov\Forms\InfoLinkForm;
use Icinga\Module\Manufaktura_elfov\Forms\RepoForm;
use Icinga\Module\Manufaktura_elfov\InfoLinks;
use Icinga\Module\Manufaktura_elfov\IniRepo;

class InfolinksController extends CrudController
{
    protected function getTab(): string
    {
        return 'infolinks';
    }

    protected function getRepo(): IniRepo
    {
        return InfoLinks::create();
    }

    protected function newForm(): RepoForm
    {
        return (new InfoLinkForm)->setRepository(InfoLinks::create())->setRedirectUrl('manufaktura_elfov/infolinks');
    }
}
