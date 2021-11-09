<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

class InfoLinkForm extends RepoForm
{
    protected function createInsertElements(array $formData): void
    {
        parent::createInsertElements($formData);

        $this->addElement('text', 'url', [
            'label' => $this->translate('URL'),
            'required' => true,
            'validators' => [[
                'validator' => 'regex',
                'options' => ['pattern' => '~^https?://~']
            ]]
        ]);
    }
}
