<?php

// v1: FEATURE COMPLETE

class PptxAnalyzer extends OfficeAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'pptx-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'pptx';
	}

	public function extractProperties($src) {
		$props = [];

		// check for presentation
		if ($this->loadXml($src, 'ppt/presentation.xml') === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_DOCUMENT_TYPE, MediaProperties::VALUE_DOCUMENT_TYPE_EDITABLE];
		$props[] = [MediaProperties::KEY_TYPE, 'PowerPoint (PPTX)'];
		$props[] = [MediaProperties::KEY_MIME_TYPE,
			'application/vnd.openxmlformats-officedocument.presentationml.presentation'];

		// extract core.xml properties
		$this->extractCoreXml($props, $src);

		// extract app.xml properties
		$this->extractAppXml($props, $src);

		// list slide
		$text = '';
		$titles = [];
		$idx = 0;
		while (($slide = $this->loadXml($src, 'ppt/slides/slide' . (++$idx) . '.xml')) !== false) {
			// extract slide text
			$slideDom = dom_import_simplexml($slide);
			$slideStrings = $slideDom->getElementsByTagName('t');
			foreach ($slideStrings as $slideString) {
				$text = $text . ' ' . $slideString->textContent;
			}
			// extract slide titles
			$this->extractTitles($titles, $slideDom);
			// extract notes text
			$notesSlide = $this->loadXml($src, 'ppt/notesSlides/notesSlide' . $idx . '.xml');
			if ($notesSlide !== false) {
				// extract slide text
				$notesSlideDom = dom_import_simplexml($notesSlide);
				$notesStrings = $notesSlideDom->getElementsByTagName('t');
				foreach ($notesStrings as $notesString) {
					$text = $text . ' ' . $notesString->textContent;
				}
			}
		}

		// normalize titles
		$titles = array_unique($titles);
		if (sizeof($titles) > 0) {
			$props[] = [MediaProperties::KEY_HEADING, $titles];
		}

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (sizeof($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}

	private function extractTitles(&$titles, &$dom) {
		$paragraphs = $dom->getElementsByTagName('sp');
		foreach ($paragraphs as $paragraph) {
			$headers = $paragraph->getElementsByTagName('ph');
			foreach ($headers as $header) {
				$title = $header->getAttribute('type');
				if ($title == 'title' || $title == 'ctrTitle') {
					$texts = $paragraph->getElementsByTagName('t');
					$headerText = '';
					foreach ($texts as $text) {
						$headerText = $headerText . $text->textContent;
					}
					// normalize title
					if (Utils::hasStringContent($headerText)) {
						$titles[] = trim(preg_replace('/[[:space:][:cntrl:]]+/', ' ', $headerText));
					}
				}
			}
		}
	}
}