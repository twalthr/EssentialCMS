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

	// for CREATE TABLES
	public function successQuery($sql) {
		$result = false;
		if($r = $this->mysqli->query($sql)) {
			$result = true;
		}
		return $result;
	}

	// for SELECT where actual result is unimportant but must exist
	public function resultQuery($sql) {
		$result = false;
		if($r = $this->mysqli->query($sql)) {
			$result = $r->num_rows > 0;
			$r->close();
		}
		return $result;
	}

	// for INSERT, UPDATE, DELETE without escaping
	public function impactQuery($sql) {
		$result = false;
		if($r = $this->mysqli->query($sql)) {
			$result = $this->mysqli->affected_rows > 0;
		}
		return $result;
	}

	public function insertAndExecute($sql, $types, ...$vars) {
		if (!$stmt = $this->mysqli->prepare($sql)) {
			return false;
		}
		if (!$stmt->bind_param($types, ...$vars)) {
			$stmt->close();
			return false;
		}
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		$result = $this->mysqli->affected_rows == 1;
		$stmt->close();
		return $result;
	}





	public function numberQuery($sql) {
		$r = $this->mysqli->query($sql);
		if ($r == false) {
			return 0;
		}
		$result = $r->num_rows;
		$r->close();
		return $result;
	}

	public function getLastError() {
		return $this->lastError;
	}
}

?>