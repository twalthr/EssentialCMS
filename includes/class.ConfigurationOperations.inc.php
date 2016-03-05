<?php

final class ConfigurationOperations {

	const CONFIGURATION_USER = 'user';
	const CONFIGURATION_PASSWORD = 'password';
	const CONFIGURATION_SESSION = 'session';
	const CONFIGURATION_TITLE = 'title';
	const CONFIGURATION_DESCRIPTION = 'description';
	const CONFIGURATION_CUSTOM_HEADER = 'header';
	const CONFIGURATION_LAYOUT = 'layout';

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getSingleValue($key) {
		$result = $this->db->valueQuery('
			SELECT `value`
			FROM `Configuration`
			WHERE `key`=? AND `order`=0',
			's',
			$key);
		if ($result === false) {
			return false;
		}
		return $result['value'];
	}

	public function setSingleValue($key, $value) {
		
	}

}

?>