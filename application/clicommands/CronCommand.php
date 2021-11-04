<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Clicommands;

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
        /** @var PolitPrisoner[] $pps */
        $pps = [];

        foreach (ExcelLists::create()->select()->order('display_name') as $el) {
            foreach ((new ExcelList($el->uuid, $el->url, $el->name_column, $el->born_column))->fetchAll() as $pp) {
                $pps[] = $pp;
            }
        }

        $fields = [];

        foreach ($pps as $pp) {
            foreach ($pp->info as $field => $_) {
                $fields[$field] = null;
            }
        }

        Db::tx(function (DbConnection $db) use ($pps, &$fields) {
            /** @var \PDO $pdo */
            $pdo = $db->getDbAdapter()->getConnection();

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
                'INSERT INTO polit_prisoner(name, born, source) VALUES (:name, :born, :source)'
                . ' ON CONFLICT ON CONSTRAINT polit_prisoner_uk_name'
                . ' DO UPDATE SET born=EXCLUDED.born, source=EXCLUDED.source RETURNING id'
            );

            foreach ($pps as $pp) {
                $stmt->execute([
                    'name' => $pp->name,
                    'born' => $pp->born === null ? null : $pp->born->format('Y-m-d'),
                    'source' => $pp->source
                ]);

                $pp->id = $stmt->fetchColumn();
            }

            $stmt = null;

            $stmt = $pdo->prepare(
                'INSERT INTO polit_prisoner_attr(polit_prisoner, field, value) VALUES (:polit_prisoner, :field, :value)'
                . ' ON CONFLICT ON CONSTRAINT polit_prisoner_attr_pk DO UPDATE SET value=EXCLUDED.value'
            );

            foreach ($pps as $pp) {
                foreach ($pp->info as $field => $value) {
                    $stmt->execute([
                        'polit_prisoner' => $pp->id,
                        'field' => $fields[$field],
                        'value' => $value
                    ]);
                }
            }

            $stmt = null;
        });
    }
}
