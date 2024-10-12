<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

interface TranslationServiceInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function translate(string $text, SiteLanguage $targetLanguage): string;
}
