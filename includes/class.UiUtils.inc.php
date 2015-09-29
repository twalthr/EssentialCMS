<?php

abstract class UiUtils {

	public static function printTextInput($postField, $defaultValue, $large, $minLength, $maxLength) {
		if ($large) {
			echo '<textarea';
			if (isset($maxLength) && $maxLength > 0) {
				echo ' maxlength="' . $maxLength . '"';
			}
			if (isset($minLength)) {
				echo ' required';
			}
			if (isset($minLength) && $minLength > 0) {
				echo ' minlength="' . $minLength . '"';
			}
			echo '>';
			echo Utils::getEscapedFieldOrVariable($postField, $defaultValue);
			echo '</textarea>';
		}
		else {
			echo '<input type="text" class="large"';
			if (isset($maxLength) && $maxLength > 0) {
				echo ' maxlength="' . $maxLength . '"';
			}
			if (isset($minLength)) {
				echo ' required';
			}
			if (isset($minLength) && $minLength > 0) {
				echo ' minlength="' . $minLength . '"';
			}
			echo ' value="';
			echo Utils::getEscapedFieldOrVariable($postField, $defaultValue);
			echo '" />';
		}
	}

}

?>