<?php

// v1: FEATURE COMPLETE

class DocxAnalyzer extends OfficeAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'docx-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'docx';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// check for word document
		$document = $this->loadXml($src, 'word/document.xml');
		if ($document === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_DOCUMENT_TYPE, MediaProperties::VALUE_DOCUMENT_TYPE_EDITABLE];
		$props[] = [MediaProperties::KEY_TYPE, 'Word (DOCX)'];
		$props[] = [MediaProperties::KEY_MIME_TYPE,
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

		// extract core.xml properties
		$this->extractCoreXml($props, $src);

		// extract app.xml properties
		$this->extractAppXml($props, $src);

		// load dom
		$documentDom = dom_import_simplexml($document);

		// extract headings
		$style = $this->loadXml($src, 'word/styles.xml');
		if ($style !== false) {
			$titles = [];
			// find style ids
			$idMapping = [
				'Title',
				'Heading1',
				'Heading2',
				'Heading3',
				'Heading4',
				'Heading5',
				'Heading6',
				'Heading7',
				'Heading8',
				'Heading9'];
			$styleDom = dom_import_simplexml($style);
			$styleDefs = $styleDom->getElementsByTagName('style');
			foreach ($styleDefs as $styleDef) {
				$names = $styleDef->getElementsByTagName('name');
				if ($names->item(0) !== null) {
					$type = $names->item(0)->getAttribute('w:val');
					switch ($type) {
						case 'Title':
						case 'heading 1':
						case 'heading 2':
						case 'heading 3':
						case 'heading 4':
						case 'heading 5':
						case 'heading 6':
						case 'heading 7':
						case 'heading 8':
						case 'heading 9':
							$idMapping[] = $styleDef->getAttribute('w:styleId');
							break;
						default:
							// do nothing
							break;
					}
				}
			}
			// find usage of style ids
			$paragraphs = $documentDom->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph) {
				$style = $paragraph->getElementsByTagName('pStyle');
				if ($style->item(0) !== null) {
					$styleId = $style->item(0)->getAttribute('w:val');
					if (in_array($styleId, $idMapping)) {
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
			// normalize titles
			$titles = array_unique($titles);
			if (sizeof($titles) > 0) {
				$props[] = [MediaProperties::KEY_HEADING, $titles];
			}
		}

		// extract text
		$documentStrings = $documentDom->getElementsByTagName('t');
		$text = '';
		foreach ($documentStrings as $documentString) {
			$text = $text . ' ' . $documentString->textContent;
		}

		// save memory
		unset($document);
		unset($documentDom);
		unset($documentStrings);

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (sizeof($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}
}