<script>
	function toggleResponseOptions(newValue, responseOptionQuestionTypesString) {ldelim}
		if (responseOptionQuestionTypesString.indexOf(';' + newValue + ';') !== -1) {ldelim}
			document.getElementById('deiaQuestionForm').addResponse.disabled = false;
		{rdelim} else {ldelim}
			if (document.getElementById('deiaQuestionForm').addResponse.disabled === false) {ldelim}
				alert({translate|json_encode key="plugins.generic.deiaSurvey.questionBlocks.questions.changeType"});
			{rdelim}
			document.getElementById('deiaQuestionForm').addResponse.disabled = true;
		{rdelim}
	{rdelim}

	$(function() {ldelim}
		$('#deiaQuestionForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form
    class="pkp_form"
    id="deiaQuestionForm"
    method="post"
    action="{url
        router=$smarty.const.ROUTE_COMPONENT
        component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestion.DeiaQuestionGridHandler"
        op="updateDeiaQuestion"
        anchor="responseOptions"
        deiaQuestionBlockId=$deiaQuestionBlockId
    }"
>
    {csrf}

    {fbvElement id="deiaQuestionBlockId" type="hidden" name="deiaQuestionBlockId" value=$deiaQuestionBlockId}
    {if $deiaQuestionId}
        {fbvElement id="deiaQuestionId" type="hidden" name="deiaQuestionId" value=$deiaQuestionId}
    {/if}

    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deiaQuestionsNotification"}

    {fbvFormArea id="deiaQuestionForm"}
        {fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.questions.text" required=true for="questionText"}
            {fbvElement type="text" id="questionText" value=$questionText multilingual=true required=true}
        {/fbvFormSection}
        {fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.description" for="questionDescription"}
            {fbvElement type="textarea" id="questionDescription" value=$questionDescription multilingual=true rich=false}
        {/fbvFormSection}
        {fbvFormSection
            title="common.type"
            required=true
            for="questionType"
            list=true
        }
            {fbvElement
                type="select"
                label="common.type"
                id="questionType"
                from=$questionTypeOptions
                selected=$questionType
                translate=true
                defaultLabel=""
                size=$fbvStyles.size.MEDIUM
                required=true
                onchange="toggleResponseOptions(this.options[this.selectedIndex].value, '{$responseOptionQuestionTypesString|escape:"javascript"}')"
            }
        {/fbvFormSection}

        <div id="responseOptions" class="full left">
            <div id="responseOptionsContainer" class="full left">
                {capture assign=responseOptionsUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.DeiaQuestionResponseOptionListbuilderHandler" op="fetch" deiaQuestionBlockId=$deiaQuestionBlockId deiaQuestionId=$deiaQuestionId escape=false}{/capture}
                {load_url_in_div id="responseOptionsListbuilderContainer" url=$responseOptionsUrl}
            </div>
        </div>

        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
        {fbvFormButtons id="deiaQuestionFormSubmit" submitText="common.save"}
    {/fbvFormArea}
</form>
