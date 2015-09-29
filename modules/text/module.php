<?php

class TextModule extends RichModule {

	public function __construct() {
		parent::__construct(1, 'text');
	}

	public function getConfigFieldGroupInfo() {
		return [];
	}

	public function getFieldGroupInfo() {
		return [];
	}

}

return new TextModule();

?>