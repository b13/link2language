<?php
namespace B13\Link2Language\LinkHandler;

/*
 * This file is part of TYPO3 CMS-based extension "link2language" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
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

    public function render(ServerRequestInterface $request)
    {
        if ((new Typo3Version())->getMajorVersion() < 11) {
            $this->view->setTemplateRootPaths([200 => 'EXT:link2language/Resources/Private/Templates/LinkBrowser10']);
        } else {
            $this->view->setTemplateRootPaths([200 => 'EXT:link2language/Resources/Private/Templates/LinkBrowser']);
        }
        return parent::render($request);
    }

    /**
     * Short-hand function to select all registered languages
     *
     * @return SiteLanguage[]
     */
    protected function getAllLanguages($pageId = null)
    {
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId ?? $this->linkParts['url']['pageuid'] ?? 0);
            return $site->getAvailableLanguages($this->getBackendUser());
        } catch (SiteNotFoundException $e) {
            return [];
        }
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
        if (!$pageId && isset($this->linkParts['url']['pageuid'])) {
            // Set to the current link page id.
            $pageId = $this->linkParts['url']['pageuid'];
        }
        // Draw the record list IF there is a page id to expand:
        if ($pageId && MathUtility::canBeInterpretedAsInteger($pageId) && $this->getBackendUser()->isInWebMount($pageId)) {
            $pageId = (int)$pageId;

            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $activePageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
            $this->view->assign('expandActivePage', true);
            $languages = $this->getAllLanguages($pageId);
            $availableLanguages = [];
            $languageIdsToFindFreeModeItems = [];
            foreach ($languages as $language) {
                $availableLanguages[$language->getLanguageId()] = [
                    'title' => $language->getTitle(),
                    'flag' => $this->iconFactory->getIcon($language->getFlagIdentifier(), Icon::SIZE_SMALL),
                ];
                $availableLanguages[$language->getLanguageId()]['url'] = $linkService->asString(['type' => LinkService::TYPE_PAGE, 'parameters' => '&L=' . $language->getLanguageId(),'pageuid' => (int)$pageId]);
                if ($language->getLanguageId() > 0) {
                    $languageIdsToFindFreeModeItems[] = $language->getLanguageId();
                }
            }
            $this->view->assign('availableLanguages', $availableLanguages);

            // Create header for listing, showing the page title/icon
            $this->view->assign('activePage', $activePageRecord);
            if ($this->isPageLinkable($activePageRecord)) {
                $this->view->assign('activePageLink', $linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId]));
            }
            $this->view->assign('activePageTitle', BackendUtility::getRecordTitle('pages', $activePageRecord, true));
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, Icon::SIZE_SMALL)->render());

            // Look up tt_content elements from the expanded page
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $constraints = $queryBuilder->expr()->in(
                'sys_language_uid',
                $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY)
            );
            if (!empty($languageIdsToFindFreeModeItems)) {
                $constraints = $queryBuilder->expr()->orX(
                    $constraints,
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($languageIdsToFindFreeModeItems, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->eq(
                            'l18n_parent',
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        )
                    )
                );
            }
            $contentElements = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                        ),
                        $constraints
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
                        'label' => $colPosMapping[$colPos] ?? '',
                        'items' => []
                    ];
                }

                $contentElement['url'] = GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_PAGE, 'parameters' => '&L=' . $languageId, 'pageuid' => (int)$pageId, 'fragment' => $contentElement['uid']]);
                $contentElement['isSelected'] = !empty($this->linkParts) && (int)($this->linkParts['url']['fragment'] ?? 0) === (int)$contentElement['uid'];
                $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, Icon::SIZE_SMALL)->render();
                $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
                $groupedContentElements[$languageId]['items'][$colPos]['items'][] = $contentElement;
            }
            ksort($groupedContentElements);
            $this->view->assign('contentElements', $contentElements);
            $this->view->assign('groupedContentElements', $groupedContentElements);
        }
    }

    protected function isPageLinkable(array $page): bool
    {
        return !in_array((int)$page['doktype'], [255, 254, 199]);
    }

}
