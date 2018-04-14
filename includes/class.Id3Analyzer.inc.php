<?php

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
			if (isset($info['video']['codec'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['codec']];
			} else if (isset($info['video']['fourcc_lookup'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['fourcc_lookup']];
			} else if (isset($info['video']['fourcc'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['fourcc']];
			} else if (isset($info['video']['encoder'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['encoder']];
			} else if (isset($info['video']['dataformat'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['video']['dataformat']];
			} else if (isset($info['fileformat'])) {
				$props[] = [MediaProperties::KEY_VIDEO_CODEC, $info['fileformat']];
			}
		} else {
			$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_AUDIO];
		}

		// audio codec
		if (isset($info['audio']['codec'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CODEC, $info['audio']['codec']];
		} else if (isset($info['audio']['dataformat'])) {
			$props[] = [MediaProperties::KEY_AUDIO_CODEC, $info['audio']['dataformat']];
		} else if (isset($info['fileformat'])) {
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
		if (isset($info['video']['resolution_x'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['video']['resolution_x']];
		} else if (isset($info['matroska']['tracks']['tracks'][0]['PixelWidth'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['matroska']['tracks']['tracks'][0]['PixelWidth']];
		} else if (isset($info['mpeg']['video']['framesize_horizontal'])) {
			$props[] = [MediaProperties::KEY_WIDTH, $info['mpeg']['video']['framesize_horizontal']];
		}
		if (isset($info['video']['resolution_y'])) {
			$props[] = [MediaProperties::KEY_HEIGHT, $info['video']['resolution_y']];
		} else if (isset($info['matroska']['tracks']['tracks'][0]['PixelHeight'])) {
			$props[] = [MediaProperties::KEY_HEIGHT, $info['matroska']['tracks']['tracks'][0]['PixelHeight']];
		} else if (isset($info['mpeg']['video']['framesize_vertical'])) {
			$props[] = [MediaProperties::KEY_HEIGHT, $info['mpeg']['video']['framesize_vertical']];
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
			$props[] = [MediaProperties::KEY_ALBUM, $info['comments']['album']];
		}

		// author
		if (isset($info['comments']['artist'])) {
			$props[] = [MediaProperties::KEY_AUTHOR, $info['comments']['artist']];
		} else if (isset($info['comments']['author'])) {
			$props[] = [MediaProperties::KEY_AUTHOR, $info['comments']['author']];
		} else if (isset($info['asf']['comments']['artist'])) {
			$props[] = [MediaProperties::KEY_AUTHOR, $info['asf']['comments']['artist']];
		}

		// genre
		if (isset($info['comments']['genre'])) {
			$props[] = [MediaProperties::KEY_GENRE, $info['comments']['genre']];
		} else if (isset($info['asf']['comments']['genre'])) {
			$props[] = [MediaProperties::KEY_GENRE, $info['asf']['comments']['genre']];
		}

		// title
		if (isset($info['comments']['title'])) {
			if (is_array($info['comments']['title'])) {
				if (count($info['comments']['title']) > 0) {
					$props[] = [MediaProperties::KEY_TITLE, Utils::findLongestString($info['comments']['title'])];
				}
			} else {
				$props[] = [MediaProperties::KEY_TITLE, $info['comments']['title']];
			}
		} else if (isset($info['comments']['subject'])) {
			$props[] = [MediaProperties::KEY_TITLE, $info['comments']['subject']];
		} else if (isset($info['asf']['comments']['title'])) {
			$props[] = [MediaProperties::KEY_TITLE, $info['asf']['comments']['title']];
		}

		// release year
		if (isset($info['comments']['year'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR, $info['comments']['year']];
		} else if (isset($info['comments']['date_release'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR, (int) $info['comments']['date_release']];
		} else if (isset($info['comments']['date'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR, (int) $info['comments']['date']];
		} else if (isset($info['comments']['creationdate'])) {
			$props[] = [MediaProperties::KEY_RELEASE_YEAR, (int) $info['comments']['creationdate']];
		}

		// part number


		// track number/part number, urls, software, language, comment, lyrics

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
}