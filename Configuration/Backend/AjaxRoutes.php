<?php

use R3H6\FormTranslator\Controller\TranslationController;
return [
    'formtranslator_translate' => [
        'path' => '/formtranslator/translate',
        'target' => TranslationController::class . '::translateAction',
    ],
];
