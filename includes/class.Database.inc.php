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
	public function resultQuery($sql, $types = NULL, ...$vars) {
		if (!$stmt = $this->mysqli->prepare($sql)) {
			return false;
		}
		if ($types !== NULL) {
			if (!$stmt->bind_param($types, ...$vars)) {
				$stmt->close();
				return false;
			}
		}
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		if ($r = $stmt->get_result()) {
			$result = $r->num_rows > 0;
			$r->free();
			$stmt->close();
			return $result ;
		}		
		$stmt->close();
		return false;
	}

	// for INSERT, UPDATE, DELETE
	public function impactQuery($sql, $types = NULL, ...$vars) {
		if (!$stmt = $this->mysqli->prepare($sql)) {
			return false;
		}
		if ($types !== NULL) {
			if (!$stmt->bind_param($types, ...$vars)) {
				$stmt->close();
				return false;
			}
		}
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		$result = $this->mysqli->affected_rows > 0;
		$stmt->close();
		return $result;
	}

		// for INSERT
	public function impactQueryWithId($sql, $types = NULL, ...$vars) {
		if ($this->impactQuery($sql, $types, ...$vars) === false) {
			return false;
		}
		return $this->mysqli->insert_id;
	}

	// for SELECT with escaping
	public function valuesQuery($sql, $types = NULL, ...$vars) {
		if (!$stmt = $this->mysqli->prepare($sql)) {
			return false;
		}
		if ($types !== NULL) {
			if (!$stmt->bind_param($types, ...$vars)) {
				$stmt->close();
				return false;
			}
		}
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		if (!$r = $stmt->get_result()) {
			$stmt->close();
			return false;
		}
		$result = array();
		while ($row = $r->fetch_array(MYSQLI_ASSOC)) {
			$result[] = $row;
		}
		$r->free();
		$stmt->close();
		return $result;
	}

	public function valueQuery($sql, $types = NULL, ...$vars) {
		$result = $this->valuesQuery($sql, $types, ...$vars);
		if ($result === false || empty($result)) {
			return false;
		}
		return $result[0];
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