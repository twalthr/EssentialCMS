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

		// reverse extracted properties because latter ones have priority if valid
		$properties = array_reverse($properties);

		// get all fields information
		$fieldInfo = MediaProperties::getFieldInfo();

		$validProperties = [];
		foreach ($properties as $kv) {
			$key = $kv[0];
			$value = $kv[1];
			$field = $fieldInfo[$key];
			// media properties must only have one type
			$type = $field->getAllowedTypesArray()[0];

			// skip duplicate properties
			if (array_key_exists($key, $seenProperties)) {
				continue;
			}

			// convert to type and content format
			$typeAndContent = [];
			// convert array
			if ($field->isArray() && is_array($value)) {
				foreach ($value as $element) {
					$typeAndContent[] = ['type' => $type, 'content' => $element];
				}
			}
			// skip unsupported arrays
			else if (is_array($value)) {
				continue;
			}
			// convert singleton
			else {
				$typeAndContent[] = ['type' => $type, 'content' => $value];
			}

			$normalized = $field->normalize($typeAndContent, true);
			// skip invalid contents
			if ($normalized === null) {
				continue;
			}
			$validProperties[$key] = $normalized;
		}

		echo var_dump($validProperties);
		die();

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