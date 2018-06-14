<?php

abstract class UiUtils {

	public static function printHiddenTypeInput($postField, $type, $disabled) {
		echo '<input name="' . $postField . '" type="hidden" value="' . 
			FieldInfo::translateTypeToString($type) . '"';
		if ($disabled === true) {
			echo ' disabled';
		}
		echo ' />';
	}

	public static function printTextInput($field, $type, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		if ($field->isLargeContent()) {
			echo '<textarea name="' . $postFieldName . '" class="' . 
				FieldInfo::translateTypeToString($type) . '"';
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
			echo '<input name="' . $postFieldName . '" type="text" class="large ' .
				FieldInfo::translateTypeToString($type) . '"';
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

	public static function printPageSelection($moduleDefinition, $field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<div class="inputWithOption">';
		echo '	<input type="hidden" name="' . $postFieldName . '" class="pageSelectionId"';
		echo '		value="';
		echo Utils::escapeString($value);
		echo '" />';

		echo '<input type="text" class="large pageSelectionName neverEnable" disabled';
		echo '	value="';
		echo Utils::escapeString($value);
		echo '" />';
		
		echo '	<button class="pageSelectionButton"';
		if (!$field->isArray()) {
			echo ' id="' . $postFieldName . '"';
		}
		echo '>';
		$moduleDefinition->text('SELECT');
		echo '	</button>';
		echo '</div>';
	}

	public static function printEnumSelection($moduleDefinition, $field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<select name="' . $postFieldName . '" class="large ' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_ENUM) . '"';
		if (!$field->isArray()) {
			echo ' id="' . $postFieldName . '"';
		}
		if ($field->isRequired() === true) {
			echo ' required';
		}
		if ($disabled === true) {
			echo ' disabled';
		}
		echo '>';
		echo '<option>';
		$moduleDefinition->text('PLEASE_SELECT');
		echo '</option>';
		foreach ($field->getAdditionalNames() as $key => $name) {
			echo '<option value="' . $key . '"';
			if ($key === $value) {
				echo ' selected';
			}
			echo '>';
			$moduleDefinition->text($name);
			echo '</option>';
		}
		echo '</select>';
	}

	public static function printIntInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<input name="' . $postFieldName . '" type="number" step="1" class="large ' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_INT) . '"';
		echo ' value="';
		echo Utils::escapeString($value);
		echo '"';
		if ($field->getMaxContentLength() !== null) {
			echo ' max="' . $field->getMaxContentLength() . '"';
		}
		if ($field->getMinContentLength() !== null) {
			echo ' min="' . $field->getMinContentLength() . '"';
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

	public static function printDateTimeInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<input name="' . $postFieldName . '" type="datetime-local" class="large ' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_DATE_TIME) . '"';
		echo ' value="';
		$value = str_replace(' ', 'T', $value);
		echo Utils::escapeString($value);
		echo '"';
		if ($field->getMaxContentLength() !== null) {
			$max = str_replace(' ', 'T', $field->getMaxContentLength());
			echo ' max="' . $max . '"';
		}
		if ($field->getMinContentLength() !== null) {
			$min = str_replace(' ', 'T', $field->getMinContentLength());
			echo ' min="' . $min . '"';
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

?>