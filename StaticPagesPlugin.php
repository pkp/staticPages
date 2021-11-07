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

namespace APP\plugins\generic\staticPages;

use APP\plugins\generic\staticPages\controllers\grid\StaticPageGridHandler;
use APP\template\TemplateManager;
use APP\core\Application;
use APP\plugins\generic\staticPages\classes\StaticPagesDAO;
use PKP\core\Registry;
use PKP\linkAction\LinkAction;
use PKP\plugins\GenericPlugin;
use PKP\linkAction\request\RedirectAction;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;

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
     * @return bool True iff TinyMCE is installed.
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
     * @param string $hookName The name of the invoked hook
     * @param array $args Hook parameters
     *
     * @return bool Hook handling status
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
     * @param string $hookName The name of the invoked hook
     * @param array $args Hook parameters
     *
     * @return bool Hook handling status
     */
    public function callbackHandleContent($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $page = & $args[0];
        $op = & $args[1];
        $handler = & $args[3];

        /** @var StaticPagesDAO */
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
                $context ? $context->getId() : Application::CONTEXT_ID_NONE,
                $path
            );
        }

        // Check if this is a request for a static page or preview.
        if ($staticPage) {
            // Trick the handler into dealing with it normally
            $page = 'pages';
            $op = 'view';

            // It is -- attach the static pages handler.
            $handler = new StaticPagesHandler($this, $staticPage);
            return true;
        }
        return false;
    }

    /**
     * Permit requests to the static pages grid handler
     *
     * @param string $hookName The name of the hook being invoked
     */
    public function setupGridHandler($hookName, $params)
    {
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component == 'plugins.generic.staticPages.controllers.grid.StaticPageGridHandler') {
            // Allow the static page grid handler to get the plugin object
            $componentInstance = new StaticPageGridHandler($this);
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
                        Application::ROUTE_PAGE,
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

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\staticPages\StaticPagesPlugin', '\StaticPagesPlugin');
}
