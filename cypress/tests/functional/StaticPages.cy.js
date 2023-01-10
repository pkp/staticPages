/**
 * @file cypress/tests/functional/StaticPages.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Static Pages plugin tests', function() {
	it('Creates and exercises a static page', function() {
		cy.login('admin', 'admin', 'publicknowledge');

		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="plugins-button"]').click();

		// Find and enable the plugin
		cy.get('input[id^="select-cell-staticpagesplugin-enabled"]').click();
		cy.get('div:contains(\'The plugin "Static Pages Plugin" has been enabled.\')');

		// Check for a 404 on the page we are about to create
		cy.visit('/index.php/publicknowledge/flarm', {failOnStatusCode: false});
		cy.get('h1:contains("404 Not Found")');

		// Find the plugin's tab
		cy.visit('');
		cy.get('a:contains("admin")').click();
		cy.get('ul[id="navigationUser"] a:contains("Dashboard")').click();
		cy.get('.app__nav a').contains('Website').click();
		cy.get('button[id="staticPages-button"]').click();

		// Create a static page
		cy.get('a[id^="component-plugins-generic-staticpages-controllers-grid-staticpagegrid-addStaticPage-button-"]').click();
		cy.get('form[id="staticPageForm"] input[id^="path-"]').type('flarm');
		cy.get('form[id^="staticPageForm"] input[id^="title-en_US-"]').type('Test Static Page');
		cy.get('textarea[id^="content-en_US-"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), 'Here is my new static page.');
		});
		cy.get('form[id="staticPageForm"] button[id^="submitFormButton-"]').click();
		cy.waitJQuery();

		// View the static page
		cy.visit('/index.php/publicknowledge/flarm');
		cy.get('h2:contains("Test Static Page")');
		cy.get('p:contains("Here is my new static page.")');
	});
})
