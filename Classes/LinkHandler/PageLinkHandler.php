<?php
namespace CMSExperts\Link2Language\LinkHandler;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Link handler for page (and content) links
 * with an additional option to show a dropdown for the selected language
 */
class PageLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\PageLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * Checks if this is the handler for the given link, and also checks for a language parameter
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!$linkParts['url']) {
            return false;
        }

        $id = $linkParts['url'];
        $language = '';
        $parts = explode('#', $id);
        if (count($parts) > 1) {
            $id = $parts[0];
            $anchor = $parts[1];
        } else {
            $anchor = '';
        }
        if (strpos($id, '&L=') !== false) {
            list($id, $language) = explode('&L=' , $id);
        }
        // Checking if the id-parameter is an alias.
        if (!MathUtility::canBeInterpretedAsInteger($id)) {
            $records = BackendUtility::getRecordsByField('pages', 'alias', $id);
            if (empty($records)) {
                return false;
            }
            $id = (int)$records[0]['uid'];
        }
        $pageRow = BackendUtility::getRecordWSOL('pages', $id);
        if (!$pageRow) {
            return false;
        }

        $this->linkParts = $linkParts;
        $this->linkParts['pageid'] = $id;
        if ($language !== '') {
            $this->linkParts['language'] = (int)$language;
        }
        $this->linkParts['anchor'] = $anchor;

        return true;
    }

    /**
     * Add the JS module as well
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/PageLinkHandler');
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Link2language/PageLinkHandler');
        return parent::render($request);
    }

    /**
     * Add the language part to the body tag attributes as well
     *
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        $bodyTagAttributes = parent::getBodyTagAttributes();
        $bodyTagAttributes['data-language'] = $this->linkParts['language'];
        return $bodyTagAttributes;
    }

    /**
     * Allow to add the language selector as link attribute as well
     *
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        $fieldDefinitions = parent::modifyLinkAttributes($fieldDefinitions);
        $fieldDefinitions = $this->addLanguageSelector($fieldDefinitions);
        return $fieldDefinitions;
    }

    /**
     * Add the language selector to the settings
     *
     * @param $fieldDefinitions
     * @return mixed
     */
    protected function addLanguageSelector($fieldDefinitions)
    {
        array_push($this->linkAttributes, 'language');
        $languages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, title', 'sys_language', 'hidden=0', '', 'uid');
        $options = ['<option value=""></option>'];

        $options[] = '<option value="0"' . ($this->linkParts['language'] === 0 ? ' selected="selected"' : '') . '>Default Language</option>';
        foreach ($languages as $language) {
            $options[] = '<option value="' . $language['uid'] . '"' . ($this->linkParts['language'] === (int)$language['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($language['title']) . '</option>';
        }

        $fieldDefinitions['language'] = '
				<form action="" name="llanguageform" id="llanguageform" class="t3js-dummyform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkClass">
						<tr>
							<td style="width: 120px;">Specific Language</td>
							<td><select name="llanguage" class="typo3-link-input">' . implode(CRLF, $options) . '</select></td>
						</tr>
					</table>
				</form>
';
        return $fieldDefinitions;
    }
}
