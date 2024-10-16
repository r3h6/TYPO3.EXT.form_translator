<?php

declare(strict_types=1);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1728934991281]
    = \R3H6\FormTranslator\Hooks\TranslateValidationErrorMessages::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'][1728934991281]
    = \R3H6\FormTranslator\Hooks\DeleteTranslationFile::class;
