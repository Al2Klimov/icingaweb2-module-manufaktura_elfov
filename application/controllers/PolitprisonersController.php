<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Controllers;

use DateTime;
use Icinga\Exception\NotFoundError;
use Icinga\Module\Manufaktura_elfov\Db;
use Icinga\Module\Manufaktura_elfov\ExcelLists;
use Icinga\Web\Controller;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabs;
use PDO;

class PolitprisonersController extends Controller
{
    public function allAction(): void
    {
        $this->view->excelLists = ExcelLists::create()->select(['uuid', 'display_name'])->fetchPairs();

        $this->view->rows = $query = Db::getPdo()->prepare(
            'SELECT id, name, born, source,'
            . ' last_seen<>(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source) vanished'
            . ' FROM polit_prisoner pp ORDER BY name'
        );

        $query->execute();
        $query->setFetchMode(PDO::FETCH_OBJ);
        $this->addTabByBirthday($this->addTabAll($this->mkTabs()))->activate('all');
    }

    public function bybirthdayAction(): void
    {
        $query = Db::getPdo()->prepare(
            'SELECT born_month, COUNT(*) amount FROM polit_prisoner GROUP BY born_month ORDER BY born_month'
        );

        $query->execute();

        $this->view->rows = $rows = $query->fetchAll(PDO::FETCH_OBJ);

        foreach ($rows as $row) {
            $row->month_name = $this->getMonthName($row->born_month);
        }

        $this->addTabByBirthday($this->addTabAll($this->mkTabs()))->activate('bybirthday');
    }

    public function bybirthmonthAction(): void
    {
        $month = $this->params->getRequired('month');

        if (preg_match('~\D~', $month)) {
            throw new NotFoundError('');
        }

        $month = (int)$month;

        if ($month < 0 || $month > 12) {
            throw new NotFoundError('');
        }

        $this->view->excelLists = ExcelLists::create()->select(['uuid', 'display_name'])->fetchPairs();

        $this->view->rows = $query = Db::getPdo()->prepare(
            'SELECT id, born, name, source,'
            . ' last_seen<>(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source) vanished'
            . ' FROM polit_prisoner pp WHERE born_month=:born_month ORDER BY born_dom, name'
        );

        $query->execute(['born_month' => $month]);
        $query->setFetchMode(PDO::FETCH_OBJ);
        $this->addTabByMonth($this->addTabByBirthday($this->mkTabs()), $month)->activate('bymonth');
    }

    public function viewAction(): void
    {
        $id = $this->params->getRequired('id');

        if (preg_match('~\D~', $id)) {
            throw new NotFoundError('');
        }

        $id = (int)$id;

        $query = Db::getPdo()->prepare(
            'SELECT name, born, source,'
            . ' last_seen<>(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source) vanished'
            . ' FROM polit_prisoner pp WHERE id=:id'
        );

        $query->execute(['id' => $id]);

        $this->view->politPrisoner = $politPrisoner = $query->fetchObject();
        $query = null;

        if ($politPrisoner === false) {
            throw new NotFoundError('');
        }

        $this->view->excelLists = ExcelLists::create()->select(['uuid', 'display_name'])
            ->where('uuid', $politPrisoner->source)->fetchPairs();

        $this->view->fields = $query = Db::getPdo()->prepare(
            'SELECT (SELECT name FROM polit_prisoner_field WHERE id=ppa.field) AS name, value,'
            . ' last_seen<>(SELECT last_import FROM polit_prisoner_source WHERE id=:source) vanished'
            . ' FROM polit_prisoner_attr ppa WHERE polit_prisoner=:id'
            . ' ORDER BY (SELECT name FROM polit_prisoner_field WHERE id=ppa.field)'
        );

        $query->execute(['id' => $id, 'source' => $politPrisoner->source]);
        $query->setFetchMode(PDO::FETCH_OBJ);

        $tabs = $this->mkTabs();

        switch ($this->getParam('from')) {
            case 'all':
                $this->addTabAll($tabs);
                break;
            case 'month':
                $this->addTabByMonth(
                    $tabs, $politPrisoner->born === null ? 0 : (int)(new DateTime($politPrisoner->born))->format('n')
                );
        }

        $tabs->add('polit_prisoner', [
            'title' => preg_replace('~( \S)\S+~u', '\\1.', $politPrisoner->name),
            'icon' => 'user',
            'url' => Url::fromRequest(),
            'active' => true
        ]);
    }

    private function getMonthName(int $month): string
    {
        switch ($month) {
            case 1:
                return $this->translate('January');
            case 2:
                return $this->translate('February');
            case 3:
                return $this->translate('March');
            case 4:
                return $this->translate('April');
            case 5:
                return $this->translate('May');
            case 6:
                return $this->translate('June');
            case 7:
                return $this->translate('July');
            case 8:
                return $this->translate('August');
            case 9:
                return $this->translate('September');
            case 10:
                return $this->translate('October');
            case 11:
                return $this->translate('November');
            case 12:
                return $this->translate('December');
            default:
                return $this->translate('Unknown');
        }
    }

    private function mkTabs(): Tabs
    {
        return $this->view->tabs = new Tabs;
    }

    private function addTabAll(Tabs $tabs): Tabs
    {
        return $tabs->add('all', [
            'title' => $this->translate('All'),
            'icon' => 'th-list',
            'url' => 'manufaktura_elfov/politprisoners/all'
        ]);
    }

    private function addTabByBirthday(Tabs $tabs): Tabs
    {
        return $tabs->add('bybirthday', [
            'title' => $this->translate('By birthday'),
            'icon' => 'calendar',
            'url' => 'manufaktura_elfov/politprisoners/bybirthday'
        ]);
    }

    private function addTabByMonth(Tabs $tabs, int $month): Tabs
    {
        return $tabs->add('bymonth', [
            'title' => $this->getMonthName($month),
            'icon' => 'calendar',
            'url' => Url::fromPath('manufaktura_elfov/politprisoners/bybirthmonth', ['month' => $month])
        ]);
    }
}
