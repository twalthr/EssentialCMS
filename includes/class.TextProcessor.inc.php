<?php

abstract class TextProcessor {

	abstract public function matches($str, $scores);

	abstract public function getLanguage();

	abstract public function tokenize($str);

	abstract public function normalizeToken($str);

	abstract public function filterToken($str);

}

?>