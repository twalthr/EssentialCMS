<?php

// v1: FEATURE COMPLETE

class XlsxAnalyzer extends OfficeAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'xlsx-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'xlsx';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// check for excel document
		$workbook = $this->loadXml($src, 'xl/workbook.xml');
		if ($workbook === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_DOCUMENT_TYPE, MediaProperties::VALUE_DOCUMENT_TYPE_EDITABLE];
		$props[] = [MediaProperties::KEY_TYPE, 'Excel (XLSX)'];
		$props[] = [MediaProperties::KEY_MIME_TYPE,
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

		// extract core.xml properties
		$this->extractCoreXml($props, $src);

		// extract app.xml properties
		$this->extractAppXml($props, $src);

		// load dom
		$workbookDom = dom_import_simplexml($workbook);

		// extract headings
		$sheets = $workbookDom->getElementsByTagName('sheet');
		$titles = [];
		$sheetCount = 0;
		foreach ($sheets as $sheet) {
			$sheetCount++;
			$title = $sheet->getAttribute('name');
			// normalize title
			if (Utils::hasStringContent($title)) {
				$titles[] = trim(preg_replace('/[[:space:][:cntrl:]]+/', ' ', $title));
			}
		}
		// normalize titles
		$titles = array_unique($titles);
		if (sizeof($titles) > 1) { // only add if more than 1 table
			$props[] = [MediaProperties::KEY_HEADING, $titles];
		}

		// save sheet count
		$props[] = [MediaProperties::KEY_PAGE_COUNT, $sheetCount];

		// extract text
		$sharedStrings = $this->loadXml($src, 'xl/sharedStrings.xml');
		$sharedStringsDom = dom_import_simplexml($sharedStrings);
		$strings = $sharedStringsDom->getElementsByTagName('t');
		$text = '';
		foreach ($strings as $string) {
			$text = $text . ' ' . $string->textContent;
		}

		// save memory
		unset($sharedStrings);
		unset($sharedStringsDom);
		unset($strings);

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (sizeof($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}
}