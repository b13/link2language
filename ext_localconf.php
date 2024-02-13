<?php
defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['removeParams_PostProc']['link2language'] = \B13\Link2Language\RteParserTypoLinkHook::class;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'TCEMAIN.linkHandler.page.handler = B13\\Link2Language\\LinkHandler\\PageLinkHandler'
);
