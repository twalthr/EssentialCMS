<?php

// v1: FEATURE COMPLETE

class GermanTextProcessor extends TextProcessor {

	private $stopwords;

	public function __construct($config) {
		parent::__construct($config);
	}

	public function open() {
		$stopwords = Utils::loadFact('de.stopwords');
		if ($stopwords === false) {
			$this->stopwords = [];
		} else {
			$stopwords = explode("\n", $stopwords);
			foreach ($stopwords as $value) {
				if ($value !== '') {
					$this->stopwords[$value] = null;
				}
			}
		}
	}

	public function matches($text, $scores) {
		$highest = array_keys($scores)[0];
		// german has highest score
		if ($highest === 'de' &&
				$scores[$highest] > Utils::configOrDefault($this->config, 'german-processor.minscore', 0.25)) {
			return true;
		}
		return false;
	}

	public function getLanguage() {
		return 'de';
	}

	public function tokenize($text) {
		// remove multiple spaces
		// remove control characters
		// we cannot remove more here as we could not filter URLs otherwise
		$text = preg_replace('/[[:space:][:cntrl:]]+/', ' ', $text);
		return explode(' ', $text);
	}

	public function normalizeToken($token) {
		// combine words with hyphens, apostrophes etc.
		$token = preg_replace('/[[:punct:]„”‚‘’»«]+/', '', $token);
		// remove numbers from tokens
		$token = preg_replace('/^[[:digit:]]+$/', '', $token);
		// lower case
		$token = strtolower($token);
		// remove umlauts and sharp s
		$search = ['ae', 'oe', 'ue', 'ä', 'ö', 'ü', 'ß'];
		$replace = ['a', 'o', 'u', 'a', 'o', 'u', 'ss'];
		$token = str_replace($search, $replace, $token);
		return $token;
	}

	public function filterToken($token) {
		return mb_strlen($token) > 1 &&
			!array_key_exists($token, $this->stopwords);
	}

	public function outputToken($token) {
		// remove leading/trailing space or punctuation marks
		// keep - within strings
		return preg_replace(
			'/(^[[:space:][:punct:]„”‚‘’»«]+)|
			([[:space:][:punct:]„”‚‘’»«]+$)|
			((?![-])[[:punct:]„”‚‘’»«]+)/',
			'',
			$token);
	}

	public function close() {
		unset($this->stopwords);
	}

}

?>