<script>
	$(function() {ldelim}
		$('#deiaSurveyForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{if $canValidateEmail}
			$('#deiaEmailValidationForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{/if}
	{rdelim});
</script>
<link rel="stylesheet" type="text/css" href="/plugins/generic/deiaSurvey/styles/questionsInProfile.css">

{if $canValidateEmail}
	<form class="pkp_form pkp_notification" id="deiaEmailValidationForm" method="post" action="{url op="requestEmailValidation"}">
		{csrf}
		{capture assign="emailValidationContents"}
			{translate key="plugins.generic.deiaSurvey.emailValidation.description"}
			<button type="submit" class="pkp_button_link">{translate key="plugins.generic.deiaSurvey.emailValidation.send"}</button>
		{/capture}
		{include
			file="controllers/notification/inPlaceNotificationContent.tpl"
			notificationId="deiaEmailValidation"
			notificationStyleClass="notifyWarning"
			notificationTitle=""
			notificationContents=$emailValidationContents
		}
	</form>
{/if}
<h2 id="deiaSurveyTitle">
	{translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
</h2>
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
{assign var="deiaConsentLocked" value=is_null($deiaDataConsent)&&!is_null($userConsentSetting)}
<form class="pkp_form deiaQuestionnaire" id="deiaSurveyForm" method="post" action="{url op="saveDeiaData"}" enctype="multipart/form-data"{if $deiaConsentLocked} data-deia-locked{/if}>
	{csrf}

	{fbvFormSection id="deiaConsentSection" list="false" label='plugins.generic.deiaSurvey.consent' description='plugins.generic.deiaSurvey.consent.description' required=true}
		{if is_null($deiaDataConsent)}
			{assign var=checkedConsentYes value=false}
			{assign var=checkedConsentNo value=false}
		{else}
			{assign var=checkedConsentYes value=$deiaDataConsent}
			{assign var=checkedConsentNo value=!$deiaDataConsent}
		{/if}
		{fbvElement type="radio" id="deiaDataConsentYes" name="deiaDataConsent" disabled=$deiaConsentLocked value=1 checked=$checkedConsentYes required=true label="plugins.generic.deiaSurvey.consent.yes"}
		{fbvElement type="radio" id="deiaDataConsentNo" name="deiaDataConsent" disabled=$deiaConsentLocked value=0 checked=$checkedConsentNo required=true label="plugins.generic.deiaSurvey.consent.no"}
	{/fbvFormSection}

	{include file="../../../plugins/generic/deiaSurvey/templates/questionBlocks.tpl"}

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

	{fbvFormButtons hideCancel=true submitText="common.save"}
</form>
{include file="../../../plugins/generic/deiaSurvey/templates/questionnaireConditions.tpl" conditionFormId="deiaSurveyForm"}

{if $canAssociateOrcid}
    <form method="post" action="{url router=ROUTE_PAGE page="deiaOrcidAssociation" op="index"}">
        {csrf}
        <p>{translate key="plugins.generic.deiaSurvey.orcidAssociation.description"}</p>
        <button type="submit">{translate key="plugins.generic.deiaSurvey.orcidAssociation.request"}</button>
    </form>
{/if}
