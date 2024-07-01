{capture assign="pageTitle"}
    {translate key="plugins.generic.demographicData.demographicQuestionnaire"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>
    {* questions will be here *}
</div>

{include file="frontend/components/footer.tpl"}