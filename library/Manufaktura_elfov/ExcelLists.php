<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Data\ConfigObject;

class ExcelLists extends IniRepo
{
    protected $queryColumns = ['excel_list' => ['uuid', 'display_name', 'url', 'name_column', 'born_column']];

    protected $triggers = ['excel_list'];

    public static function create(): self
    {
        return static::createRepo('excel_lists');
    }

    protected function onInsertExcelList(ConfigObject $new): ConfigObject
    {
        return $this->onInsertRow($new);
    }
}
