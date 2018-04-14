<?php

abstract class MediaAnalyzer {

	protected $cmsVersion;
	protected $name;
	protected $config;

	public function __construct($cmsVersion, $name, $config) {
		$this->cmsVersion = $cmsVersion;
		$this->name = $name;
		$this->config = $config;
	}

	public function nameMatches($name) {
		return false;
	}

	public function extensionMatches($extension) {
		return false;
	}

	public function magicNumberMatches($hexMagicNumber) {
		return false;
	}

	public function mimeMatches($mime) {
		return false;
	}

	public function textContentMatches($text) {
		return false;
	}

	// full indicates if thumbnail can be cropped 
	public function generateThumbnail($full, $width, $height, $src, $dst) {
		return false;
	}

	public function extractProperties($src, $ext) {
		return [];
	}
}