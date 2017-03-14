<?php

abstract class MediaProperties {

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

	const KEY_DEVICE = 'device'; // e.g. camera or scanner information

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


		$props[KEY_OTHER] = FieldInfo::create([
			'key' => KEY_OTHER,
			'types' => FieldInfo::TYPE_PLAIN,
			'name' => 'OTHER',
			'array' => true]);
	}



	// general
	const FIELD_AUTHOR = 'AUTHOR';
	const FIELD_AUTHOR_ORGANIZATION = 'AUTHOR_ORGANIZATION';
	const FIELD_TITEL = 'TITLE';
	const FIELD_DESCRIPTION = 'DESCRIPTION';
	const FIELD_TAGS = 'TAGS';
	const FIELD_COPYRIGHT = 'COPYRIGHT';
	const FIELD_RATING = 'RATING';
	const FIELD_COMMENT = 'COMMENT';
	const FIELD_LINKED = 'LINKED';
	const FIELD_CONTAINED_PERSON = 'CONTAINED_PERSON'; // e.g. actors or people in image
	const FIELD_ORDER = 'ORDER'; // e.g. for songs

	// video and audio
	const FIELD_VIDEO_TYPE = 'VIDEO_TYPE';
	const VALUE_VIDEO_TYPE_SERIES = 'SERIES';
	const VALUE_VIDEO_TYPE_MOVIE = 'MOVIE';
	const VALUE_VIDEO_TYPE_VIDEO = 'VIDEO';
	const FIELD_AUDIO_TYPE = 'AUDIO_TYPE';
	const VALUE_AUDIO_TYPE_SONG = 'SONG';
	const VALUE_AUDIO_TYPE_BOOK = 'BOOK';
	const VALUE_AUDIO_TYPE_AUDIO = 'AUDIO';
	const FIELD_GENRE = 'GENRE';
	const FIELD_YEAR = 'YEAR';
	const FIELD_DURATION = 'DURATION';
	const FIELD_LANGUAGE = 'LANGUAGE';
	const FIELD_SUBTITLE_LANGUAGE = 'SUBTITLE_LANGUAGE';
	const FIELD_AUDIO_LANGUAGE = 'AUDIO_LANGUAGE';
	const FIELD_AUDIO_CHANNELS = 'AUDIO_CANNELS';
	const FIELD_ALBUM = 'ALBUM';
	const FIELD_TEXT_CONTENT = 'TEXT_CONTENT'; // e.g. lyrics or subtitles

	// documents, images and code
	const FIELD_DOCUMENT_TYPE = 'DOCUMENT_TYPE';
	const VALUE_DOCUMENT_TYPE_BOOK = 'BOOK';
	const VALUE_DOCUMENT_TYPE_EDITABLE= 'EDITABLE'; // e.g. word, power point document
	const VALUE_DOCUMENT_TYPE_DOCUMENT = 'DOCUMENT'; // e.g. scanned invoice, contract
	const FIELD_PAGES = 'PAGES';
	const FIELD_PAGE_FORMAT = 'PAGE_FORMAT';
	const FIELD_FREQUENT_WORDS = 'FREQUENT_WORDS';
	const FIELD_WORDS = 'WORDS';
	const FIELD_CHARACTERS = 'CHARACTERS';
	const FIELD_LINES = 'LINES';
	const FIELD_PARAGRAPHS = 'PARAGRAPHS';
	const FIELD_HEADING = 'HEADING';
	const FIELD_API = 'API'; // e.g. classes and methods
}