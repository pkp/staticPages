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
		if (!$this->isTinyMCEInstalled())
			$description .= "<br />".__('plugins.generic.staticPages.requirement.tinymce');
		return $description;
	}

	/**
	 * Check whether or not the TinyMCE plugin is installed.
	 * @return boolean True iff TinyMCE is installed.
	 */
	function isTinyMCEInstalled() {
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

				// Kludge to permit controllers to know the plugin name
				define('STATIC_PAGES_PLUGIN_NAME', $this->getName());
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
		list($verbName, $verbLocalized) = $verb;

		switch ($verbName) {
			case 'settings':
				// Generate a link action for the "settings" action
				$router = $request->getRouter();
				import('lib.pkp.classes.linkAction.request.AjaxLegacyPluginModal');
				return new LinkAction(
					$verbName,
					new AjaxLegacyPluginModal(
						$router->url($request, null, null, 'plugin', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => $this->getCategory())),
						$this->getDisplayName()
					),
					$verbLocalized,
					null
				);
			default:
				return parent::getManagementVerbLinkAction($request, $verb);
		}
	}

 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		switch ($verb) {
			case 'settings':
				$request = $this->getRequest();
				$context = $request->getContext();
				import('lib.pkp.classes.form.Form');
				$form = new Form($this->getTemplatePath() . 'staticPages.tpl');
				$pluginModalContent = $form->fetch($request);
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

	/**
	 * @copydoc PKPPlugin::getTemplatePath
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}
}

?>
