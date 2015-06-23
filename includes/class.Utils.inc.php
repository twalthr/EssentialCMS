<?php

class Utils {
	public static function hasStringContents($str) {
		return !isset($str) || trim($str)==='';
	}
}

?>