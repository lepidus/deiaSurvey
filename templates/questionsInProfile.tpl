<script>
	$(function() {ldelim}
		$('#deiaSurveyForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<link rel="stylesheet" type="text/css" href="/plugins/generic/deiaSurvey/styles/questionsInProfile.css">

<h3 id="deiaSurveyTitle">
	{translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
</h3>
{if is_null($deiaDataConsent) && $userConsentSetting}
	<div id="deiaAlreadyAnsweredMessage" class="pkp_notification">
		{capture assign="content"}
			{translate key="plugins.generic.deiaSurvey.alreadyAnswered.{$applicationName}" contextName=$userConsentSetting[0]['contextName']}
		{/capture}
		{include
			file="controllers/notification/inPlaceNotificationContent.tpl"
			notificationId="deiaAlreadyAnsweredMessage-"|uniqid
			notificationStyleClass="notifySuccess"
			notificationContents=$content
		}
	</div>
{/if}
<form class="pkp_form" id="deiaSurveyForm" method="post" action="{url op="saveDeiaData"}" enctype="multipart/form-data">
	{csrf}

	{fbvFormSection list="false" label='plugins.generic.deiaSurvey.consent' description='plugins.generic.deiaSurvey.consent.description' required=true}
		{if is_null($deiaDataConsent)}
			{assign var=checkedConsentYes value=false}
			{assign var=checkedConsentNo value=false}
		{else}
			{assign var=checkedConsentYes value=$deiaDataConsent}
			{assign var=checkedConsentNo value=!$deiaDataConsent}
		{/if}
		{fbvElement type="radio" id="deiaDataConsentYes" name="deiaDataConsent" value=1 checked=$checkedConsentYes required=true label="plugins.generic.deiaSurvey.consent.yes"}
		{fbvElement type="radio" id="deiaDataConsentNo" name="deiaDataConsent" value=0 checked=$checkedConsentNo required=true label="plugins.generic.deiaSurvey.consent.no"}
	{/fbvFormSection}

	{foreach $questionBlocks as $questionBlock}
		{fbvFormArea id="questionBlock_"|concat:$questionBlock['id'] title=$questionBlock['title'] translate=false}
			{fbvFormSection	description=$questionBlock['description'] translate=false}
				{foreach $questionBlock['questions'] as $question}
					{include file="../../../plugins/generic/deiaSurvey/templates/question.tpl" question=$question}
				{/foreach}
			{/fbvFormSection}
		{/fbvFormArea}
	{/foreach}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>

<script>
	function setRequirementOnQuestions(required){ldelim}
		let questions = document.querySelectorAll('[id^="deiaResponses"]');
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
		let deiaDataConsentYes = document.getElementById('deiaDataConsentYes');
		let deiaDataConsentNo = document.getElementById('deiaDataConsentNo');

		{if !is_null($deiaDataConsent) || is_null($userConsentSetting)}
			deiaDataConsentYes.addEventListener('change', (event) => {ldelim}
				if (event.currentTarget.checked) {ldelim}
					setRequirementOnQuestions(true);
				{rdelim}
			{rdelim});

			deiaDataConsentNo.addEventListener('change', (event) => {ldelim}
				if (event.currentTarget.checked) {ldelim}
					setRequirementOnQuestions(false);
				{rdelim}
			{rdelim});

			if (deiaDataConsentNo.checked) {ldelim}
				setRequirementOnQuestions(false);
			{rdelim}
		{else}
			setRequirementOnQuestions(false);
			deiaDataConsentYes.disabled = true;
			deiaDataConsentNo.disabled = true;
		{/if}
	{rdelim});
</script>
