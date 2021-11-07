<?php

/**
 * @file classes/StaticPage.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPage
 * Data object representing a static page.
 */

namespace APP\plugins\generic\staticPages\classes;

class StaticPage extends \PKP\core\DataObject
{
    //
    // Get/set methods
    //

    /**
     * Get context ID
     *
     * @return string
     */
    public function getContextId()
    {
        return $this->getData('contextId');
    }

    /**
     * Set context ID
     *
     * @param int $contextId
     */
    public function setContextId($contextId)
    {
        return $this->setData('contextId', $contextId);
    }


    /**
     * Set page title
     *
     * @param string $title
     * @param string $locale
     */
    public function setTitle($title, $locale)
    {
        return $this->setData('title', $title, $locale);
    }

    /**
     * Get page title
     *
     * @param string $locale
     *
     * @return string
     */
    public function getTitle($locale)
    {
        return $this->getData('title', $locale);
    }

    /**
     * Get Localized page title
     *
     * @return string
     */
    public function getLocalizedTitle()
    {
        return $this->getLocalizedData('title');
    }

    /**
     * Set page content
     *
     * @param string $content
     * @param string $locale
     */
    public function setContent($content, $locale)
    {
        return $this->setData('content', $content, $locale);
    }

    /**
     * Get page content
     *
     * @param string $locale
     *
     * @return string
     */
    public function getContent($locale)
    {
        return $this->getData('content', $locale);
    }

    /**
     * Get "localized" content
     *
     * @return string
     */
    public function getLocalizedContent()
    {
        return $this->getLocalizedData('content');
    }

    /**
     * Get page path string
     *
     * @return string
     */
    public function getPath()
    {
        return $this->getData('path');
    }

    /**
     * Set page path string
     *
     * @param string $path
     */
    public function setPath($path)
    {
        return $this->setData('path', $path);
    }
}
