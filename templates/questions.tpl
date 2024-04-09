<script>
	$(function() {ldelim}
		$('#demographicDataForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="demographicDataForm" method="post" action="{url op="saveDemographicData"}" enctype="multipart/form-data">
	{csrf}

	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="identityFormNotification"}

    {foreach $questions as $question}
        {fbvFormArea id="demographicQuestion"}
            {fbvFormSection title=$question['title'] translate=false required=true}
				{fbvFormSection for="demographicResponse" description=$question['description'] translate=false}
					{fbvElement type="text" multilingual="true" name="response" id="response" value=$question['response'] size=$fbvStyles.size.LARGE}
				{/fbvFormSection}
            {/fbvFormSection}
        {/fbvFormArea}
    {/foreach}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>
