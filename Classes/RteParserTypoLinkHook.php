<?php
namespace CMSExperts\Link2Language;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
