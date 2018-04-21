<?php

// v1: FEATURE COMPLETE

class JpegAnalyzer extends MediaAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'jpeg-analyzer', $config);
	}

	public function magicNumberMatches($hexMagicNumber) {
		return Utils::stringStartsWith($hexMagicNumber, 'ffd8ff');
	}

	public function extensionMatches($extension) {
		return $extension === 'jpg' || $extension === 'jpeg';
	}

	public function mimeMatches($mime) {
		return Utils::stringStartsWith($mime, 'image/jpeg');
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// basic check and information
		$basic = getimagesize($src);
		if ($basic === false || $basic['mime'] !== 'image/jpeg') {
			return $props;
		}

		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_IMAGE];
		$props[] = [MediaProperties::KEY_TYPE, 'JPEG'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, $basic['mime']];
		if ($basic[0] > 0) {
			$props[] = [MediaProperties::KEY_WIDTH, $basic[0]];
		}
		if ($basic[1] > 0) {
			$props[] = [MediaProperties::KEY_HEIGHT, $basic[1]];
		}
		$this->addOtherProperty($basic, 'channels', $props);
		$this->addOtherProperty($basic, 'bits', $props);

		// exif check and information
		$exif = exif_read_data($src);
		if ($exif === false) {
			return $props;
		}

		// if 'FNumber' is set we can assume that this is a photo
		if (isset($exif['FNumber']) && Utils::hasStringContent($exif['FNumber'])) {
			$props[] = [MediaProperties::KEY_IMAGE_TYPE, MediaProperties::VALUE_IMAGE_TYPE_PHOTO];
		}

		if (isset($exif['Make'])) {
			$props[] = [MediaProperties::KEY_MANUFACTURER, $exif['Make']];
		}
		if (isset($exif['Model'])) {
			$props[] = [MediaProperties::KEY_MODEL, $exif['Model']];
		}
		if (isset($exif['Orientation'])) {
			$props[] = [MediaProperties::KEY_ORIENTATION, $exif['Orientation'] - 1];
		}
		if (isset($exif['Software'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE, $exif['Software']];
		} else if (isset($exif['FirmwareVersion'])) {
			$props[] = [MediaProperties::KEY_SOFTWARE, $exif['FirmwareVersion']];
		}
		if ($this->addDateTime($exif, 'DateTimeOriginal', MediaProperties::KEY_CREATED, $props) ||
				$this->addDateTime($exif, 'DateTimeDigitized', MediaProperties::KEY_CREATED, $props)) {
			$this->addDateTime($exif, 'DateTime', MediaProperties::KEY_EDITED, $props);
		} else {
			$this->addDateTime($exif, 'DateTime', MediaProperties::KEY_CREATED, $props);
		}
		if (isset($exif['XResolution']) && isset($exif['YResolution']) && isset($exif['ResolutionUnit'])
				&& $exif['XResolution'] === $exif['YResolution'] && ((string) $exif['ResolutionUnit']) === '2') {
			$resolution = $this->evalRatio($exif['XResolution']);
			if ($resolution > 0) {
				$props[] = [MediaProperties::KEY_RESOLUTION, intval($resolution)];
			}
		}
		if (isset($exif['GPSLatitudeRef']) && isset($exif['GPSLatitude']) &&
				isset($exif['GPSLongitudeRef']) && isset($exif['GPSLongitude'])) {
			$lat = $this->getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
			$lon = $this->getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
			if ($lat !== 0 || $lon !== 0) {
				$props[] = [MediaProperties::KEY_POSITION_LAT, $lat];
				$props[] = [MediaProperties::KEY_POSITION_LON, $lon];
			}
		}
		if (isset($exif['GPSAltitudeRef']) && isset($exif['GPSAltitude'])) {
			$alt = $this->evalRatio($exif['GPSAltitude']);
			if ($alt > 0) {
				$alt = ($exif['GPSAltitudeRef'] === '1' ? -1.0 : 1.0) * $alt;
				$props[] = [MediaProperties::KEY_POSITION_ALT, $alt];
			}
		}
		if (isset($exif['COMPUTED']['IsColor']) && isset($exif['ColorSpace']) && $exif['ColorSpace'] > 1) {
			$props[] = [MediaProperties::KEY_COLORS, $exif['ColorSpace']];
		}
		// Apple specific
		if (isset($exif['UndefinedTag:0xA433']) && isset($exif['UndefinedTag:0xA434'])) {
			$props[] = [MediaProperties::KEY_DEVICE, $exif['UndefinedTag:0xA433'] . ' ' .
				$exif['UndefinedTag:0xA434']];
		}

		// add all exif properties as 'other' information
		foreach ($exif as $key => $value) {
			if (Utils::stringStartsWith($key, 'UndefinedTag')) {
				continue;
			}
			$this->addOtherProperty($exif, $key, $props);
		}
		return $props;
	}

	// convert GPS coordinates
	// source: http://stackoverflow.com/a/2572991
	private function getGps($exifCoord, $hemi) {
		$degrees = count($exifCoord) > 0 ? $this->evalRatio($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? $this->evalRatio($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? $this->evalRatio($exifCoord[2]) : 0;

		$flip = ($hemi == 'W'|| $hemi == 'S') ? -1 : 1;

		return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	private function evalRatio($ratio) {
		$parts = explode('/', $ratio);

		if (count($parts) <= 0) {
			return 0;
		}

		if (count($parts) == 1) {
			return $parts[0];
		}

		return floatval($parts[0]) / floatval($parts[1]);
	}
}