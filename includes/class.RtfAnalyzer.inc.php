<?php

// v1: FEATURE COMPLETE

class RtfAnalyzer extends DocumentAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'rtf-analyzer', $config);
	}

	public function magicNumberMatches($hexMagicNumber) {
		return Utils::stringStartsWith($hexMagicNumber, '7b5c72746631');
	}

	public function extensionMatches($extension) {
		return $extension === 'rtf';
	}

	public function mimeMatches($mime) {
		return $mime === 'application/rtf';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// read content
		$text = file_get_contents($src);
		if ($text === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_DOCUMENT_TYPE, MediaProperties::VALUE_DOCUMENT_TYPE_EDITABLE];
		$props[] = [MediaProperties::KEY_TYPE, 'RTF'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/rtf'];

		// remove rich text characteristics

		// resolve unicode escape sequences for RTF (UTF-16)
		// source: https://stackoverflow.com/a/2934602
		$text = preg_replace_callback(
			'/\\\\u([0-9a-fA-F]{4})/',
			function ($match) {
				return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
			},
			$text);

		// remove rich text syntax (e.g. \fonttbl)
		$text = preg_replace('/(\\\\)(\\S+)/', '', $text);

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (count($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}
}