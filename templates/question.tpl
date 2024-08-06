{assign var="questionId" value="question-{$question['questionId']}"}
{if $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES'] or $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
    {assign var="isListSection" value=true}
{/if}

{fbvFormSection title=$question['title'] translate=false}
{fbvFormSection description=$question['description'] translate=false list=$isListSection}
        {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.SMALL}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
            {fbvElement type="textarea" multilingual="true" name=$questionId id="responses" value=$question['response'] rich=false size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
            {foreach from=$question['possibleResponses'] item="possibleResponse"}
                {fbvElement type="checkbox" name=$questionId id="responses" label=$possibleResponse value=$possibleResponse checked=$possibleResponse|compare:$question['response'] translate=false}
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
            {foreach from=$question['possibleResponses'] item="possibleResponse"}
                {fbvElement type="radio" name=$questionId id="responses" label=$possibleResponse value=$possibleResponse checked=$possibleResponse|compare:$question['response'] translate=false}
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
            {fbvElement type="select" name=$questionId id="responses" from=$question['possibleResponses'] checked=$question['response'] translate=false size=$fbvStyles.size.LARGE}
        {/if}
    {/fbvFormSection}
{/fbvFormSection}