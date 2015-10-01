<?php

abstract class UiUtils {

	public static function printHiddenTypeInput($typePostField, $type, $disabled) {
		echo '<input name="' . $typePostField . '" type="hidden" value="' . FieldInfo::translateTypeToString($type) . '"';
		if ($disabled === true) {
			echo ' disabled';
		}
		echo ' />';
	}

	public static function printTextInput($type, $postField, $defaultValue, $disabled, $field) {
		if ($field->isLargeContentField()) {
			echo '<textarea name="' . $postField . '" class="' . FieldInfo::translateTypeToString($type) . '"';
			if ($field->getMaxContentLength() !== null && $field->getMaxContentLength() > 0) {
				echo ' maxlength="' . $field->getMaxContentLength() . '"';
			}
			if ($field->getMinContentLength() !== null && $field->getMinContentLength() > 0) {
				echo ' minlength="' . $field->getMinContentLength() . '"';
			}
			if ($field->isRequired() === true) {
				echo ' required';
			}
			if ($disabled === true) {
				echo ' disabled';
			}
			echo ' id="' . $postField . '">';
			echo Utils::getEscapedFieldOrVariable($postField, $defaultValue);
			echo '</textarea>';
		}
		else {
			echo '<input name="' . $postField . '" type="text" class="large ' . FieldInfo::translateTypeToString($type) . '"';
			echo ' value="';
			echo Utils::getEscapedFieldOrVariable($postField, $defaultValue);
			echo '"';
			if ($field->getMaxContentLength() !== null && $field->getMaxContentLength() > 0) {
				echo ' maxlength="' . $field->getMaxContentLength() . '"';
			}
			if ($field->getMinContentLength() !== null && $field->getMinContentLength() > 0) {
				echo ' minlength="' . $field->getMinContentLength() . '"';
			}
			if ($field->isRequired() === true) {
				echo ' required';
			}
			if ($disabled === true) {
				echo ' disabled';
			}
			echo ' id="' . $postField . '" />';
		}
	}

}

?>