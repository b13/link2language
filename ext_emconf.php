<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Links to specific languages',
	'description' => 'Allows to select links to pages or content elements for a specific language',
	'category' => 'be',
	'version' => '2.0.1',
	'state' => 'stable',
	'clearcacheonload' => 1,
	'author' => 'b13 GmbH',
	'author_email' => 'typo3@b13.com',
	'author_company' => 'b13 GmbH',
	'constraints' => array(
		'depends' => array(
			'typo3' => '9.5.0-11.5.99',
			'recordlist' => '9.5.0-11.5.99',
		),
	),
);
