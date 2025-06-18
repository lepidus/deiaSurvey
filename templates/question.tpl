{assign var="questionId" value="question-{$question['questionId']}-{$question['inputType']}"}
{if $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES'] or $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
    {assign var="isListSection" value=true}
{/if}

{fbvFormSection title=$question['title'] required=true translate=false}
    {fbvFormSection for=$questionId description=$question['description'] translate=false list=$isListSection}
        {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="demographicResponses" value=$question['response']['value'] required=true size=$fbvStyles.size.SMALL}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
            {fbvElement type="text" multilingual="true" name=$questionId id="demographicResponses" value=$question['response']['value'] required=true size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
            {fbvElement type="textarea" multilingual="true" name=$questionId id="demographicResponses" value=$question['response']['value'] required=true rich=false size=$fbvStyles.size.LARGE}
        {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
            {foreach from=$question['responseOptions'] item="responseOption"}
                <div class="responseOption">
                    {fbvElement
                        type="checkbox"
                        name="{$questionId}[]"
                        id="demographicResponses"
                        label=$responseOption->getLocalizedOptionText()
                        value=$responseOption->getId()
                        checked=in_array($responseOption->getId(), $question['response']['value'])
                        translate=false
                    }

                    {if $responseOption->hasInputField()}
                        {assign var="optionInputName" value="responseOptionInput-{$responseOption->getId()}"}
                        {if isset($question['response']['optionsInputValue'][$responseOption->getId()])}
                            {assign var="optionInputValue" value=$question['response']['optionsInputValue'][$responseOption->getId()]}
                        {else}
                            {assign var="optionInputValue" value=""}
                        {/if}
                        
                        {fbvElement type="text" name=$optionInputName id="responseOptionsInputs" value=$optionInputValue size=$fbvStyles.size.MEDIUM}
                    {/if}
                </div>
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
            {foreach from=$question['responseOptions'] item="responseOption"}
                <div class="responseOption">
                    {fbvElement
                        type="radio"
                        name="{$questionId}[]"
                        id="demographicResponses"
                        label=$responseOption->getLocalizedOptionText()
                        value=$responseOption->getId()
                        checked=in_array($responseOption->getId(), $question['response']['value'])
                        required=true
                        translate=false
                    }

                    {if $responseOption->hasInputField()}
                        {assign var="optionInputName" value="responseOptionInput-{$responseOption->getId()}"}
                        {if isset($question['response']['optionsInputValue'][$responseOption->getId()])}
                            {assign var="optionInputValue" value=$question['response']['optionsInputValue'][$responseOption->getId()]}
                        {else}
                            {assign var="optionInputValue" value=""}
                        {/if}
                        
                        {fbvElement type="text" name=$optionInputName id="responseOptionsInputs" value=$optionInputValue size=$fbvStyles.size.MEDIUM}
                    {/if}
                </div>
            {/foreach}
        {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
            {fbvElement type="select" name=$questionId id="demographicResponses" from=$question['responseOptions'] selected=$question['response']['value'] translate=false required=true size=$fbvStyles.size.LARGE}
        {/if}
    {/fbvFormSection}
{/fbvFormSection}