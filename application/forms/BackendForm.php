<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm;

class BackendForm extends ConfigForm
{
    public function init(): void
    {
        $this->setTitle($this->translate('Databases'));
        $this->setSubmitLabel($this->translate('Save'));
    }

    public function createElements(array $formData): void
    {
        $postgres = ['' => ''];

        foreach (ResourceFactory::getResourceConfigs() as $name => $config) {
            if ($config->type === 'db' && $config->db === 'pgsql') {
                $postgres[$name] = $name;
            }
        }

        ksort($postgres);

        $this->addElement('select', 'backend_resource', [
            'label' => $this->translate('Database'),
            'description' => $this->translate('Must be a PostgreSQL one'),
            'required' => true,
            'multiOptions' => $postgres
        ]);

        $this->addElement('select', 'backend_gt2db', [
            'label' => $this->translate('gt2db database'),
            'description' => $this->translate('Must be a PostgreSQL one'),
            'required' => true,
            'multiOptions' => $postgres
        ]);
    }
}
