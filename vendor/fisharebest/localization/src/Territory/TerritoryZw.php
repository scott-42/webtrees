<?php namespace Fisharebest\Localization\Territory;

/**
 * Class AbstractTerritory - Representation of the territory ZW - Zimbabwe.
 *
 * @author    Greg Roach <fisharebest@gmail.com>
 * @copyright (c) 2015 Greg Roach
 * @license   GPLv3+
 */
class TerritoryZw extends AbstractTerritory implements TerritoryInterface {
	public function code() {
		return 'ZW';
	}

	public function firstDay() {
		return 0;
	}
}
