<?php

abstract class MediaProperties {

	// Note: All property fields should only have one type in order to perform 
	// normalization after analyzation.

	// --------------------------------------------------------------------------------------------
	// General properties
	// --------------------------------------------------------------------------------------------

	const KEY_TYPE_GROUP = 'typegroup';
	const VALUE_TYPE_GROUP_OTHER = 0;
	const VALUE_TYPE_GROUP_TEXT = 1;
	const VALUE_TYPE_GROUP_BINARY = 2;
	const VALUE_TYPE_GROUP_IMAGE = 3;
	const VALUE_TYPE_GROUP_AUDIO = 4;
	const VALUE_TYPE_GROUP_VIDEO = 5;
	const VALUE_TYPE_GROUP_ARCHIVE = 6;
	const VALUE_TYPE_GROUP_CODE = 7;
	const VALUE_TYPE_GROUP_DOCUMENT = 8;
	const VALUE_TYPE_GROUP_APPLICATION = 9;

	const KEY_TYPE = 'type'; // contains file extension or Software name

	const KEY_MIME_TYPE = 'mimetype';

	const KEY_WIDTH = 'width';

	const KEY_HEIGHT = 'height';

	const KEY_MANUFACTURER = 'manufacturer';

	const KEY_MODEL = 'model';

	const KEY_SOFTWARE = 'software';

	const KEY_CREATED = 'created';

	const KEY_EDITED = 'edited';

	const KEY_POSITION_LAT = 'positionlat';

	const KEY_POSITION_LON = 'positionlon';

	const KEY_POSITION_ALT = 'positionalt';

	const KEY_DEVICE = 'device'; // e.g. camera (e.g. front camera of iPhone) or scanner information

	const KEY_AUTHOR = 'author';

	const KEY_AUTHOR_ORGANIZATION = 'authororganization';

	const KEY_TITLE = 'title';

	const KEY_DESCRIPTION = 'description';

	const KEY_REVISION = 'revision';

	const KEY_TAGS = 'tags';

	const KEY_PART = 'part'; // starts at 1

	const KEY_PARTS = 'parts';

	const KEY_TEXT_CONTENT = 'textcontent'; // e.g. lyrics or subtitles

	const KEY_LANGUAGE = 'language';

	const KEY_COPYRIGHT = 'copyright';

	const KEY_LINKED = 'linked';

	const KEY_COMMENT = 'comment';

	const KEY_CONTAINED_PERSON = 'contained_person';

	// --------------------------------------------------------------------------------------------
	// Document and image properties
	// --------------------------------------------------------------------------------------------

	const KEY_ORIENTATION = 'orientation';
	const VALUE_ORIENTATION_NONE_0 = 0;
	const VALUE_ORIENTATION_HORIZONTAL_0 = 1;
	const VALUE_ORIENTATION_NONE_180 = 2;
	const VALUE_ORIENTATION_VERTICAL_0 = 3;
	const VALUE_ORIENTATION_HORIZONTAL_90 = 4;
	const VALUE_ORIENTATION_NONE_90 = 5;
	const VALUE_ORIENTATION_HORIZONTAL_270 = 6;
	const VALUE_ORIENTATION_NONE_270 = 7;

	const KEY_RESOLUTION = 'resolution';

	const KEY_COLORS = 'colors';

	const KEY_IMAGE_TYPE = 'imagetype';
	const VALUE_IMAGE_TYPE_IMAGE = 0;
	const VALUE_IMAGE_TYPE_PHOTO = 1;
	const VALUE_IMAGE_TYPE_GRAPHIC = 2;

	const KEY_DOCUMENT_TYPE = 'documenttype';
	const VALUE_DOCUMENT_TYPE_BOOK = 0;
	const VALUE_DOCUMENT_TYPE_EDITABLE = 1; // e.g. word, power point document
	const VALUE_DOCUMENT_TYPE_DOCUMENT = 2; // e.g. scanned invoice, contract

	const KEY_PAGE_COUNT = 'pagecount';

	const KEY_PAGE_FORMAT = 'pageformat';

