<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Links to specific languages',
	'description' => 'Allows to select links to pages for a specific language',
	'category' => 'be',
	'version' => '1.1.0',
	'state' => 'beta',
	'clearcacheonload' => 1,
	'author' => 'b13 GmbH',
	'author_email' => 'typo3@b13.com',
	'author_company' => 'b13 GmbH',
	'constraints' => array(
		'depends' => array(
			'typo3' => '8.7.0-10.4.99',
			'recordlist' => '8.7.0-10.4.99',
		),
	),
);
