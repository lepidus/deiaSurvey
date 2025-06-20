{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.saveSuccess.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>{translate key="plugins.generic.deiaSurvey.questionnairePage.saveSuccess.message"}</p>

    <a href="{url op="index" authorId=$authorId authorToken=$authorToken}">
        {translate key="plugins.generic.deiaSurvey.questionnairePage.saveSuccess.checkAnswers"}
    </a>
</div>

{include file="frontend/components/footer.tpl"}