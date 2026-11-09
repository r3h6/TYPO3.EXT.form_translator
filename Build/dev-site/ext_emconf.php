<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Dev Site',
    'description' => 'Development distribution sitepackage for the form translator dev site.',
    'category' => 'distribution',
    'author' => 'R3 H6',
    'author_email' => 'r3h6@outlook.com',
    'state' => 'beta',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
            'form' => '13.4.0-14.3.99',
            'fluid_styled_content' => '13.4.0-14.3.99',
            'impexp' => '13.4.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
