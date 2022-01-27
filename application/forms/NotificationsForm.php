<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Icinga\Forms\ConfigForm;

class NotificationsForm extends ConfigForm
{
    public function init(): void
    {
        $this->setTitle($this->translate('Notifications'));
        $this->setSubmitLabel($this->translate('Save'));
    }

    public function createElements(array $formData): void
    {
        $this->addElement('text', 'notifications_email', [
            'label' => $this->translate('Recipient'),
            'description' => $this->translate('eMail address')
        ]);

        $this->addElement('number', 'notifications_birthday_leadtime', [
            'label' => $this->translate('Birthday lead time'),
            'description' => $this->translate('In days')
        ]);
    }
}
