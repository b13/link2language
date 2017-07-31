<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Links to specific languages',
	'description' => 'Allows to select links to pages for a specific language',
	'category' => 'be',
	'version' => '0.1.1',
	'state' => 'beta',
	'clearcacheonload' => 1,
	'author' => 'b:dreizehn GmbH',
	'author_email' => 'typo3@b13.de',
	'author_company' => 'b:dreizehn GmbH',
	'constraints' => array(
		'depends' => array(
			'typo3' => '8.7.0-8.7.99',
			'recordlist' => '8.7.0-8.7.99',
		),
	),
);
