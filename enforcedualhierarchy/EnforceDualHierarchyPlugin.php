<?php

namespace Craft;

class EnforceDualHierarchyPlugin extends BasePlugin {
	public function getName() { return Craft::t('Enforce Dual Hierarchy'); }
	public function getDescription() { return Craft::t('Validates the placement of parent and child pages in a structure section hierarchy'); }
	public function getDeveloper() { return 'Jordan Lev'; }
	public function getDeveloperUrl() { return 'https://webconcentrate.com'; }
	public function hasCpSection() { return false; }
	
	public function getVersion() { return '0.1'; }
	public function getSchemaVersion() { return '0.1'; }
	
	private $sectionId = 1;
	private $parentEntryTypeHandle = 'department';
	private $parentEntryTypeLabel = 'Department';
	private $childEntryTypeHandle = 'employee';
	private $childEntryTypeLabel = 'Employee';
	
	
	/**
	 * So... when an entry is dragged around in the "tree view",
	 * only the onBeforeMoveElement event is triggered.
	 * But when an entry is edited and its parent is changed
	 * via the "edit entry" form, then BOTH the onBeforeSaveEntry
	 * AND the onBeforeMoveElement events are called!
	 * Ideally we'd only need code in onBeforeMoveElement to handle
	 * both situations, BUT the onBeforeMoveElement event handler
	 * does not provide a way to send an error message to the CP,
	 * and also the only way to cancel the rest of the save operation
	 * from the "edit entry" form is to cancel the onBeforeSaveEntry event.
	 * 
	 * Hence, we go through the trouble of handling both events,
	 * so that at least for the "edit entry" form we can provide
	 * the better user experience of telling the user what went wrong
	 * and re-displaying the form with validation errors.
	 * 
	 * This variable is a flag so that the onBeforeMoveElement handler
	 * knows if the onBeforeSaveEntry handler has already validated things.
	 */
	private $onBeforeSaveEntryWasCalled = false;
	
	public function init() {
		craft()->on('entries.onBeforeSaveEntry', function(Event $event) {
			$this->onBeforeSaveEntryWasCalled = true;
			
			$error = $this->validateEntryMove($event->params['entry']);
			
			if ($error) {
				$event->performAction = false;
				$event->params['entry']->addError('parent', $error);
				craft()->userSession->setNotice($error); //note that we cannot use ->setError() because that gets overridden by craft's generic "Couldn't save entry" message
			}
		});

		craft()->on('structures.onBeforeMoveElement', function(Event $event) {
			if ($this->onBeforeSaveEntryWasCalled) {
				return;
			}
			
			$error = $this->validateEntryMove($event->params['element']);
			
			if ($error) {
				$event->performAction = false;
				
				//Unfortunately there is no way to send error messages
				// for the ajax call from the section structure tree view...
				// The best we can do is throw an exception,
				// which results in a generic error message being shown to the user.
				throw new Exception($error);
			}
		});

		parent::init();
	}
	
	//If the move is valid, returns empty string.
	//If move is invalid, returns human-readable error message
	private function validateEntryMove($entry) {
		if ($entry->sectionId != $this->sectionId) {
			return;
		}
		
		$entryTypeIsChild = ($entry->type->handle == $this->childEntryTypeHandle);
		$entryTypeIsParent = ($entry->type->handle == $this->parentEntryTypeHandle);

		//This is the only reliable way to get the NEW parentId,
		// both for the structure tree view drag/drop stuff
		// (because the soon-to-be-new-parent is not provided
		// to the onBeforeMoveElement event handler),
		// AND for the edit entry form (because $params['entry]->getParent()
		// is unreliable... if user makes no choice then it stays at its old value).
		$toParentId = craft()->request->getPost('parentId');
		$toParent = $toParentId ? craft()->entries->getEntryById($toParentId, $entry->locale) : null;
		
		if ($toParent) {
			$newParentIsNull = false;
			$newParentIsChildType = ($toParent->type->handle == $this->childEntryTypeHandle);
			$newParentIsParentType = ($toParent->type->handle == $this->parentEntryTypeHandle);
		} else {
			$newParentIsNull = true;
			$newParentIsChildType = false;
			$newParentIsParentType = false;
		}
		
		$error = '';
		
		//Entries of the child type can only be placed underneath entries of the parent type
		// (not other child entries and not at the top-level).
		//Entries of the parent type can only be placed at the top-level (not as children of any type).
		if ($entryTypeIsChild && $newParentIsChildType) {
			$error = $this->childEntryTypeLabel . ' pages can only be placed underneath ' . $this->parentEntryTypeLabel . ' pages (not other ' . $this->childEntryTypeLabel . ' pages)';
		} else if ($entryTypeIsChild && $newParentIsNull) {
			$error = $this->childEntryTypeLabel . ' pages must be underneath a ' . $this->parentEntryTypeLabel . ' page';
		} else if ($entryTypeIsParent && $newParentIsChildType) {
			$error = $this->parentEntryTypeLabel . ' pages cannot be placed underneath ' . $this->childEntryTypeLabel . ' pages';
		} else if ($entryTypeIsParent && $newParentIsParentType) {
			$error = $this->parentEntryTypeLabel . ' pages cannot be placed underneath other ' . $this->parentEntryTypeLabel . ' pages';
		}
		
		return $error;
	}
}