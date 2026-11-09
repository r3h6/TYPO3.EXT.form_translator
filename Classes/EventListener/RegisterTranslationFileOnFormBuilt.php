<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\EventListener;

use R3H6\FormTranslator\Hooks\RegisterTranslationFile;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;

#[AsEventListener]
final readonly class RegisterTranslationFileOnFormBuilt
{
    public function __construct(private RegisterTranslationFile $registerTranslationFile) {}

    public function __invoke(AfterFormIsBuiltEvent $event): void
    {
        $this->registerTranslationFile->register($event->form);
    }
}
