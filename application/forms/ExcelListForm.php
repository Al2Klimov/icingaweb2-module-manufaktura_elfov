<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

class ExcelListForm extends RepositoryForm
{
    protected function createInsertElements(array $_): void
    {
        $this->addElement('text', 'display_name', [
            'label' => $this->translate('Name'),
            'required' => true
        ]);

        $this->addElement('text', 'url', [
            'label' => $this->translate('URL'),
            'required' => true,
            'validators' => [[
                'validator' => 'regex',
                'options' => ['pattern' => '~^https?://~']
            ]]
        ]);

        $this->addElement('text', 'name_column', [
            'label' => $this->translate('Name column'),
            'required' => true
        ]);

        $this->addElement('text', 'born_column', [
            'label' => $this->translate('Born column')
        ]);

        $this->setSubmitLabel($this->shouldInsert() ? $this->translate('Add') : $this->translate('Save'));
    }

    protected function createDeleteElements(array $_): void
    {
        $this->setSubmitLabel($this->translate('Confirm Removal'));
    }

    protected function createFilter(): Filter
    {
        return Filter::where('uuid', $this->getIdentifier());
    }

    protected function getInsertMessage($success): string
    {
        return $success ? $this->translate('OK') : $this->translate('Error');
    }

    protected function getUpdateMessage($success): string
    {
        return $success ? $this->translate('OK') : $this->translate('Error');
    }

    protected function getDeleteMessage($success): string
    {
        return $success ? $this->translate('OK') : $this->translate('Error');
    }
}
