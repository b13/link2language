# Link to a page with a specific language in TYPO3

This is a TYPO3 extension that allows an editor to select a specific language when linking to a
page or (free) content elements in the TYPO3 Backend.

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

## Linking to "free mode" content elements

In addition, linking to a specific content elements which are in a specific language
is also possible in the link picker for pages.

## How to install this extension?

Use `composer req b13/link2language` or install it via TYPO3's Extension Manager from the
[TYPO3 Extension Repository](https://extensions.typo3.org) using the extension key `link2language`.

## License

The extension is licensed under GPL v2+, same as the TYPO3 Core.

For details see the LICENSE file in this repository.

## Open Issues

If you find an issue, feel free to create an issue on GitHub or a pull request.

## ToDo

- Editors can choose any language right now, this should be limited to the editor rights.
- Site Configuration should be utilized.
- The dropdown should only show up if there are actually multiple languages in the system.
- The labels should be localizable.
- It is not possible to modify the "L=" parameter, if any other is used.
- The extension removes any additional parameters after &L= from the links selected, this might
especially be a problem when other extensions extend the page linking as well.


### Credits

This extension was created by [Benni Mack](https://github.com/bmack) for [b13 GmbH](https://b13.com).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
