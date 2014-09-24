{**
 * plugins/generic/staticPages/editStaticPageForm.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a static page
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#staticPageForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{url|assign:actionUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.staticPages.controllers.grid.StaticPageGridHandler" op="updateStaticPage" existingPageName=$blockName escape=false}
<form class="pkp_form" id="staticPageForm" method="post" action="{$actionUrl}">
	{if $staticPageId}
		<input type="hidden" name="staticPageId" value="{$staticPageId|escape}" />
	{/if}
	{fbvFormArea id="staticPagesFormArea" class="border"}
		{fbvFormSection}
			{fbvElement type="text" label="plugins.generic.staticPages.path" id="path" value=$path maxlength="40" inline=true size=$fbvStyles.size.MEDIUM}
			{fbvElement type="text" label="plugins.generic.staticPages.pageTitle" id="title" value=$title maxlength="40" inline=true multilingual=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection}
			{url|replace:"REPLACEME":"%PATH%"|assign:"exampleUrl" router=$smarty.const.ROUTE_PAGE path="page" op="view" path="REPLACEME"}
			{translate key="plugins.generic.staticPages.viewInstructions" pagesPath=$exampleUrl}
		{/fbvFormSection}
		{fbvFormSection label="plugins.generic.staticPages.content" for="content"}
			{fbvElement type="textarea" multilingual=true name="content" id="content" value=$content rich=true height=$fbvStyles.height.TALL}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons submitText="common.save"}
</form>
