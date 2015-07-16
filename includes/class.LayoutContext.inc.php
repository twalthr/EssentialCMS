<?php
 
class LayoutContext {
	private $title;
	private $description;
	private $config;
	private $root;
	private $customHeader; // html
	private $logo; // html
	private $menuItems;
	private $currentSubMenuItems;
	private $beforeContentModules;
	private $contentModules;
	private $afterContentModules;
	private $footer;

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

	public function hasMenuItems() {
		return is_array($menuItems) && !empty($menuItems);
	}

	public function setMenuItems($menuItems) {
		$this->$menuItems = $menuItems;
	}

	public function getMenuItems() {
		return $this->$menuItems;
	}

	public function hasCurrentSubMenuItems() {
		return is_array($currentSubMenuItems) && !empty($currentSubMenuItems);
	}

	public function setCurrentSubMenuItems($currentSubMenuItems) {
		$this->$currentSubMenuItems = $currentSubMenuItems;
	}

	public function getCurrentSubMenuItems() {
		return $this->$currentSubMenuItems;
	}

	public function hasBeforeContentModules() {
		return is_array($beforeContentModules) && !empty($beforeContentModules);
	}

	public function setBeforeContentModules($beforeContentModules) {
		$this->$beforeContentModules = $beforeContentModules;
	}

	public function getBeforeContentModules() {
		return $this->$beforeContentModules;
	}

	public function hasContentModules() {
		return is_array($contentModules) && !empty($contentModules);
	}

	public function setContentModules($contentModules) {
		$this->$contentModules = $contentModules;
	}

	public function getContentModules() {
		return $this->$contentModules;
	}

	public function hasAfterContentModules() {
		return is_array($afterContentModules) && !empty($afterContentModules);
	}

	public function setAfterContentModules($afterContentModules) {
		$this->$afterContentModules = $afterContentModules;
	}

	public function getAfterContentModules() {
		return $this->$afterContentModules;
	}

	public function hasFooter() {
		return Utils::hasStringContents($this->$footer);
	}

	public function getFooter() {
		return $this->$footer;
	}
}
 
?>