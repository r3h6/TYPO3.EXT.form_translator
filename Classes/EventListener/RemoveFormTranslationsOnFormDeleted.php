<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\EventListener;

use R3H6\FormTranslator\Hooks\RemoveFormTranslations;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent;

#[AsEventListener]
final readonly class RemoveFormTranslationsOnFormDeleted
{
    public function __construct(private RemoveFormTranslations $removeFormTranslations) {}

    public function __invoke(BeforeFormIsDeletedEvent $event): void
    {
        $this->removeFormTranslations->remove($event->formPersistenceIdentifier);
    }
}
