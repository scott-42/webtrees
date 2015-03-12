<?php namespace Fisharebest\Localization;

/**
 * Class Locale - The “root” locale, from which all others are derived.
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license   GPLv3+
 */
abstract class Locale {
	// "Source" strings, when translating numbers
	const DECIMAL  = '.'; // The default decimal mark
	const GROUP    = ','; // The digit group separator
	const NEGATIVE = '-'; // Negative numbers

	// "Target" strings, when translating numbers
	const APOSTROPHE   = '’';
	const ARAB_DECIMAL = "\xD9\xAB";
	const ARAB_GROUP   = "\xD9\xAC";
	const ARAB_MINUS   = "\xE2\x88\x92";
	const ARAB_PERCENT = "\xD9\xAA";
	const COMMA        = ',';
	const DOT          = '.';
	const HYPHEN       = '-';
	const LTR_MARK     = "\xE2\x80\x8E"; // Left-to-right marker
	const MINUS_SIGN   = "\xE2\x88\x92";
	const NBSP         = "\xC2\xA0"; // A non-breaking space
	const PRIME        = '\'';
	const RTL_MARK     = "\xE2\x80\x8F"; // Right-to-left marker

	// For formatting percentages
	const PERCENT      = '%%';

	/**
	 * Generate a linux locale code for this locale.  Examples include
	 * "fr", “en_GB”, “ca_ES@valencia” and “sr@latin”.
	 *
	 * @return string
	 */
	public function code() {
		$code = $this->language()->code() . '_' . $this->territory()->code();

		if ($this->script() != $this->language()->defaultScript()) {
			$code .= '@' . strtolower($this->script()->unicodeName());
		}

		if ($this->variant()) {
			if ($this->variant()->code() === 'posix') {
				$code = 'POSIX';
			} else {
				$code .= '@' . $this->variant()->code();
			}
		}

		return $code;
	}

	/**
	 * Which collation sequence should be used for this locale?
	 * “unicode_ci” would mean use “utf8_unicode_ci”, “utf8mb4_unicode_ci”, etc.
	 *
	 * @link http://dev.mysql.com/doc/refman/5.7/en/charset-unicode-sets.html
	 *
	 * @return string
	 */
	public function collation() {
		return 'unicode_ci';
	}

	/**
	 * Callback for PHP sort functions - allows lists of locales to be sorted.
	 * Diacritics are removed and text is capitalized to allow fast/simple sorting.
	 *
	 * @param Locale $x
	 * @param Locale $y
	 *
	 * @return integer
	 */
	public static function compare(Locale $x, Locale $y) {
		return strcmp($x->endonymSortable(), $y->endonymSortable());
	}

	/**
	 * Create a locale from a language tag (or locale code).
	 *
	 * @param string $language
	 *
	 * @return Locale
	 * @throws \DomainException
	 */
	public static function create($language) {
		$parts = preg_split('/[^a-zA-Z0-9]+/', $language);
		array_walk($parts, function (&$x) {
			$x = ucfirst(strtolower($x));
		});
		$class = __NAMESPACE__ . '\Locale' . implode($parts);

		if (class_exists($class)) {
			return new $class;
		} else {
			throw new \DomainException($language);
		}
	}

	/**
	 * Convert (Hindu-Arabic) digits into a localized form
	 *
	 * @param string $string  e.g. "123.45"
	 *
	 * @return string
	 */
	public function digits($string) {
		return strtr($string, $this->numberSymbols() + $this->numerals());
	}

	/**
	 * When writing large numbers place a separator after this number of digits.
	 *
	 * @return integer
	 */
	protected function digitsFirstGroup() {
		return 3;
	}

	/**
	 * When writing large numbers place a separator after this number of digits.
	 *
	 * @return integer
	 */
	protected function digitsGroup() {
		return 3;
	}

	/**
	 * Is text written left-to-right “ltr” or right-to-left “rtl”.
	 * Most scripts are only written in one direction, but there are a few that
	 * can be written in either direction.
	 *
	 * @return string “ltr” or “rtl”
	 */
	public function direction() {
		return $this->script()->direction();
	}

