{assign var="questionId" value="question-{$question['questionId']}-{$question['inputType']}"}
{if $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES'] or $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
    {assign var="isListSection" value=true}
{/if}

{fbvFormSection title=$question['title'] required=true translate=false}
    {fbvFormSection for=$questionId description=$question['description'] translate=false list=$isListSection}
        {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] required=true size=$fbvStyles.size.SMALL}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="responses" value=$question['response'] required=true size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
            {fbvElement type="textarea" multilingual="true" name=$questionId id="responses" value=$question['response'] required=true rich=false size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
            {foreach from=$question['possibleResponses'] key="possibleResponseValue" item="possibleResponseLabel"}
                {fbvElement
                    type="checkbox"
                    name="{$questionId}[]"
                    id="responses"
                    label=$possibleResponseLabel
                    value=$possibleResponseValue
                    checked=in_array($possibleResponseValue, $question['response'])
                    required=true
                    translate=false
                }
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
            {foreach from=$question['possibleResponses'] key="possibleResponseValue" item="possibleResponseLabel"}
                {fbvElement
                    type="radio"
                    name="{$questionId}[]"
                    id="responses"
                    label=$possibleResponseLabel
                    value=$possibleResponseValue
                    checked=in_array($possibleResponseValue, $question['response'])
                    required=true
                    translate=false
                }
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
            {fbvElement type="select" name=$questionId id="responses" from=$question['possibleResponses'] selected=$question['response'] translate=false required=true size=$fbvStyles.size.LARGE}
        {/if}
    {/fbvFormSection}
{/fbvFormSection}