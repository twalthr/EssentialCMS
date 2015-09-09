<?php
 
class BasicModule {
	private $cmsVersion;
	private $name;

	public function __construct($cmsVersion, $name) {
		$this->cmsVersion = $cmsVersion;
		$this->name = $name;
	}

	public function getCmsVersion() {
		return $this->cmsVersion;
	}

	public function getName() {
		return $this->name;
	}

	public function getContent($config) {
		return '';
	}

	public function text($id, ...$args) {
		global $TR;
		echo $TR->translate($id, ...$args);
	}
}

?>