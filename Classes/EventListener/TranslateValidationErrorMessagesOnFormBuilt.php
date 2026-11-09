<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\EventListener;

use R3H6\FormTranslator\Hooks\TranslateValidationErrorMessages;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;

#[AsEventListener(after: RegisterTranslationFileOnFormBuilt::class)]
final readonly class TranslateValidationErrorMessagesOnFormBuilt
{
    public function __construct(private TranslateValidationErrorMessages $translateValidationErrorMessages) {}

    public function __invoke(AfterFormIsBuiltEvent $event): void
    {
        foreach ($event->form->getRenderablesRecursively() as $renderable) {
            if ($renderable instanceof AbstractFormElement) {
                $this->translateValidationErrorMessages->translateRenderable($renderable);
            }
        }
    }
}
