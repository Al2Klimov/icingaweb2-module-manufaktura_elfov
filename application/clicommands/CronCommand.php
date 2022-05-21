<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Clicommands;

use DateInterval;
use DateTime;
use Icinga\Application\Config;
use Icinga\Cli\Command;
use Icinga\Data\Db\DbConnection;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Manufaktura_elfov\Db;
use Icinga\Module\Manufaktura_elfov\ExcelList;
use Icinga\Module\Manufaktura_elfov\ExcelLists;
use Icinga\Module\Manufaktura_elfov\NamePatterns;
use Icinga\Module\Manufaktura_elfov\PolitPrisoner;
use PDO;
use PDOStatement;

class CronCommand extends Command
{
    public function dailyAction(): void
    {
        $this->syncPolitPrisoners();
        $this->notifyUpcomingBirthdays();
        $this->notifyNotPatternCovered();
        $this->updateGt2db();
    }

    private function syncPolitPrisoners(): void
    {
        $now = (new DateTime)->format(DateTime::ISO8601);
        $sources = [];

        /** @var PolitPrisoner[] $pps */
        $pps = [];

        foreach (ExcelLists::create()->select()->order('display_name') as $el) {
            $el = new ExcelList($el->uuid, $el->url, $el->name_column, $el->born_column);
            $sources[] = $el->getUuid();

            foreach ($el->fetchAll() as $pp) {
                $pps[] = $pp;
            }
        }

        $fields = [];

        foreach ($pps as $pp) {
            foreach ($pp->info as $field => $_) {
                $fields[$field] = null;
            }
        }

        Db::tx(function (DbConnection $db) use ($now, $sources, $pps, &$fields) {
            /** @var PDO $pdo */
            $pdo = $db->getDbAdapter()->getConnection();

            $stmt = $pdo->prepare(
                'INSERT INTO polit_prisoner_source(id, last_import) VALUES (:id, :last_import)'
                . ' ON CONFLICT ON CONSTRAINT polit_prisoner_source_pk'
                . ' DO UPDATE SET last_import=EXCLUDED.last_import'
            );

            foreach ($sources as $id) {
                $stmt->execute(['id' => $id, 'last_import' => $now]);
            }

            $stmt = null;

            if (!empty($fields)) {
                $stmt = $pdo->prepare(
                    'INSERT INTO polit_prisoner_field(id, name)'
                    . ' VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM polit_prisoner_field), :name)'
                    . ' ON CONFLICT ON CONSTRAINT polit_prisoner_field_uk_name'
                    . ' DO UPDATE SET name=EXCLUDED.name RETURNING id'
                );

                foreach ($fields as $field => &$id) {
                    $stmt->execute(['name' => $field]);
                    $id = $stmt->fetchColumn();
                }
                unset($id);

                $stmt = null;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO polit_prisoner(id, name, born, source, last_seen)'
                . ' VALUES ((SELECT COALESCE(MAX(id), 0) + 1 FROM polit_prisoner), :name, :born, :source, :last_seen)'
                . ' ON CONFLICT ON CONSTRAINT polit_prisoner_uk_name'
                . ' DO UPDATE SET born=EXCLUDED.born, source=EXCLUDED.source, last_seen=EXCLUDED.last_seen RETURNING id'
            );

            foreach ($pps as $pp) {
                $stmt->execute([
                    'name' => $pp->name,
                    'born' => $pp->born === null ? null : $pp->born->format('Y-m-d'),
                    'source' => $pp->source,
                    'last_seen' => $now
                ]);

                $pp->id = $stmt->fetchColumn();
            }

            $stmt = null;

            $stmt = $pdo->prepare(
                'INSERT INTO polit_prisoner_attr(polit_prisoner, field, value, last_seen)'
                . ' VALUES (:polit_prisoner, :field, :value, :last_seen) ON CONFLICT ON CONSTRAINT'
                . ' polit_prisoner_attr_pk DO UPDATE SET value=EXCLUDED.value, last_seen=EXCLUDED.last_seen'
            );

            foreach ($pps as $pp) {
                foreach ($pp->info as $field => $value) {
                    $stmt->execute([
                        'polit_prisoner' => $pp->id,
                        'field' => $fields[$field],
                        'value' => $value,
                        'last_seen' => $now
                    ]);
                }
            }

            $stmt = null;
        });
    }

    private function notifyUpcomingBirthdays(): void
    {
        $notifications = $this->Config()->getSection('notifications');

        if (!($notifications->email && $notifications->birthday_leadtime)) {
            return;
        }

        $birthday = new DateTime;
        $days = (int)$notifications->birthday_leadtime;

        if ($days > 0) {
            $birthday->add(new DateInterval("P${days}D"));
        } elseif ($days < 0) {
            $days = 0 - $days;
            $birthday->add(new DateInterval("P${days}D"));
        }

        $query = Db::getPdo()->prepare(
            'SELECT name FROM polit_prisoner pp'
            . ' WHERE born_month=:born_month AND born_dom=:born_dom'
            . ' AND last_seen=(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source)'
            . ' ORDER BY name'
        );

        $query->execute([
            'born_month' => (int)$birthday->format('m'),
            'born_dom' => (int)$birthday->format('d')
        ]);

        $query->setFetchMode(PDO::FETCH_COLUMN, 0);

        foreach ($query as $name) {
            mail($notifications->email, 'BIRTHDAY ' . $birthday->format('Y-m-d') . " $name", '');
        }
    }

    private function notifyNotPatternCovered(): void
    {
        $notifications = $this->Config()->getSection('notifications');

        if (!$notifications->email) {
            return;
        }

        $searches = NamePatterns::create()->select(['search'])->fetchColumn();

        foreach ($this->queryNotVanishedPolitPrisonerNames() as $politPrisoner) {
            foreach ($searches as $search) {
                if (preg_match($search, $politPrisoner)) {
                    continue 2;
                }
            }

            mail($notifications->email, "NOT PATTERN COVERED $politPrisoner", '');
        }
    }

    private function updateGt2db(): void
    {
        $resource = Config::module('manufaktura_elfov')->get('backend', 'gt2db');

        if ($resource === null) {
            throw new ConfigurationError('gt2db database not configured');
        }

        $searches = NamePatterns::create()->select(['search', 'replace'])->order('display_name')->fetchPairs();

        /** @var PDO $pdo */
        $pdo = ResourceFactory::create($resource)->getDbAdapter()->getConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO keyword(keyword) VALUES (:keyword) ON CONFLICT ON CONSTRAINT keyword_uk_keyword DO NOTHING'
        );

        foreach ($this->queryNotVanishedPolitPrisonerNames() as $politPrisoner) {
            foreach ($searches as $search => $replace) {
                $count = 0;
                $replaced = preg_replace($search, $replace, $politPrisoner, -1, $count);

                if ($count) {
                    $stmt->execute(['keyword' => $replaced]);
                    continue 2;
                }
            }
        }
    }

    private function queryNotVanishedPolitPrisonerNames(): PDOStatement
    {
        $politPrisoners = Db::getPdo()->prepare(
            'SELECT name FROM polit_prisoner pp'
            . ' WHERE last_seen=(SELECT last_import FROM polit_prisoner_source WHERE id=pp.source) ORDER BY name'
        );

        $politPrisoners->execute();
        $politPrisoners->setFetchMode(PDO::FETCH_COLUMN, 0);

        return $politPrisoners;
    }
}
