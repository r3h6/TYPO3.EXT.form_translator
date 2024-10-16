<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Event;

use R3H6\FormTranslator\Translation\Dto\Typo3Language;

final class FinalizeTypo3LanguagesEvent
{
    public function __construct(
        /** @var Typo3Language[] */
        private array $languages,
    ) {}

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setLanguages(array $languages): void
    {
        $this->languages = $languages;
    }
}
