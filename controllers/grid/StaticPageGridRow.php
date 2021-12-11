<?php

/**
 * @file controllers/grid/StaticPageGridRow.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StaticPageGridRow
 * @ingroup controllers_grid_staticPages
 *
 * @brief Handle custom blocks grid row requests.
 */

namespace APP\plugins\generic\staticPages\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class StaticPageGridRow extends GridRow
{
    //
    // Overridden template methods
    //
    /**
     * @copydoc GridRow::initialize()
     *
     * @param null|mixed $template
     */
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $staticPageId = $this->getId();
        if (!empty($staticPageId)) {
            $router = $request->getRouter();

            // Create the "edit static page" action
            $this->addAction(
                new LinkAction(
                    'editStaticPage',
                    new AjaxModal(
                        $router->url($request, null, null, 'editStaticPage', null, ['staticPageId' => $staticPageId]),
                        __('grid.action.edit'),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            // Create the "delete static page" action
            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('grid.action.delete'),
                        $router->url($request, null, null, 'delete', null, ['staticPageId' => $staticPageId]),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
