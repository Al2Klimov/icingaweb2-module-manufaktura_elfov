<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov\Forms;

use DateTime;
use Icinga\Data\Db\DbConnection;
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

        $this->addElement('textarea', 'comment', [
            'label' => $this->translate('Comment')
        ]);
    }

    public function onSuccess(): void
    {
        $now = (new DateTime)->format(DateTime::ISO8601);
        $politPrisoner = $this->politPrisoner;
        $awareness = $this->getValue('unknown') ? null : (int)$this->getValue('score');
        $comment = (string)$this->getValue('comment');

        Db::tx(function (DbConnection $db) use ($now, $politPrisoner, $awareness, $comment): void {
            /** @var \PDO $pdo */
            $pdo = $db->getDbAdapter()->getConnection();

            $pdo->prepare('UPDATE polit_prisoner SET awareness=:awareness WHERE id=:id')->execute([
                'awareness' => $awareness, 'id' => $politPrisoner
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO web_user(name) VALUES (:name) ON CONFLICT ON CONSTRAINT web_user_uk_name'
                . ' DO UPDATE SET name=EXCLUDED.name RETURNING id'
            );

            $stmt->execute(['name' => $this->Auth()->getUser()->getUsername()]);
            $webUser = $stmt->fetchColumn();
            $stmt = null;

            $pdo->prepare(
                'INSERT INTO polit_prisoner_awareness(polit_prisoner, edited, editor, awareness, comment)'
                . ' VALUES (:polit_prisoner, :edited, :editor, :awareness, :comment)'
            )->execute([
                'polit_prisoner' => $politPrisoner,
                'edited' => $now,
                'editor' => $webUser,
                'awareness' => $awareness,
                'comment' => $comment
            ]);
        });
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
