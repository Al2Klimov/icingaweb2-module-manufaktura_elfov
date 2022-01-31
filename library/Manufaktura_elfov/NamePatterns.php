<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Data\ConfigObject;

class NamePatterns extends IniRepo
{
    protected $queryColumns = ['name_pattern' => ['uuid', 'display_name', 'search', 'replace']];

    protected $triggers = ['name_pattern'];

    public static function create(): self
    {
        return static::createRepo('name_patterns');
    }

    protected function onInsertNamePattern(ConfigObject $new): ConfigObject
    {
        return $this->onInsertRow($new);
    }
}
