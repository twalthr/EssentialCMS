<?php

// v1: FEATURE COMPLETE

class TextAnalyzer extends DocumentAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'text-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'txt' || $extension === 'csv';
	}

	public function mimeMatches($mime) {
		return $mime === 'text/plain' || $mime === 'text/csv';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// read content
		$text = file_get_contents($src);
		if ($text === false) {
			return $props;
		}

		// determine character count
		$props[] = [MediaProperties::KEY_CHARACTER_COUNT, $this->generateCharacterCount($text)];

		// determine line count
		$props[] = [MediaProperties::KEY_LINE_COUNT, $this->generateLineCount($text)];

		if ($ext === 'csv') {
			// add general properties
			$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_TEXT];
			$props[] = [MediaProperties::KEY_TYPE, 'CSV'];
			$props[] = [MediaProperties::KEY_MIME_TYPE, 'text/csv'];

			// split by punctuation if CSV format
			$text = preg_replace('/[,;|]/', ' ', $text);
		} else {
			// add general properties
			$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_TEXT];
			$props[] = [MediaProperties::KEY_TYPE, 'Text'];
			$props[] = [MediaProperties::KEY_MIME_TYPE, 'text/plain'];
		}

		// determine frequent words
		$words = $this->generateFrequentWords($text);
		if (count($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		// determine word count
		$wordCount = $this->generateWordCount($text);
		if (isset($wordCount)) {
			$props[] = [MediaProperties::KEY_WORD_COUNT, $wordCount];
		}

		return $props;
	}
}