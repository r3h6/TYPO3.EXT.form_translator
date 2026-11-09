<?php

declare(strict_types=1);

defined('TYPO3') or die();

if (version_compare(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version(), '14.0.0', '<')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1728934991280]
        = \R3H6\FormTranslator\Hooks\RegisterTranslationFile::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1728934991281]
        = \R3H6\FormTranslator\Hooks\TranslateValidationErrorMessages::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'][1728934991281]
        = \R3H6\FormTranslator\Hooks\RemoveFormTranslations::class;
}
