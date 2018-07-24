<?php

/**
 * @file controllers/grid/StaticPageGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageGridHandler
 * @ingroup controllers_grid_staticPages
 *
 * @brief Handle static pages grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.staticPages.controllers.grid.StaticPageGridRow');
import('plugins.generic.staticPages.controllers.grid.StaticPageGridCellProvider');

class StaticPageGridHandler extends GridHandler {
	/** @var StaticPagesPlugin The static pages plugin */
	static $plugin;

	/**
	 * Set the static pages plugin.
	 * @param $plugin StaticPagesPlugin
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array('index', 'fetchGrid', 'fetchRow', 'addStaticPage', 'editStaticPage', 'updateStaticPage', 'delete')
		);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		$context = $request->getContext();

		// Set the grid details.
		$this->setTitle('plugins.generic.staticPages.staticPages');
		$this->setEmptyRowText('plugins.generic.staticPages.noneCreated');

		// Get the pages and add the data to the grid
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		$this->setGridDataElements($staticPagesDao->getByContextId($context->getId()));

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
		$cellProvider = new StaticPageGridCellProvider();
		$this->addColumn(new GridColumn(
			'title',
			'plugins.generic.staticPages.pageTitle',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
		$this->addColumn(new GridColumn(
			'path',
			'plugins.generic.staticPages.path',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	function getRowInstance() {
		return new StaticPageGridRow();
	}

	//
	// Public Grid Actions
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$context = $request->getContext();
		import('lib.pkp.classes.form.Form');
		$form = new Form(self::$plugin->getTemplateResource('staticPages.tpl'));
		return new JSONMessage(true, $form->fetch($request));
	}

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
		$staticPagesPlugin = self::$plugin;
		$staticPageForm = new StaticPageForm(self::$plugin, $context->getId(), $staticPageId);
		$staticPageForm->initData();
		return new JSONMessage(true, $staticPageForm->fetch($request));
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
		$staticPagesPlugin = self::$plugin;
		$staticPageForm = new StaticPageForm(self::$plugin, $context->getId(), $staticPageId);
		$staticPageForm->readInputData();

		// Check the results
		if ($staticPageForm->validate()) {
			// Save the results
			$staticPageForm->execute();
 			return DAO::getDataChangedEvent();
		} else {
			// Present any errors
			return new JSONMessage(true, $staticPageForm->fetch($request));
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
