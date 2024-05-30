<script>
	$(function() {ldelim}
		$('#demographicDataForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="demographicDataForm" method="post" action="{url op="saveDemographicData"}" enctype="multipart/form-data">
	{csrf}

	{fbvFormSection list="false" label='plugins.generic.demographicData.consent'}
		{fbvElement type="radio" id="demographicDataConsentYes" name="demographicDataConsent" value=1 checked=$demographicDataConsent label="plugins.generic.demographicData.consent.yes"}
		{fbvElement type="radio" id="demographicDataConsentNo" name="demographicDataConsent" value=0 checked=!$demographicDataConsent label="plugins.generic.demographicData.consent.no"}
	{/fbvFormSection}

	{fbvFormArea id="demographicQuestion"}
		<div id="Hello" name="questions">
			{foreach $questions as $question}
				{assign var="questionId" value="question-{$question['questionId']}"}
				{fbvFormSection title=$question['title'] translate=false}
					{fbvFormSection for="demographicResponse" description=$question['description'] translate=false}
						{fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.LARGE}
					{/fbvFormSection}
				{/fbvFormSection}
			{/foreach}
		</div>
    {/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>

<script>
	function setRequirementOnQuestions(required){ldelim}
		let questions = document.querySelectorAll('[id^="responses-{$currentLocale}"]');

		for(let question of questions) {ldelim}
			question.required = required;
		{rdelim}
	{rdelim}
	
	$(document).ready(function () {ldelim}
		let demographicDataConsentYes = document.getElementById('demographicDataConsentYes');
		let demographicDataConsentNo = document.getElementById('demographicDataConsentNo');

		demographicDataConsentYes.addEventListener('change', (event) => {ldelim}
			if (event.currentTarget.checked) {ldelim}
				setRequirementOnQuestions(true);
			{rdelim}
		{rdelim});

		demographicDataConsentNo.addEventListener('change', (event) => {ldelim}
			if (event.currentTarget.checked) {ldelim}
				setRequirementOnQuestions(false);
			{rdelim}
		{rdelim});
	{rdelim});
</script>
