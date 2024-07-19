<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form Translator',
    'description' => 'Provides a backend module and cli for translating forms.',
    'category' => 'module',
    'author' => 'R3 H6',
    'author_email' => 'r3h6@outlook.com',
    'state' => 'beta',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
            'form' => '11.5.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
