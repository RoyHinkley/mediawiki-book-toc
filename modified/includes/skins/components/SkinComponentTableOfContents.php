<?php
/**
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Skin;

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutputFlags;

/**
 * @internal for use inside Skin and SkinTemplate classes only
 * @unstable
 */
class SkinComponentTableOfContents implements SkinComponent {
	/** @var OutputPage */
	private $output;

	public function __construct( OutputPage $output ) {
		$this->output = $output;
	}

	/**
	 * Nests child sections within their parent sections.
	 *
	 * @param array $sections
	 * @param int $toclevel
	 * @return array
	 */
	private function getSectionsDataInternal( array $sections, int $toclevel = 1 ): array {
		$data = [];
		foreach ( $sections as $i => $section ) {
			// Child section belongs to a higher parent.
			if ( $section->tocLevel < $toclevel ) {
				return $data;
			}

			// Set all the parent sections at the current top level.
			if ( $section->tocLevel === $toclevel ) {
				$childSections = $this->getSectionsDataInternal(
					array_slice( $sections, $i + 1 ),
					$toclevel + 1
				);
				$data[] = $section->toLegacy() + [
					'array-sections' => $childSections,
					'is-top-level-section' => $toclevel === 1,
					'is-parent-section' => $childSections !== []
				];
			}
		}
		return $data;
	}

	/**
	 * Get table of contents template data
	 *
	 * Enriches section data by nesting child elements within parent elements
	 * such that the table of contents can be rendered in Mustache.
	 *
	 * For an example of how to render the data, see TableOfContents.mustache in
	 * the Vector skin.
	 */
	// JW - Book TOC mod
	// JW - Commented out this original version of getTOCDataInternal()
	// JW - and replaced it with the modified version below this comment.
	// private function getTOCDataInternal(): array {
		// $tocData = $this->output->getTOCData();
		// // Return data only if TOC present T298796.
		// if ( $tocData === null ) {
			// return [];
		// }
		// // Respect __NOTOC__
		// if ( $this->output->getOutputFlag( ParserOutputFlags::NO_TOC ) ) {
			// return [];
		// }

		// $outputSections = $tocData->getSections();

		// return count( $outputSections ) > 0 ? [
			// 'number-section-count' => count( $outputSections ),
			// 'array-sections' => $this->getSectionsDataInternal( $outputSections, 1 ),
		// ] : [];
	// }


	/////////////////////////////////
	// JW - Book TOC mod BEGIN
	//
	private function getTOCDataInternal(): array {
		$tocData = $this->output->getTOCData();
		// Return data only if TOC present T298796.
		if ( $tocData === null ) {
			return [];
		}
		// Respect __NOTOC__
		if ( $this->output->getOutputFlag( ParserOutputFlags::NO_TOC ) ) {
			return [];
		}

		//error_log($this->output->getHTML());
		$currentPageTitle = $this->output->getTitle()->getText();

		$outputSections = $tocData->getSections();
		$sectionsCount = count( $outputSections );
		if ($sectionsCount == 0)
		{
			//error_log("?? $currentPageTitle: There are no sections");
			return [];
		}
		
		$toc = [
			'number-section-count' => $sectionsCount,
			'array-sections' => $this->getSectionsDataInternal( $outputSections, 1 )
		];
		//error_log("?? $currentPageTitle: currentToC: " . json_encode($toc, JSON_PRETTY_PRINT));

		// Prepend the linkAnchors with '#'.
		// It was removed from TableOfContents__line.mustache to permit external links.
		$this->setAnchors($toc['array-sections']);

		// Look for book TOC entries.
		$bookToc = $this->extractBookTocEntries($sectionsCount);

		$isBookToc = !empty($bookToc['before']) || !empty($bookToc['after']);
		
		// The boolean show-toc-beginning controls the output of 
		// the default "Beginning" link, which is redundant for 
		// Book TOCs. Takes effect in TableOfContents__list.mustache.
		$toc['show-toc-beginning'] = !$isBookToc;
		
		if ($bookToc['index'] > $toc['number-section-count'])
		{
			$before = $bookToc['before'];
			$after = $bookToc['after'];
			$current =& $before[array_key_last($before)];	// get a reference to the entry for the current page
			$current['array-sections'] = $toc['array-sections'];
			$this->updateChildren($current['array-sections'], $current['number']);
			
			$toc['array-sections'] = empty($after) ? $before : array_merge($before, $after);
			$toc['number-section-count'] = $bookToc['index'];
		}
		//if ($isBookToc)
			//error_log("?? $currentPageTitle: bookToC: " . json_encode($toc, JSON_PRETTY_PRINT));	
		return $toc;
	}
	
	// Recursively prepend linkAnchors with '#'.
	// It was removed from TableOfContents__line.mustache to permit external links.
	private function setAnchors( array &$sections ) {
		foreach ( $sections as &$section ) {
			$section['linkAnchor'] = '#' . $section['linkAnchor'];
			if (!empty($section['array-sections']))
				$this->setAnchors($section['array-sections']);
		}
	}

