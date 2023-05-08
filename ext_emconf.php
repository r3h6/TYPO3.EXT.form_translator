<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form Translator',
    'description' => 'Provides a backend module and cli for translating forms.',
    'category' => 'module',
    'author' => 'R3 H6',
    'author_email' => 'r3h6@outlook.com',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'version' => '1.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
            'form' => '10.4.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
