<?php

// v1: FEATURE COMPLETE

abstract class DocumentAnalyzer extends MediaAnalyzer {

	private $processors = [];

	public function __construct($cmsVersion, $name, $config) {
		parent::__construct($cmsVersion, $name, $config);

		$this->processors[] = new EnglishTextProcessor($config);
		$this->processors[] = new GermanTextProcessor($config);
	}

	public function generateFrequentWords($text) {

		$subtext = substr($text, 0, Utils::configOrDefault($this->config, 'document-analyzer.langlength', 16384));

		// too little information
		if (strlen($subtext) < 50) {
			return [];
		}

		Utils::requireLibrary('Text_LanguageDetect');

		$detector = new Text_LanguageDetect();
		$detector->setNameMode(2);
		$scores = $detector->detect($subtext);

		// save memory
		unset($detector);

		$processor = null;
		foreach ($this->processors as $p) {
			if ($p->matches($subtext, $scores)) {
				$processor = $p;
				break;
			}
		}

		// language could not be detected
		if (!isset($processor)) {
			return [];
		}

		// initiate processor
		$processor->open();

		// tokenize
		$tokens = $processor->tokenize($text);
		$normalizedTokens = [];

		// process tokens
		foreach ($tokens as $token) {
			// remove references
			$token = preg_replace('/[\[<].*[\]>]/', '', $token);
			// filter empty tokens
			if (strlen($token) === 0) {
				continue;
			}
			// remove links
			if (filter_var($token, FILTER_VALIDATE_URL) !== false) {
				continue;
			}
			// remove emails
			if (filter_var($token, FILTER_VALIDATE_EMAIL) !== false) {
				continue;
			}
			// split into subtokens
			$subtokens = preg_split('/(?![-])[[:punct:]]+/', $token);
			foreach ($subtokens as $subtoken) {
				// filter strange subtokens with numbers in between
				if (preg_match('/([^-][0-9])|([0-9][^-])/', $subtoken)) {
					continue;
				}
				// normalize
				$normalized = $processor->normalizeToken($subtoken);
				// filter e.g. stopwords
				if (!$processor->filterToken($normalized)) {
					continue;
				}
				// add to normalized tokens
				$normalizedTokens[] = [$normalized, $subtoken];
			}
		}

		// save memory
		unset($tokens);

		// create histogram
		// "normalizedword(s)" => [count, original word(s)]
		$histogram = [];
		$histogramLimit = Utils::configOrDefault($this->config, 'document-analyzer.histogramlimit', 8192);
		$keyphraseLength = Utils::configOrDefault($this->config, 'document-analyzer.keyphraselength', 3);
		$sampleSize = Utils::configOrDefault($this->config, 'document-analyzer.samplesize', 512);
		$length = sizeof($normalizedTokens);
		for ($i = 0; $i < $length; ++$i) {
			// generate histogram of multiple words (e.g. "operating system explorer")
			$nToken = '';
			$oToken = '';
			$stop = min($keyphraseLength, $length - $i);
			for ($j = 0; $j < $stop; ++$j) {
				$nToken .= ' ' . $normalizedTokens[$i + $j][0];
				$oToken .= ' ' . $normalizedTokens[$i + $j][1];
				// token already in histogram
				if (isset($histogram[$nToken])) {
					// increase counter
					$histogram[$nToken][0]++;
				}
				// token not in histogram yet
				else {
					// enough space, just add it
					if (sizeof($histogram) < $histogramLimit) {
						$histogram[$nToken] = [1, $nToken, $oToken];
					}
					// cleanup
					else {
						// sample
						$sampleKeys = array_rand($histogram, min(sizeof($histogram), $sampleSize));
						$sample = [];
						foreach ($sampleKeys as &$sampleKey) {
							$sample[] = $histogram[$sampleKey][0];
						}
						sort($sample, SORT_NUMERIC);
						$median = $sample[floor(sizeof($sample) / 2)];
						// clean up histogram (filter everything below median)
						foreach ($histogram as $k => &$v) {
							if ($v[0] <= $median) {
								unset($histogram[$k]);
							}
						}
					}
				}
			}
		}

		// save memory
		unset($normalizedTokens);

		// sort according to count and length of normalized token
		uasort($histogram, function(&$a, &$b) {
			$order = $b[0] - $a[0];
			return ($order === 0) ? strlen($a[1]) - strlen($b[1]) : $order;
		});

		// filter histogram by removing parts of a keyphrase
		reset($histogram);
		foreach ($histogram as $currentKey => $currentValue) {
			$nextValue = current($histogram);
			$nextKey = key($histogram);
			if ($nextValue !== false &&
					$currentValue[0] === $nextValue[0] && // same count as keyphrase
					Utils::stringStartsWith($nextKey, $currentKey)) {
				unset($histogram[$currentKey]);
			}
			next($histogram);
		}

		$topCountFactor = Utils::configOrDefault($this->config, 'document-analyzer.topcountfactor', 0.2);
		$topCount = min(sizeof($histogram) * $topCountFactor, 40);

		$topHistogram = array_slice($histogram, 0, $topCount);

		// save memory
		unset($histogram);

		// generate output result
		$result = [];
		foreach ($topHistogram as $value) {
			$oToken = $value[2];
			$outToken = $processor->outputToken($oToken);
			if ($outToken !== '') {
				$result[] = $outToken;
			} 
		}

		// close processor
		$processor->close();

		return $result;
	}
}