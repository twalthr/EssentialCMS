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

		// page
		$config[] = new FieldInfo(
			'field6', // key
			FieldInfo::TYPE_PAGE, // allowedTypes
			'FIELD_6' // name
			);

		// enum
		$config[] = new FieldInfo(
			'field7', // key
			FieldInfo::TYPE_ENUM | FieldInfo::TYPE_PLAIN, // allowedTypes
			'FIELD_7', // name
			true, // array
			true, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			['OTHER' => 'MY_OTHER_FIELD', 'APPLICATION' => 'MY_APPLICATION_FIELD'], // additionalNames
			[FieldInfo::TYPE_PLAIN, FieldInfo::TYPE_ENUM], // defaultType
			['hello', 'OTHER'] // defaultContent
			);

		// enum
		$config[] = new FieldInfo(
			'field7', // key
			FieldInfo::TYPE_ENUM, // allowedTypes
			'FIELD_7', // name
			false, // array
			true, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			['OTHER' => 'MY_OTHER_FIELD', 'APPLICATION' => 'MY_APPLICATION_FIELD'], // additionalNames
			FieldInfo::TYPE_ENUM, // defaultType
			'APPLICATION' // defaultContent
			);

		// int
		$config[] = new FieldInfo(
			'field8', // key
			FieldInfo::TYPE_INT, // allowedTypes
			'FIELD_8', // name
			false, // array
			true, // required
			null, // largeContent
			3, // minContentLength
			10, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_INT, // defaultType
			7 // defaultContent
			);

		// date time
		$config[] = new FieldInfo(
			'field9', // key
			FieldInfo::TYPE_DATE_TIME, // allowedTypes
			'FIELD_9', // name
			false, // array
			true, // required
			null, // largeContent
			'2018-01-01 12:30:00', // minContentLength
			'2018-06-01 12:30:00', // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_DATE_TIME, // defaultType
			'2018-03-01 12:30:00' // defaultContent
			);

		// tags
		$config[] = new FieldInfo(
			'field10', // key
			FieldInfo::TYPE_TAGS, // allowedTypes
			'FIELD_10', // name
			false, // array
			true, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_TAGS, // defaultType
			'hello, world' // defaultContent
			);

		// float
		$config[] = new FieldInfo(
			'field11', // key
			FieldInfo::TYPE_FLOAT, // allowedTypes
			'FIELD_11', // name
			false, // array
			true, // required
			null, // largeContent
			3, // minContentLength
			10, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_FLOAT, // defaultType
			7.0 // defaultContent
			);

		// locale
		$config[] = new FieldInfo(
			'field12', // key
			FieldInfo::TYPE_LOCALE, // allowedTypes
			'FIELD_12', // name
			false, // array
			true, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // additionalNames
			FieldInfo::TYPE_LOCALE, // defaultType
			'de' // defaultContent
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

		// page
		$fields[] = new FieldInfo(
			'field3', // key
			FieldInfo::TYPE_PAGE, // allowedTypes
			'FIELD_3', // name
			false,
			true
			);

		// array of mixed small type required
		$fields[] = new FieldInfo(
			'field4', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedTypes
			'FIELD_4', // name
			true
			);

		// array of one type required
		$fields[] = new FieldInfo(
			'field5', // key
			FieldInfo::TYPE_PLAIN, // allowedTypes
			'FIELD_5', // name
			true
			);

		// array of with page type required
		$fields[] = new FieldInfo(
			'field6', // key
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_PAGE, // allowedTypes
			'FIELD_6', // name
			true
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

		// page
		$fieldGroup2[] = new FieldInfo(
			'field3', // key
			FieldInfo::TYPE_PAGE, // allowedTypes
			'FIELD_3' // name
			);


		$fieldGroup2 = new FieldGroupInfo('fieldGroup2', 'MYPOST', 'MYPOSTS', $fields2, 0, null, false, true);
		
		return [$fieldGroup, $fieldGroup2];
	}

}

return new DebugModule();

?>