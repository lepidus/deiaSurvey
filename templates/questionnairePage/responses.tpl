{capture assign="pageTitle"}
    {translate key="plugins.generic.demographicData.questionnairePage.index.title"}
{/capture}

<link rel="stylesheet" type="text/css" href="/plugins/generic/demographicData/styles/questionnairePage.css">
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.demographicData.questionnairePage.responsesFor.{$authorExternalType}" externalId=$authorExternalId}
    </p>
    
</div>

{include file="frontend/components/footer.tpl"}