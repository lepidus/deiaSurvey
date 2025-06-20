<script>
	$(function() {ldelim}
		$('#demographicDataForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="demographicDataForm" method="post" action="{url op="saveDemographicData"}" enctype="multipart/form-data">
	{csrf}

	{fbvFormSection list="false" label='plugins.generic.demographicData.consent' description='plugins.generic.demographicData.consent.description' required=true}
		{if is_null($demographicDataConsent)}
			{assign var=checkedConsentYes value=false}
			{assign var=checkedConsentNo value=false}
		{else}
			{assign var=checkedConsentYes value=$demographicDataConsent}
			{assign var=checkedConsentNo value=!$demographicDataConsent}
		{/if}
		{fbvElement type="radio" id="demographicDataConsentYes" name="demographicDataConsent" value=1 checked=$checkedConsentYes required=true label="plugins.generic.demographicData.consent.yes"}
		{fbvElement type="radio" id="demographicDataConsentNo" name="demographicDataConsent" value=0 checked=$checkedConsentNo required=true label="plugins.generic.demographicData.consent.no"}
	{/fbvFormSection}

	{fbvFormArea id="demographicQuestion"}
		<div id="Hello" name="questions">
			{foreach $questions as $question}
				{include file="../../../plugins/generic/deiaSurvey/templates/question.tpl" question=$question}
			{/foreach}
		</div>
    {/fbvFormArea}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>

<script>
	function setRequirementOnQuestions(required){ldelim}
		let questions = document.querySelectorAll('[id^="demographicResponses"]');
		let reqSymbols = document.querySelectorAll('span.req');
		let reqErrorMessages = document.querySelectorAll('label.error');

		for (let question of questions) {ldelim}
			question.required = required;
			question.disabled = !required;
		{rdelim}

		for (let reqSymbol of reqSymbols) {ldelim}
			let reqSymbolParent = reqSymbol.parentNode;

			if (!reqSymbolParent.textContent.includes('{translate key="plugins.generic.demographicData.consent"}')) {ldelim}
				reqSymbol.style.display = (required ? 'inline' : 'none');
			{rdelim}
		{rdelim}

		for (let reqErrorMessage of reqErrorMessages) {ldelim}
			reqErrorMessage.style.display = 'none';
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

		if (demographicDataConsentNo.checked) {ldelim}
			setRequirementOnQuestions(false);
		{rdelim}
	{rdelim});
</script>
