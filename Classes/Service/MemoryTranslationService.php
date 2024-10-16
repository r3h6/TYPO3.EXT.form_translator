<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use R3H6\FormTranslator\Translation\ItemCollection;

class MemoryTranslationService implements TranslationServiceInterface
{
    public function __construct(protected readonly FormService $formService) {}

    public function getName(): string
    {
        return 'Memory';
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function translate(string $text, Typo3Language $targetLanguage): string
    {
        $forms = $this->formService->listForms();
        $items = new ItemCollection();
        foreach ($forms as $form) {
            $this->formService->getTranslation($items, $form['persistenceIdentifier'], $targetLanguage);
        }

        foreach ($items as $item) {
            if ($item->getSource() === $text) {
                return $item->getTarget();
            }
        }
        return '';
    }
}
