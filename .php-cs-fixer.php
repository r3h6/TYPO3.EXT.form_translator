<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()->in(__DIR__)->exclude([
    '.Build',
    'config/system',
    'Resources/Private/Php'
]);

return $config;
