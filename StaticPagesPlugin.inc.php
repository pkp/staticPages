<?php

/**
 * @file StaticPagesPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesPlugin
 * Static pages plugin main class
 */

use PKP\linkAction\LinkAction;
use PKP\plugins\GenericPlugin;
use PKP\linkAction\request\RedirectAction;

class StaticPagesPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.staticPages.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        $description = __('plugins.generic.staticPages.description');
        if (!$this->isTinyMCEInstalled()) {
            $description .= __('plugins.generic.staticPages.requirement.tinymce');
        }
        return $description;
    }

    /**
     * Check whether or not the TinyMCE plugin is installed.
     *
     * @return boolean True iff TinyMCE is installed.
     */
    public function isTinyMCEInstalled()
    {
        $application = Application::get();
        $products = $application->getEnabledProducts('plugins.generic');
        return (isset($products['tinymce']));
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                // Register the static pages DAO.
                import('plugins.generic.staticPages.classes.StaticPagesDAO');
                $staticPagesDao = new StaticPagesDAO();
                DAORegistry::registerDAO('StaticPagesDAO', $staticPagesDao);

                HookRegistry::register('Template::Settings::website', [$this, 'callbackShowWebsiteSettingsTabs']);

                // Intercept the LoadHandler hook to present
                // static pages when requested.
                HookRegistry::register('LoadHandler', [$this, 'callbackHandleContent']);

                // Register the components this plugin implements to
                // permit administration of static pages.
                HookRegistry::register('LoadComponentHandler', [$this, 'setupGridHandler']);
            }
            return true;
        }
        return false;
    }

    /**
     * Extend the website settings tabs to include static pages
     *
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters
     *
     * @return boolean Hook handling status
     */
    public function callbackShowWebsiteSettingsTabs($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = & $args[2];
        $request = & Registry::get('request');
        $dispatcher = $request->getDispatcher();

        $output .= $templateMgr->fetch($this->getTemplateResource('staticPagesTab.tpl'));

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    /**
     * Declare the handler function to process the actual page PATH
     *
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters
     *
     * @return boolean Hook handling status
     */
    public function callbackHandleContent($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $page = & $args[0];
        $op = & $args[1];

        $staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
        if ($page == 'pages' && $op == 'preview') {
            // This is a preview request; mock up a static page to display.
            // The handler class ensures that only managers and administrators
            // can do this.
            $staticPage = $staticPagesDao->newDataObject();
            $staticPage->setContent((array) $request->getUserVar('content'), null);
            $staticPage->setTitle((array) $request->getUserVar('title'), null);
        } else {
            // Construct a path to look for
            $path = $page;
            if ($op !== 'index') {
                $path .= "/${op}";
            }
            if ($ops = $request->getRequestedArgs()) {
                $path .= '/' . implode('/', $ops);
            }

            // Look for a static page with the given path
            $context = $request->getContext();
            $staticPage = $staticPagesDao->getByPath(
                $context ? $context->getId() : CONTEXT_ID_NONE,
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
     *
     * @param $hookName string The name of the hook being invoked
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        if ($component == 'plugins.generic.staticPages.controllers.grid.StaticPageGridHandler') {
            // Allow the static page grid handler to get the plugin object
            import($component);
            StaticPageGridHandler::setPlugin($this);
            return true;
        }
        return false;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $dispatcher = $request->getDispatcher();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new RedirectAction($dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_PAGE,
                        null,
                        'management',
                        'settings',
                        'website',
                        ['uid' => uniqid()], // Force reload
                        'staticPages' // Anchor for tab
                    )),
                    __('plugins.generic.staticPages.editAddContent'),
                    null
                ),
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    /**
     * @copydoc Plugin::getInstallMigration()
     */
    public function getInstallMigration()
    {
        $this->import('StaticPagesSchemaMigration');
        return new StaticPagesSchemaMigration();
    }

    /**
     * Get the JavaScript URL for this plugin.
     */
    public function getJavaScriptURL($request)
    {
        return $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js';
    }
}
