<?php

class DebugModule extends RichModule {

	public function __construct() {
		parent::__construct(1, 'debug');
	}

	public function getConfigFieldInfo() {
		$config = [];
		// mixed small type
		$config[] = new FieldInfo(
			'field1', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			'FIELD_1' // name
			);

		// mixed small type with default
		$config[] = new FieldInfo(
			'field2', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			'FIELD_2', // name
			null,
			null,
			null,
			null,
			null,
			null,
			2,
			'<b>THIS IS STRONG</b>'
			);

		// mixed small type required
		$config[] = new FieldInfo(
			'field3', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			'FIELD_3', // name
			null,
			false
			);

		return $config;
	}

	public function getFieldGroupInfo() {
		return [];
	}

}

return new DebugModule();

?>