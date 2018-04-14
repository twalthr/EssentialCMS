<?php

// v1: FEATURE COMPLETE

class ImageAnalyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'image-analyzer', $config);
	}

	public function mimeMatches($mime) {
		return Utils::stringStartsWith($mime, 'image/');
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// basic check and information
		$basic = getimagesize($src);
		if ($basic === false) {
			return $props;
		}

		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_IMAGE];
		$props[] = [MediaProperties::KEY_MIME_TYPE, $basic['mime']];
		if ($basic[0] > 0) {
			$props[] = [MediaProperties::KEY_WIDTH, $basic[0]];
		}
		if ($basic[1] > 0) {
			$props[] = [MediaProperties::KEY_HEIGHT, $basic[1]];
		}
		$this->addOtherProperty($basic, 'channels', $props);
		$this->addOtherProperty($basic, 'bits', $props);
		return $props;
	}

	private function addOtherProperty(&$values, $key, &$props) {
		if (isset($values[$key])) {
			$value = $values[$key];
			if (Utils::hasStringContent($value)) {
				$props[] = [MediaProperties::KEY_OTHER, $key . '=' . $value];
			}
		}
	}
}