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
namespace Fisharebest\Webtrees\Controller;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Functions\FunctionsPrint;
use Fisharebest\Webtrees\Functions\FunctionsPrintFacts;
use Fisharebest\Webtrees\GedcomCode\GedcomCodeName;
use Fisharebest\Webtrees\GedcomTag;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module;
use Fisharebest\Webtrees\Module\ModuleTabInterface;

/**
 * Controller for the individual page
 */
class IndividualController extends GedcomRecordController {
	/** @var int Count of names */
	public $name_count  = 0;

	/** @var int Count of names. */
	public $total_names = 0;

	/**
	 * Startup activity
	 *
	 * @param Individual|null $record
	 */
	public function __construct($record) {
		parent::__construct($record);

		// If we can display the details, add them to the page header
		if ($this->record && $this->record->canShow()) {
			$this->setPageTitle($this->record->getFullName() . ' ' . $this->record->getLifeSpan());
		}
	}

	/**
	 * Get significant information from this page, to allow other pages such as
	 * charts and reports to initialise with the same records
	 *
	 * @return Individual
	 */
	public function getSignificantIndividual() {
		if ($this->record) {
			return $this->record;
		}

		return parent::getSignificantIndividual();
	}

	/**
	 * Get significant information from this page, to allow other pages such as
	 * charts and reports to initialise with the same records
	 *
	 * @return Family
	 */
	public function getSignificantFamily() {
		if ($this->record) {
			foreach ($this->record->getChildFamilies() as $family) {
				return $family;
			}
			foreach ($this->record->getSpouseFamilies() as $family) {
				return $family;
			}
		}

		return parent::getSignificantFamily();
	}

	/**
	 * Which tabs should we show on this individual's page.
	 * We don't show empty tabs.
	 *
	 * @return ModuleTabInterface[]
	 */
	public function getTabs() {
		$active_tabs = Module::getActiveTabs($this->record->getTree());

		return array_filter($active_tabs, function(ModuleTabInterface $tab) { return $tab->hasTabContent(); });
	}

	/**
	 * Handle AJAX requests - to generate the tab content
	 */
	public function ajaxRequest() {
		header('Content-Type: text/html; charset=UTF-8');

		$tab  = Filter::get('module');
		$tabs = $this->getTabs();

		if (Auth::isSearchEngine()) {
			// Search engines should not make AJAX requests
			http_response_code(403);
		} elseif (!array_key_exists($tab, $tabs)) {
			http_response_code(404);
		} else {
			echo $tabs[$tab]->getTabContent();
		}
	}

