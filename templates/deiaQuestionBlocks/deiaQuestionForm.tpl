{literal}
<style>
	#deiaConditionFields:not([hidden]) {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr));
		gap: 0.75rem;
		margin: 0.75rem 0 0;
	}
</style>
{/literal}
<script>
	function toggleResponseOptions(newValue, responseOptionQuestionTypesString, showWarning) {ldelim}
		var form = document.getElementById('deiaQuestionForm'),
			responseOptions = document.getElementById('responseOptions'),
			allowsResponseOptions = responseOptionQuestionTypesString.indexOf(';' + newValue + ';') !== -1;

		if (responseOptions) {ldelim}
			responseOptions.style.display = allowsResponseOptions ? '' : 'none';
		{rdelim}

		if (allowsResponseOptions) {ldelim}
			if (form.addResponse) {ldelim}
				form.addResponse.disabled = false;
			{rdelim}
		{rdelim} else {ldelim}
			if (showWarning && form.addResponse && form.addResponse.disabled === false) {ldelim}
				alert({translate|json_encode key="plugins.generic.deiaSurvey.questionBlocks.questions.changeType"});
			{rdelim}
			if (form.addResponse) {ldelim}
				form.addResponse.disabled = true;
			{rdelim}
		{rdelim}
	{rdelim}

	$(function() {ldelim}
		const form = document.getElementById('deiaQuestionForm');
		$(form).pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		const conditional = document.getElementById('deiaConditionEnabled');
		const fields = document.getElementById('deiaConditionFields');
		const source = document.getElementById('showWhenQuestion');
		const option = document.getElementById('showWhenOption');

		function updateCondition() {ldelim}
			fields.hidden = !conditional.checked;
			fields.disabled = !conditional.checked;
			option.disabled = source.value === '';

			for (const item of option.options) {ldelim}
				const unavailable = item.value !== '' && item.dataset.question !== source.value;
				item.hidden = unavailable;
				item.disabled = unavailable;
			{rdelim}
			if (option.selectedOptions[0]?.disabled) {ldelim}
				option.value = '';
			{rdelim}
		{rdelim}
		if (conditional) {ldelim}
			form.querySelectorAll('[name="questionVisibility"]').forEach((radio) => radio.addEventListener('change', updateCondition));
			source.addEventListener('change', updateCondition);
			updateCondition();
		{rdelim}
		toggleResponseOptions(
			$('#questionType').val(),
			'{$responseOptionQuestionTypesString|escape:"javascript"}',
			false
		);
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

	{fbvFormArea id="deiaQuestionFields"}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.questions.text" required=true for="questionText"}
			{fbvElement type="text" id="questionText" value=$questionText multilingual=true required=true}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.description" for="questionDescription"}
			{fbvElement type="textarea" id="questionDescription" value=$questionDescription multilingual=true rich=false}
		{/fbvFormSection}

		{if $conditionSources}
			{assign var=selectedConditionQuestion value=$showWhenQuestion|default:null}
			{assign var=selectedConditionOption value=$showWhenOption|default:null}
			{fbvFormArea id="deiaQuestionVisibility" title="plugins.generic.deiaSurvey.questionBlocks.questions.visibility"}
				<ul class="checkbox_and_radiobutton">
					<li><label>
						<input type="radio" name="questionVisibility" value="always"{if !$selectedConditionQuestion} checked{/if}>
						{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.noCondition"}
					</label></li>
					<li><label>
						<input type="radio" id="deiaConditionEnabled" name="questionVisibility" value="conditional"{if $selectedConditionQuestion} checked{/if}>
						{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.conditional"}
					</label></li>
				</ul>
				<fieldset id="deiaConditionFields"{if !$selectedConditionQuestion} hidden disabled{/if}>
					<div>
						<label for="showWhenQuestion">{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.conditionQuestion"}</label>
						<select name="showWhenQuestion" id="showWhenQuestion" required>
							<option value="">{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.chooseConditionQuestion"}</option>
							{foreach $conditionSources as $sourceId => $source}
								<option value="{$sourceId|escape}"{if $sourceId == $selectedConditionQuestion} selected{/if}>{$source.text|escape}</option>
							{/foreach}
						</select>
					</div>
					<div>
						<label for="showWhenOption">{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.conditionOption"}</label>
						<select name="showWhenOption" id="showWhenOption" required{if !$selectedConditionQuestion} disabled{/if}>
							<option value="">{translate key="plugins.generic.deiaSurvey.questionBlocks.questions.chooseOption"}</option>
							{foreach $conditionSources as $sourceId => $source}
								{foreach $source.options as $optionId => $optionText}
									<option data-question="{$sourceId|escape}" value="{$optionId|escape}"{if $optionId == $selectedConditionOption} selected{/if}>{$optionText|escape}</option>
								{/foreach}
							{/foreach}
						</select>
					</div>
				</fieldset>
			{/fbvFormArea}
		{/if}

		{fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.questions.responseType" required=true for="questionType"}
			{fbvElement
				type="select"
				id="questionType"
				from=$questionTypeOptions
				selected=$questionType
				translate=true
				defaultLabel=""
				size=$fbvStyles.size.MEDIUM
				required=true
				onchange="toggleResponseOptions(this.options[this.selectedIndex].value, '{$responseOptionQuestionTypesString|escape:"javascript"}', true)"
			}
		{/fbvFormSection}

		<div id="responseOptions" class="section"{if !$questionTypeAllowsResponseOptions} style="display: none;"{/if}>
			<div id="responseOptionsContainer" class="full left">
				{capture assign=responseOptionsUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.deiaSurvey.classes.controllers.listbuilder.deiaQuestion.DeiaQuestionResponseOptionListbuilderHandler" op="fetch" deiaQuestionBlockId=$deiaQuestionBlockId deiaQuestionId=$deiaQuestionId escape=false}{/capture}
				{load_url_in_div id="responseOptionsListbuilderContainer" url=$responseOptionsUrl}
			</div>
		</div>


		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		{fbvFormButtons id="deiaQuestionFormSubmit" submitText="common.save"}
	{/fbvFormArea}
</form>
