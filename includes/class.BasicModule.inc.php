<?php
 
abstract class BasicModule {
	private $cmsVersion;
	private $name;

	public function __construct($cmsVersion, $name) {
		$this->cmsVersion = $cmsVersion;
		$this->name = $name;

		// TODO validate name 
	}

	public function getCmsVersion() {
		return $this->cmsVersion;
	}

	public function getName() {
		return $this->name;
	}

	public function getContent($config) {
		ob_start();
		$this->printContent($config);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function printContent($config) {
		echo '';
	}

	public function text($id, ...$args) {
		echo Translator::get()->translate($id, ...$args);
	}

	public function textString($id, ...$args) {
		return Translator::get()->translate($id, ...$args);
	}
}

?>