<?php

/**
 * @file controllers/grid/form/StaticPageForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticPageForm
 * @ingroup controllers_grid_staticPages
 *
 * Form for press managers to create and modify sidebar blocks
 *
 */

namespace APP\plugins\generic\staticPages\controllers\grid\form;

use APP\template\TemplateManager;
use PKP\db\DAORegistry;

class StaticPageForm extends \PKP\form\Form
{
    /** @var int Context (press / journal) ID */
    public $contextId;

    /** @var string Static page name */
    public $staticPageId;

    /** @var StaticPagesPlugin Static pages plugin */
    public $plugin;

    /**
     * Constructor
     *
     * @param StaticPagesPlugin $staticPagesPlugin The static page plugin
     * @param int $contextId Context ID
     * @param int $staticPageId Static page ID (if any)
     */
    public function __construct($staticPagesPlugin, $contextId, $staticPageId = null)
    {
        parent::__construct($staticPagesPlugin->getTemplateResource('editStaticPageForm.tpl'));

        $this->contextId = $contextId;
        $this->staticPageId = $staticPageId;
        $this->plugin = $staticPagesPlugin;

        // Add form checks
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'title', 'required', 'plugins.generic.staticPages.nameRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'path', 'required', 'plugins.generic.staticPages.pathRegEx', '/^[a-zA-Z0-9\/._-]+$/'));
        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'path', 'required', 'plugins.generic.staticPages.duplicatePath', function ($path) use ($form) {
            $staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
            $page = $staticPagesDao->getByPath($form->contextId, $path);
            return !$page || $page->getId() == $form->staticPageId;
        }));
    }

    /**
     * Initialize form data from current group group.
     */
    public function initData()
    {
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
    public function readInputData()
    {
        $this->readUserVars(['path', 'title', 'content']);
    }

    /**
     * @copydoc Form::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager();
        $templateMgr->assign([
            'staticPageId' => $this->staticPageId,
            'pluginJavaScriptURL' => $this->plugin->getJavaScriptURL($request),
        ]);

        if ($context = $request->getContext()) {
            $templateMgr->assign('allowedVariables', [
                'contactName' => __('plugins.generic.tinymce.variables.principalContactName', ['value' => $context->getData('contactName')]),
                'contactEmail' => __('plugins.generic.tinymce.variables.principalContactEmail', ['value' => $context->getData('contactEmail')]),
                'supportName' => __('plugins.generic.tinymce.variables.supportContactName', ['value' => $context->getData('supportName')]),
                'supportPhone' => __('plugins.generic.tinymce.variables.supportContactPhone', ['value' => $context->getData('supportPhone')]),
                'supportEmail' => __('plugins.generic.tinymce.variables.supportContactEmail', ['value' => $context->getData('supportEmail')]),
            ]);
        }

        return parent::fetch($request, $template, $display);
    }

    /**
     * Save form values into the database
     */
    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

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
