<?php

// v1: FEATURE COMPLETE

class Id3Analyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'id3-analyzer', $config);
	}

	public function mimeMatches($mime) {
		return Utils::stringStartsWith($mime, 'audio/') ||
			Utils::stringStartsWith($mime, 'video/');
	}

	public function extensionMatches($extension) {
		switch ($extension) {
			case '3gp':
			case 'aac':
			case 'ac3':
			case 'aif':
			case 'aiff':
			case 'ape':
			case 'asf':
			case 'au':
			case 'avi':
			case 'avr':
			case 'bonk':
			case 'cda':
			case 'ds2':
			case 'dss':
			case 'dts':
			case 'flac':
			case 'flv':
			case 'iff':
			case 'la':
			case 'lw':
			case 'm4a':
			case 'mid':
			case 'mka':
			case 'mkv':
			case 'mov':
			case 'mp1':
			case 'mp2':
			case 'mp3':
			case 'mp4':
			case 'mpc':
			case 'mpg':
			case 'nsv':
			case 'ofs':
			case 'ogg':
			case 'pac':
			case 'ra':
			case 'rka':
			case 'rm':
			case 'sds':
			case 'shn':
			case 'spx':
			case 'swf':
			case 'tta':
			case 'vgf':
			case 'voc':
			case 'vox':
			case 'wav':
			case 'webm':
			case 'wma':
			case 'wmv':
			case 'wv':
			case 'wvc':
				return true;
			default:
				return false;
		}
	}

	public function extractProperties($src, $ext) {
		$props = [];

		Utils::requireLibrary('getID3', 'getid3/getid3.php');

		$id3 = new getID3();
		$info = $id3->analyze($src);
		// merge all available tags (for example, ID3v2 + ID3v1) into one array
		getid3_lib::CopyTagsToComments($info);

		// check for a proper playtime
		if (!isset($info['playtime_seconds'])) {
			return $props;
		}

		// check for audio or video
		if ($this->isVideo($info)) {
			$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_VIDEO];

			// video codec
			if ($this->hasContent($info['video']['fourcc_lookup'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['fourcc_lookup']];
			} else if ($this->hasContent($info['video']['codec'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['codec']];
			} else if ($this->hasContent($info['video']['fourcc'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['fourcc']];
			} else if ($this->hasContent($info['video']['encoder'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['encoder']];
			} else if ($this->hasContent($info['video']['dataformat'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['dataformat']];
			} else if ($this->hasContent($info['fileformat'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['fileformat']];
			}
		} else {
			$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_AUDIO];
		}

		// audio codec
		if ($this->hasContent($info['audio']['codec'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CODEC, $info['audio']['codec']];
		} else if ($this->hasContent($info['audio']['dataformat'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CODEC, $info['audio']['dataformat']];
		} else if ($this->hasContent($info['fileformat'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CODEC, $info['fileformat']];
		}

		// name type
		if (strlen($ext) >= 2) {
			$props[] = [MediaProperties::KEY_TYPE, strtoupper($ext)];
		}

		// mime type
		if (isset($info['mime_type'])) {
			$props[] = [MediaProperties::KEY_MIME_TYPE, $info['mime_type']];
		}
		
		// duration
		$props[] = [MediaProperties::KEY_DURATION, $info['playtime_seconds']];

		// resolution
		if ($this->hasContent($info['video']['resolution_x'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['video']['resolution_x']];
		} else if ($this->hasContent($info['matroska']['tracks']['tracks'][0]['PixelWidth'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['matroska']['tracks']['tracks'][0]['PixelWidth']];
		} else if ($this->hasContent($info['mpeg']['video']['framesize_horizontal'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['mpeg']['video']['framesize_horizontal']];
		}
		if ($this->hasContent($info['video']['resolution_y'])) {
			$props[] = [MediaProperties::KEY_HEIGHT, $info['video']['resolution_y']];
		} else if ($this->hasContent($info['matroska']['tracks']['tracks'][0]['PixelHeight'])) {
			$props[] = [MediaProperties::KEY_HEIGHT,
				$info['matroska']['tracks']['tracks'][0]['PixelHeight']];
		} else if ($this->hasContent($info['mpeg']['video']['framesize_vertical'])) {
			$props[] = [MediaProperties::KEY_HEIGHT, $info['mpeg']['video']['framesize_vertical']];
		}

		// orientation
		if (isset($info['video']['rotate'])) {
			switch ($info['video']['rotate']) {
				case 0:
					$props[] = [MediaProperties::KEY_ORIENTATION, 0];
					break;
				case 90:
					$props[] = [MediaProperties::KEY_ORIENTATION, 5];
					break;
				case 180:
					$props[] = [MediaProperties::KEY_ORIENTATION, 2];
					break;
				case 270:
					$props[] = [MediaProperties::KEY_ORIENTATION, 7];
					break;
				default:
					// do nothing
					break;
			}
		}

		// bitrate
		if (isset($info['bitrate'])) {
			$props[] = [MediaProperties::KEY_BITRATE, $info['bitrate']];
		}

		// channels
		if (isset($info['audio']['channels'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CHANNELS, $info['audio']['channels']];
		}

		// album
		if (isset($info['comments']['album'])) {
			$props[] = [MediaProperties::KEY_ALBUM, Utils::findLongestString($info['comments']['album'])];
		}

		// author
		if ($this->hasContent($info['comments']['artist'])) {
			$props[] = [MediaProperties::KEY_AUTHOR, Utils::findLongestString($info['comments']['artist'])];
		} else if ($this->hasContent($info['comments']['author'])) {
			$props[] = [MediaProperties::KEY_AUTHOR, Utils::findLongestString($info['comments']['author'])];
		} else if ($this->hasContent($info['asf']['comments']['artist'])) {
			$props[] = [MediaProperties::KEY_AUTHOR,
				Utils::findLongestString($info['asf']['comments']['artist'])];
		}

		// organization
		if ($this->hasContent($info['comments']['publisher'])) {
			$props[] = [MediaProperties::KEY_AUTHOR_ORGANIZATION,
				Utils::findLongestString($info['comments']['publisher'])];
		} else if ($this->hasContent($info['comments']['contentdistributor'])) {
			$props[] = [MediaProperties::KEY_AUTHOR_ORGANIZATION,
				Utils::findLongestString($info['comments']['contentdistributor'])];
		}

		// genre
		if ($this->hasContent($info['comments']['genre'])) {
			$props[] = [MediaProperties::KEY_GENRE, Utils::findLongestString($info['comments']['genre'])];
		} else if ($this->hasContent($info['asf']['comments']['genre'])) {
			$props[] = [MediaProperties::KEY_GENRE,
				Utils::findLongestString($info['asf']['comments']['genre'])];
		}

		// title
		if ($this->hasContent($info['comments']['title'])) {
			$props[] = [MediaProperties::KEY_TITLE, Utils::findLongestString($info['comments']['title'])];
		} else if ($this->hasContent($info['comments']['subject'])) {
			$props[] = [MediaProperties::KEY_TITLE, Utils::findLongestString($info['comments']['subject'])];
		} else if ($this->hasContent($info['asf']['comments']['title'])) {
			$props[] = [MediaProperties::KEY_TITLE,
				Utils::findLongestString($info['asf']['comments']['title'])];
		}

		// release year
		if ($this->hasContent($info['comments']['year'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR,
				Utils::findLongestString($info['comments']['year'])];
		} else if ($this->hasContent($info['comments']['date_release'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR,
				(int) Utils::findLongestString($info['comments']['date_release'])];
		} else if ($this->hasContent($info['comments']['date'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR,
				(int) Utils::findLongestString($info['comments']['date'])];
		}

		// part number
		if ($this->hasContent($info['comments']['track_number'])) {
			// might be e.g. "1/22"
			$split = explode('/', Utils::findLongestString($info['comments']['track_number']));
			if (count($split) == 1 && ((int) $split[0]) > 0) {
				$props[] = [MediaProperties::KEY_PART, $split[0]];
			} else if (count($split) == 2 && ((int) $split[0]) > 0 && ((int) $split[1]) > 0) {
				$props[] = [MediaProperties::KEY_PART, $split[0]];
				$props[] = [MediaProperties::KEY_PARTS, $split[1]];
			}
		} else if ($this->hasContent($info['comments']['track'])) {
			$props[] = [MediaProperties::KEY_PART, Utils::findLongestString($info['comments']['track'])];
		} else if ($this->hasContent($info['comments']['part_number'])) {
			$props[] = [MediaProperties::KEY_PART, Utils::findLongestString($info['comments']['part_number'])];
		} else if ($this->hasContent($info['comments']['tracknumber'])) {
			$props[] = [MediaProperties::KEY_PART, Utils::findLongestString($info['comments']['tracknumber'])];
		}

		// parts
		if (isset($info['comments']['total_parts'])) {
			$props[] = [MediaProperties::KEY_PARTS,
				Utils::findLongestString($info['comments']['total_parts'])];
		}

		// software
		if ($this->hasContent($info['comments']['encoder'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE,
				Utils::findLongestString($info['comments']['encoder'])];
		} else if ($this->hasContent($info['comments']['software'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE,
				Utils::findLongestString($info['comments']['software'])];
		} else if ($this->hasContent($info['comments']['writingapp'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE,
				Utils::findLongestString($info['comments']['writingapp'])];
		} else if ($this->hasContent($info['comments']['toolname'])) {
			$tool = Utils::findLongestString($info['comments']['toolname']);
			if (isset($info['comments']['toolversion'])) {
				$tool = $tool . ' ' . Utils::findLongestString($info['comments']['toolversion']);
			}
			$props[] = [MediaProperties::KEY_SOFTWARE, $tool];
		} else if ($this->hasContent($info['comments']['encoded_by'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE,
				Utils::findLongestString($info['comments']['encoded_by'])];
		} else if ($this->hasContent($info['comments']['encodedby'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE,
				Utils::findLongestString($info['comments']['encodedby'])];
		}

		// lyrics
		if (isset($info['comments']['unsynchronised_lyric'])) {
			$props[] = [MediaProperties::KEY_TEXT_CONTENT,
				Utils::findLongestString($info['comments']['unsynchronised_lyric'])];
		}

		// language
		if (isset($info['comments']['language'])) {
			$lang = Utils::findLongestString($info['comments']['language']);
			if ($lang !== 'Undetermined') {
				$props[] = [MediaProperties::KEY_LANGUAGE, $lang];
			}
		}

		// copyright
		if (isset($info['comments']['copyright'])) {
			$props[] = [MediaProperties::KEY_COPYRIGHT,
				Utils::findLongestString($info['comments']['copyright'])];
		}

		// urls
		$urls = [];
		if (isset($info['comments']['url']) && is_array($info['comments']['url'])) {
			$this->addUrls($info['comments']['url'], $urls);
		}
		if (isset($info['comments']['url_station']) && is_array($info['comments']['url_station'])) {
			$this->addUrls($info['comments']['url_station'], $urls);
		}
		if (isset($info['comments']['url_publisher']) && is_array($info['comments']['url_publisher'])) {
			$this->addUrls($info['comments']['url_publisher'], $urls);
		}
		$props[] = [MediaProperties::KEY_LINKED, $urls];

		// Apple specific tags

		// manufacturer
		if (isset($info['comments']['make'])) {
			$props[] = [MediaProperties::KEY_MANUFACTURER,
				Utils::findLongestString($info['comments']['make'])];
		}

		// model
		if (isset($info['comments']['model'])) {
			$props[] = [MediaProperties::KEY_MODEL,
				Utils::findLongestString($info['comments']['model'])];
		}

		// creation date
		$this->addDateTime($info['comments'], 'creationdate', MediaProperties::KEY_CREATED, $props);

		// GPS
		if (isset($info['comments']['gps_latitude']) && isset($info['comments']['gps_longitude'])) {
			$props[] = [MediaProperties::KEY_POSITION_LAT,
				Utils::findLongestString($info['comments']['gps_latitude'])];
			$props[] = [MediaProperties::KEY_POSITION_LON,
				Utils::findLongestString($info['comments']['gps_longitude'])];
		}
		if (isset($info['comments']['gps_altitude'])) {
			$props[] = [MediaProperties::KEY_POSITION_ALT,
				Utils::findLongestString($info['comments']['gps_altitude'])];
		}

		// comments
		if (isset($info['comments']['comment'])) {
			$props[] = [MediaProperties::KEY_COMMENT, Utils::findLongestString($info['comments']['comment'])];
		}

		// other audio
		if (isset($info['audio'])) {
			foreach ($info['audio'] as $key => $value) {
				$this->addOtherProperty($info['audio'], $key, $props);
			}
		}
		// other video
		if (isset($info['video'])) {
			foreach ($info['video'] as $key => $value) {
				$this->addOtherProperty($info['video'], $key, $props);
			}
		}
		// other comments
		foreach ($info['comments'] as $key => $value) {
			$this->addOtherProperty($info['comments'], $key, $props);
		}

		return $props;
	}

	private function isVideo($info) {
		// check if there is a video tag
		if (isset($info['video']['resolution_x']) && isset($info['video']['resolution_y'])) {
			// check that resolution is not 0x0
			if ($info['video']['resolution_x'] === 0 && $info['video']['resolution_y'] === 0) {
				return false;
			}
		}
		// check mime type
		if (isset($info['mime_type'])) {
			$split = explode('/', $info['mime_type']);
			if ($split[0] === 'video') {
				return true;
			} else if ($split[0] === 'audio') {
				return false;
			}
		}
		// check for keys that indicate a video format
		// use statistics for container formats (e.g. quicktime is usually video)
		$keys = ['video', 'asf', 'mpeg', 'nsv', 'quicktime', 'real', 'swf', 'matroska'];
		$infoKeys = array_keys($info);
		if (count(array_intersect($keys, $infoKeys)) > 0) {
			return true;
		}
		return false;
	}

	private function hasContent(&$element) {
		if (isset($element)) {
			$value = Utils::findLongestString($element);
			return Utils::hasStringContent($value);
		}
	}

	private function addUrls(&$elements, &$urls) {
		foreach ($elements as $value) {
			if (Utils::stringStartsWith($value, 'http://') ||
					Utils::stringStartsWith($value, 'https://')) {
				$urls[] = $value;
			} else if (Utils::stringStartsWith($value, 'www.')) {
				$urls[] = 'http://' . $value;
			}
		}
	}
}