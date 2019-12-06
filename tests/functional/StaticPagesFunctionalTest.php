<?php

/**
 * @file tests/functional/StaticPagesFunctionalTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPagesFunctionalTest
 * @package plugins.generic.staticPages
 *
 * @brief Functional tests for the static pages plugin.
 */

import('lib.pkp.tests.WebTestCase');

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

class StaticPagesFunctionalTest extends WebTestCase {
	/**
	 * @copydoc WebTestCase::getAffectedTables
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}

	/**
	 * Enable the plugin
	 */
	function testStaticPages() {
		$this->open(self::$baseUrl);

		$this->logIn('admin', 'admin');
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[contains(text(),"Settings")]'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[contains(text(),"Website")]'))
			->perform();
		$this->click('//button[@id="plugins-button"]');

		// Find and enable the plugin
		$this->waitForElementPresent($selector = '//input[starts-with(@id, \'select-cell-staticpagesplugin-enabled\')]');
		self::$driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::xpath('//a[contains(text(),"Static Pages")]')));
		$this->click($selector); // Enable plugin
		$this->waitForElementPresent('//div[contains(.,\'The plugin "Static Pages Plugin" has been enabled.\')]');

		// Check for a 404 on the page we are about to create
		$this->open(self::$baseUrl . '/index.php/publicknowledge/flarm');
		$this->waitForElementPresent('//h1[contains(text(),"404 Not Found")]');

		// Find the plugin's tab
		$this->open(self::$baseUrl);
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('css=ul#navigationUser>li.profile>a'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->click($this->waitForElementPresent('//ul[@id="navigationUser"]//a[contains(text(),"Dashboard")]'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->moveToElement($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[contains(text(),"Settings")]'))
			->perform();
		$actions = new WebDriverActions(self::$driver);
		$actions->click($this->waitForElementPresent('//ul[@id="navigationPrimary"]//a[contains(text(),"Website")]'))
			->perform();
		$this->click('//button[@id="staticPages-button"]');

		// Create a static page
		$this->click('//a[starts-with(@id, \'component-plugins-generic-staticpages-controllers-grid-staticpagegrid-addStaticPage-button-\')]');
		$this->waitForElementPresent($selector='//form[@id=\'staticPageForm\']//input[starts-with(@id, \'path-\')]');
		$this->type($selector, 'flarm');
		$this->type($selector='//form[@id=\'staticPageForm\']//input[starts-with(@id, \'title-\')]', 'Test Static Page');
		$this->typeTinyMCE('content', 'Here is my new static page.');
		$this->click('//form[@id=\'staticPageForm\']//button[starts-with(@id, \'submitFormButton-\')]');
		self::$driver->wait()->until(WebDriverExpectedCondition::invisibilityOfElementLocated(WebDriverBy::cssSelector('div.pkp_modal_panel')));

		// View the static page
		$this->click('//a[text()=\'flarm\']');
		self::$driver->wait()->until(WebDriverExpectedCondition::numberOfWindowsToBe(2));
		$handles = self::$driver->getWindowHandles();
		self::$driver->switchTo()->window(end($handles));
		$this->waitForElementPresent('//h2[contains(text(),\'Test Static Page\')]');
		$this->waitForElementPresent('//p[contains(text(),\'Here is my new static page.\')]');
		self::$driver->close();
	}
}

