<?php

/**
 * @file StaticPagesHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesHandler
 * Find static page content and display it when requested.
 */

namespace APP\plugins\generic\staticPages;

use APP\core\Application;
use APP\plugins\generic\staticPages\StaticPagesPlugin;
use APP\plugins\generic\staticPages\classes\StaticPage;
use APP\template\TemplateManager;
use PKP\security\Role;

class StaticPagesHandler extends \APP\handler\Handler
{
    /** @var StaticPagesPlugin The static pages plugin */
    protected $plugin;

    /** @var StaticPage The static page to view */
    protected $staticPage;

    public function __construct(StaticPagesPlugin $plugin, StaticPage $staticPage) {
        $this->plugin = $plugin;
        $this->staticPage = $staticPage;
    }

    /**
     * Handle index request (redirect to "view")
     *
     * @param array $args Arguments array.
     * @param PKPRequest $request Request object.
     */
    public function index($args, $request)
    {
        $request->redirect(null, null, 'view', $request->getRequestedOp());
    }

    /**
     * Handle view page request (redirect to "view")
     *
     * @param array $args Arguments array.
     * @param PKPRequest $request Request object.
     */
    public function view($args, $request)
    {
        $path = array_shift($args);
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : Application::CONTEXT_ID_NONE;

        // Ensure that if we're previewing, the current user is a manager or admin.
        $roles = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);
        if (!$this->staticPage->getId() && count(array_intersect([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN], $roles)) == 0) {
            fatalError('The current user is not permitted to preview.');
        }

        // Assign the template vars needed and display
        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $templateMgr->assign('title', $this->staticPage->getLocalizedTitle());

        $vars = [];
        if ($context) {
            $vars = [
                '{$contactName}' => $context->getData('contactName'),
                '{$contactEmail}' => $context->getData('contactEmail'),
                '{$supportName}' => $context->getData('supportName'),
                '{$supportPhone}' => $context->getData('supportPhone'),
                '{$supportEmail}' => $context->getData('supportEmail'),
            ];
        }
        $templateMgr->assign('content', strtr($this->staticPage->getLocalizedContent(), $vars));

        $templateMgr->display($this->plugin->getTemplateResource('content.tpl'));
    }
}
