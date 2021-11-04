<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Repository\IniRepository;

class ExcelLists extends IniRepository
{
    protected $queryColumns = ['excel_list' => ['uuid', 'display_name', 'url', 'name_column', 'born_column']];

    protected $triggers = ['excel_list'];

    public static function create(): self
    {
        $ds = Config::module('manufaktura_elfov', 'excel_lists');
        $ds->getConfigObject()->setKeyColumn('uuid');

        return new static($ds);
    }

    protected function onInsertExcelList(ConfigObject $new): ConfigObject
    {
        if (!isset($new->uuid)) {
            $new->uuid = rtrim(file_get_contents('/proc/sys/kernel/random/uuid'));
        }

        return $new;
    }
}
