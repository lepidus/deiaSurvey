<form class="pkp_form" id="deiaQuestionBlockPreview" method="post" action="#">
	<h3>{$questionBlock['title']|escape}</h3>
	<p>{$questionBlock['description']|escape}</p>

	{foreach $questionBlock['questions'] as $question}
		{include file="../../../plugins/generic/deiaSurvey/templates/question.tpl" question=$question}
	{/foreach}
</form>
