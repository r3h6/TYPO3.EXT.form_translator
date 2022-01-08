<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\R3H6\FormTranslator\Property\TypeConverters\SiteLanguageConverter::class);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\R3H6\FormTranslator\Property\TypeConverters\ItemCollectionConverter::class);
})();
