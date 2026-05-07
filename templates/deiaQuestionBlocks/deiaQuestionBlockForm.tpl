<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#deiaQuestionBlockForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form 
    class="pkp_form" 
    id="deiaQuestionBlockForm"
    method="post" 
    action="{url 
        router=$smarty.const.ROUTE_COMPONENT 
        component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler"
        op="updateDeiaQuestionBlock"
    }"
>
	{csrf}

	{if $deiaQuestionBlockId}
		{fbvElement id="deiaQuestionBlockId" type="hidden" name="deiaQuestionBlockId" value=$deiaQuestionBlockId}
	{/if}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="deiaQuestionBlocksNotification"}

	{fbvFormArea id="deiaQuestionBlockForm"}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.title" required=true for="title"}
			{fbvElement type="text" id="title" value=$title multilingual=true required=true}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.description" for="description"}
			{fbvElement type="textarea" id="description" value=$description multilingual=true rich=false}
		{/fbvFormSection}
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="deiaQuestionBlockFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
