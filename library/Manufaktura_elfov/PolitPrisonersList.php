<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Generator;

abstract class PolitPrisonersList
{
    private string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return PolitPrisoner[]
     */
    abstract public function fetchAll(): Generator;
}
