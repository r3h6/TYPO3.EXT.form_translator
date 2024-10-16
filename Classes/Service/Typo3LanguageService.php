<?php

declare(strict_types=1);

namespace R3H6\FormTranslator\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use R3H6\FormTranslator\Event\FinalizeTypo3LanguagesEvent;
use R3H6\FormTranslator\Translation\Dto\Typo3Language;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3LanguageService
{
    public function __construct(
        private readonly Locales $locales,
        private readonly SiteFinder $siteFinder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $typo3LanguageWhitelist,
    ) {}

    /**
     * @return Typo3Language[]
     */
    public function findAll(): array
    {
        $siteLanguages = $this->getAllSiteLanguages();
        $siteLanguages = $this->getAllowdLanguages($siteLanguages);
        $languages = $this->getTypo3Languages($siteLanguages);
        $languages = $this->normalizeLanguages($languages);

        $event = new FinalizeTypo3LanguagesEvent(array_values($languages));
        $this->eventDispatcher->dispatch($event);
        return $event->getLanguages();
    }

    public function find(string $typo3Language): ?Typo3Language
    {
        $languages = $this->findAll();
        foreach ($languages as $language) {
            if ($language->getTypo3Language() === $typo3Language) {
                return $language;
            }
        }

        return null;
    }

    /** @return array<int, SiteLanguage> */
    private function getAllSiteLanguages(): array
    {
        $siteLanguages = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $languageId => $siteLanguage) {
                if ($siteLanguage->isEnabled() && !isset($siteLanguages[$languageId])) {
                    $siteLanguages[$languageId] = $siteLanguage;
                }
            }
        }
        return $siteLanguages;
    }

    /** @return array<int, SiteLanguage> */
    private function getAllowdLanguages(array $siteLanguages): array
    {
        $allowedLanguages = GeneralUtility::intExplode(',', $this->getBackendUser()->groupData['allowed_languages'] ?? '', true);
        if ($allowedLanguages) {
            return array_intersect_key($siteLanguages, array_flip($allowedLanguages));
        }
        return $siteLanguages;
    }

    /** @return array<string, Typo3Language> */
    private function getTypo3Languages(array $siteLanguages): array
    {
        $languages = [];
        foreach ($siteLanguages as $siteLanguage) {
            $language = new \R3H6\FormTranslator\Translation\Dto\Typo3Language(
                $siteLanguage->getTypo3Language(),
                $siteLanguage->getTitle(),
                $siteLanguage->getFlagIdentifier(),
            );
            $languages[$language->getTypo3Language()] = $language;
        }
        return $languages;
    }

    private function normalizeLanguages(array $languages): array
    {
        $normalizedLanguages = [];
        foreach ($languages as $language) {
            $normalizedLanguage = $this->normalizeLanguage($language);
            $isPriorityLanguage = strtolower($language->getLanguageCode()) === strtolower($language->getCountryCode());
            if ($normalizedLanguage && (!isset($normalizedLanguages[$normalizedLanguage->getTypo3Language()]) || $isPriorityLanguage)) {
                $normalizedLanguages[$normalizedLanguage->getTypo3Language()] = $normalizedLanguage;
            }
        }
        return $normalizedLanguages;
    }

    private function normalizeLanguage(Typo3Language $language): ?Typo3Language
    {
        if (in_array($language->getTypo3Language(), $this->locales->getActiveLanguages())) {
            return $language;
        }
        if (GeneralUtility::inList($this->typo3LanguageWhitelist, $language->getTypo3Language())) {
            return $language;
        }
        $languageCode = $language->getLanguageCode();
        $languageCode = $languageCode === 'en' ? 'default' : $languageCode;
        if (in_array($languageCode, $this->locales->getActiveLanguages())) {
            return new Typo3Language(
                $languageCode,
                $this->locales->getLanguages()[$languageCode],
                $language->getFlagIdentifier(),
            );
        }
        return null;
    }

    private function getBackendUser(): \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
