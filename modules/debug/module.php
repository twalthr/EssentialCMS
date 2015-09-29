<?php

class DebugModule extends RichModule {

	public function __construct() {
		parent::__construct(1, 'debug');
	}

	public function getConfigFieldInfo() {
		$config = [];
		// mixed small type without meta content
		$config[] = new FieldInfo(
			'plain', // key
			false, // hasMetaContent
			FieldInfo::TYPE_PLAIN | FieldInfo::TYPE_HTML | FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			null, // allowedMetaContentTypes
			'MIXED_TEXT', // name
			null, // metaName
			false, // largeContentField
			false, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);

		// TYPE_PLAIN
		$config[] = new FieldInfo(
			'plain', // key
			true, // hasMetaContent
			FieldInfo::TYPE_PLAIN, // allowedContentTypes
			FieldInfo::TYPE_PLAIN, // allowedMetaContentTypes
			'PLAIN_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_HTML
		$config[] = new FieldInfo(
			'html', // key
			true, // hasMetaContent
			FieldInfo::TYPE_HTML, // allowedContentTypes
			FieldInfo::TYPE_HTML, // allowedMetaContentTypes
			'HTML_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_MARKDOWN
		$config[] = new FieldInfo(
			'markdown', // key
			true, // hasMetaContent
			FieldInfo::TYPE_MARKDOWN, // allowedContentTypes
			FieldInfo::TYPE_MARKDOWN, // allowedMetaContentTypes
			'MARKDOWN_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_IMAGE
		$config[] = new FieldInfo(
			'image', // key
			true, // hasMetaContent
			FieldInfo::TYPE_IMAGE, // allowedContentTypes
			FieldInfo::TYPE_IMAGE, // allowedMetaContentTypes
			'IMAGE_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_FILE
		$config[] = new FieldInfo(
			'file', // key
			true, // hasMetaContent
			FieldInfo::TYPE_FILE, // allowedContentTypes
			FieldInfo::TYPE_FILE, // allowedMetaContentTypes
			'FILE_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_TAGS
		$config[] = new FieldInfo(
			'tags', // key
			true, // hasMetaContent
			FieldInfo::TYPE_TAGS, // allowedContentTypes
			FieldInfo::TYPE_TAGS, // allowedMetaContentTypes
			'TAGS_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_INT
		$config[] = new FieldInfo(
			'int', // key
			true, // hasMetaContent
			FieldInfo::TYPE_INT, // allowedContentTypes
			FieldInfo::TYPE_INT, // allowedMetaContentTypes
			'INT_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_BOOLEAN
		$config[] = new FieldInfo(
			'boolean', // key
			true, // hasMetaContent
			FieldInfo::TYPE_BOOLEAN, // allowedContentTypes
			FieldInfo::TYPE_BOOLEAN, // allowedMetaContentTypes
			'BOOLEAN_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			1024, // maxContentLength
			1024, // maxMetaContentLength
			'ENABLE_ME' // additionalNames
			);
		// TYPE_ENUM
		$config[] = new FieldInfo(
			'enum', // key
			true, // hasMetaContent
			FieldInfo::TYPE_ENUM, // allowedContentTypes
			FieldInfo::TYPE_ENUM, // allowedMetaContentTypes
			'ENUM_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			[0 => 'ALT1', 1 => 'ALT2'] // additionalNames
			);
		// TYPE_DATE
		$config[] = new FieldInfo(
			'date', // key
			true, // hasMetaContent
			FieldInfo::TYPE_DATE, // allowedContentTypes
			FieldInfo::TYPE_DATE, // allowedMetaContentTypes
			'DATE_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_COLOR
		$config[] = new FieldInfo(
			'color', // key
			true, // hasMetaContent
			FieldInfo::TYPE_COLOR, // allowedContentTypes
			FieldInfo::TYPE_COLOR, // allowedMetaContentTypes
			'COLOR_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_LINK
		$config[] = new FieldInfo(
			'link', // key
			true, // hasMetaContent
			FieldInfo::TYPE_LINK, // allowedContentTypes
			FieldInfo::TYPE_LINK, // allowedMetaContentTypes
			'LINK_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_PAGE
		$config[] = new FieldInfo(
			'page', // key
			true, // hasMetaContent
			FieldInfo::TYPE_PAGE, // allowedContentTypes
			FieldInfo::TYPE_PAGE, // allowedMetaContentTypes
			'PAGE_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_ID
		$config[] = new FieldInfo(
			'id', // key
			true, // hasMetaContent
			FieldInfo::TYPE_ID, // allowedContentTypes
			FieldInfo::TYPE_ID, // allowedMetaContentTypes
			'ID_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_EMAIL
		$config[] = new FieldInfo(
			'email', // key
			true, // hasMetaContent
			FieldInfo::TYPE_EMAIL, // allowedContentTypes
			FieldInfo::TYPE_EMAIL, // allowedMetaContentTypes
			'EMAIL_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);
		// TYPE_LOCALE
		$config[] = new FieldInfo(
			'locale', // key
			true, // hasMetaContent
			FieldInfo::TYPE_LOCALE, // allowedContentTypes
			FieldInfo::TYPE_LOCALE, // allowedMetaContentTypes
			'LOCALE_TEXT', // name
			'ALT_TEXT', // metaName
			true, // largeContentField
			true, // largeMetaContentField
			0, // minContentLength
			0, // minMetaContentLength
			null, // maxContentLength
			null, // maxMetaContentLength
			null // additionalNames
			);

		return $config;
	}

	public function getFieldGroupInfo() {
		return [];
	}

}

return new DebugModule();

?>