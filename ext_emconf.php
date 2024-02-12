<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Links to specific languages',
    'description' => 'Allows to select links to pages or content elements for a specific language',
    'category' => 'be',
    'version' => '3.0.0',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'b13 GmbH',
    'author_email' => 'typo3@b13.com',
    'author_company' => 'b13 GmbH',
    'constraints' => array(
        'depends' => array(
            'typo3' => '11.5.0-12.4.99',
        ),
    ),
);
