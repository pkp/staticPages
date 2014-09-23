<?php

/**
 * @file StaticPagesPlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesPlugin
 * Static pages plugin main class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class StaticPagesPlugin extends GenericPlugin {
	/**
	 * Get the plugin's display (human-readable) name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.staticPages.displayName');
	}

	/**
	 * Get the plugin's display (human-readable) description.
	 * @return string
	 */
	function getDescription() {
		$description = __('plugins.generic.staticPages.description');
		if ( !$this->isTinyMCEInstalled() )
			$description .= "<br />".__('plugins.generic.staticPages.requirement.tinymce');
		return $description;
	}

	/**
	 * Check whether or not the TinyMCE plugin is installed.
	 * @return boolean True iff TinyMCE is installed.
	 */
	function isTinyMCEInstalled() {
		// If the thesis plugin isn't enabled, don't do anything.
		$application = PKPApplication::getApplication();
		$products = $application->getEnabledProducts('plugins.generic');
		return (isset($products['tinymce']));
	}

	/**
	 * Register the plugin, attaching to hooks as necessary.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// Register the static pages DAO.
				$this->import('StaticPagesDAO');
				$staticPagesDao = new StaticPagesDAO($this->getName());
				DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

				// Intercept the LoadHandler hook to present
				// static pages when requested.
				HookRegistry::register('LoadHandler', array($this, 'callbackHandleContent'));

				// Register the components this plugin implements to
				// permit administration of static pages.
				HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Declare the handler function to process the actual page PATH
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackHandleContent($hookName, $args) {
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);

		$page =& $args[0];
		$op =& $args[1];

		// Check if this is a request for a static page.
		if ($page == 'pages' && in_array($op, array('index', 'view'))) {
			// It is -- attach the static pages handler.
			define('STATIC_PAGES_PLUGIN_NAME', $this->getName());
			define('HANDLER_CLASS', 'StaticPagesHandler');
			$this->import('StaticPagesHandler');
			return true;
		}
		return false;
	}

	/**
	 * Permit requests to the static pages grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.staticPages.controllers.grid.StaticPageGridHandler') {
			define('CUSTOMBLOCKMANAGER_PLUGIN_NAME', $this->getName());
			return true;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 * @return array Management verbs
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		if ($this->getEnabled()) {
			if ($this->isTinyMCEInstalled()) {
				$verbs[] = array('settings', __('plugins.generic.staticPages.editAddContent'));
			}
		}
		return $verbs;
	}

	/**
	 * @copydoc Plugin::getManagementVerbLinkAction()
	 */
	function getManagementVerbLinkAction($request, $verb) {
		$router = $request->getRouter();

		list($verbName, $verbLocalized) = $verb;

		if ($verbName === 'settings') {
			// Generate a link action for the "manage" action
			import('lib.pkp.classes.linkAction.request.AjaxLegacyPluginModal');
			$actionRequest = new AjaxLegacyPluginModal(
					$router->url($request, null, null, 'plugin', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
					$this->getDisplayName()
			);
			return new LinkAction($verbName, $actionRequest, $verbLocalized, null);
		}

		return null;
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		$request = $this->getRequest();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

		switch ($verb) {
			case 'settings':
				$context = $request->getContext();
				import('lib.pkp.classes.form.Form');
				$form = new Form($this->getTemplatePath() . 'staticPages.tpl');
				$pluginModalContent = $form->fetch($request);
				return true;
			case 'edit':
			case 'add':
				$templateMgr->assign('pagesPath', $request->url(null, 'pages', 'view', 'REPLACEME'));
				$context = $request->getContext();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $context->getId(), $staticPageId);

				if ($form->isLocaleResubmit()) {
					$form->readInputData();
					$form->addTinyMCE();
				} else {
					$form->initData();
				}

				$form->display();
				return true;
			case 'save':
				$context = $request->getContext();

				$this->import('StaticPagesEditForm');

				$staticPageId = isset($args[0])?(int)$args[0]:null;
				$form = new StaticPagesEditForm($this, $context->getId(), $staticPageId);

				if ($request->getUserVar('edit')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->save();
						$templateMgr->assign(array(
							'currentUrl' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'pageTitle' => 'plugins.generic.staticPages.displayName',
							'message' => 'plugins.generic.staticPages.pageSaved',
							'backLink' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
							'backLinkLabel' => 'common.continue'
						));
						$templateMgr->display('common/message.tpl');
						exit;
					} else {
						$form->addTinyMCE();
						$form->display();
						exit;
					}
				}
				$request->redirect(null, null, 'manager', 'plugins');
				return false;
			case 'delete':
				$context = $request->getContext();
				$staticPageId = isset($args[0])?(int) $args[0]:null;
				$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
				$staticPagesDao->deleteById($staticPageId);

				$templateMgr->assign(array(
					'currentUrl' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'pageTitle' => 'plugins.generic.staticPages.displayName',
					'message' => 'plugins.generic.staticPages.pageDeleted',
					'backLink' => $request->url(null, null, null, array($this->getCategory(), $this->getName(), 'settings')),
					'backLinkLabel' => 'common.continue'
				));

				$templateMgr->display('common/message.tpl');
				return true;
			default:
				return parent::manage($verb, $args, $message, $messageParams);
		}
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 * @return string Full path and filename to schema descriptor.
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}
}

?>
