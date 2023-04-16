<?php

use R3H6\FormTranslator\Controller\FormController;

return [
    'web_FormTranslator' => [
        'parent' => 'web',
        'position' => ['after' => 'web_FormFormbuilder'],
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-web-formtranslator',
        'inheritNavigationComponentFromMainModule' => false,
        'path' => '/module/web/FormTranslator',
        'labels' => 'LLL:EXT:form_translator/Resources/Private/Language/locallang_translator.xlf',
        'extensionName' => 'FormTranslator',
        'controllerActions' => [
            FormController::class => [
                'index',
                'localize',
                'save',
            ],
        ],
    ],
];
