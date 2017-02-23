<?php

class MediaStore {

	// database operations
	private $mediaOperations;

	// member variables
	private $rawPath;
	private $thumbnailSmallPath;
	private $thumbnailLargePath;

	public function __construct(
			$rawPath,
			$thumbnailSmallPath,
			$thumbnailLargePath,
			$mediaOperations) {
		$this->rawPath = $rawPath;
		$this->thumbnailSmallPath = $thumbnailSmallPath;
		$this->thumbnailLargePath = $thumbnailLargePath;
		$this->mediaOperations = $mediaOperations;
	}

	public function storeTempMedia($mgid, $file) {
		$result = true;
		// create directories if they don't exist
		if (!file_exists($this->rawPath)) {
			$result = $result && mkdir($this->rawPath, 0777, true);
		}
		if (!file_exists($this->thumbnailSmallPath)) {
			$result = $result && mkdir($this->thumbnailSmallPath, 0777, true);
		}
		if (!file_exists($this->thumbnailLargePath)) {
			$result = $result && mkdir($this->thumbnailLargePath, 0777, true);
		}

		if (!$result) {
			return 'FILESYSTEM_ACCESS_ERROR';
		}

		$checksum = md5_file($file);
		$size = filesize($file);

		$mid = $this->mediaOperations->addTempMedia($mgid, $checksum, $size);
		if ($mid === false) {
			return 'UNKNOWN_ERROR';
		}

		$result = move_uploaded_file($file, $this->rawPath . '/' . $mid);
		if (!$result) {
			return 'FILESYSTEM_ACCESS_ERROR';
		}

		return $mid;
	}

	public function commitTempMedia($mgid, $mid, $size, $checksum, $path, $modified) {
		$media = $this->mediaOperations->getTempMedia($mid);
		if ($media === false || $media['group'] !== $mgid) {
			return 'MEDIA_NOT_FOUND';
		}
		// compare checksum and size (if possible)
		if ($size !== null && $size !== $media['size']) {
			return 'CORRUPT_MEDIA';
		}
		if ($checksum !== null && $checksum !== $media['checksum']) {
			return 'CORRUPT_MEDIA';
		}

		$modified = ($modified !== null) ? date('Y-m-d H:i:s', $modified) : null;

		if ($this->mediaOperations->commitTempMedia($mid, $path, $modified) === false) {
			return 'UNKNOWN_ERROR';
		} else {
			return true;
		}
	}

	public function deleteAllTempMedia($mgid) {
		$media = $this->mediaOperations->getAllTempMedia();
		if ($media === false) {
			return 'UNKNOWN_ERROR';
		}
		if (count($media) === 0) {
			return true;
		}
		// delete file(s)
		foreach ($media as $mid) {
			$result = true;
			if (file_exists($this->rawPath . '/' . $mid['value'])) {
				$result = $result && unlink($this->rawPath . '/' . $mid['value']);
			}
			if (file_exists($this->thumbnailSmallPath . '/' . $mid['value'])) {
				$result = $result && unlink($this->thumbnailSmallPath . '/' . $mid['value']);
			}
			if (file_exists($this->thumbnailLargePath . '/' . $mid['value'])) {
				$result = $result && unlink($this->thumbnailLargePath . '/' . $mid['value']);
			}
			if ($result === false) {
				return 'FILESYSTEM_ACCESS_ERROR';
			}
		}
		// clean database
		$result = $this->mediaOperations->deleteAllTempMedia($mgid);
		if ($result === false) {
			return 'UNKNOWN_ERROR';
		}
		return true;
	}

	public function deleteMedia($mid) {
		// delete attachments
		$attachments = $this->mediaOperations->getAttachements($mid);
		if ($attachments === false) {
			return 'UNKNOWN_ERROR';
		}
		foreach ($attachments as $attachment) {
			$result = $this->deleteMedia($attachment['value']);
			if ($result !== true) {
				return $result;
			}
		}
		// delete file(s)
		$result = unlink($this->rawPath . '/' . $mid);
		if (file_exists($this->thumbnailSmallPath . '/' . $mid)) {
			$result = $result && unlink($this->thumbnailSmallPath . '/' . $mid);
		}
		if (file_exists($this->thumbnailLargePath . '/' . $mid)) {
			$result = $result && unlink($this->thumbnailLargePath . '/' . $mid);
		}
		if ($result === false) {
			return 'FILESYSTEM_ACCESS_ERROR';
		}
		// delete in media database
		$result = $this->mediaOperations->deleteMedia($mid);
		if ($result === false) {
			return 'UNKNOWN_ERROR';
		}
		return true;
	}

	public function moveMedia($mid, $path) {
		$result = $this->mediaOperations->moveMedia($mid, $path);
		if ($result === false) {
			return 'UNKNOWN_ERROR';
		}
		return true;
	}

	public function attachMedia($targetMid, $attachmentMid, $attachmentPath) {
		$result = $this->mediaOperations->attachMedia($targetMid, $attachmentMid, $attachmentPath);
		if ($result === false) {
			return 'UNKNOWN_ERROR';
		}
		return true;
	}

	public function detachMedia($mid, $detachmentPath) {
		$result = $this->mediaOperations->detachMedia($mid, $detachmentPath);
		if ($result === false) {
			return 'UNKNOWN_ERROR';
		}
		return true;
	}

	public function copyMedia($mid, $copiedpath) {
		$newMid = $this->mediaOperations->copyMedia($mid, $copiedpath);
		if ($newMid === false) {
			return 'UNKNOWN_ERROR';
		}
		// copy file(s)
		$result = copy($this->rawPath . '/' . $mid, $this->rawPath . '/' . $newMid);
		if (file_exists($this->thumbnailSmallPath . '/' . $mid)) {
			$result = $result && copy(
				$this->thumbnailSmallPath . '/' . $mid,
				$this->thumbnailSmallPath . '/' . $newMid);
		}
		if (file_exists($this->thumbnailLargePath . '/' . $mid)) {
			$result = $result && copy(
				$this->thumbnailLargePath . '/' . $mid,
				$this->thumbnailLargePath . '/' . $newMid);
		}
		if ($result === false) {
			return 'FILESYSTEM_ACCESS_ERROR';
		}
		// copy attachments
		$attachments = $this->mediaOperations->getAttachements($mid);
		if ($attachments === false) {
			return 'UNKNOWN_ERROR';
		}
		foreach ($attachments as $attachment) {
			$result = $this->copyAttachment($attachment['value'], $newMid);
			if ($result !== true) {
				return $result;
			}
		}
		return true;
	}

	public function copyAttachment($mid, $parent) {
		$newMid = $this->mediaOperations->copyAttachment($mid, $parent);
		if ($newMid === false) {
			return 'UNKNOWN_ERROR';
		}
		// copy file(s)
		$result = copy($this->rawPath . '/' . $mid, $this->rawPath . '/' . $newMid);
		if (file_exists($this->thumbnailSmallPath . '/' . $mid)) {
			$result = $result && copy(
				$this->thumbnailSmallPath . '/' . $mid,
				$this->thumbnailSmallPath . '/' . $newMid);
		}
		if (file_exists($this->thumbnailLargePath . '/' . $mid)) {
			$result = $result && copy(
				$this->thumbnailLargePath . '/' . $mid,
				$this->thumbnailLargePath . '/' . $newMid);
		}
		if ($result === false) {
			return 'FILESYSTEM_ACCESS_ERROR';
		}
		return true;
	}
}
?>