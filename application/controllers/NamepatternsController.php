<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use Icinga\Module\Manufaktura_elfov\CrudController;
use Icinga\Module\Manufaktura_elfov\Db;
use Icinga\Module\Manufaktura_elfov\Forms\NamePatternForm;
use Icinga\Module\Manufaktura_elfov\Forms\RepoForm;
use Icinga\Module\Manufaktura_elfov\IniRepo;
use Icinga\Module\Manufaktura_elfov\NamePatterns;
use PDO;

class NamepatternsController extends CrudController
{
    public function indexAction(): void
    {
        $query = Db::getPdo()->prepare(
            'SELECT name FROM polit_prisoner pp'
            . ' WHERE last_seen=(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source) ORDER BY name'
        );

        $query->execute();
        $this->view->politPrisoners = $query->fetchAll(PDO::FETCH_COLUMN);

        parent::indexAction();
    }

    protected function getTab(): string
    {
        return 'namepatterns';
    }

    protected function getRepo(): IniRepo
    {
        return NamePatterns::create();
    }

    protected function newForm(): RepoForm
    {
        return (new NamePatternForm)->setRepository(NamePatterns::create())
            ->setRedirectUrl('manufaktura_elfov/namepatterns');
    }
}
