<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;

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
        foreach ($this->formService->getTranslations($targetLanguage) as $item) {
            if ($item->getSource() === $text) {
                return $item->getTarget();
            }
        }
        return '';
    }
}
