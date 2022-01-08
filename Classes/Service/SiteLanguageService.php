<?php

namespace R3H6\FormTranslator\Service;

use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SiteLanguageService
{
    public function findAll(): array
    {
        $siteLanguages = [];
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $languageId => $language) {
                if (!isset($siteLanguages[$languageId])) {
                    $siteLanguages[$languageId] = $language;
                }
            }
        }
        return $siteLanguages;
    }
}
