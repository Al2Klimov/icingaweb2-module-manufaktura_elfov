<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Icinga\Data\Filter\Filter;
use Icinga\Forms\RepositoryForm;

abstract class RepoForm extends RepositoryForm
{
    protected function createInsertElements(array $_): void
    {
        $this->addElement('text', 'display_name', [
            'label' => $this->translate('Name'),
            'required' => true
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
