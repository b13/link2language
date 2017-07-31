<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['removeParams_PostProc']['link2language'] = \CMSExperts\Link2language\RteParserTypoLinkHook::class;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    'TCEMAIN.linkHandler.page.handler = CMSExperts\\Link2language\\LinkHandler\\PageLinkHandler'
);