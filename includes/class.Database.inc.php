<?php

class Database {

	private $host;
	private $dbname;
	private $username;
	private $password;

	private $mysqli;
	private $lastError;

	public function __construct($host, $dbname, $username, $password) {
		$this->host = $host;
		$this->dbname = $dbname;
		$this->username = $username;
		$this->password = $password;
	}

	public function connect() {
		$this->mysqli = new mysqli($this->host, $this->username, $this->password, $this->dbname);
		if ($this->mysqli->connect_errno) {
			$this->lastError = "Error " . $this->mysqli->connect_errno . ": " . $this->mysqli->connect_error;
			return false;
		}
		else {
			return true;
		}
	}

	public function getLastError() {
		return $this->lastError;
	}
}

?>