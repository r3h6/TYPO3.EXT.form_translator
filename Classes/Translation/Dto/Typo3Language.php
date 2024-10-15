<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Translation\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Typo3Language
{
    public function __construct(
        private readonly string $typo3Language,
        private readonly string $title,
        private readonly string $flagIdentifier,
    ) {}

    public function getTypo3Language(): string
    {
        return $this->typo3Language;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFlagIdentifier(): string
    {
        return $this->flagIdentifier;
    }

    public function getLanguageCode(): string
    {
        if ($this->typo3Language === 'default') {
            return 'en';
        }
        return GeneralUtility::trimExplode('_', $this->typo3Language, true)[0];
    }

    public function getCountryCode(): ?string
    {
        if ($this->typo3Language === 'default') {
            return null;
        }
        return GeneralUtility::trimExplode('_', $this->typo3Language, true)[1] ?? null;
    }
}
