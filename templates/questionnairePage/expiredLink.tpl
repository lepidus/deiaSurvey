{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>
    <p>{translate key="plugins.generic.deiaSurvey.questionnairePage.expiredLink"}</p>
    <form method="post" action="{url op="requestNewLink" authorId=$authorId authorToken=$authorToken}">
        {csrf}
        <button type="submit">{translate key="plugins.generic.deiaSurvey.questionnairePage.requestNewLink"}</button>
    </form>
</div>

{include file="frontend/components/footer.tpl"}
