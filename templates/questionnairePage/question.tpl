<div class="question">
    {assign var="questionId" value="question-{$question['questionId']}"}
    <span class="questionTitle">{$question['title']}</span>
    <span class="questionDescription">{$question['description']}</span>

    {if $question['type'] == $questionTypeConsts['TYPE_SMALL_TEXT_FIELD']}
        <input type="text" id="responses" class="questionSmallText" name="{$questionId}" value="" required aria-required="true">
    {elseif $question['type'] == $questionTypeConsts['TYPE_TEXT_FIELD']}
        <input type="text" id="responses" class="questionText" name="{$questionId}" value="" required aria-required="true">
    {elseif $question['type'] == $questionTypeConsts['TYPE_TEXTAREA']}
        <textarea id="responses" class="questionTextArea" name="{$questionId}" value="" required aria-required="true"></textarea>
    {elseif $question['type'] == $questionTypeConsts['TYPE_CHECKBOXES']}
        {foreach from=$question['possibleResponses'] item="possibleResponse"}
            <label class="questionCheckbox">
                <input type="checkbox" id="responses" name="{$questionId}" value="{$possibleResponse}">
                {$possibleResponse}
            </label><br>
        {/foreach}
    {elseif $question['type'] == $questionTypeConsts['TYPE_RADIO_BUTTONS']}
        {foreach from=$question['possibleResponses'] item="possibleResponse"}
            <label class="questionRadio">
                <input type="radio" id="responses" name="{$questionId}" value="{$possibleResponse}">
                {$possibleResponse}
            </label><br>
        {/foreach}
    {elseif $question['type'] == $questionTypeConsts['TYPE_DROP_DOWN_BOX']}
        <select id="responses" class="questionSelect" name="{$questionId}">
            {foreach from=$question['possibleResponses'] item="possibleResponse"}
                <option value="{$possibleResponse}">{$possibleResponse}</option>
            {/foreach}
        </select>
    {/if}
</div>