<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Zend_Validate_Callback;

class NamePatternForm extends RepoForm
{
    protected function createInsertElements(array $formData): void
    {
        parent::createInsertElements($formData);

        $this->addElement('text', 'search', [
            'label' => $this->translate('Pattern'),
            'required' => true,
            'validators' => [new Zend_Validate_Callback(function (string $value): bool {
                return @preg_match($value, '') !== false;
            })]
        ]);

        $this->addElement('text', 'replace', [
            'label' => $this->translate('Replacement'),
            'required' => true
        ]);
    }
}
