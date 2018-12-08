<?php

// v1: FEATURE COMPLETE

class ArchiveAnalyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'archive-analyzer', $config);
	}

	public function extensionMatches($extension) {
		switch ($extension) {
			case 'iso':
			case 'tar':
			case 'gz':
			case 'bz2':
			case 'tgz':
			case 'xz':
			case 'txz':
			case 'lzma':
			case 'tlz':
			case '7z':
			case 'cab':
			case 'dmg':
			case 'jar':
			case 'rar':
				return true;
			default:
				return false;
		}
	}

	public function mimeMatches($mime) {
		return Utils::stringEndsWith($mime, '-compressed') ||
			Utils::stringEndsWith($mime, '-compress') ||
			Utils::stringEndsWith($mime, '-archive');
	}

	public function extractProperties($src, $ext) {
		$props = [];

		switch ($ext) {
			case 'iso':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_APPLICATION];
				$props[] = [MediaProperties::KEY_TYPE, 'ISO'];
				break;
			case 'tar':
			case 'gz':
			case 'bz2':
			case 'tgz':
			case 'xz':
			case 'txz':
			case 'lzma':
			case 'tlz':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_ARCHIVE];
				$props[] = [MediaProperties::KEY_TYPE, 'TAR'];
				break;
			case '7z':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_ARCHIVE];
				$props[] = [MediaProperties::KEY_TYPE, '7-Zip'];
				$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/x-7z-compressed'];
				break;
			case 'dmg':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_APPLICATION];
				$props[] = [MediaProperties::KEY_TYPE, 'DMG'];
				$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/x-apple-diskimage'];
				break;
			case 'cab':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_APPLICATION];
				$props[] = [MediaProperties::KEY_TYPE, 'CAB'];
				$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/vnd.ms-cab-compressed'];
				break;
			case 'jar':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_APPLICATION];
				$props[] = [MediaProperties::KEY_TYPE, 'JAR'];
				$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/java-archive'];
				break;
			case 'rar':
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_ARCHIVE];
				$props[] = [MediaProperties::KEY_TYPE, 'RAR'];
				$props[] = [MediaProperties::KEY_MIME_TYPE, 'application/x-rar-compressed'];
				break;
			default:
				$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_ARCHIVE];
		}

		return $props;
	}
}