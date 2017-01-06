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

use Fisharebest\Webtrees\Controller\LifespanController;
use Fisharebest\Webtrees\Functions\FunctionsEdit;

require 'app/bootstrap.php';

global $WT_TREE;

$controller = new LifespanController;
$controller
	->restrictAccess(Module::isActiveChart($WT_TREE, 'lifespans_chart'))
	->pageHeader();

?>
<h2><?= I18N::translate('Lifespans') ?></h2>

<form>
	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="newpid">
			<?= I18N::translate('Add individuals') ?>
		</label>
		<div class="col-sm-9">
			<?= FunctionsEdit::formControlIndividual(null, ['id' => 'newpid', 'name' => 'newpid']) ?>
			<?= Bootstrap4::checkbox(/* I18N: Label for a configuration option */ I18N::translate('Include the individual’s immediate family'), false, ['name' => 'addFamily']) ?>
		</div>
	</div>

	<fieldset class="row form-group">
		<legend class="col-sm-3 col-form-legend">
			<?= I18N::translate('Select individuals by place or date') ?>
		</legend>
		<label class="col-sm-3" for="place">
			<?= I18N::translate('Place') ?>
		</label>
		<div class="col-sm-6">
			<input class="form-control" type="text" name="place">
		</div>
		<label class="col-sm-1" for="beginYear">
			<?= /* I18N: The earliest year in a range */ I18N::translate('Start year') ?>
		</label>
		<div class="col-sm-1">
			<input class="form-control" type="text" name="beginYear">
		</div>
		<label class="col-sm-1" for="endYear">
			<?= /* I18N: The latest year in a range */ I18N::translate('End year') ?>
		</label>
		<div class="col-sm-1">
			<input class="form-control" type="text" name="endYear">
		</div>
		<label class="col-sm-1" for="calendar">
			<?= I18N::translate('Calendar') ?>
		</label>
		<div class="col-sm-2">
			<?= Bootstrap4::select(Date::calendarNames(), 'gregorian', ['id' => 'calendar', 'name' => 'calendar']) ?>
			<?= Bootstrap4::checkbox(I18N::translate('Match calendar'), false, ['name' => 'strictDate']) ?>
		</div>
	</fieldset>

	<div class="row form-group">
		<div class="col-sm-9 offset-sm-3">
			<input id="clear" type="hidden" name="clear" value="0">
			<input class="btn btn-primary" type="submit" value="<?= /* I18N: A button label. */ I18N::translate('view') ?>">
			<input class="btn btn-default" type="reset" value="<?= /* I18N: A button label. */ I18N::translate('reset') ?>">
		</div>
	</div>
</form>

<div id="lifespan-chart">
	<h4><?= $controller->subtitle ?></h4>
	<div id="lifespan-scale">
		<?php $controller->printTimeline() ?>
	</div>
	<div id="lifespan-people">
		<?php $maxY = $controller->fillTimeline() ?>
	</div>
</div>
<?php
$controller
	->addInlineJavascript("
		var scale = $('#lifespan-scale'),
			barHeight = $('#lifespan-people').children().first().outerHeight();
		$('#lifespan-chart')
			.width(scale.width())
			.height(Math.ceil($('h4').outerHeight() + scale.height() + barHeight + $maxY));
		$('form').on('reset', function() {
			$('#clear').val(1);
			$(this).submit();
		});
	");