	// Recursively prepend the original numbers to shift them under the 
	// new entry for the current page.
	private function updateChildren(&$entries, $currentNumber) {
		foreach ($entries as &$entry) {
			$entry['number'] = $currentNumber . '.' . $entry['number'];
			$entry['toclevel'] = $entry['toclevel'] + 1;
			$entry['level'] = strval($entry['level'] + 1);
			$entry['is-top-level-section'] = false;
			if (!empty($entry['array-sections'])) {
				$this->updateChildren($entry['array-sections'], $currentNumber);
			}
		}
	}			
	
	private function createBookTocEntry( \DOMElement $link, string $index, string $number ): array {
		$href = $link->getAttribute('href');
		$content = $link->nodeValue;
		$title = $link->getAttribute('title');
		$fromTitle = str_replace( ' ', '_', $title );	// Wiki-fy the title.
		$anchor =  str_replace(':', '_', $fromTitle); // needed?

		// The point of book TOC entries is that they are "off-page." They refer pages other than the current one.
		// Standard TOC entries are links to anchors at section headings on the current page ("on-page").
		$bookTocEntry = [					
			"toclevel" => 1,      			// Always 1 for book TOC entries
			"level" => "2",       			// Always "2" for book TOC entries
			"line" => $content,	            // The display value (anchor text for anchor entries, page title for book TOC entries)
			"number" => $number,  			// For toclevel 1, same as index (for toclevel > 1, it's parent index followed by .subIndex.furtherSubindex(...))
			"index" => $index,   			// Serial number / TOC entry counter.
			"fromtitle" => $fromTitle,  	// Wiki-fied (' '=>'_') title of the destination page (the current page, for anchor entries).
			"byteoffset" => 0,  			// For book TOC entries, always 0 (start of chapter).
			"anchor" => $anchor,  			// Wiki-fied (' '=>'_') anchor id (what follows '#' for anchor entries).
			"linkAnchor" => $href,          // The full href for off-page entries; '#'.anchor for on-page entries.
			"array-sections" => [],         // Sub-sections of this entry. Off-page entries' sub-sections are omitted for now.
			"is-top-level-section" => true,	// $number contains no '.'
			"is-parent-section" => false,   // !empty(array-sections)
		];

		return $bookTocEntry;
	}
	
	private function extractBookTocEntries(int $index ): array {
		$entriesBefore = [];
		$entriesAfter = [];

		$pageHtml = $this->output->getHTML();
		$doc = new \DOMDocument();
		@$doc->loadHTML( $pageHtml ); // Suppress warnings from malformed HTML
		$xpath = new \DOMXPath( $doc );

		// Find the <div id="book-toc">
		$bookTocDiv = $xpath->query("//div[@id='book-toc']");
		if ( $bookTocDiv->length > 0 ) {
			
			// Construct the TOC entry for the current page
			$titleObj = $this->output->getTitle();
			$pageTitle = $titleObj->getPrefixedText();
			$fromTitle = str_replace(' ', '_', $pageTitle); // Wiki format
			$anchor =  str_replace(':', '_', $fromTitle); // needed?
			$currentEntry = [
				"toclevel" => 1,
				"level" => "2",
				"line" => $titleObj->getText(), // Get plain page title
				"number" => "0",
				"index" => "0",
				"fromtitle" => $fromTitle,
				"byteoffset" => 0,
				"anchor" => $anchor,
				"linkAnchor" => "#",
				"array-sections" => [], // To be filled later
				"is-top-level-section" => true,
				"is-parent-section" => true,
			];

			// Extract all links inside <div id="book-toc">
			$links = $xpath->query("//div[@id='book-toc']//a");

			$notFound = 100000;	// An impossibly big number; haven't seen the current page yet
			$current = $notFound;
			foreach ( $links as $link ) {
				// Does this link point to the current page?
				if ($current == $notFound && strpos($link->getAttribute('class'), 'mw-selflink') !== false)
				{
					$current = ++$index;
					$currentEntry['line'] = $link->nodeValue;
				}
				else
				{
					$entry = $this->createBookTocEntry( $link, strval(++$index), strval($index) );
					if ( $index < $current ) {
						$entriesBefore[] = $entry; // Add to the 'before' list
					} else {
						$entriesAfter[] = $entry; // Add to the 'after' list
					}
				}
			}
			if ($current == $notFound) $current = ++$index; // Current page is missing from book-toc??? Add it to the end.
			$currentEntry['index'] = strval($current);
			$currentEntry['number'] = $currentEntry['index']; // Ensure consistency
			$entriesBefore[] = $currentEntry;			
		}
		return ['index' => $index, 'before' => $entriesBefore, 'after' => $entriesAfter ];
	}

	//
	// JW - Book TOC mod END
	/////////////////////////////////

	/**
	 * @inheritDoc
	 */
	public function getTemplateData(): array {
		return $this->getTOCDataInternal();
	}
}
