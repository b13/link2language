# Link to a page with a specific language in TYPO3

This is a TYPO3 extension that allows an editor to select a specific language when linking to a
page in the TYPO3 Backend.

## When do I need this extension?

Big instances with multiple page trees and loads of languages per tree might bring trouble when
allowing to link to a page in a different language.

The simplest way would be to link to the login page in a different language, with an automatic
redirect.

However the real strength of the extension pays off when having multiple page trees with different
languages to link to a properly available news.

The extension adds an additional select dropdown to editors in the Link selector popup, when linking
an image, or linking a text in the Rich Text Editor. This dropdown is always enabled
when the extension is installed, no configuration needed.

## How to install this extension?

You can set this up via composer (composer require cmsexperts/link2language) or via
TER (extension name "link2language"), it runs with TYPO3 v8 LTS (use v0.1.0 for v7 LTS).

## License

The extension is licensed under GPL v2+, same as the TYPO3 Core.

For details see the LICENSE file in this repository.

## ToDo

- Editors can choose any language right now, this should be limited to the editor rights.
- The dropdown should only show up if there are actually multiple languages in the system.
- The labels should be localizable.
- It is not possible to modify the "L=" parameter, if any other is used.
- The extension removes any additional parameters after &L= from the links selected, this might
especially be a problem when other extensions extend the page linking as well.
