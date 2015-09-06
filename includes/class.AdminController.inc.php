<?php

class AdminController {

	private $config;

	public function __construct() {	
		global $PUBLIC_ROOT, $CMS_FULLNAME, $CMS_URL;
		$this->config = new Configuration();
		$this->config->setPublicRoot($PUBLIC_ROOT);
		$this->config->setCmsFullname($CMS_FULLNAME);
		$this->config->setCmsUrl($CMS_URL);
	}

	public function layoutContent(...$contentModules) {
		$layoutContext = $this->generateLayoutContext();
		$layoutContext->setContentModules($contentModules);
		$this->layout($layoutContext);
	}

	public function install() {

	}

	// --------------------------------------------------------------------------------------------

	private function generateLayoutContext() {
		$layoutContext = new LayoutContext($this->config);
		return $layoutContext;
	}

	private function layout($layoutContext) {
		global $INCLUDE_DIRECTORY;
		require_once($INCLUDE_DIRECTORY . '/templ.AdminLayout.inc.php');
	}
}

?>