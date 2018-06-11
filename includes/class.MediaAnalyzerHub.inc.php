<?php

class MediaAnalyzerHub {

	private $analyzers = [];

	public function __construct() {
		$this->analyzers[] = new JpegAnalyzer([]);
		$this->analyzers[] = new ImageAnalyzer([]);
		$this->analyzers[] = new PdfAnalyzer([]);
		$this->analyzers[] = new PptxAnalyzer([]);
		$this->analyzers[] = new DocxAnalyzer([]);
		$this->analyzers[] = new XlsxAnalyzer([]);
		$this->analyzers[] = new Id3Analyzer([]);
	}

	public function summarize($mid, $originalFileName, $rawPath, $smallThumbnailPath, $largeThumbnailPath) {
		$matchingAnalyzers = [];

		$ext = Utils::getFileExtension($originalFileName);
		$magicNumber = $this->readMagicNumber($rawPath);
		$finfo = finfo_open();
		$mime = finfo_file($finfo, $rawPath, FILEINFO_MIME);
		finfo_close($finfo);
		// check text content
		$content = null;
		if (Utils::stringStartsWith($mime, 'text')) {
			$content = file_get_contents($rawPath);
		}

		foreach ($this->analyzers as $analyzer) {
			// continue even with exceptions
			try {
				// check for at least one match
				if ($analyzer->nameMatches($originalFileName) ||
						$analyzer->extensionMatches($ext) ||
						$analyzer->magicNumberMatches($magicNumber) ||
						$analyzer->mimeMatches($mime) ||
						$analyzer->textContentMatches($content)) {
					$matchingAnalyzers[] = $analyzer;
				}
			} catch (Exception $e) {
				logWarning('Analyzer "match" step has a bug.', $e);
			}
		}

		// extract properties
		$properties = [];
		foreach ($matchingAnalyzers as $analyzer) {
			// continue even with exceptions
			try {
				$properties = array_merge($properties, $analyzer->extractProperties($rawPath, $ext));
			} catch (Exception $e) {
				logWarning('Analyzer "extract" step has a bug.', $e);
			}
		}

		$fieldInfo = MediaProperties::getFieldInfo();

		$normalizedProperties = [];
		foreach ($properties as $kv) {
			$key = $kv[0];
			$value = $kv[1];
			$field = $fieldInfo[$key];

			// filter empty properties
			if (!Utils::hasStringContent($value)) {
				continue;
			}

			$allowedTypes = $field->getAllowedTypesArray();

			// trim strings accordingly
			if (in_array(FieldInfo::TYPE_PLAIN, $allowedTypes)) {

			}

			// trim array elements and make arrays unique
		}

		

		

		

		// remove control characters and escape

		// remove duplicate properties

		// check content of properties

		// merge other properties and array values

		echo var_dump($properties);
		die();

		// make properties unique

		// create thumbnails
	}

	private function readMagicNumber($path) {
		$result = '';
		$handle = fopen($path, 'rb');
		if ($handle) {
			$fsize = filesize($path); 
			$contents = fread($handle, min($fsize, 40)); // read first 40 bytes
			fclose($handle);
			$result = bin2hex($contents);
		}
		return $result;
	}
}