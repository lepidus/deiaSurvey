<div class="question">
    {assign var="questionId" value="question-{$question['id']}-{$question['inputType']}"}
    <span class="questionTitle">{$question['title']|escape}</span>
    <span class="questionDescription">{$question['description']|escape}</span>

    {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
        <input type="text" id="deiaResponses" class="questionSmallText" name="{$questionId|escape}" value="" required aria-required="true">
    {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
        <input type="text" id="deiaResponses" class="questionText" name="{$questionId|escape}" value="" required aria-required="true">
    {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
        <textarea id="deiaResponses" class="questionTextArea" name="{$questionId|escape}" required aria-required="true"></textarea>
    {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
        {foreach from=$question['responseOptions'] item="responseOption"}
            <div id="responseOption-{$responseOption->getId()|escape}">
                <label class="questionCheckbox">
                    <input type="checkbox" id="deiaResponses" name="{$questionId|escape}[]" value="{$responseOption->getId()|escape}">
                    {$responseOption->getLocalizedOptionText()|escape}
                </label>
                {if $responseOption->hasInputField()}
                    {assign var="optionInputName" value="responseOptionInput-{$responseOption->getId()}"}
                    <input type="text" id="responseOptionsInputs" class="questionText" name="{$optionInputName|escape}" value="">
                {/if}
                <br>
            </div>
        {/foreach}
    {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
        {foreach from=$question['responseOptions'] item="responseOption"}
            <div id="responseOption-{$responseOption->getId()|escape}">
                <label class="questionRadio">
                    <input type="radio" id="deiaResponses" name="{$questionId|escape}[]" value="{$responseOption->getId()|escape}">
                    {$responseOption->getLocalizedOptionText()|escape}
                </label>
                {if $responseOption->hasInputField()}
                    {assign var="optionInputName" value="responseOptionInput-{$responseOption->getId()}"}
                    <input type="text" id="responseOptionsInputs" class="questionText" name="{$optionInputName|escape}" value="">
                {/if}
                <br>
            </div>
        {/foreach}
    {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
        <select id="deiaResponses" class="questionSelect" name="{$questionId|escape}">
            {foreach from=$question['responseOptions'] key="responseOptionValue" item="responseOptionLabel"}
                <option value="{$responseOptionValue|escape}">{$responseOptionLabel|escape}</option>
            {/foreach}
        </select>
    {/if}
</div>
