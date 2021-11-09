<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use Icinga\Module\Manufaktura_elfov\Db;
use Icinga\Web\Form;

class AwarenessForm extends Form
{
    private ?int $politPrisoner;
    private ?int $awarenessScore;

    public function init(): void
    {
        $this->setSubmitLabel($this->translate('Save'));
    }

    public function createElements(array $formData): void
    {
        $this->addElement('checkbox', 'unknown', [
            'label' => $this->translate('Not sure'),
            'value' => $this->awarenessScore === null,
            'autosubmit' => true
        ]);

        if ($formData['unknown'] ?? $this->awarenessScore === null) {
            $this->addElement('hidden', 'score', []);
        } else {
            $this->addElement('number', 'score', [
                'label' => $this->translate('Score'),
                'value' => $this->awarenessScore,
                'required' => true
            ]);

            $this->addElement(
                'note',
                'notice',
                ['value' => $this->getView()->escape($this->translate('1 = John Doe, 10 = Alexey Navalny'))]
            );
        }
    }

    public function onSuccess(): void
    {
        $stmt = Db::getPdo()->prepare('UPDATE polit_prisoner SET awareness=:awareness WHERE id=:id');

        $stmt->execute([
            'awareness' => $this->getValue('unknown') ? null : (int)$this->getValue('score'),
            'id' => $this->politPrisoner
        ]);
    }

    public function setPolitPrisoner(int $politPrisoner): AwarenessForm
    {
        $this->politPrisoner = $politPrisoner;
        return $this;
    }

    public function setAwarenessScore(?int $awarenessScore): AwarenessForm
    {
        $this->awarenessScore = $awarenessScore;
        return $this;
    }
}
