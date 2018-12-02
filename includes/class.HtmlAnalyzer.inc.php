<?php

// v1: FEATURE COMPLETE

class HtmlAnalyzer extends DocumentAnalyzer {

	public function __construct($config) {
		parent::__construct(1, 'html-analyzer', $config);
	}

	public function extensionMatches($extension) {
		return $extension === 'html' || $extension === 'htm' ||
			$extension === 'xhtm' || $extension === 'xhtml';
	}

	public function mimeMatches($mime) {
		return $mime === 'text/html' || $mime === 'application/xhtml+xml';
	}

	public function textContentMatches($text) {
		return Utils::stringStartsWith($text, '<!DOCTYPE html') ||
			Utils::stringStartsWith($text, '<!DOCTYPE HTML') ||
			Utils::stringStartsWith($text, '<HTML>') ||
			Utils::stringStartsWith(
				$text,
				'<?xml version="1.0" encoding="UTF-8"?>'. "\n" . '<!DOCTYPE html');
	}

	public function extractProperties($src, $ext) {
		$props = [];

		// read content
		$text = file_get_contents($src);
		if ($text === false) {
			return $props;
		}

		// add general properties
		$props[] = [MediaProperties::KEY_TYPE_GROUP, MediaProperties::VALUE_TYPE_GROUP_DOCUMENT];
		$props[] = [MediaProperties::KEY_DOCUMENT_TYPE, MediaProperties::VALUE_DOCUMENT_TYPE_WEBSITE];
		$props[] = [MediaProperties::KEY_TYPE, 'HTML'];
		$props[] = [MediaProperties::KEY_MIME_TYPE, 'text/html'];

		// analyze DOM
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$result = $dom->loadHTMLFile($src, LIBXML_NOWARNING | LIBXML_NOERROR);
		libxml_use_internal_errors(false);
		if ($result === false) {
			return $props;
		}

		// remove scripts and styles
		foreach (iterator_to_array($dom->getElementsByTagName('script')) as $node) {
			$node->parentNode->removeChild($node);
		}
		foreach (iterator_to_array($dom->getElementsByTagName('style')) as &$node) {
			$node->parentNode->removeChild($node);
		}

		// title
		foreach ($dom->getElementsByTagName('title') as $node) {
			$props[] = [MediaProperties::KEY_TITLE, $node->textContent];
		}

		$xpath = new DOMXpath($dom);

		// headings
		$headings = [];
		$elements = $xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');
		foreach ($elements as $node) {
			$headings[] = $node->textContent;
		}
		$props[] = [MediaProperties::KEY_HEADING, $headings];

		$tags = [];
		$authors = [];

		// link tags
		$elements = $xpath->query("//a[@rel='tag']");
		foreach ($elements as $node) {
			$tags[] = $node->textContent;
		}

		// link authors
		$elements = $xpath->query("//a[@rel='author']");
		foreach ($elements as $node) {
			$authors[] = $node->textContent;
		}

		// link license
		$elements = $xpath->query("//a[@rel='license']");
		foreach ($elements as $node) {
			$props[] = [MediaProperties::KEY_COPYRIGHT, $node->textContent];
		}

		// extract meta data
		foreach ($dom->getElementsByTagName('meta') as $node) {
			$key = null;
			if ($node->hasAttribute('name')) {
				$key = $node->getAttribute('name');
			} else if ($node->hasAttribute('property')) {
				$key = $node->getAttribute('property');
			}
			$value = null;
			if ($node->hasAttribute('content')) {
				$value = $node->getAttribute('content');
			}
			if (isset($key) && isset($value)) {
				$key = strtolower($key);
				switch ($key) {
					case 'article:tag':
					case 'book:tag':
						$tags[] = $value;
						break;
					case 'article:author':
					case 'book:author':
					case 'twitter:creator':
						$authors[] = $value;
						break;
					default:
						$this->addMetaInformation($props, $key, $value);
						break;
				}
			}
		}

		// tags
		if (count($tags) > 0) {
			$props[] = [MediaProperties::KEY_TAGS, $tags];
		}

		// authors
		if (count($authors) > 0) {
			$props[] = [MediaProperties::KEY_AUTHOR, $authors];
		}

		// determine frequent words
		$words = $this->generateFrequentWords($dom->textContent);
		if (count($words) > 0) {
			$props[] = [MediaProperties::KEY_FREQUENT_WORDS, implode(', ', $words)];
		}

		return $props;
	}

	private function addMetaInformation(&$props, $key, $value) {
		switch ($key) {
			case 'author':
				$props[] = [MediaProperties::KEY_AUTHOR, explode(';', $value)];
				break;
			case 'generator':
				$props[] = [MediaProperties::KEY_SOFTWARE, $value];
				break;
			case 'keywords':
				$props[] = [MediaProperties::KEY_TAGS, $value];
				break;
			case 'description':
			case 'og:description':
			case 'twitter:description':
				$props[] = [MediaProperties::KEY_DESCRIPTION, $value];
				break;
			case 'og:locale':
				$props[] = [MediaProperties::KEY_LANGUAGE, $value];
				break;
			case 'og:type':
				switch ($value) {
					case 'article':
						$props[] = [
							MediaProperties::KEY_DOCUMENT_TYPE,
							MediaProperties::VALUE_DOCUMENT_TYPE_ARTICLE];
						break;
					case 'book':
						$props[] = [
							MediaProperties::KEY_DOCUMENT_TYPE,
							MediaProperties::VALUE_DOCUMENT_TYPE_BOOK];
						break;
				}
				break;
			case 'og:title':
			case 'twitter:title':
				$props[] = [MediaProperties::KEY_TITLE, $value];
				break;
			case 'og:url':
				$props[] = [MediaProperties::KEY_LINKED, $value];
				break;
			case 'og:site_name':
				$props[] = [MediaProperties::KEY_AUTHOR_ORGANIZATION, $value];
				break;
			case 'article:section':
				$props[] = [MediaProperties::KEY_TEXT_CONTENT, $value];
				break;
			case 'article:published_time':
			case 'book:release_date':
				$props[] = [MediaProperties::KEY_CREATED, $value];
				break;
			case 'article:modified_time':
			case 'og:updated_time':
				$props[] = [MediaProperties::KEY_EDITED, $value];
				break;
			case 'book:isbn':
				$props[] = [MediaProperties::KEY_REVISION, $value];
				break;
		}
	}
}