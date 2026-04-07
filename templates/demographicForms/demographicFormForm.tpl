<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#demographicFormForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form 
    class="pkp_form" 
    id="demographicFormForm" 
    method="post" 
    action="{url 
        router=$smarty.const.ROUTE_COMPONENT 
        component="plugins.generic.deiaSurvey.classes.controllers.grid.demographicForm.DemographicFormGridHandler" 
        op="updateDemographicForm"
    }"
>
	{csrf}

	{if $demographicFormId}
		{fbvElement id="demographicFormId" type="hidden" name="demographicFormId" value=$demographicFormId}
	{/if}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="demographicFormsNotification"}

	{fbvFormArea id="demographicFormForm"}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.title" required=true for="title"}
			{fbvElement type="text" id="title" value=$title multilingual=true required=true}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.description" for="description"}
			{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
		{/fbvFormSection}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="demographicFormFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
