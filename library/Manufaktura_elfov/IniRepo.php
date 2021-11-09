<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Repository\IniRepository;

abstract class IniRepo extends IniRepository
{
    protected static function createRepo(string $configName): self
    {
        $ds = Config::module('manufaktura_elfov', $configName);
        $ds->getConfigObject()->setKeyColumn('uuid');

        return new static($ds);
    }

    protected function onInsertRow(ConfigObject $new): ConfigObject
    {
        if (!isset($new->uuid)) {
            $new->uuid = rtrim(file_get_contents('/proc/sys/kernel/random/uuid'));
        }

        return $new;
    }
}
