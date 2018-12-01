<?php

class MediaAnalyzerHub {

	private $analyzers = [];

	public function __construct() {
		$this->analyzers[] = new ImageAnalyzer([]); // generic analyzers first!
		$this->analyzers[] = new TextAnalyzer([]);
		$this->analyzers[] = new RtfAnalyzer([]);
		$this->analyzers[] = new JpegAnalyzer([]);
		$this->analyzers[] = new PdfAnalyzer([]);
		$this->analyzers[] = new PptxAnalyzer([]);
		$this->analyzers[] = new DocxAnalyzer([]);
		$this->analyzers[] = new XlsxAnalyzer([]);
		$this->analyzers[] = new Id3Analyzer([]);
		$this->analyzers[] = new MarkdownAnalyzer([]);
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

			// skip duplicate properties but merge 'other' properties
			if ($key !== MediaProperties::KEY_OTHER && array_key_exists($key, $validProperties)) {
				continue;
			}

			// convert to type and content format
			$typeAndContent = null;
			// convert array
			if ($field->isArray() && is_array($value)) {
				$typeAndContent = [];
				foreach ($value as $element) {
					$typeAndContent[] = ['type' => $type, 'content' => $element];
				}
			}
			// convert singleton
			else if ($field->isArray()) {
				$typeAndContent = [['type' => $type, 'content' => $value]];
			}
			// skip unsupported arrays
			else if (is_array($value)) {
				continue;
			}
			// convert non-array
			else {
				$typeAndContent = ['type' => $type, 'content' => $value];
			}

			$normalized = $field->normalize($typeAndContent, true);
			// skip invalid contents
			if ($normalized === null) {
				continue;
			}

			// merge 'other' properties
			if ($key === MediaProperties::KEY_OTHER && array_key_exists($key, $validProperties)) {
				$validProperties[$key] = array_merge($normalized, $validProperties[$key]);
			} else {
				$validProperties[$key] = $normalized;
			}
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
			if ($fsize === 0) {
				return $result;
			}
			$contents = fread($handle, min($fsize, 40)); // read first 40 bytes
			fclose($handle);
			$result = bin2hex($contents);
		}
		return $result;
	}
}