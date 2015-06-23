<?php
 
class LayoutContext {
	private $title;
	private $description;
	private $config;
	private $root;
	private $customHeader; // html
	private $logo; // html

	public function __construct($config) {
		$this->$config = $config;
		$this->$root = $config->getPublicRoot();
	}
	
	public function hasTitle() {
		return Utils::hasStringContents($this->$title);
	}

	public function setTitle($title) {
		$this->$title = $title;
	}

	public function getTitle() {
		return $this->$title;
	}

	public function hasDescription() {
		return Utils::hasStringContents($this->$description);
	}

	public function setDescription($description) {
		$this->$description = $description;
	}

	public function getDescription() {
		return $this->$description;
	}

	public function getConfig() {
		return $this->$config;
	}

	public function setRoot($root) {
		$this->$root = $root;
	}

	public function getRoot() {
		return $this->$root;
	}

	public function hasCustomHeader() {
		return Utils::hasStringContents($this->$customHeader);
	}

	public function setCustomHeader($customHeader) {
		$this->$customHeader = $customHeader;
	}

	public function getCustomHeader() {
		return $this->$customHeader;
	}

	public function setLogo($logo) {
		$this->$logo = $logo;
	}

	public function getLogo() {
		return $this->$logo;
	}
}
 
?>