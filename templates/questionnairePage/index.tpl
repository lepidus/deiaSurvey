{capture assign="pageTitle"}
    {translate key="plugins.generic.demographicData.questionnairePage.index.title"}
{/capture}

<link rel="stylesheet" type="text/css" href="/plugins/generic/demographicData/styles/questionnairePage.css">
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.demographicData.questionnairePage.externalId.{$externalType}" externalId=$externalId}
    </p>
    
    <form class="pkp_form" id="demographicDataForm" method="post" action="{url op="saveQuestionnaire" authorId=$authorId authorToken=$authorToken}" role="form">
        {csrf}

        <fieldset class="fields">
            {foreach $questions as $question}
                {include file="../../../plugins/generic/demographicData/templates/questionnairePage/question.tpl" question=$question}
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