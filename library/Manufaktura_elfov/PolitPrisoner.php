<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use DateTime;

class PolitPrisoner
{
    public ?int $id = null;
    public string $name;
    public ?DateTime $born = null;
    public string $source;
    public bool $vanished = false;
    public array $info = [];
}
