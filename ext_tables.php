<?php

defined('TYPO3') || die();

(static function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'FormTranslator',
        'web',
        'translator',
        'after:FormFormbuilder',
        [
            \R3H6\FormTranslator\Controller\FormController::class => 'index, localize, save',

        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:form_translator/Resources/Public/Icons/user_mod_translator.svg',
            'labels' => 'LLL:EXT:form_translator/Resources/Private/Language/locallang_translator.xlf',
            'navigationComponentId' => '',
            'inheritNavigationComponentFromMainModule' => false,
        ]
    );
})();
