{capture assign="pageTitle"}
    {translate key="plugins.generic.demographicData.questionnairePage.index.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>{translate key="plugins.generic.demographicData.questionnairePage.alreadyAnswered"}</p>
</div>

{include file="frontend/components/footer.tpl"}