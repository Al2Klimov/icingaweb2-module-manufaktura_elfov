<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Exception;
use Icinga\Application\Config;
use Icinga\Data\Db\DbConnection;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use PDOException;

class Db
{
    private static ?DbConnection $db = null;

    public static function get(): DbConnection
    {
        if (self::$db === null) {
            $resource = Config::module('manufaktura_elfov')->get('backend', 'resource');

            if ($resource === null) {
                throw new ConfigurationError('Database not configured');
            }

            $db = ResourceFactory::create($resource);

            /** @var \PDO $pdo */
            $pdo = $db->getDbAdapter()->getConnection();

            $pdo->exec('SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            self::$db = $db;
        }

        return self::$db;
    }

    public static function tx(callable $do): void
    {
        $dba = self::get()->getDbAdapter();

        for (; ;) {
            $dba->beginTransaction();

            $ret = null;

            $success = self::handleSerializationFailure(function () use ($do, &$ret): void {
                $ret = call_user_func($do, self::get());
            });

            if (!$success) {
                continue;
            }

            if ($ret === false) {
                $dba->rollBack();
            } else if (!self::handleSerializationFailure([$dba, 'commit'])) {
                continue;
            }

            break;
        }
    }

    private static function handleSerializationFailure(callable $do): bool
    {
        try {
            call_user_func($do);
        } catch (Exception $e) {
            try {
                self::get()->getDbAdapter()->rollBack();
            } catch (Exception $_) {
            }

            if ($e instanceof PDOException && $e->errorInfo[0] == 40001) {
                return false;
            }

            throw $e;
        }

        return true;
    }
}
