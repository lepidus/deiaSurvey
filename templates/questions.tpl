<script>
	$(function() {ldelim}
		$('#demographicDataForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="demographicDataForm" method="post" action="{url op="saveDemographicData"}" enctype="multipart/form-data">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="identityFormNotification"}

	{fbvFormArea id="demographicQuestion"}
		<div id="Hello" name="questions">
			{foreach $questions as $question}
				{assign var="questionId" value="question-{$question['questionId']}"}
				{fbvFormSection title=$question['title'] translate=false}
					{fbvFormSection for="demographicResponse" description=$question['description'] translate=false}
						{fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.LARGE required=true}
					{/fbvFormSection}
				{/fbvFormSection}
			{/foreach}
		</div>
    {/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>
