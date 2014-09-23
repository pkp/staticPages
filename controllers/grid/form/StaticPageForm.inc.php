<?php

/**
 * @file plugins/generic/staticPages/controllers/grid/form/StaticPageForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageForm
 * @ingroup controllers_grid_staticPages
 *
 * Form for press managers to create and modify sidebar blocks
 *
 */

import('lib.pkp.classes.form.Form');

class StaticPageForm extends Form {
	/** @var int Context (press / journal) ID */
	var $contextId;

	/** @var string Static page name */
	var $staticPageId;

	/**
	 * Constructor
	 * @param $template string the path to the form template file
	 * @param $contextId int Context ID
	 * @param $staticPageId int Static page ID (if any)
	 */
	function StaticPageForm($template, $contextId, $staticPageId = null) {
		parent::Form($template);

		$this->contextId = $contextId;
		$this->staticPageId = $staticPageId;

		// Add form checks
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.staticPages.nameRequired'));
		$this->addCheck(new FormValidatorAlphaNum($this, 'path', 'required', 'plugins.generic.staticPages.pathRegEx'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'plugins.generic.staticPages.duplicatePath', create_function('$path,$form,$staticPagesDao', '$page = $staticPagesDao->getByPath($form->contextId, $path); return !$page || $page->getId()==$form->staticPageId;'), array($this, DAORegistry::getDAO('StaticPagesDAO'))));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$templateMgr = TemplateManager::getManager();
		if ($this->staticPageId) {
			$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
			$staticPage = $staticPagesDao->getById($this->staticPageId, $this->contextId);
			$this->setData('path', $staticPage->getPath());
			$this->setData('title', $staticPage->getTitle(null)); // Localized
			$this->setData('content', $staticPage->getContent(null)); // Localized
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('path', 'title', 'content'));
	}

	/**
	 * @see Form::fetch
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('staticPageId', $this->staticPageId);
		return parent::fetch($request);
	}

	/**
	 * Save form values into the database
	 */
	function execute() {
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		if ($this->staticPageId) {
			// Load and update an existing page
			$staticPage = $staticPagesDao->getById($this->staticPageId, $this->contextId);
		} else {
			// Create a new static page
			$staticPage = $staticPagesDao->newDataObject();
			$staticPage->setContextId($this->contextId);
		}

		$staticPage->setPath($this->getData('path'));
		$staticPage->setTitle($this->getData('title'), null); // Localized
		$staticPage->setContent($this->getData('content'), null); // Localized

		if ($this->staticPageId) {
			$staticPagesDao->updateObject($staticPage);
		} else {
			$staticPagesDao->insertObject($staticPage);
		}
	}
}

?>
