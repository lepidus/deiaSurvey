{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteData.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteData.message"}
    </p>

    <form method="post" action="{url op="deleteData" authorId=$authorId authorToken=$authorToken}">
        {csrf}
        <input type="hidden" name="confirm" value="1">
        <button id="deleteDeiaData" type="submit">
            {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteMyData"}
        </button>
    </form>
</div>

{include file="frontend/components/footer.tpl"}
