<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use Icinga\Web\View;
use Icinga\Web\Widget\Tabs;

trait CommonController
{
    private function setupTabs(View $view, Tabs $tabs, string $active): void
    {
        $view->tabs = $tabs->activate($active);
        $view->title = $tabs->get($active)->getLabel();
    }
}
