<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Clicommands;

use DateTime;
use Icinga\Cli\Command;
use Icinga\Data\Db\DbConnection;
use Icinga\Module\Manufaktura_elfov\Db;
use Icinga\Module\Manufaktura_elfov\ExcelList;
use Icinga\Module\Manufaktura_elfov\ExcelLists;
use Icinga\Module\Manufaktura_elfov\PolitPrisoner;

class CronCommand extends Command
{
    public function dailyAction(): void
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
            /** @var \PDO $pdo */
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
                    'INSERT INTO polit_prisoner_field(name) VALUES (:name)'
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
                'INSERT INTO polit_prisoner(name, born, source, last_seen) VALUES (:name, :born, :source, :last_seen)'
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
}