	/**
	 * Format a name record
	 *
	 * @param int  $primary
	 * @param Fact $name_fact
	 *
	 * @return string
	 */
	public function formatNameRecord($n, Fact $name_fact) {
		$individual = $name_fact->getParent();

		// Create a dummy record, so we can extract the formatted NAME value from it.
		$dummy = new Individual(
			'xref',
			"0 @xref@ INDI\n1 DEAT Y\n" . $name_fact->getGedcom(),
			null,
			$individual->getTree()
		);
		$dummy->setPrimaryName(0); // Make sure we use the name from "1 NAME"

		$container_class = 'card';
		$content_class   = 'collapse';
		$aria            = 'false';

		if ($n === 0) {
			$content_class = 'collapse show';
			$aria  = 'true';

			// Display gender icon
			foreach ($individual->getFacts('SEX') as $sex_fact) {
				//self::printSexRecord($sex_fact);
			}
		}
		if ($name_fact->isPendingDeletion()) {
			$container_class .= ' old';
		}
		if ($name_fact->isPendingAddition()) {
			$container_class .= ' new';
		}

		ob_start();
		echo '<dl><dt class="label">', I18N::translate('Name'), '</dt>';
		echo '<dd class="field">', $dummy->getFullName();
		if ($this->record->canEdit() && !$name_fact->isPendingDeletion()) {
			echo "<div class=\"deletelink\"><a class=\"deleteicon\" href=\"#\" onclick=\"return delete_fact('" . I18N::translate('Are you sure you want to delete this fact?') . "', '" . $this->record->getXref() . "', '" . $name_fact->getFactId() . "');\" title=\"" . I18N::translate('Delete this name') . '"><span class="link_text">' . I18N::translate('Delete this name') . '</span></a></div>';
			echo '<div class="editlink"><a href="edit_interface.php?action=editname&amp;xref=' . $this->record->getXref() . '&amp;fact_id=' . $name_fact->getFactId() . '&amp;ged=' . $this->record->getTree()->getNameHtml() .  '" class="editicon" title="' . I18N::translate('Edit the name') . '"><span class="link_text">' . I18N::translate('Edit the name') . '</span></a></div>';
		}
		echo '</dd>';
		$ct = preg_match_all('/\n2 (\w+) (.*)/', $name_fact->getGedcom(), $nmatch, PREG_SET_ORDER);
		for ($i = 0; $i < $ct; $i++) {
			$tag = $nmatch[$i][1];
			if ($tag != 'SOUR' && $tag != 'NOTE' && $tag != 'SPFX') {
				echo '<dt class="label">', GedcomTag::getLabel($tag, $this->record), '</dt>';
				echo '<dd class="field">'; // Before using dir="auto" on this field, note that Gecko treats this as an inline element but WebKit treats it as a block element
				if (isset($nmatch[$i][2])) {
					$name = Filter::escapeHtml($nmatch[$i][2]);
					$name = str_replace('/', '', $name);
					$name = preg_replace('/(\S*)\*/', '<span class="starredname">\\1</span>', $name);
					switch ($tag) {
					case 'TYPE':
						echo GedcomCodeName::getValue($name, $this->record);
						break;
					case 'SURN':
						// The SURN field is not necessarily the surname.
						// Where it is not a substring of the real surname, show it after the real surname.
						$surname = Filter::escapeHtml($dummy->getAllNames()[0]['surname']);
						if (strpos($dummy->getAllNames()[0]['surname'], str_replace(',', ' ', $nmatch[$i][2])) !== false) {
							echo '<span dir="auto">' . $surname . '</span>';
						} else {
							echo I18N::translate('%1$s (%2$s)', '<span dir="auto">' . $surname . '</span>', '<span dir="auto">' . $name . '</span>');
						}
						break;
					default:
						echo '<span dir="auto">' . $name . '</span>';
						break;
					}
				}
				echo '</dd>';
				echo '</dl>';
			}
		}
		if (preg_match("/\n2 SOUR/", $name_fact->getGedcom())) {
			echo '<div id="indi_sour" class="clearfloat">', FunctionsPrintFacts::printFactSources($name_fact->getGedcom(), 2), '</div>';
		}
		if (preg_match("/\n2 NOTE/", $name_fact->getGedcom())) {
			echo '<div id="indi_note" class="clearfloat">', FunctionsPrint::printFactNotes($name_fact->getGedcom(), 2), '</div>';
		}
		$content = ob_get_clean();

		$html = '
			<div class="' . $container_class . '">
        <div class="card-header" role="tab" id="name-header-' . $n . '">
		      <div>
		        <a data-toggle="collapse" data-parent="#individual-names" href="#name-content-' . $n . '" aria-expanded="' . $aria . '" aria-controls="name-content-' . $n . '">' . $dummy->getFullName() . '</a>
		      </div>
        </div>
		    <div id="name-content-' . $n . '" class="' . $content_class . '" role="tabpanel" aria-labelledby="name-header-' . $n . '">
		      <div class="card-block">' . $content . '</div>
        </div>
      </div>';
		return $html;
	}

