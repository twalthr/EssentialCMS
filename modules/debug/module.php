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
// auxiliaryInfo
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
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
			['OTHER' => 'MY_OTHER_FIELD', 'APPLICATION' => 'MY_APPLICATION_FIELD'], // auxiliaryInfo
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
			['OTHER' => 'MY_OTHER_FIELD', 'APPLICATION' => 'MY_APPLICATION_FIELD'], // auxiliaryInfo
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
			FieldInfo::TYPE_LOCALE, // defaultType
			'de' // defaultContent
			);

		// duration
		$config[] = new FieldInfo(
			'field13', // key
			FieldInfo::TYPE_DURATION, // allowedTypes
			'FIELD_13', // name
			false, // array
			true, // required
			null, // largeContent
			1, // minContentLength
			10, // maxContentLength
			null, // auxiliaryInfo
			FieldInfo::TYPE_DURATION, // defaultType
			'0000-00-00 12:30:00' // defaultContent
			);

		// boolean
		$config[] = new FieldInfo(
			'field14', // key
			FieldInfo::TYPE_BOOLEAN, // allowedTypes
			'FIELD_14', // name
			false, // array
			false, // required
			null, // largeContent
			null, // minContentLength
			null, // maxContentLength
			"Dhis is a test.", // auxiliaryInfo
			FieldInfo::TYPE_BOOLEAN, // defaultType
			null // defaultContent
			);

		// range
		$config[] = new FieldInfo(
			'field15', // key
			FieldInfo::TYPE_RANGE, // allowedTypes
			'FIELD_15', // name
			false, // array
			false, // required
			null, // largeContent
			0, // minContentLength
			10, // maxContentLength
			1, // auxiliaryInfo
			null, // defaultType
			null // defaultContent
			);

		// encrypted
		$config[] = new FieldInfo(
			'field16', // key
			FieldInfo::TYPE_ENCRYPTED, // allowedTypes
			'FIELD_16', // name
			false, // array
			false, // required
			false, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // auxiliaryInfo
			null, // defaultType
			null // defaultContent
			);

		// encrypted
		$config[] = new FieldInfo(
			'field17', // key
			FieldInfo::TYPE_ENCRYPTED, // allowedTypes
			'FIELD_17', // name
			false, // array
			false, // required
			true, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // auxiliaryInfo
			null, // defaultType
			null // defaultContent
			);

		// color
		$config[] = new FieldInfo(
			'field18', // key
			FieldInfo::TYPE_COLOR, // allowedTypes
			'FIELD_18', // name
			false, // array
			false, // required
			false, // largeContent
			null, // minContentLength
			null, // maxContentLength
			null, // auxiliaryInfo
			null, // defaultType
			null // defaultContent
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
			null, // auxiliaryInfo
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
			null, // auxiliaryInfo
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