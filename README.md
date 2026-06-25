# MediaWiki Book TOC

A modification to the MediaWiki table of contents that extends the built-in TOC mechanism to support multi-page "books."

## Purpose

Extends the MediaWiki table of contents to support links to other wiki pages, allowing a collection of pages to be presented as chapters of a single book.

## Compatibility

Verified with:

- MediaWiki 1.45.3
- Vector 2022

## History

This repository was extracted from a working MediaWiki 1.45.3 installation.

The modification was originally developed for MediaWiki 1.43 and subsequently migrated to MediaWiki 1.45.3. Although the upstream files changed slightly between those versions, the functional changes required to implement the Book TOC feature remained unchanged.

## Repository Contents

The following four modified source files are included:

```text
includes/skins/components/SkinComponentTableOfContents.php
skins/Vector/resources/skins.vector.js/tableOfContents.js
skins/Vector/resources/skins.vector.templates.mustache/TableOfContents__list.mustache
skins/Vector/resources/skins.vector.templates.mustache/TableOfContents__line.mustache
```

### Repository Layout

```text
README.md
LICENSE
modified/
├── includes/
│   └── skins/
│       └── components/
│           └── SkinComponentTableOfContents.php
└── skins/
    └── Vector/
        ├── resources/
        │   └── skins.vector.js/
        │       └── tableOfContents.js
        └── resources/
            └── skins.vector.templates.mustache/
                ├── TableOfContents__list.mustache
                └── TableOfContents__line.mustache
```


## Installation and Maintenance

Installation is accomplished by replacing the four corresponding files of a MediaWiki installation directory with the modified versions from this repository. Because the repository preserves the MediaWiki directory structure beneath the modified directory, a single command can do the copying:

### Linux
```bash
cp -a modified/. /path/to/mediawiki/
```
### Windows (Command Prompt)
```cmd
robocopy modified "\path\to\mediawiki" /E
```
### Windows (PowerShell)
```powershell
Copy-Item .\modified\* "\path\to\mediawiki\" -Recurse -Force
```

Because the modification replaces MediaWiki source files rather than installing as an extension, the modified files should be reviewed whenever MediaWiki or the Vector skin is upgraded.

## Documentation

Further documentation is available in this example book:

- [Book TOC Example – The Book TOC Mod](https://wiki.mypjs.us/index.php?title=Book_TOC_Example_-_The_Book_TOC_Mod)

## License

This repository is distributed under the GNU General Public License, version 2 or (at your option) any later version. See the accompanying `LICENSE` file.

