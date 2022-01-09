<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\R3H6\FormTranslator\Property\TypeConverters\SiteLanguageConverter::class);
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\R3H6\FormTranslator\Property\TypeConverters\ItemCollectionConverter::class);

    // \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
    //     plugin.tx_form.settings.yamlConfigurations.100 = EXT:form_translator/Tests/Unit/Fixtures/Yaml/BaseSetup.yaml
    //     module.tx_form.settings.yamlConfigurations.100 = EXT:form_translator/Tests/Unit/Fixtures/Yaml/BaseSetup.yaml
    // ');
})();
