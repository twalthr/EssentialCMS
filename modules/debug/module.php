<?php

class DebugModule extends RichModule {

	public function __construct() {
		parent::__construct(1, 'debug');
	}

	public function getConfigFieldInfo() {
		$config = [];
		// mixed small type
		$config[] = new FieldInfo(
			'plain', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			'MIXED_TEXT' // name
			);

		return $config;
	}

	public function getFieldGroupInfo() {
		return [];
	}

}

return new DebugModule();

?>