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
 * Extending the page link handler to also add the language value from the dropdown
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser', 'TYPO3/CMS/Recordlist/PageLinkHandler'], function($, LinkBrowser, OriginalPageLinkHandler) {
    'use strict';

    /**
     *
     * @type {{currentLink: string}}
     * @exports TYPO3/CMS/Recordlist/PageLinkHandler
     */
    var PageLinkHandler = {
        currentLink: ''
    };

    /**
     *
     * @param {Event} event
     */
    PageLinkHandler.linkPage = function(event) {
        event.preventDefault();

        var id = $(this).attr('href');

        id = PageLinkHandler.addLanguageValueToCurrentValue(id);
        LinkBrowser.finalizeFunction(id);
    };

    /**
     *
     * @param {Event} event
     */
    PageLinkHandler.linkPageByTextfield = function(event) {
        event.preventDefault();

        var value = $('#luid').val();
        if (!value) {
            return;
        }

        value = PageLinkHandler.addLanguageValueToCurrentValue(value);
        LinkBrowser.finalizeFunction(value);
    };

    /**
     *
     * @param {Event} event
     */
    PageLinkHandler.linkCurrent = function(event) {
        event.preventDefault();

        var value = PageLinkHandler.addLanguageValueToCurrentValue(PageLinkHandler.currentLink);
        LinkBrowser.finalizeFunction(value);
    };


    /**
     * Custom function to add the language value, that was selected, and remove the existing L parameter
     * @param input
     * @returns {*}
     */
    PageLinkHandler.addLanguageValueToCurrentValue = function(input) {
        var language = PageLinkHandler.fetchLanguageValue();
        if (language !== '') {
            var fragment = '';
            if (typeof input === 'string' && input.indexOf('&L=') !== -1) {
              fragment = input.substr(input.indexOf('#'));
              input = input.substr(0, input.indexOf('&L='));
            }
            input = input + '&L=' + language + fragment;
        }
        return input;
    };

    PageLinkHandler.fetchLanguageValue = function()
    {
        var attributeValues = LinkBrowser.getLinkAttributeValues();
        return (typeof attributeValues.language === 'undefined' ? '' : attributeValues.language);
    };

    $(function() {
        PageLinkHandler.currentLink = $('body').data('currentLink');

        // remove the click events for the original link handler
        $('a.t3js-pageLink').off('click', OriginalPageLinkHandler.linkPage).on('click', PageLinkHandler.linkPage);
        $('input.t3js-linkCurrent').off('click', OriginalPageLinkHandler.linkCurrent).on('click', PageLinkHandler.linkCurrent);
        $('input.t3js-pageLink').off('click', OriginalPageLinkHandler.linkPageByTextfield).on('click', PageLinkHandler.linkPageByTextfield);
    });

    return PageLinkHandler;
});
