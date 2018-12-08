<?php

// v1: FEATURE COMPLETE

class ZipAnalyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'zip-analyzer', $config);
	}

	public function magicNumberMatches($hexMagicNumber) {
		return Utils::stringStartsWith($hexMagicNumber, '504b');
	}

	public function extensionMatches($extension) {
		return $extension === 'zip' || $extension === 'zipx';
	}

	public function mimeMatches($mime) {
		return $mime === 'application/zip';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		$za = new ZipArchive(); 
		$za->open($src);

		if ($za->numFiles === 0) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_ARCHIVE];
		$props[] = [MediaProperties::KEY_TYPE, 'ZIP'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/zip'];

		$props[] = [MediaProperties::KEY_PARTS, $za->numFiles];
		$props[] = [MediaProperties::KEY_COMMENT, $za->comment];

		// list content
		$firstLevel = [];
		$secondLevel = [];
		for($i = 0; $i < $za->numFiles; $i++){ 
			$stat = $za->statIndex($i);
			$parts = explode('/', $stat['name']);
			if (count($parts) > 1) {
				$firstLevel[] = $parts[0] . '/...';
				if (count($parts) > 3) {
					$secondLevel[] = $parts[0] . '/' . $parts[1] . '/...';
				} else {
					$secondLevel[] = $parts[0] . '/' . $parts[1];
				}
			} else {
				$firstLevel[] = $parts[0];
			}
		}

		$firstLevel = array_unique($firstLevel, SORT_STRING);
		$firstLevel = array_filter($firstLevel, function ($baseDir) use ($firstLevel) {
			return !in_array($baseDir . '/...', $firstLevel);
		});
		$secondLevel = array_unique($secondLevel, SORT_STRING);
		$secondLevel = array_filter($secondLevel, function ($baseDir) use ($secondLevel) {
			return !in_array($baseDir . '/...', $secondLevel);
		});

		if (count($firstLevel) > 1) {
			$props[] = [MediaProperties::KEY_TEXT_CONTENT, implode("\n", $firstLevel)];
		} else {
			$props[] = [MediaProperties::KEY_TEXT_CONTENT, implode("\n", $secondLevel)];
		}

		// find license file
		$license = $this->getFile($za, $firstLevel, 'LICENSE');
		if (!isset($license)) {
			$license = $this->getFile($za, $firstLevel, 'LICENSE.txt');
		}
		if (!isset($license)) {
			$license = $this->getFile($za, $firstLevel, 'license.txt');
		}
		if (isset($license)) {
			$props[] = [MediaProperties::KEY_COPYRIGHT, $license];
		}

		// find readme file
		$readme = $this->getFile($za, $firstLevel, 'README.md');
		if (!isset($readme)) {
			$readme = $this->getFile($za, $firstLevel, 'README');
		}
		if (!isset($readme)) {
			$readme = $this->getFile($za, $firstLevel, 'README.txt');
		}
		if (!isset($readme)) {
			$readme = $this->getFile($za, $firstLevel, 'readme.txt');
		}
		if (isset($readme)) {
			$props[] = [MediaProperties::KEY_DESCRIPTION, $readme];
		}

		$za->close();

		return $props;
	}

	private function getFile($za, $firstLevel, $filename) {
		// first level
		if ($za->locateName($filename) !== false) {
			return file_get_contents('zip://' . $za->filename . '#' . $filename);
		}
		// second level
		else if (count($firstLevel) === 1) {
			$absoluteName = preg_replace('/\\/\\.\\.\\.$/', '/', $firstLevel[0]) . $filename;
			return file_get_contents('zip://' . $za->filename . '#' . $absoluteName);
		}
		return null;
	}
}