	/**
	 * The name of this locale, in its own language/script, and with the
	 * customary capitalization of the locale.
	 *
	 * @return string
	 */
	abstract public function endonym();

	/**
	 * A sortable version of the locale name.  For example, “British English”
	 * might sort as “ENGLISH, BRITISH” to keep all the variants of English together.
	 *
	 * All-capitals makes sorting easier, as we can use a simple strcmp().
	 *
	 * @return string
	 */
	protected function endonymSortable() {
		return $this->endonym();
	}

	/**
	 * Markup for an HTML element
	 *
	 * @return string e.g. lang="ar" dir="rtl"
	 */
	public function htmlAttributes() {
		if ($this->direction() === 'rtl' || $this->direction() !== $this->script()->direction()) {
			return 'lang="' . $this->languageTag() . '" dir="' . $this->direction() . '"';
		} else {
			return 'lang="' . $this->languageTag() . '"';
		}
	}

	/**
	 * The language used by this locale.
	 *
	 * @return Language
	 */
	abstract public function language();

	/**
	 * The IETF language tag for the locale.  Examples include
	 * “fr, “en-GB”, “ca-ES-valencia” and “sr-Latn”.
	 *
	 * @return string
	 */
	public function languageTag() {
		$language_tag = $this->language()->code();
		if ($this->script() != $this->language()->defaultScript()) {
			$language_tag .= '-' . $this->script()->code();
		}
		if ($this->territory() != $this->language()->defaultTerritory()) {
			$language_tag .= '-' . $this->territory()->code();
		}
		if ($this->variant()) {
			$language_tag .= '-' . $this->variant()->code();
		}

		return $language_tag;
	}

	/**
	 * When using grouping digits in numbers, keep this many of digits together.
	 *
	 * @return integer
	 */
	protected function minimumGroupingDigits() {
		return 1;
	}

	/**
	 * Convert (Hindu-Arabic) digits into a localized form
	 *
	 * @param string|float|integer $number The number to be localized
	 *
	 * @return string
	 */
	public function number($number) {
		if ($number < 0) {
			$number   = -$number;
			$negative = self::NEGATIVE;
		} else {
			$negative = '';
		}
		$parts    = explode(self::DECIMAL, $number, 2);
		$integers = $parts[0];
		if (strlen($integers) >= $this->digitsFirstGroup() + $this->minimumGroupingDigits()) {
			$todo     = substr($integers, 0, -$this->digitsFirstGroup());
			$integers = self::GROUP . substr($integers, -$this->digitsFirstGroup());
			while (strlen($todo) >= $this->digitsGroup() + $this->minimumGroupingDigits()) {
				$integers = self::GROUP . substr($todo, -$this->digitsGroup()) . $integers;
				$todo     = substr($todo, 0, -$this->digitsGroup());
			}
			$integers = $todo . $integers;
		}
		if (count($parts) > 1) {
			$decimals = self::DECIMAL . $parts[1];
		} else {
			$decimals = '';
		}

		return $this->digits($negative . $integers . $decimals);
	}

	/**
	 * The symbols used to format numbers.
	 *
	 * @return string[]
	 */
	protected function numberSymbols() {
		return array();
	}

	/**
	 * The numerals (0123456789) used by this locale.
	 *
	 * @return string[]
	 */
	protected function numerals() {
		return $this->script()->numerals();
	}

	/**
	 * Convert (Hindu-Arabic) digits into a localized form
	 *
	 * @param string|float|integer $number The number to be localized
	 *
	 * @return string
	 */
	public function percent($number, $precision = 0) {
		$number = round($number * 100, $precision);

		return sprintf($this->percentFormat(), $this->number($number));
	}

	/**
	 * How to format a floating point number (%s) as a percentage.
	 *
	 * @return string
	 */
	protected function percentFormat() {
		return '%s%%';
	}

	/**
	 * The script used by this locale.
	 *
	 * @return Script
	 */
	public function script() {
		return $this->language()->defaultScript();
	}

	/**
	 * The territory used by this locale.
	 *
	 * @return Territory
	 */
	public function territory() {
		return $this->language()->defaultTerritory();
	}

	/**
	 * The variant, if any of this locale.
	 *
	 * @return Variant|null
	 */
	public function variant() {
		return null;
	}
}