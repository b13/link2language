<?php

declare(strict_types=1);

namespace B13\Link2Language\LinkHandler;

/*
 * This file is part of TYPO3 CMS-based extension "link2language" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Link handler for page (and content) links
 * with an additional option to show a dropdown for the selected language
 */
class PageLinkHandler extends \TYPO3\CMS\Backend\LinkHandler\PageLinkHandler
{
    public function __construct(
        private readonly TranslationConfigurationProvider $translationConfigurationProvider,
        private readonly BackendLayoutView $backendLayoutView,
        private readonly LinkService $linkService,
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    /**
     * Short-hand function to select all registered languages
     *
     * @return SiteLanguage[]
     */
    protected function getAllLanguages($pageId = null)
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId ?? $this->linkParts['url']['pageuid'] ?? 0);
            return $site->getAvailableLanguages($this->getBackendUser(), true);
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

            $activePageRecord = BackendUtility::getRecordWSOL('pages', $pageId);
            $this->view->assign('expandActivePage', true);
            $languages = $this->getAllLanguages($pageId);
            $availableLanguages = [];
            $languageIds = [];
            foreach ($languages as $language) {
                $availableLanguages[$language->getLanguageId()] = [
                    'title' => $language->getTitle(),
                    'flag' => $this->iconFactory->getIcon($language->getFlagIdentifier(), Icon::SIZE_SMALL),
                ];
                if ($language->getLanguageId() > -1) {
                    $availableLanguages[$language->getLanguageId()]['url'] = $this->linkService->asString([
                        'type' => LinkService::TYPE_PAGE,
                        'parameters' => '&L=' . $language->getLanguageId(),
                        'pageuid' => (int)$pageId,
                    ]);
                } else {
                    $availableLanguages[$language->getLanguageId()]['url'] = $this->linkService->asString([
                        'type' => LinkService::TYPE_PAGE,
                        'pageuid' => (int)$pageId,
                    ]);
                }
                if ($language->getLanguageId() > 0) {
                    $languageIds[] = $language->getLanguageId();
                }
            }
            $this->view->assign('availableLanguages', $availableLanguages);

            // Create header for listing, showing the page title/icon
            $this->view->assign('activePage', $activePageRecord);
            if ($this->isPageLinkable($activePageRecord)) {
                $this->view->assign('activePageLink', $this->linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => $pageId]));
            }
            $this->view->assign('activePageTitle', BackendUtility::getRecordTitle('pages', $activePageRecord, true));
            $this->view->assign('activePageIcon', $this->iconFactory->getIconForRecord('pages', $activePageRecord, Icon::SIZE_SMALL)->render());

            // Look up tt_content elements from the expanded page
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class));

            $contentElements = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pageId, ParameterType::INTEGER)
                    ),
                    $queryBuilder->expr()->in(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(array_merge([0, -1], $languageIds), ArrayParameterType::INTEGER)
                    )
                )
                ->orderBy('colPos')
                ->addOrderBy('sorting')
                ->executeQuery()
                ->fetchAllAssociative();

            $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageId);
            $colPosArray = [];
            if ($backendLayout !==  null) {
                $colPosArray = $backendLayout->getUsedColumns();
            }
            $languages = $this->translationConfigurationProvider->getSystemLanguages($pageId);

            $colPosMapping = [];
            foreach ($colPosArray as $colPos => $label) {
                $colPosMapping[(int)($colPos)] = $label;
            }
            // Enrich list of records
            $groupedContentElements = [];
            $groupedContentElements[-1] = [
                'label' => $languages[-1]['title'],
                'flag' => $this->iconFactory->getIcon($languages[-1]['flagIcon'], Icon::SIZE_SMALL),
                'items' => [],
            ];
            foreach ($contentElements as &$contentElement) {
                $languageId = (int)$contentElement['sys_language_uid'];
                if (!isset($groupedContentElements[$languageId])) {
                    $groupedContentElements[$languageId] = [
                        'label' => $languages[$languageId]['title'],
                        'flag' => $this->iconFactory->getIcon($languages[$languageId]['flagIcon'], Icon::SIZE_SMALL),
                        'items' => [],
                    ];
                }

                $colPos = (int)$contentElement['colPos'];
                if (!isset($groupedContentElements[$languageId]['items'][$colPos])) {
                    $groupedContentElements[$languageId]['items'][$colPos] = [
                        'label' => $colPosMapping[$colPos] ?? '',
                        'items' => [],
                    ];
                }

                $contentElement['url'] = $this->linkService->asString(['type' => LinkService::TYPE_PAGE, 'parameters' => '&L=' . $languageId, 'pageuid' => (int)$pageId, 'fragment' => $contentElement['uid']]);
                $contentElement['isSelected'] = !empty($this->linkParts) && (int)($this->linkParts['url']['fragment'] ?? 0) === (int)$contentElement['uid'];
                $contentElement['icon'] = $this->iconFactory->getIconForRecord('tt_content', $contentElement, Icon::SIZE_SMALL)->render();
                $contentElement['title'] = BackendUtility::getRecordTitle('tt_content', $contentElement, true);
                $groupedContentElements[$languageId]['items'][$colPos]['items'][] = $contentElement;
                if ($languageId === 0) {
                    $contentElementCopy = $contentElement;
                    $contentElementCopy['url'] = $this->linkService->asString(['type' => LinkService::TYPE_PAGE, 'pageuid' => (int)$pageId, 'fragment' => $contentElement['uid']]);
                    if (!isset($groupedContentElements[-1]['items'][$colPos])) {
                        $groupedContentElements[-1]['items'][$colPos] = [
                            'label' => $colPosMapping[$colPos] ?? '',
                            'items' => [],
                        ];
                    }
                    $groupedContentElements[-1]['items'][$colPos]['items'][] = $contentElementCopy;
                }
            }
            ksort($groupedContentElements);
            $this->view->assign('contentElements', $contentElements);
            $this->view->assign('groupedContentElements', $groupedContentElements);
        }
    }

}
