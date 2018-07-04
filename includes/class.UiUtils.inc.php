<?php

abstract class UiUtils {

	private static $uniqueCounter;

	public static function getAndInc() {
		if (!isset(self::$uniqueCounter)) {
			self::$uniqueCounter = 0;
		}
		return self::$uniqueCounter++;
	}

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
		echo '<';
		if ($field->isLargeContent()) {
			echo 'textarea';
		} else {
			echo 'input type="text" value="';
			echo Utils::escapeString($value);
			echo '"';
		}
		echo ' name="' . $postFieldName . '" class="large ' . FieldInfo::translateTypeToString($type) . '"';
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
		if ($field->isLargeContent()) {
			echo '>';
			echo Utils::escapeString($value);
			echo '</textarea>';
		} else {
			echo ' />';
		}
	}

	public static function printPageSelection($field, $value, $uniqueId) {
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
		echo Translator::get()->translate('SELECT');
		echo '	</button>';
		echo '</div>';
	}

	public static function printEnumSelection($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<select name="' . $postFieldName . '" class="' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_ENUM);
		if ($field->isLargeContent()) {
			echo ' large';
		}
		echo '"';
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
		echo Translator::get()->translate('PLEASE_SELECT');
		echo '</option>';
		foreach ($field->getAuxiliaryInfo() as $key => $name) {
			echo '<option value="' . $key . '"';
			if ($key === $value) {
				echo ' selected';
			}
			echo '>';
			echo Translator::get()->translate($name);
			echo '</option>';
		}
		echo '</select>';
	}

	public static function printIntInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<input name="' . $postFieldName . '" type="number" step="1" class="' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_INT);
		if ($field->isLargeContent()) {
			echo ' large';
		}
		echo '"';
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

	public static function printFloatInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<input name="' . $postFieldName . '" type="text" '.
			'pattern="[+-]?[0-9]+([\.,][0-9]+)?" title="42.01   42,01   -42" class="' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_FLOAT);
		if ($field->isLargeContent()) {
			echo ' large';
		}
		echo '"';
		echo ' value="';
		echo Utils::escapeString($value);
		echo '"';
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

	public static function printLocaleSelection($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<select name="' . $postFieldName . '" class="' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_LOCALE);
		if ($field->isLargeContent()) {
			echo ' large';
		}
		echo '"';
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
		echo Translator::get()->translate('PLEASE_SELECT');
		echo '</option>';
		$locales = Translator::get()->translateLocaleList(true);
		// modifies keys
		Utils::sortArray($locales, ['translated', 'original']);
		foreach ($locales as $locale) {
			echo '<option value="' . $locale['locale'] . '"';
			if ($locale['locale'] === $value) {
				echo ' selected';
			}
			echo '>';
			if (isset($locale['original'])) {
				echo $locale['translated'] . ' [' . $locale['original'] .']';
			} else {
				echo $locale['translated'];
			}
			echo '</option>';
		}
		echo '</select>';
	}

	public static function printDurationInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		// we use 10 as maximum number to fit into every integer
		echo '<input name="' . $postFieldName . '" type="text" '.
			'pattern="([0-9]{1,10})-([0-9]{1,10})-([0-9]{1,10}) '.
			'([0-9]{1,10}):([0-9]{1,10}):([0-9]{1,10})([\.,][0-9]{1,3})?" '.
			'title="0000-00-00 02:15:42.999   0000-00-00 00:00:500" class="large ' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_DURATION) . '"';
		echo ' value="';
		echo Utils::escapeString($value);
		echo '"';
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

	public static function printCheckbox($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<div class="checkboxWrapper">';
		echo '<input name="' . $postFieldName . '" type="checkbox" class="' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_BOOLEAN) . '"';
		echo ' value="1"';
		if ($value === '1') {
			echo ' checked';
		}
		if ($disabled === true) {
			echo ' disabled';
		}
		if ($field->isArray()) {
			$checkboxId = self::getAndInc();
			echo ' id="' . $postFieldName . '_' . $checkboxId. '"';
		} else {
			echo ' id="' . $postFieldName . '"';
		}
		echo ' />';
		if ($field->isArray()) {
			echo '<label for="' . $postFieldName . '_' . $checkboxId . '" class="checkbox">';
		} else {
			echo '<label for="' . $postFieldName . '" class="checkbox">';
		}
		$label = Translator::get()->translate($field->getAuxiliaryInfo());
		echo $label;
		echo '</label>';
		echo $label;
		echo '</div>';
	}

	public static function printRangeInput($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<div class="rangeWrapper">';
		echo '<input type="text" class="rangeValue" disabled />';
		if ($field->getMinContentLength() !== null) {
			echo $field->getMinContentLength();
		}
		echo '<input name="' . $postFieldName . '" type="range" class="large ' .
			FieldInfo::translateTypeToString(FieldInfo::TYPE_RANGE) . '"';
		echo ' value="';
		echo Utils::escapeString($value);
		echo '"';
		if ($field->getMaxContentLength() !== null) {
			echo ' max="' . $field->getMaxContentLength() . '"';
		}
		if ($field->getMinContentLength() !== null) {
			echo ' min="' . $field->getMinContentLength() . '"';
		}
		if ($field->getAuxiliaryInfo() !== null) {
			echo ' step="' . $field->getAuxiliaryInfo() . '"';
		}
		if ($field->isRequired() === true) {
			echo ' required';
		}
		if ($disabled === true) {
			echo ' disabled';
		}
		if ($field->isArray()) {
			$id = $postFieldName . '_' . self::getAndInc() . '"';
		} else {
			$id = $postFieldName;
		}
		echo ' id="' . $id . '"';
		echo ' list="' . $id . '_list"';
		echo ' />';
		if ($field->getMaxContentLength() !== null && $field->getMinContentLength() !== null &&
				$field->getAuxiliaryInfo() !== null) {
			echo '<datalist id="' . $id . '_list">';
			for ($i = $field->getMinContentLength();
					$i <= $field->getMaxContentLength();
					$i += $field->getAuxiliaryInfo()) {
				echo '<option value="' . $i . '" label="' . $i . '">';
			}
			echo '</datalist>';
		}
		if ($field->getMaxContentLength() !== null) {
			echo $field->getMaxContentLength();
		}
		echo '</div>';
	}

	public static function printEncryptedInput($field, $value, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');

		echo '<div class="encryptionWrapper inputWithOption">';

		// errors
		echo '<div class="dialog-error-message hidden shortPassword">' .
			Translator::get()->translate('PASSWORD_MINLENGTH') . '</div>';
		echo '<div class="dialog-error-message hidden unequalPasswords">' .
			Translator::get()->translate('PASSWORDS_NOT_EQUAL') . '</div>';
		echo '<div class="dialog-error-message hidden wrongPassword">' .
			Translator::get()->translate('WRONG_PASSWORD') . '</div>';
		echo '<div class="dialog-error-message hidden unsupportedBrowser">' .
			Translator::get()->translate('ENCRYPTION_ERROR') . '</div>';
		// value
		echo '<input type="hidden" value="';
		echo Utils::escapeString($value);
		echo '" name="' . $postFieldName . '" />';

		// visualization
		if ($field->isLargeContent()) {
			echo '<textarea pattern="^$" title="' .
				Translator::get()->translate('PLAIN_TEXT_HINT') . '" class="large ' .
				FieldInfo::translateTypeToString(FieldInfo::TYPE_ENCRYPTED) . '"';
			if (!$field->isArray()) {
				echo ' id="' . $postFieldName . '"';
			}
			echo '></textarea>';
		} else {
			// the pattern enforces that no plain text is submitted
			echo '<input type="text" pattern="^$" title="' .
				Translator::get()->translate('PLAIN_TEXT_HINT') . '" class="large ' .
				FieldInfo::translateTypeToString(FieldInfo::TYPE_ENCRYPTED) . '"';
			if (!$field->isArray()) {
				echo ' id="' . $postFieldName . '"';
			}
			echo ' />';
		}

		// password 1
		echo '<input type="password" class="large hidden" maxlength="64" placeholder="' .
			Translator::get()->translate('ENCRYPTION_PASSWORD') .
			'" />';

		// password 2
		echo '<input type="password" class="large hidden" maxlength="64" placeholder="' .
			Translator::get()->translate('RETYPE_ENCRYPTION_PASSWORD') .
			'" />';

		// encryption button
		echo '<button class="encryptButton">';
		echo Translator::get()->translate('ENCRYPT');
		echo '</button>';

		// decryption button
		echo '<button class="decryptButton">';
		echo Translator::get()->translate('DECRYPT');
		echo '</button>';

		echo '</div>';
	}

	public static function printColorPicker($field, $value, $disabled, $uniqueId) {
		$postFieldName = $field->generateContentName($uniqueId) . ($field->isArray() ? '[]' : '');
		echo '<input name="' . $postFieldName . '" type="color" pattern="#[0-9a-fA-F]{6}" ' . 
			'title="#rrggbb" class="' . FieldInfo::translateTypeToString(FieldInfo::TYPE_INT) . '"';
		echo ' value="';
		if (!isset($value) || !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
			echo '#000000';
		} else {
			echo Utils::escapeString($value);
		}
		echo '"';
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