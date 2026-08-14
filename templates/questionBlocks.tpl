{foreach $questionBlocks as $questionBlock}
	{fbvFormArea id="questionBlock_"|concat:$questionBlock['id'] title=$questionBlock['title'] translate=false}
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
