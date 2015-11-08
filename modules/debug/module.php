<?php

class DebugModule extends RichModule {

	public function __construct() {
		parent::__construct(1, 'debug');
	}

	/*
// key,
// allowedTypes,
// name,
// array
// required
// largeContent
// minContentLength
// maxContentLength
// additionalNames
// defaultType
// defaultContent
	*/

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
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_2', // name
			null, // array
			null, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_HTML, // defaultType
			'<b>THIS IS STRONG</b>' // defaultContent
			);

		// mixed small type required
		$config[] = new FieldInfo(
			'field3', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_3', // name
			null, // array
			true, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_HTML, // defaultType
			'<b>THIS IS STRONG</b>' // defaultContent
			);

		// array of TEXT
		$config[] = new FieldInfo(
			'field4', // key
			FieldInfo::TYPE_PLAIN, // allowedTypes
			'FIELD_4', // name
			true
			);

		// array of mixed small type required
		$config[] = new FieldInfo(
			'field5', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_5', // name
			true
			);

		return $config;
	}

	public function getFieldGroupInfo() {
		$fields = [];

		// mixed small type with default
		$fields[] = new FieldInfo(
			'title', // key
			FieldInfo::TYPE_PLAIN, // allowedTypes
			'TITLE', // name
			null,
			true
			);

		// mixed small type with default
		$fields[] = new FieldInfo(
			'field2', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_2', // name
			null, // array
			null, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_HTML, // defaultType
			'<b>COOL</b>' // defaultContent
			);


		$fieldGroup = new FieldGroupInfo('fieldGroup1', 'BLOGPOST', 'BLOGPOSTS', $fields, 0, 2, true, true);




		$fields2 = [];

		// mixed small type with default
		$fields2[] = new FieldInfo(
			'title', // key
			FieldInfo::TYPE_PLAIN, // allowedTypes
			'TITLE', // name
			null,
			true
			);

		// mixed small type with default
		$fields2[] = new FieldInfo(
			'field2', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_2', // name
			null, // array
			null, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_HTML, // defaultType
			'<b>COOL</b>' // defaultContent
			);


		$fieldGroup2 = new FieldGroupInfo('fieldGroup2', 'MYPOST', 'MYPOSTS', $fields2, 0, null, false, true);
		
		return [$fieldGroup, $fieldGroup2];
	}

}

return new DebugModule();

?>