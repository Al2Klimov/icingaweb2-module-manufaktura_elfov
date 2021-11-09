<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Data\ConfigObject;

class InfoLinks extends IniRepo
{
    protected $queryColumns = ['info_link' => ['uuid', 'display_name', 'url']];

    protected $triggers = ['info_link'];

    public static function create(): self
    {
        return static::createRepo('info_links');
    }

    protected function onInsertInfoLink(ConfigObject $new): ConfigObject
    {
        return $this->onInsertRow($new);
    }
}
