{foreach $questionBlocks as $questionBlock}
	<div class="deiaQuestionBlockSeparator"></div>
	{fbvFormArea id="questionBlock_"|concat:$questionBlock['id'] class="deiaQuestionBlock" title=$questionBlock['title'] translate=false}
		{fbvFormSection description=$questionBlock['description'] translate=false}
			{foreach $questionBlock['questions'] as $question}
				{include
					file="../../../plugins/generic/deiaSurvey/templates/question.tpl"
					question=$question
					questionTypeConsts=$questionTypeConsts
					formLocales=$formLocales
					formLocale=$formLocale
				}
			{/foreach}
		{/fbvFormSection}
	{/fbvFormArea}
{/foreach}
