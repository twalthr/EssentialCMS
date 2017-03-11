<?php

class JpegAnalyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'jpeg-analyzer', $config);
	}

	public function extensionMatches($extension) {
		echo var_dump($extension);
		return $extension === 'jpg' || $extension === 'jpeg';
	}

	public function extractProperties($src) {
		echo "ROL";
		$data = exif_read_data($src);
		echo var_dump($data);
		die();
		return [];
	}
}