	/**
	 * print information for a sex record
	 *
	 * @param Fact $event the Event object
	 */
	public function printSexRecord(Fact $event) {
		$sex = $event->getValue();
		if (empty($sex)) {
			$sex = 'U';
		}
		echo '<a href="edit_interface.php?action=edit&amp;xref=' . $event->getParent()->getXref() . '&amp;fact_id=' . $event->getFactId() . '&amp;ged=' . $event->getParent()->getTree()->getNameHtml() . '" class="';
		if ($event->isPendingDeletion()) {
			echo 'old ';
		}
		if ($event->isPendingAddition()) {
			echo 'new ';
		}
		switch ($sex) {
		case 'M':
			echo 'male_gender"';
			if ($event->canEdit()) {
				echo ' title="', I18N::translate('Male'), ' - ', I18N::translate('Edit'), '">';
			 } else {
				echo ' title="', I18N::translate('Male'), '">';
			 }
			break;
		case 'F':
			echo 'female_gender"';
			if ($event->canEdit()) {
				echo ' title="', I18N::translate('Female'), ' - ', I18N::translate('Edit'), '">';
			 } else {
				echo ' title="', I18N::translate('Female'), '">';
			 }
			break;
		default:
			echo 'unknown_gender"';
			if ($event->canEdit()) {
				echo ' title="', I18N::translateContext('unknown gender', 'Unknown'), ' - ', I18N::translate('Edit'), '">';
			 } else {
				echo ' title="', I18N::translateContext('unknown gender', 'Unknown'), '">';
			 }
			break;
		}
		echo 'SEX</a>';
	}
	/**
	 * get edit menu
	 */
	public function getEditMenu() {
		if (!$this->record || $this->record->isPendingDeletion()) {
			return null;
		}
		// edit menu
		$menu = new Menu(I18N::translate('Edit'), '#', 'menu-indi');

		if (Auth::isEditor($this->record->getTree())) {
			$menu->addSubmenu(new Menu(I18N::translate('Add a name'), 'edit_interface.php?action=addname&amp;xref=' . $this->record->getXref() . '&amp;ged=' . $this->record->getTree()->getNameHtml(), 'menu-indi-addname'));

			$has_sex_record = false;
			foreach ($this->record->getFacts() as $fact) {
				if ($fact->getTag() === 'SEX' && $fact->canEdit()) {
					$menu->addSubmenu(new Menu(I18N::translate('Edit the gender'), 'edit_interface.php?action=edit&amp;xref=' . $this->record->getXref() . '&amp;fact_id=' . $fact->getFactId() . '&amp;ged=' . $this->record->getTree()->getNameHtml(), 'menu-indi-editsex'));
					$has_sex_record = true;
					break;
				}
			}
			if (!$has_sex_record) {
				$menu->addSubmenu(new Menu(I18N::translate('Edit the gender'), '#', 'menu-indi-editsex', [
					'onclick' => 'return add_new_record("' . $this->record->getXref() . '", "SEX");',
				]));
			}

			if (count($this->record->getSpouseFamilies()) > 1) {
				$menu->addSubmenu(new Menu(I18N::translate('Re-order families'), '#', 'menu-indi-orderfam', [
					'onclick' => 'return reorder_families("' . $this->record->getXref() . '");',
				]));
			}

			// delete
			$menu->addSubmenu(new Menu(I18N::translate('Delete'), '#', 'menu-indi-del', [
				'onclick' => 'return delete_record("' . I18N::translate('Are you sure you want to delete “%s”?', Filter::escapeJs(Filter::unescapeHtml($this->record->getFullName()))) . '", "' . $this->record->getXref() . '");',
			]));
		}

		// edit raw
		if (Auth::isAdmin() || Auth::isEditor($this->record->getTree()) && $this->record->getTree()->getPreference('SHOW_GEDCOM_RECORD')) {
			$menu->addSubmenu(new Menu(I18N::translate('Edit the raw GEDCOM'), 'edit_interface.php?action=editraw&amp;ged=' . $this->record->getTree()->getNameHtml() . '&amp;xref=' . $this->record->getXref(), 'menu-indi-editraw'));
		}

		return $menu;
	}

	/**
	 * get the person box stylesheet class for the given person
	 *
	 * @param Individual $person
	 *
	 * @return string returns 'person_box', 'person_boxF', or 'person_boxNN'
	 */
	public function getPersonStyle($person) {
		switch ($person->getSex()) {
		case 'M':
			$class = 'person_box';
			break;
		case 'F':
			$class = 'person_boxF';
			break;
		default:
			$class = 'person_boxNN';
			break;
		}
		if ($person->isPendingDeletion()) {
			$class .= ' old';
		} elseif ($person->isPendingAddtion()) {
			$class .= ' new';
		}

		return $class;
	}

	/**
	 * Get significant information from this page, to allow other pages such as
	 * charts and reports to initialise with the same records
	 *
	 * @return string
	 */
	public function getSignificantSurname() {
		if ($this->record) {
			list($surn) = explode(',', $this->record->getSortName());

			return $surn;
		} else {
			return '';
		}
	}

	/**
	 * Get the contents of sidebar.
	 *
	 * @return string
	 */
	public function getSideBarContent() {
		$html = '';
		foreach (Module::getActiveSidebars($this->record->getTree()) as $module) {
			if ($module->hasSidebarContent()) {
				$class = $module->getName() === 'family_nav' ? 'collapse show' : 'collapse';
				$aria  = $module->getName() === 'family_nav' ? 'true' : 'false';
				$html .= '
				<div class="card">
          <div class="card-header" role="tab" id="sidebar-header-' . $module->getName() . '">
			      <div class="card-title mb-0">
			        <a data-toggle="collapse" data-parent="#sidebar" href="#sidebar-content-' . $module->getName() . '" aria-expanded="' . $aria . '" aria-controls="sidebar-content-' . $module->getName() . '">' . $module->getTitle() . '</a>
			      </div>
	        </div>
			    <div id="sidebar-content-' . $module->getName() . '" class="' . $class . '" role="tabpanel" aria-labelledby="sidebar-header-' . $module->getName() . '">
			      <div class="card-block">' . $module->getSidebarContent() . '</div>
          </div>
        </div>';
			}
		}

		if ($html) {
			return '<div id="sidebar" role="tablist">' . $html . '</div>';
		} else {
			return '';
		}
	}

	/**
	 * Get the description for the family.
	 *
	 * For example, "XXX's family with new wife".
	 *
	 * @param Family     $family
	 * @param Individual $individual
	 *
	 * @return string
	 */
	public function getSpouseFamilyLabel(Family $family, Individual $individual) {
		$spouse = $family->getSpouse($individual);
		if ($spouse) {
			return
				/* I18N: %s is the spouse name */
				I18N::translate('Family with %s', $spouse->getFullName());
		} else {
			return $family->getFullName();
		}
	}
}
