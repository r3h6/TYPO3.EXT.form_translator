<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;

interface TranslationServiceInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function translate(string $text, Typo3Language $targetLanguage): string;
}
