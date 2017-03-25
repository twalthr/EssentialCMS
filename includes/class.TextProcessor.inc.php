<?php

abstract class TextProcessor {

	protected $config;

	public function __construct($config) {
		$this->config = $config;
	}

	abstract public function open();

	abstract public function matches($text, $scores);

	abstract public function getLanguage();

	abstract public function tokenize($text);

	abstract public function normalizeToken($token);

	abstract public function filterToken($token);

	abstract public function outputToken($token);

	abstract public function close();

}

?>