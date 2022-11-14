<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()->in(__DIR__)->exclude([
    '.Build',
    'Resources/Private/Php'
]);

return $config;
