<?php
 
class MenuItem {
	private $url;
	private $hoverTitle;
	private $isCurrent;
	private $title;
	private $children;

	public function __construct($url, $hoverTitle, $isCurrent, $title, $children) {
		$this->$url = $url;
		$this->$hoverTitle = $hoverTitle;
		$this->$isCurrent = $isCurrent;
		$this->$title = $title;
		$this->$children = $children;
	}

	public function getUrl() {
		return $this->$url;
	}

	public function hasHoverTitle() {
		return Utils::hasStringContents($this->$hoverTitle);
	}

	public function getHoverTitle() {
		return $this->$hoverTitle;
	}

	public function isCurrent() {
		return $this->$isCurrent;
	}

	public function getTitle() {
		return $this->$title;
	}

	public function hasChild() {
		return is_array($children) && !empty($children);
	}

	public function getChildren() {
		return $this->$children;
	}
}

?>