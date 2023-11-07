<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Links to specific languages',
	'description' => 'Allows to select links to pages or content elements for a specific language',
	'category' => 'be',
	'version' => '3.0.0',
	'state' => 'alpha',
	'clearcacheonload' => 1,
	'author' => 'b13 GmbH',
	'author_email' => 'typo3@b13.com',
	'author_company' => 'b13 GmbH',
	'constraints' => array(
		'depends' => array(
			'typo3' => '10.4.0-12.5.99',
			'recordlist' => '11.5.99-12.5.99',
		),
	),
);
