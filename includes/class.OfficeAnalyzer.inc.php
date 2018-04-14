<?php

// v1: FEATURE COMPLETE

class OfficeAnalyzer extends DocumentAnalyzer {

	public function __construct($cmsVersion, $name, $config) {
		parent::__construct($cmsVersion, $name, $config);
	}

	public function extractCoreXml(&$props, $src) {
		// extract core.xml properties
		$core = $this->loadXml($src, 'docProps/core.xml');
		if ($core !== false) {
			$dc = $core->children('dc', true);
			$cp = $core->children('cp', true);
			$dcterms = $core->children('dcterms', true);

			// title
			if (isset($dc->title) && ((string) $dc->title) !== 'PowerPoint Presentation') {
				$props[] = [MediaProperties::KEY_TITLE, (string) $dc->title];
			}

			// authors
			$authors = [];
			if (isset($cp->lastModifiedBy) && strpos((string) $cp->lastModifiedBy, 'Microsoft') === false) {
				$authors = array_merge($authors, explode(';', (string) $cp->lastModifiedBy));
			}
			if (isset($dc->creator) && strpos((string) $dc->creator, 'Microsoft') === false) {
				$authors = array_merge($authors, explode(';', (string) $dc->creator));
			}
			$props[] = [MediaProperties::KEY_AUTHOR, $authors];

			// revision
			if (isset($cp->revision)) {
				$props[] = [MediaProperties::KEY_REVISION, (string) $cp->revision];
			}

			// created
			if (isset($dcterms->created)) {
				$created = strtotime((string) $dcterms->created);
				if ($created !== false && $created > 0) {
					$props[] = [MediaProperties::KEY_CREATED, date("Y-m-d H:i:s", $created)];
				}
			}

			// modified
			if (isset($dcterms->modified)) {
				$modified = strtotime((string) $dcterms->modified);
				if ($modified !== false && $modified > 0) {
					$props[] = [MediaProperties::KEY_EDITED, date("Y-m-d H:i:s", $modified)];
				}
			}

			// printed
			if (isset($cp->lastPrinted)) {
				$lastPrinted = strtotime((string) $cp->lastPrinted);
				if ($lastPrinted !== false && $lastPrinted > 0) {
					$props[] = [MediaProperties::KEY_OTHER,
						'lastPrinted=' . date("Y-m-d H:i:s", $lastPrinted)];
				}
			}

			// tags
			if (isset($cp->keywords)) {
				$props[] = [MediaProperties::KEY_TAGS, Utils::normalizeTags((string) $cp->keywords)];
			}

			// description
			if (isset($dc->description)) {
				$props[] = [MediaProperties::KEY_DESCRIPTION, (string) $dc->description];
			}
		}
	}

	public function extractAppXml(&$props, $src) {
		// extract app.xml properties
		$app = $this->loadXml($src, 'docProps/app.xml');
		if ($app !== false) {
			$p = $app->children();

			// words
			if (isset($p->Words)) {
				$props[] = [MediaProperties::KEY_WORD_COUNT, (int) $p->Words];
			}

			// paragraphs
			if (isset($p->Paragraphs)) {
				$props[] = [MediaProperties::KEY_PARAGRAPH_COUNT, (int) $p->Paragraphs];
			}

			// lines
			if (isset($p->Lines)) {
				$props[] = [MediaProperties::KEY_LINE_COUNT, (int) $p->Lines];
			}

			// characters
			if (isset($p->CharactersWithSpaces)) {
				$props[] = [MediaProperties::KEY_CHARACTER_COUNT, (int) $p->CharactersWithSpaces];
			} else if (isset($p->Characters)) {
				$props[] = [MediaProperties::KEY_CHARACTER_COUNT, (int) $p->Characters];
			}

			// application
			if (isset($p->Application) && isset($p->AppVersion)) {
				$props[] = [MediaProperties::KEY_SOFTWARE,
					((string) $p->Application) . ' ' . ((string) $p->AppVersion)];
			} else if (isset($p->Application)) {
				$props[] = [MediaProperties::KEY_SOFTWARE, (string) $p->Application];
			}

			// pages
			if (isset($p->Slides)) {
				$props[] = [MediaProperties::KEY_PAGE_COUNT, (int) $p->Slides];
			} else if (isset($p->Pages)) {
				$props[] = [MediaProperties::KEY_PAGE_COUNT, (int) $p->Pages];
			}

			// company
			if (isset($p->Company)) {
				$props[] = [MediaProperties::KEY_AUTHOR_ORGANIZATION, (string) $p->Company];
			}

			// hidden slides
			if (Utils::hasStringContent($p->HiddenSlides)) {
				$props[] = [MediaProperties::KEY_OTHER, 'HiddenSlides=' . (string) $p->HiddenSlides];
			}
			// notes
			if (Utils::hasStringContent($p->Notes)) {
				$props[] = [MediaProperties::KEY_OTHER, 'Notes=' . (string) $p->Notes];
			}
			// presentation format
			if (Utils::hasStringContent($p->PresentationFormat)) {
				$props[] = [MediaProperties::KEY_OTHER,
					'PresentationFormat=' . (string) $p->PresentationFormat];
			}
			// total time
			if (Utils::hasStringContent($p->TotalTime)) {
				$props[] = [MediaProperties::KEY_OTHER, 'TotalTime=' . (string) $p->TotalTime];
			}
		}
	}

	public function loadXml($src, $path) {
		return Utils::ignoreErrors(function() use ($src, $path){
			return simplexml_load_file(
				'zip://' . $src . '#' . $path,
				'SimpleXMLElement',
				LIBXML_NOWARNING);
		});
	}
}