<script>
	$(function() {ldelim}
		$('#deiaSurveyForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<link rel="stylesheet" type="text/css" href="/plugins/generic/deiaSurvey/styles/questionsInProfile.css">

<h3 id="deiaSurveyTitle">
	{translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
</h3>
{if is_null($demographicDataConsent) && $userConsentSetting}
	<div id="demographicAlreadyAnsweredMessage" class="pkp_notification">
		{capture assign="content"}
			{translate key="plugins.generic.deiaSurvey.alreadyAnswered.{$applicationName}" contextName=$userConsentSetting[0]['contextName']}
		{/capture}
		{include
			file="controllers/notification/inPlaceNotificationContent.tpl"
			notificationId="demographicAlreadyAnsweredMessage-"|uniqid
			notificationStyleClass="notifySuccess"
			notificationContents=$content
		}
	</div>
{/if}
<form class="pkp_form" id="deiaSurveyForm" method="post" action="{url op="saveDemographicData"}" enctype="multipart/form-data">
	{csrf}

	{fbvFormSection list="false" label='plugins.generic.deiaSurvey.consent' description='plugins.generic.deiaSurvey.consent.description' required=true}
		{if is_null($demographicDataConsent)}
			{assign var=checkedConsentYes value=false}
			{assign var=checkedConsentNo value=false}
		{else}
			{assign var=checkedConsentYes value=$demographicDataConsent}
			{assign var=checkedConsentNo value=!$demographicDataConsent}
		{/if}
		{fbvElement type="radio" id="demographicDataConsentYes" name="demographicDataConsent" value=1 checked=$checkedConsentYes required=true label="plugins.generic.deiaSurvey.consent.yes"}
		{fbvElement type="radio" id="demographicDataConsentNo" name="demographicDataConsent" value=0 checked=$checkedConsentNo required=true label="plugins.generic.deiaSurvey.consent.no"}
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

			if (!reqSymbolParent.textContent.includes('{translate key="plugins.generic.deiaSurvey.consent"}')) {ldelim}
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
