<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Form Translator',
    'description' => 'Provides a backend module and cli for translating forms.',
    'category' => 'module',
    'author' => 'R3 H6',
    'author_email' => 'r3h6@outlook.com',
    'state' => 'beta',
    'version' => '3.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'form' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
