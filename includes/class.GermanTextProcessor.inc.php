<?php

class GermanTextProcessor extends TextProcessor {

	public function __construct($config) {
		parent::__construct($config);
	}

	public function open() {
		
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
		return explode(' ', $text);
	}

	public function normalizeToken($token) {

	}

	public function filterToken($token) {

	}

	public function outputToken($token) {
		
	}

	public function close() {
		
	}

}

?>