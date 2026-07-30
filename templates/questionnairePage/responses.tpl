{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.responses.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.deiaSurvey.questionnairePage.responsesFor.{$authorExternalType}" externalId=$authorExternalId|escape}
    </p>

    <fieldset class="fields">
        {foreach $questions as $question}
            <div class="authorResponse">
                <span class="questionTitle">{$question['title']|escape}</span>
                <span class="responseValue">{$responses[$question['questionId']]|escape}</span>
            </div>
        {/foreach}
    </fieldset>

    <p>{translate key="plugins.generic.deiaSurvey.questionnairePage.checkAnswersAnytime"}</p>
    <p>
        {translate key="plugins.generic.deiaSurvey.questionnairePage.dataMigration.{$authorExternalType}" externalId=$authorExternalId|escape}
    </p>

    <a id="deleteDeiaData" href="{url op="deleteData" authorId=$authorId authorToken=$authorToken}">
        {translate key="plugins.generic.deiaSurvey.questionnairePage.deleteMyData"}
    </a>
</div>

{include file="frontend/components/footer.tpl"}
