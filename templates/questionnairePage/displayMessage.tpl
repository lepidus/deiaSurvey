{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>{$messageToDisplay}</p>
</div>

{include file="frontend/components/footer.tpl"}