	const KEY_FREQUENT_WORDS = 'frequentwords';

	const KEY_WORD_COUNT = 'wordcount';

	const KEY_LINE_COUNT = 'linecount';

	const KEY_PARAGRAPH_COUNT = 'paragraphcount';

	const KEY_CHARACTER_COUNT = 'charactercount';

	const KEY_HEADING = 'heading';

	// --------------------------------------------------------------------------------------------
	// Audio and video properties
	// --------------------------------------------------------------------------------------------

	const KEY_DURATION = 'duration'; // in seconds

	const KEY_AUDIO_CODEC = 'audiocodec';

	const KEY_VIDEO_CODEC = 'videocodec';

	const KEY_BITRATE = 'bitrate'; // audio + video

	const KEY_AUDIO_CHANNELS = 'audiochannels';

	const KEY_ALBUM = 'album';

	const KEY_GENRE = 'genre';

	const KEY_RELEASE_YEAR = 'releaseyear';

	// --------------------------------------------------------------------------------------------
	// Other properties
	// --------------------------------------------------------------------------------------------

	const KEY_OTHER = 'other';

	public static function getFieldInfo() {
		$props = [];

		$props[KEY_TYPE_GROUP] = FieldInfo::create([
			'key' => KEY_TYPE_GROUP,
			'types' => FieldInfo::TYPE_ENUM,
			'name' => 'TYPE_GROUP',
			'values' => [
				'OTHER',
				'TEXT',
				'BINARY',
				'IMAGE',
				'AUDIO',
				'VIDEO',
				'ARCHIVE',
				'CODE',
				'DOCUMENT',
				'APPLICATION']
			]);

		$props[KEY_TYPE] = FieldInfo::create([
			'key' => KEY_TYPE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'TYPE',
			'max' => 64]);

		$props[KEY_MIME_TYPE] = FieldInfo::create([
			'key' => KEY_MIME_TYPE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'MIME_TYPE',
			'max' => 256]);

		$props[KEY_WIDTH] = FieldInfo::create([
			'key' => KEY_WIDTH,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'WIDTH',
			'min' => 0]);

		$props[KEY_HEIGHT] = FieldInfo::create([
			'key' => KEY_HEIGHT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'HEIGHT',
			'min' => 0]);

		$props[KEY_MANUFACTURER] = FieldInfo::create([
			'key' => KEY_MANUFACTURER,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'MANUFACTURER',
			'max' => 256]);

		$props[KEY_MODEL] = FieldInfo::create([
			'key' => KEY_MODEL,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'MODEL',
			'max' => 256]);

		$props[KEY_SOFTWARE] = FieldInfo::create([
			'key' => KEY_SOFTWARE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'SOFTWARE',
			'max' => 256]);

		$props[KEY_CREATED] = FieldInfo::create([
			'key' => KEY_CREATED,
			'types' => FieldInfo::TYPE_DATE_TIME,
			'name' => 'CREATED']);

		$props[KEY_EDITED] = FieldInfo::create([
			'key' => KEY_EDITED,
			'types' => FieldInfo::TYPE_DATE_TIME,
			'name' => 'EDITED']);

		$props[KEY_POSITION_LAT] = FieldInfo::create([
			'key' => KEY_POSITION_LAT,
			'types' => FieldInfo::TYPE_FLOAT,
			'name' => 'POSITION_LAT']);

		$props[KEY_POSITION_LON] = FieldInfo::create([
			'key' => KEY_POSITION_LON,
			'types' => FieldInfo::TYPE_FLOAT,
			'name' => 'POSITION_LON']);

		$props[KEY_POSITION_ALT] = FieldInfo::create([
			'key' => KEY_POSITION_ALT,
			'types' => FieldInfo::TYPE_FLOAT,
			'name' => 'POSITION_ALT']);

		$props[KEY_DEVICE] = FieldInfo::create([
			'key' => KEY_DEVICE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'DEVICE',
			'max' => 256]);

		$props[KEY_AUTHOR] = FieldInfo::create([
			'key' => KEY_AUTHOR,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'AUTHOR',
			'max' => 256,
			'array' => true]);

		$props[KEY_AUTHOR_ORGANIZATION] = FieldInfo::create([
			'key' => KEY_AUTHOR_ORGANIZATION,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'AUTHOR_ORGANIZATION',
			'max' => 256]);

		$props[KEY_ORIENTATION] = FieldInfo::create([
			'key' => KEY_ORIENTATION,
			'types' => FieldInfo::TYPE_ENUM,
			'name' => 'TYPE_GROUP',
			'values' => [
				'NONE_0',
				'HORIZONTAL_0',
				'NONE_180',
				'VERTICAL_0',
				'HORIZONTAL_90',
				'NONE_90',
				'HORIZONTAL_270',
				'NONE_270']
			]);

		$props[KEY_TITLE] = FieldInfo::create([
			'key' => KEY_TITLE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'TITLE',
			'max' => 512]);

		$props[KEY_DESCRIPTION] = FieldInfo::create([
			'key' => KEY_DESCRIPTION,
			'types' => FieldInfo::TYPE_PLAIN,
			'large' => true,
			'name' => 'DESCRIPTION']);

		$props[KEY_REVISION] = FieldInfo::create([
			'key' => KEY_REVISION,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'REVISION',
			'max' => 512]);

		$props[KEY_TAGS] = FieldInfo::create([
			'key' => KEY_TAGS,
			'types' => FieldInfo::TYPE_TAGS,
			'name' => 'TAGS',
			'max' => 2048]);

		$props[KEY_PART] = FieldInfo::create([
			'key' => KEY_PART,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'PART',
			'min' => 1]);

		$props[KEY_PARTS] = FieldInfo::create([
			'key' => KEY_PARTS,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'PARTS',
			'min' => 1]);

		$props[KEY_TEXT_CONTENT] = FieldInfo::create([
			'key' => KEY_TEXT_CONTENT,
			'types' => FieldInfo::TYPE_PLAIN,
			'large' => true,
			'name' => 'TEXT_CONTENT']);

		$props[KEY_LANGUAGE] = FieldInfo::create([
			'key' => KEY_LANGUAGE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'LANGUAGE',
			'max' => 256]);

		$props[KEY_COPYRIGHT] = FieldInfo::create([
			'key' => KEY_COPYRIGHT,
			'types' => FieldInfo::TYPE_PLAIN,
			'large' => true,
			'name' => 'COPYRIGHT']);

		$props[KEY_LINKED] = FieldInfo::create([
			'key' => KEY_LINKED,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'LINKED',
			'max' => 2048,
			'array' => true]);

		$props[KEY_COMMENT] = FieldInfo::create([
			'key' => KEY_COMMENT,
			'types' => FieldInfo::TYPE_PLAIN,
			'large' => true,
			'name' => 'COMMENT']);

		$props[KEY_CONTAINED_PERSON] = FieldInfo::create([
			'key' => KEY_CONTAINED_PERSON,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'CONTAINED_PERSON',
			'max' => 256,
			'array' => true]);

		$props[KEY_RESOLUTION] = FieldInfo::create([
			'key' => KEY_RESOLUTION,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'RESOLUTION',
			'min' => 0]);

		$props[KEY_COLORS] = FieldInfo::create([
			'key' => KEY_COLORS,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'COLORS',
			'min' => 1]);

		$props[KEY_IMAGE_TYPE] = FieldInfo::create([
			'key' => KEY_IMAGE_TYPE,
			'types' => FieldInfo::TYPE_ENUM,
			'name' => 'IMAGE_TYPE',
			'values' => [
				'IMAGE',
				'PHOTO',
				'GRAPHIC']
			]);

		$props[KEY_DOCUMENT_TYPE] = FieldInfo::create([
			'key' => KEY_DOCUMENT_TYPE,
			'types' => FieldInfo::TYPE_ENUM,
			'name' => 'DOCUMENT_TYPE',
			'values' => [
				'BOOK',
				'EDITABLE',
				'DOCUMENT']
			]);

		$props[KEY_PAGE_COUNT] = FieldInfo::create([
			'key' => KEY_PAGE_COUNT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'PAGE_COUNT',
			'min' => 0]);

		$props[KEY_PAGE_FORMAT] = FieldInfo::create([
			'key' => KEY_PAGE_FORMAT,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'PAGE_FORMAT',
			'max' => 256]);

		$props[KEY_FREQUENT_WORDS] = FieldInfo::create([
			'key' => KEY_FREQUENT_WORDS,
			'types' => FieldInfo::TYPE_TAGS,
			'name' => 'FREQUENT_WORDS',
			'max' => 2048]);

		$props[KEY_WORD_COUNT] = FieldInfo::create([
			'key' => KEY_WORD_COUNT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'WORD_COUNT',
			'min' => 0]);

		$props[KEY_LINE_COUNT] = FieldInfo::create([
			'key' => KEY_LINE_COUNT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'LINE_COUNT',
			'min' => 0]);

		$props[KEY_PARAGRAPH_COUNT] = FieldInfo::create([
			'key' => KEY_PARAGRAPH_COUNT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'PARAGRAPH_COUNT',
			'min' => 0]);

		$props[KEY_CHARACTER_COUNT] = FieldInfo::create([
			'key' => KEY_CHARACTER_COUNT,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'CHARACTER_COUNT',
			'min' => 0]);

		$props[KEY_HEADING] = FieldInfo::create([
			'key' => KEY_HEADING,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'HEADING',
			'max' => 512,
			'array' => true]);

		$props[KEY_DURATION] = FieldInfo::create([
			'key' => KEY_DURATION,
			'types' => FieldInfo::TYPE_DURATION,
			'name' => 'DURATION']);

		$props[KEY_AUDIO_CODEC] = FieldInfo::create([
			'key' => KEY_AUDIO_CODEC,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'AUDIO_CODEC',
			'max' => 256]);

		$props[KEY_VIDEO_CODEC] = FieldInfo::create([
			'key' => KEY_VIDEO_CODEC,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'VIDEO_CODEC',
			'max' => 256]);

		$props[KEY_BITRATE] = FieldInfo::create([
			'key' => KEY_BITRATE,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'BITRATE',
			'min' => 0]);

		$props[KEY_AUDIO_CHANNELS] = FieldInfo::create([
			'key' => KEY_AUDIO_CHANNELS,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'AUDIO_CHANNELS',
			'min' => 0]);

		$props[KEY_ALBUM] = FieldInfo::create([
			'key' => KEY_ALBUM,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'ALBUM',
			'max' => 256]);

		$props[KEY_GENRE] = FieldInfo::create([
			'key' => KEY_GENRE,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'GENRE',
			'max' => 256]);

		$props[KEY_RELEASE_YEAR] = FieldInfo::create([
			'key' => KEY_RELEASE_YEAR,
			'types' => FieldInfo::TYPE_INT,
			'name' => 'RELEASE_YEAR',
			'min' => 0]);

		$props[KEY_OTHER] = FieldInfo::create([
			'key' => KEY_OTHER,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'OTHER',
			'array' => true]);

		return $props;
	}

	// general
	const FIELD_RATING = 'RATING';

	// video and audio
	const FIELD_VIDEO_TYPE = 'VIDEO_TYPE';
	const VALUE_VIDEO_TYPE_SERIES = 'SERIES';
	const VALUE_VIDEO_TYPE_MOVIE = 'MOVIE';
	const VALUE_VIDEO_TYPE_VIDEO = 'VIDEO';
	const FIELD_AUDIO_TYPE = 'AUDIO_TYPE';
	const VALUE_AUDIO_TYPE_SONG = 'SONG';
	const VALUE_AUDIO_TYPE_BOOK = 'BOOK';
	const VALUE_AUDIO_TYPE_AUDIO = 'AUDIO';
	const FIELD_SUBTITLE_LANGUAGE = 'SUBTITLE_LANGUAGE';
	const FIELD_AUDIO_LANGUAGE = 'AUDIO_LANGUAGE';

	// documents, images and code
	const FIELD_API = 'API'; // e.g. classes and methods
}