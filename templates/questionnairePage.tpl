{capture assign="pageTitle"}
    {translate key="plugins.generic.demographicData.demographicQuestionnaire"}
{/capture}

<link rel="stylesheet" type="text/css" href="/plugins/generic/demographicData/styles/questionnairePage.css">
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>
    
    <form class="pkp_form" id="demographicDataForm" method="post" action="noneYet" enctype="multipart/form-data">
        {csrf}

        <fieldset class="fields">
            {foreach $questions as $question}
                <div class="question">
                    {assign var="questionId" value="question-{$question['questionId']}"}
                    <span class="questionTitle">{$question['title']}</span>
                    <span class="questionDescription">{$question['description']}</span>
                    
                    <input type="text" id="responses" class="questionInput" name="{$questionId}" value="" required aria-required="true">
                </div>
            {/foreach}
        </fieldset>

        <div class="buttons">
            <button id="submitDemographicQuestionnaire" class="submit" type="submit">
                {translate key="common.save"}
            </button>
        </div>
    </form>
</div>

{include file="frontend/components/footer.tpl"}