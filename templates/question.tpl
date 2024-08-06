{assign var="questionId" value="question-{$question['questionId']}"}

{fbvFormSection title=$question['title'] translate=false}
    {fbvFormSection description=$question['description'] translate=false}
        {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.SMALL}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.LARGE}
        {/if}
    {/fbvFormSection}
{/fbvFormSection}