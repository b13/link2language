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

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $res = parent::canHandleLink($linkParts);

        // Extract the language parameter
        if (isset($linkParts['url']['parameters'])) {
            $parameters = GeneralUtility::explodeUrl2Array($linkParts['url']['parameters']);
            if (isset($parameters['L'])) {
                $language = (int)$parameters['L'];
                unset($parameters['L']);
                $linkParts['url']['parameters'] = GeneralUtility::implodeArrayForUrl('', $parameters);
                $this->linkParts['language'] = (int)$language;
            }
        }
        return $res;
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
     * @return array
     */
    protected function addLanguageSelector($fieldDefinitions)
    {
        array_push($this->linkAttributes, 'language');
        $languages = $this->getAllLanguages();
        $options = ['<option value=""></option>'];

        $options[] = '<option value="0"' . ($this->linkParts['language'] === 0 ? ' selected="selected"' : '') . '>Default Language</option>';
        foreach ($languages as $language) {
            $options[] = '<option value="' . $language['uid'] . '"' . ($this->linkParts['language'] === (int)$language['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($language['title']) . '</option>';
        }

        $fieldDefinitions['language'] = '
				<form action="" name="llanguageform" id="llanguageform" class="t3js-dummyform form-horizontal">
				<div class="form-group form-group-sm">
				    <label class="col-xs-4 control-label">Specific Language</label> 
                    <div class="col-xs-8">
                        <select name="llanguage" class="form-control">' . implode(CRLF, $options) . '</select>
                    </div>
                </div>
				</form>
';
        return $fieldDefinitions;
    }

    /**
     * Short-hand function to select all registered languages
     *
     * @return array
     */
    protected function getAllLanguages()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        return $queryBuilder
            ->select('uid', 'title')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('hidden', 0)
            )
            ->execute()
            ->fetchAll();
    }
}
