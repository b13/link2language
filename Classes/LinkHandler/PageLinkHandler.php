<?php
namespace B13\Link2Language\LinkHandler;

/*
 * This file is part of TYPO3 CMS-based extension "link2language" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;

/**
 * Link handler for page (and content) links
 * with an additional option to show a dropdown for the selected language
 */
class PageLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\PageLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{

    /**
     * Initialize the handler
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        parent::initialize($linkBrowser, $identifier, $configuration);
        $this->view->setTemplateRootPaths([200 => 'EXT:link2language/Resources/Private/Templates/LinkBrowser']);
    }

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
        foreach ($languages as $language) {
            $options[] = '<option value="' . $language->getLanguageId() . '"' . ($this->linkParts['language'] === $language->getLanguageId() ? ' selected="selected"' : '') . '>' . htmlspecialchars($language->getTitle()) . '</option>';
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
     * @return SiteLanguage[]
     */
    protected function getAllLanguages()
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->linkParts['url']['pageuid'] ?? 0);
        return $site->getAvailableLanguages($this->getBackendUser());
    }

    /**
     * This adds all content elements on a page to the view and lets you create a link to the element.
     *
     * @param int $pageId Page uid to expand
     */
    protected function getRecordsOnExpandedPage($pageId)
    {
        // If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
        if (!$pageId && isset($this->linkParts['url']['fragment']) && isset($this->linkParts['url']['fragment'])) {
            // Set to the current link page id.
            $pageId = $this->linkParts['url']['pageuid'];
        }
        // Draw the record list IF there is a page id to expand:
        if ($pageId && MathUtility::canBeInterpretedAsInteger($pageId) && $this->getBackendUser()->isInWebMount($pageId)) {
            $pageId = (int)$pageId;

            $activePageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
            $this->view->assign('expandActivePage', true);

            // Create header for listing, showing the page title/icon
            $this->view->assign('activePage', $activePageRecord);
            $this->view->assign('activePageTitle', BackendUtility::getRecordTitle('pages', $activePageRecord, true));
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, Icon::SIZE_SMALL)->render());

            // Look up tt_content elements from the expanded page
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $contentElements = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in(
                                'sys_language_uid',
                                $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY)
                            ),
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->gt(
                                    'sys_language_uid',
                                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    'l18n_parent',
                                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                                )
                            )
                        )
                    )
                )
                ->orderBy('colPos')
                ->addOrderBy('sorting')
                ->execute()
                ->fetchAll();

            $colPosArray = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getColPosListItemsParsed', $pageId, $this);
            $languages = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($pageId);

            $colPosMapping = [];
            foreach ($colPosArray as $colPos) {
                $colPosMapping[(int)$colPos[1]] = $colPos[0];
            }
            // Enrich list of records
            $groupedContentElements = [];
            foreach ($contentElements as &$contentElement) {
                $languageId = (int)$contentElement['sys_language_uid'];
                if (!isset($groupedContentElements[$languageId])) {
                    $groupedContentElements[$languageId] = [
                        'label' => $languages[$languageId]['title'],
                        'flag' => $this->iconFactory->getIcon($languages[$languageId]['flagIcon'], Icon::SIZE_SMALL),
                        'items' => []
                    ];
                }

                $colPos = (int)$contentElement['colPos'];
                if (!isset($groupedContentElements[$languageId]['items'][$colPos])) {
                    $groupedContentElements[$languageId]['items'][$colPos] = [
                        'label' => $colPosMapping[(int)$contentElement['colPos']],
                        'items' => []
                    ];
                }

                if ($languageId > 0) {
                    $contentElement['url'] = GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_PAGE, 'parameters' => '&L=' . $languageId, 'pageuid' => (int)$pageId, 'fragment' => $contentElement['uid']]);
                } else {
                    $contentElement['url'] = GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => (int)$pageId, 'fragment' => $contentElement['uid']]);
                }
                $contentElement['isSelected'] = !empty($this->linkParts) && (int)$this->linkParts['url']['fragment'] === (int)$contentElement['uid'];
                $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, Icon::SIZE_SMALL)->render();
                $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
                $groupedContentElements[$languageId]['items'][$colPos]['items'][] = $contentElement;
            }
            ksort($groupedContentElements);
            $this->view->assign('contentElements', $contentElements);
            $this->view->assign('groupedContentElements', $groupedContentElements);
        }
    }

}
