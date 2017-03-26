<?php

class PdfAnalyzer extends DocumentAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'pdf-analyzer', $config);
	}

	public function magicNumberMatches($hexMagicNumber) {
		return Utils::stringStartsWith($hexMagicNumber, '25504446');
	}

	public function extensionMatches($extension) {
		return $extension === 'pdf';
	}

	public function mimeMatches($mime) {
		return $mime === 'application/pdf';
	}

	public function extractProperties($src) {
		global $MAX_RUNTIME;
		global $MAX_RUNTIME_STOP_FACTOR;
		$props = [];

		Utils::requireLibrary('PdfToText');

		$pdf = new PdfToText();
		$pdf->MaxExecutionTime = $MAX_RUNTIME - $MAX_RUNTIME * $MAX_RUNTIME_STOP_FACTOR;
		$pdf->PageSeparator = ' ';
		$pdf->BlockSeparator = ' ';
		$pdf->Options = PdfToText::PDFOPT_ENFORCE_EXECUTION_TIME |
			PdfToText::PDFOPT_NO_HYPHENATED_WORDS;

		// load number of pages
		$pdf->MaxSelectedPages = -1;
		$pdf->Load($src);

		// empty PDFs will be skipped
		if (isset($pdf->Pages) && sizeof($pdf->Pages) === 0) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_TYPE, 'PDF'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/pdf'];

		$props[] = [MediaProperties::KEY_AUTHOR, $pdf->Author];

		$created = strtotime($pdf->CreationDate);
		if ($created !== false && $created > 0) {
			$props[] = [MediaProperties::KEY_CREATED, date("Y-m-d H:i:s", $created)];
		}

		$edited = strtotime($pdf->ModificationDate);
		if ($edited !== false && $edited > 0) {
			$props[] = [MediaProperties::KEY_EDITED, date("Y-m-d H:i:s", $edited)];
		}

		$props[] = [MediaProperties::KEY_SOFTWARE, $pdf->CreatorApplication];

		$props[] = [MediaProperties::KEY_PAGE_COUNT, max(array_keys($pdf->Pages))];

		// analyze content
		$pdf->MaxSelectedPages = Utils::configOrDefault($this->config, 'pdf-analyzer.maxpages', 64);
		$pdf->Load($src);

		$text = $pdf->Text;

		// save memory
		unset($pdf);

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (sizeof($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}
}