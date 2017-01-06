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

use Fisharebest\Webtrees\Controller\FamilyBookController;
use Fisharebest\Webtrees\Functions\FunctionsEdit;

/**
 * @global Tree   $WT_TREE
 */
global $WT_TREE;

require 'app/bootstrap.php';

$controller = new FamilyBookController;
$controller
	->restrictAccess(Module::isActiveChart($WT_TREE, 'family_book_chart'))
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
			<?= Bootstrap4::select(FunctionsEdit::numericOptions(range(2, $WT_TREE->getPreference('MAX_PEDIGREE_GENERATIONS'))), $controller->generations, ['id' => 'generations', 'name' => 'generations']) ?>
		</div>
	</div>

	<div class="row form-group">
		<label class="col-sm-3 col-form-label" for="descent">
			<?= I18N::translate('Descendant generations') ?>
		</label>
		<div class="col-sm-9">
			<?= Bootstrap4::select(FunctionsEdit::numericOptions(range(2, 9)), $controller->descent, ['id' => 'descent', 'name' => 'descent']) ?>
		</div>
	</div>

	<fieldset class="row form-group">
		<legend class="col-sm-3 col-form-legend">
			<?= I18N::translate('Spouses') ?>
		</legend>
		<div class="col-sm-9">
			<?= Bootstrap4::checkbox(I18N::translate('Show spouses'), false, ['name' => 'show_spouse', 'checked' => (bool) $controller->show_spouse]) ?>
		</div>
	</fieldset>

	<div class="row form-group">
		<div class="col-sm-9 offset-sm-3">
			<input class="btn btn-primary" type="submit" value="<?= /* I18N: A button label. */ I18N::translate('view') ?>">
		</div>
	</div>
</form>

<div id="familybook_chart" style="z-index:1;">
	<?php $controller->printFamilyBook($controller->root, $controller->descent) ?>
</div>
