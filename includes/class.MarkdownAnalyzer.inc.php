<?php

// v1: FEATURE COMPLETE

class MarkdownAnalyzer extends DocumentAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'markdown-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'markdown' || $extension === 'md';
	}

	public function mimeMatches($mime) {
		return $mime === 'text/markdown';
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// read content
		$text = file_get_contents($src);
		if ($text === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_TEXT];
		$props[] = [MediaProperties::KEY_TYPE, 'Markdown'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, 'text/markdown'];

		// determine character count
		$props[] = [MediaProperties::KEY_CHARACTER_COUNT, $this->generateCharacterCount($text)];

		// determine line count
		$props[] = [MediaProperties::KEY_LINE_COUNT, $this->generateLineCount($text)];

		// remove HTML if present

		// preserve headings
		for ($i = 1; $i <= 6; $i++) {
			$text = preg_replace('/<\\s*h' . $i . '\\s*>/i', "<h" . $i . ">\n" . str_repeat('#', $i), $text);
			$text = preg_replace('/<\\/\\s*h' . $i . '\\s*>/i', "</h" . $i . ">\n", $text);
		}

		// preserve separation
		$text = preg_replace('/>/', "> ", $text);

		// remove HTML tags
		$text = strip_tags($text);

		// remove HTML escaping
		$text = html_entity_decode($text);

		// process markdown lines
		$headings = [];
		$text = str_replace(["\r\n", "\r"], "\n", $text);
		$lines = explode("\n", $text);
		for ($i = 0; $i < count($lines); $i++) {
			$line = $lines[$i];
			if (Utils::stringStartsWith($line, '#')) {
				$headings[] = ltrim($line, '#');
			} else if ($i + 1 < count($lines)) {
				$nextLine = $lines[$i + 1];
				if (Utils::stringStartsWith($nextLine, '=') || Utils::stringStartsWith($nextLine, '-')) {
					$headings[] = $line;
				}
			}
		}

		if (count($headings) > 0) {
			$props[] = [MediaProperties::KEY_HEADING, $headings];
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