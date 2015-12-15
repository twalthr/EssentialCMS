<?php

final class GlobalOperations {

	private $menuItemOperations;
	private $pageOperations;

	public function __construct($menuItemOperations, $pageOperations) {
		$this->menuItemOperations = $menuItemOperations;
		$this->pageOperations = $pageOperations;
	}

	public function isValidPageExternalId($externalId) {
		return $this->pageOperations->isValidExternalId($externalId);
	}

	public function getPageTitle($pid) {
		return $this->pageOperations->getPageTitle($pid);
	}

}

?>