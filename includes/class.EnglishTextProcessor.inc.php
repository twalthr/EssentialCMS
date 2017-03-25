<?php

class EnglishTextProcessor extends TextProcessor {

	private $stopwords;

	public function __construct($config) {
		parent::__construct($config);
	}

	public function open() {
		$stopwords = Utils::loadFact('en.stopwords');
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
		// english has highest score
		if ($highest === 'en' &&
				$scores[$highest] > Utils::configOrDefault($this->config, 'english-processor.minscore', 0.25)) {
			return true;
		}
		return false;
	}

	public function getLanguage() {
		return 'en';
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
		$token = preg_replace('/[[:punct:]]+/', '', $token);
		// remove numbers from tokens
		$token = preg_replace('/[[:digit:]]+/', '', $token);
		// lower case
		$token = strtolower($token);
		return $this->basicStemming($token);
	}

	public function filterToken($token) {
		return mb_strlen($token) > 1 && !array_key_exists($token, $this->stopwords);
	}

	public function outputToken($token) {
		
	}

	public function close() {
		$this->stopwords = null;
	}

	private function basicStemming($str) {
		$len = mb_strlen($str);
		$pos1 =mb_substr($str, $len - 1, 1);
		if ($len < 3 || $pos1 !== 's') {
			// keep it
			return mb_substr($str, 0, $len);
		}
		$pos2 = mb_substr($str, $len - 2, 1);
		switch ($pos2) {
			case 'u':
			case 's':
				// keep it
				return mb_substr($str, 0, $len);
			case 'e':
				$pos3 = mb_substr($str, $len - 3, 1);
				$pos4 = mb_substr($str, $len - 4, 1);
				if ($len > 3 && $pos3 === 'i' && $pos4 !== 'a' && $pos4 !== 'e') {
					// remove last three and add y
					return mb_substr($str, 0, $len - 3) . 'y';
				}
				if ($pos3 === 'i' || $pos3 === 'a' || $pos3 === 'o' || $pos3 === 'e') {
					// keep it
					return $str;
				}
				// fall through
			default:
				return mb_substr($str, 0, $len - 1);
		}
	}

}

?>