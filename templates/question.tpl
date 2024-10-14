{assign var="questionId" value="question-{$question['questionId']}-{$question['inputType']}"}
{if $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES'] or $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
    {assign var="isListSection" value=true}
{/if}

{fbvFormSection title=$question['title'] required=true translate=false}
    {fbvFormSection for=$questionId description=$question['description'] translate=false list=$isListSection}
        {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="demographicResponses" value=$question['response'] required=true size=$fbvStyles.size.SMALL}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="demographicResponses" value=$question['response'] required=true size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
            {fbvElement type="textarea" multilingual="true" name=$questionId id="demographicResponses" value=$question['response'] required=true rich=false size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
            {foreach from=$question['responseOptions'] item="responseOption"}
                {fbvElement
                    type="checkbox"
                    name="{$questionId}[]"
                    id="demographicResponses"
                    label=$responseOption->getLocalizedOptionText()
                    value=$responseOption->getId()
                    checked=in_array($responseOption->getId(), $question['response'])
                    translate=false
                }
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
            {foreach from=$question['responseOptions'] item="responseOption"}
                {fbvElement
                    type="radio"
                    name="{$questionId}[]"
                    id="demographicResponses"
                    label=$responseOption->getLocalizedOptionText()
                    value=$responseOption->getId()
                    checked=in_array($responseOption->getId(), $question['response'])
                    required=true
                    translate=false
                }
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
            {fbvElement type="select" name=$questionId id="demographicResponses" from=$question['responseOptions'] selected=$question['response'] translate=false required=true size=$fbvStyles.size.LARGE}
        {/if}
    {/fbvFormSection}
{/fbvFormSection}