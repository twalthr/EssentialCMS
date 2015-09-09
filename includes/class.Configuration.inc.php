<?php

class Configuration {
	private $cmsFullname;
	private $cmsUrl;
	private $root;
	private $userId;

	public function setCmsFullname($cmsFullname) {
		$this->cmsFullname = $cmsFullname;
	}

	public function getCmsFullname() {
		return $this->cmsFullname;
	}

	public function setCmsUrl($cmsUrl) {
		$this->cmsUrl = $cmsUrl;
	}

	public function getCmsUrl() {
		return $this->cmsUrl;
	}

	public function setPublicRoot($root) {
		$this->root = $root;
	}

	public function getPublicRoot() {
		return $this->root;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
	}

	public function getUserId() {
		return $this->userId;
	}

}

?>