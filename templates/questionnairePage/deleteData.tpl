{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteData.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteData.message"}
    </p>

    <a id="deleteDemographicData" href="{url op="deleteData" authorId=$authorId authorToken=$authorToken save=true}">
        {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteMyData"}
    </a>
</div>

{include file="frontend/components/footer.tpl"}