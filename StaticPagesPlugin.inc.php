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
			$description .= __('plugins.generic.staticPages.requirement.tinymce');
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
				import('plugins.generic.staticPages.classes.StaticPagesDAO');
				$staticPagesDao = new StaticPagesDAO();
				DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

				HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));

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
	 * Extend the website settings tabs to include static pages
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackShowWebsiteSettingsTabs($hookName, $args) {
		$output =& $args[2];
		$request =& Registry::get('request');
		$dispatcher = $request->getDispatcher();

		// Add a new tab for static pages
		$output .= '<li><a name="staticPages" href="' . $dispatcher->url($request, ROUTE_COMPONENT, null, 'plugins.generic.staticPages.controllers.grid.StaticPageGridHandler', 'index') . '">' . __('plugins.generic.staticPages.staticPages') . '</a></li>';

		// Permit other plugins to continue interacting with this hook
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

		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		if ($page == 'pages' && $op == 'preview') {
			// This is a preview request; mock up a static page to display.
			// The handler class ensures that only managers and administrators
			// can do this.
			$staticPage = $staticPagesDao->newDataObject();
			$staticPage->setContent((array) $request->getUserVar('content'), null);
			$staticPage->setTitle((array) $request->getUserVar('title', null));
		} else {
			// Construct a path to look for
			$path = $page;
			if ($op !== 'index') $path .= "/$op";
			if ($ops = $request->getRequestedArgs()) $path .= '/' . implode('/', $ops);

			// Look for a static page with the given path
			$context = $request->getContext();
			$staticPage = $staticPagesDao->getByPath(
				$context?$context->getId():CONTEXT_ID_NONE,
				$path
			);
		}

		// Check if this is a request for a static page or preview.
		if ($staticPage) {
			// Trick the handler into dealing with it normally
			$page = 'pages';
			$op = 'view';

			// It is -- attach the static pages handler.
			define('HANDLER_CLASS', 'StaticPagesHandler');
			$this->import('StaticPagesHandler');

			// Allow the static pages page handler to get the plugin object
			StaticPagesHandler::setPlugin($this);
			StaticPagesHandler::setPage($staticPage);
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
			// Allow the static page grid handler to get the plugin object
			import($component);
			StaticPageGridHandler::setPlugin($this);
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
				$dispatcher = $request->getDispatcher();
				import('lib.pkp.classes.linkAction.request.RedirectAction');
				return new LinkAction(
					$verbName,
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'management', 'settings', 'website',
						array('uid' => uniqid()), // Force reload
						'staticPages' // Anchor for tab
					)),
					$verbLocalized,
					null
				);
			default:
				return parent::getManagementVerbLinkAction($request, $verb);
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

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL($request) {
		return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js';
	}
}

?>
