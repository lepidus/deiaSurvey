<link rel="stylesheet" type="text/css" href="/plugins/generic/deiaSurvey/styles/questionsInProfile.css">

<form class="pkp_form" id="deiaQuestionBlockPreview" method="post" action="#">
	<h3>{$questionBlock['title']|escape}</h3>
	<p class="questionBlockDescription">{$questionBlock['description']|escape}</p>

	{foreach $questionBlock['questions'] as $question}
		{include file="../../../plugins/generic/deiaSurvey/templates/question.tpl" question=$question}
	{/foreach}
</form>
