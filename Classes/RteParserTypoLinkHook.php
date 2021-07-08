<?php
namespace B13\Link2Language;

/*
 * This file is part of TYPO3 CMS-based extension "link2language" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use TYPO3\CMS\Core\Html\RteHtmlParser;

/**
 * Hook to remove the attribute "language"
 */
class RteParserTypoLinkHook
{
    /**
     * Removes "language" attribute so the typolink link can be built
     *
     * @param array $parameters
     * @param RteHtmlParser $parserObject
     * @return array
     */
    public function removeParams(&$parameters, $parserObject)
    {
        if (isset($parameters['aTagParams']['language'])) {
            unset($parameters['aTagParams']['language']);
        }
        return $parameters['aTagParams'];
    }
}
