<?php

class MediaAnalyzerHub {

	private $analyzers = [];

	public function __construct() {
		$this->analyzers[] = new JpegAnalyzer([]);
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
			// check for at least one match
			if ($analyzer->nameMatches($originalFileName) ||
					$analyzer->extensionMatches($ext) ||
					$analyzer->magicNumberMatches($magicNumber) ||
					$analyzer->mimeMatches($mime) ||
					$analyzer->textContentMatches($content)) {
				$matchingAnalyzers[] = $analyzer;
			}
		}

		// extract properties
		$properties = [];
		foreach ($matchingAnalyzers as $analyzer) {
			$properties = array_merge($analyzer->extractProperties($rawPath));
		}

		echo "HELLO";

		// make properties unique

		// create thumbnails
	}

	private function readMagicNumber($path) {
		$result = '';
		$handle = fopen($path, 'rb');
		if ($handle) {
			$fsize = filesize($path); 
			$contents = fread($handle, min($fsize, 32)); // read first 32 bytes
			fclose($handle);
			$result = bin2hex($contents);
		}
		return $result;
	}
}