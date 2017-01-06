<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2017 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Fisharebest\Webtrees;

use Fisharebest\Webtrees\Controller\DescendancyController;
use Fisharebest\Webtrees\Functions\FunctionsEdit;
use Fisharebest\Webtrees\Functions\FunctionsPrint;
use Fisharebest\Webtrees\Functions\FunctionsPrintLists;

/** @global Tree $WT_TREE */
global $WT_TREE;

require 'app/bootstrap.php';

$controller = new DescendancyController;
$controller
	->restrictAccess(Module::isActiveChart($WT_TREE, 'descendancy_chart'))
	->pageHeader();

?>

<h2><?= $controller->getPageTitle() ?></h2>
<form>
	<input type="hidden" name="ged" value="<?= $WT_TREE->getNameHtml() ?>">

	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="rootid">
			<?= I18N::translate('Individual') ?>
		</label>
		<div class="col-sm-9">
			<?= FunctionsEdit::formControlIndividual($controller->root, ['id' => 'rootid', 'name' => 'rootid']) ?>
		</div>
	</div>

	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="generations">
			<?= I18N::translate('Generations') ?>
		</label>
		<div class="col-sm-9">
			<?= Bootstrap4::select(FunctionsEdit::numericOptions(range(2, $WT_TREE->getPreference('MAX_DESCENDANCY_GENERATIONS'))), $controller->generations), ['id' => 'generations', 'name' => 'generations'] ?>
		</div>
	</div>

	<fieldset class="row form-group">
		<legend class="col-sm-3 col-form-legend">
			<?= I18N::translate('Layout') ?>
		</legend>
		<div class="col-sm-9">
			<?= Bootstrap4::radioButtons('chart_style', ['0' => I18N::translate('List'), '1' => I18N::translate('Booklet'), '2' => I18N::translate('Individuals'), '3' => I18N::translate('Families')], $controller->chart_style, true, ['onclick' => 'statusDisable("show_cousins");']) ?>
		</div>
	</fieldset>

	<div class="row form-group">
		<div class="col-sm-9 offset-sm-3">
			<input class="btn btn-primary" type="submit" value="<?= /* I18N: A button label. */ I18N::translate('view') ?>">
		</div>
	</div>
</form>

<?php
if ($controller->error_message) {
	echo '<p class="ui-state-error">', $controller->error_message, '</p>';
} else {
	switch ($controller->chart_style) {
	case 0: // List
		echo '<ul id="descendancy_chart" class="chart_common">';
		$controller->printChildDescendancy($controller->root, $controller->generations);
		echo '</ul>';
		break;
	case 1: // Booklet
		$show_cousins = true;
		echo '<div id="descendancy_booklet">';
		$controller->printChildFamily($controller->root, $controller->generations);
		echo '</div>';
		break;
	case 2: // Individual list
		$descendants = $controller->individualDescendancy($controller->root, $controller->generations, []);
		echo '<div id="descendancy-list">', FunctionsPrintLists::individualTable($descendants), '</div>';
		break;
	case 3: // Family list
		$descendants = $controller->familyDescendancy($controller->root, $controller->generations, []);
		echo '<div id="descendancy-list">', FunctionsPrintLists::familyTable($descendants), '</div>';
		break;
	}
}
