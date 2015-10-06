<?php

abstract class UiUtils {

	public static function printHiddenTypeInput($postField, $type, $disabled) {
		echo '<input name="' . $postField . '" type="hidden" value="' . FieldInfo::translateTypeToString($type) . '"';
		if ($disabled === true) {
			echo ' disabled';
		}
		echo ' />';
	}

	public static function printTextInput($field, $type, $value, $disabled) {
		$postFieldName = $field->getContentPostFieldName() . ($field->isArray() ? '[]' : '');
		if ($field->isLargeContent()) {
			echo '<textarea name="' . $postFieldName . '" class="' . FieldInfo::translateTypeToString($type) . '"';
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
			if (!$field->isArray()) {
				echo ' id="' . $postFieldName . '"';
			}
			echo '>';
			echo Utils::escapeString($value);
			echo '</textarea>';
		}
		else {
			echo '<input name="' . $postFieldName . '" type="text" class="large ' . FieldInfo::translateTypeToString($type) . '"';
			echo ' value="';
			echo Utils::escapeString($value);
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
			if (!$field->isArray()) {
				echo ' id="' . $postFieldName . '"';
			}
			echo ' />';
		}
	}

}

?>