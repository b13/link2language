<?php
defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= chr(10) . 'TCEMAIN.linkHandler.page.handler = B13\\Link2Language\\LinkHandler\\PageLinkHandler';
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] .= chr(10) . 'templates.typo3/cms-backend.1643293191 = b13/link2language:Resources/Private/TemplateOverrides';