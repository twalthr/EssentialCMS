<?php

class LayoutContext {
	// general properties
	private $title;
	private $description;
	private $config;
	private $customHeader;

	// modules
	private $preContentModules;
	private $contentModules;
	private $asideContentModules;
	private $postContentModules;
	private $logoModules;
	private $asideHeaderModules;
	private $footerModules;

	// menu items
	private $menuItems;
	private $currentSubMenuItems;

	public function __construct($config) {
		$this->config = $config;
	}

	public function hasTitle() {
		return Utils::hasStringContent($this->title);
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function hasDescription() {
		return Utils::hasStringContent($this->description);
	}

	public function setDescription($description) {
		$this->description = $description;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getCmsUrl() {
		return $this->config->getCmsUrl();
	}

	public function getCmsFullname() {
		return $this->config->getCmsFullname();
	}

	public function getRoot() {
		return $this->config->getPublicRoot();
	}

	public function hasCustomHeader() {
		return Utils::hasStringContent($this->customHeader);
	}

	public function setCustomHeader($customHeader) {
		$this->customHeader = $customHeader;
	}

	public function getCustomHeader() {
		return $this->customHeader;
	}

	// --------------------------------------------------------------------------------------------

	public function hasPreContentModules() {
		return isset($this->preContentModules);
	}

	public function setPreContentModules($preContentModules) {
		$this->preContentModules = $preContentModules;
	}

	public function getPreContentModules() {
		return $this->preContentModules;
	}

	public function hasContentModules() {
		return isset($this->contentModules);
	}

	public function setContentModules($contentModules) {
		$this->contentModules = $contentModules;
	}

	public function getContentModules() {
		return $this->contentModules;
	}

	public function hasAsideContentModules() {
		return isset($this->asideContentModules);
	}

	public function setAsideContentModules($asideContentModules) {
		$this->asideContentModules = $asideContentModules;
	}

	public function getAsideContentModules() {
		return $this->asideContentModules;
	}

	public function hasPostContentModules() {
		return isset($this->postContentModules);
	}

	public function setPostContentModules($postContentModules) {
		$this->postContentModules = $postContentModules;
	}

	public function getPostContentModules() {
		return $this->postContentModules;
	}

	public function hasLogoModules() {
		return isset($this->logoModules);
	}

	public function setLogoModules($logoModules) {
		$this->logoModules = $logoModules;
	}

	public function getLogoModules() {
		return $this->logoModules;
	}

	public function hasAsideHeaderModules() {
		return isset($this->asideHeaderModules);
	}

	public function setAsideHeaderModules($asideHeaderModules) {
		$this->asideHeaderModules = $asideHeaderModules;
	}

	public function getAsideHeaderModules() {
		return $this->asideHeaderModules;
	}

	public function hasFooterModules() {
		return isset($this->footerModules);
	}

	public function setFooterModules($footerModules) {
		$this->footerModules = $footerModules;
	}

	public function getFooterModules() {
		return $this->footerModules;
	}

	// --------------------------------------------------------------------------------------------

	public function hasMenuItems() {
		return is_array($this->menuItems) && !empty($this->menuItems);
	}

	public function setMenuItems($menuItems) {
		$this->menuItems = $menuItems;
	}

	public function getMenuItems() {
		return $this->menuItems;
	}

	public function hasCurrentSubMenuItems() {
		return is_array($this->currentSubMenuItems) && !empty($this->currentSubMenuItems);
	}

	public function setCurrentSubMenuItems($currentSubMenuItems) {
		$this->currentSubMenuItems = $currentSubMenuItems;
	}

	public function getCurrentSubMenuItems() {
		return $this->currentSubMenuItems;
	}
}

?>