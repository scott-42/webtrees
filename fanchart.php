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

use Fisharebest\Webtrees\Controller\FanchartController;
use Fisharebest\Webtrees\Functions\FunctionsEdit;

require 'app/bootstrap.php';

$controller = new FanchartController;
global $WT_TREE;

if (Filter::getBool('img')) {
	header('Content-Type: image/png');
	echo $controller->generateFanChart('png');

	return;
}

$controller
	->restrictAccess(Module::isActiveChart($WT_TREE, 'fan_chart'))
	->pageHeader()
	->addInlineJavascript('
		var WT_FANCHART = (function() {
			$("area")
				.click(function (e) {
					e.stopPropagation();
					e.preventDefault();
					var target = $(this.hash);
					target
						// position the menu centered immediately above the mouse click position and
						// make sure it doesn’t end up off the screen
						.css({
							left: Math.max(0 ,e.pageX - (target.outerWidth()/2)),
							top:  Math.max(0, e.pageY - target.outerHeight())
						})
						.toggle()
						.siblings(".fan_chart_menu").hide();
				});
			$(".fan_chart_menu")
				.on("click", "a", function(e) {
					e.stopPropagation();
				});
			$("#fan_chart")
				.click(function(e) {
					$(".fan_chart_menu").hide();
				});
			return "' . strip_tags($controller->root->getFullName()) . '";
		})();
	');

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
		<label class="col-sm-3 col-form-label">
			<?= I18N::translate('Layout') ?>
		</label>
		<div class="col-sm-9">
			<?= Bootstrap4::select($controller->getFanStyles(), $controller->fan_style, ['id' => 'fan_style', 'name' => 'fan_style']) ?>
		</div>
	</div>

	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="generations">
			<?= I18N::translate('Generations') ?>
		</label>
		<div class="col-sm-9">
			<?= Bootstrap4::select(FunctionsEdit::numericOptions(range(2, 9)), $controller->generations, ['id' => 'generations', 'name' => 'generations']) ?>
		</div>
	</div>

	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="fan_width">
			<?= I18N::translate('Zoom') ?>
		</label>
		<div class="col-sm-9">
			<div class="input-group">
				<input class="form-control" type="text" size="3" id="fan_width" name="fan_width" value="<?= $controller->fan_width ?>">
				<span class="input-group-addon">%</span>
			</div>
		</div>
	</div>

	<div class="row form-group">
		<div class="col-sm-9 offset-sm-3">
			<input class="btn btn-primary" type="submit" value="<?= /* I18N: A button label. */ I18N::translate('view') ?>">
		</div>
	</div>
</form>
<?php

if ($controller->error_message) {
	echo '<p class="ui-state-error">', $controller->error_message, '</p>';

	return;
}

if ($controller->root) {
	echo '<div id="fan_chart">', $controller->generateFanChart('html'), '</div>';
}
