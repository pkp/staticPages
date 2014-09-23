<?php

/**
 * @file plugins/generic/staticPages/controllers/grid/StaticPageGridHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageGridHandler
 * @ingroup controllers_grid_staticPages
 *
 * @brief Handle static pages grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.staticPages.controllers.grid.StaticPageGridRow');

class StaticPageGridHandler extends GridHandler {
	/** @var StaticPagesPlugin The static pages plugin */
	var $plugin;

	/**
	 * Constructor
	 */
	function StaticPageGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow', 'addStaticPage', 'editStaticPage', 'updateStaticPage', 'delete')
		);
		$this->plugin = PluginRegistry::getPlugin('generic', CUSTOMBLOCKMANAGER_PLUGIN_NAME);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request);
		$context = $request->getContext();

		// Set the grid title.
		$this->setTitle('plugins.generic.staticPages.staticPages');
		// Set the grid instructions.
		$this->setInstructions('plugins.generic.staticPages.introduction');
		// Set the no items row text.
		$this->setEmptyRowText('plugins.generic.staticPages.noneCreated');

		// Get the pages and add the data to the grid
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		$pages = $staticPagesDao->getByContextId($context->getId());
		$gridData = array();
		while ($page = $pages->next()) {
			$gridData[$page->getId()] = array(
				'path' => $page->getPath(),
				'title' => $page->getLocalizedTitle(),
			);
		}
		$this->setGridDataElements($gridData);

		// Add grid-level actions
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'addStaticPage',
				new AjaxModal(
					$router->url($request, null, null, 'addStaticPage'),
					__('plugins.generic.staticPages.addStaticPage'),
					'modal_add_item'
				),
				__('plugins.generic.staticPages.addStaticPage'),
				'add_item'
			)
		);

		// Columns
		$this->addColumn(new GridColumn(
			'title',
			'plugins.generic.staticPages.pageTitle',
			null,
			'controllers/grid/gridCell.tpl'
		));
		$this->addColumn(new GridColumn(
			'path',
			'plugins.generic.staticPages.path',
			null,
			'controllers/grid/gridCell.tpl'
		));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see Gridhandler::getPublishChangeEvents()
	 * @return array List of events that should be published upon change
	 * Used to update the site context switcher upon create/delete.
	 */
	function getPublishChangeEvents() {
		return array('updateSidebar');
	}

	/**
	 * @copydoc Gridhandler::getRowInstance()
	 */
	function getRowInstance() {
		return new StaticPageGridRow();
	}

	//
	// Public Grid Actions
	//
	/**
	 * An action to add a new custom static page
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 */
	function addStaticPage($args, $request) {
		// Calling editStaticPage with an empty ID will add
		// a new static page.
		return $this->editStaticPage($args, $request);
	}

	/**
	 * An action to edit a static page
	 * @param $args array Arguments to the request
	 * @param $request PKPRequest Request object
	 * @return string Serialized JSON object
	 */
	function editStaticPage($args, $request) {
		$staticPageId = $request->getUserVar('staticPageId');
		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.staticPages.controllers.grid.form.StaticPageForm');
		$staticPagesPlugin = $this->plugin;
		$template = $staticPagesPlugin->getTemplatePath() . 'editStaticPageForm.tpl';
		$staticPageForm = new StaticPageForm($template, $context->getId(), $staticPageId);
		$staticPageForm->initData();
		$json = new JSONMessage(true, $staticPageForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a custom block
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateStaticPage($args, $request) {
		$staticPageId = $request->getUserVar('staticPageId');
		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and populate the form
		import('plugins.generic.staticPages.controllers.grid.form.StaticPageForm');
		$staticPagesPlugin = $this->plugin;
		$template = $staticPagesPlugin->getTemplatePath() . 'editStaticPageForm.tpl';
		$staticPageForm = new StaticPageForm($template, $context->getId(), $staticPageId);
		$staticPageForm->readInputData();

		// Check the results
		if ($staticPageForm->validate()) {
			// Save the results
			$staticPageForm->execute();
 			return DAO::getDataChangedEvent();
		} else {
			// Present any errors
			$json = new JSONMessage(true, $staticPageForm->fetch($request));
			return $json->getString();
		}
	}

	/**
	 * Delete a static page
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function delete($args, $request) {
		$staticPageId = $request->getUserVar('staticPageId');
		$context = $request->getContext();

		// Delete the static page
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		$staticPage = $staticPagesDao->getById($staticPageId, $context->getId());
		$staticPagesDao->deleteObject($staticPage);

		return DAO::getDataChangedEvent();
	}
}

?>
