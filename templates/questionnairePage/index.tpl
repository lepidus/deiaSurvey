{capture assign="pageTitle"}
    {translate key="plugins.generic.deiaSurvey.questionnairePage.index.title"}
{/capture}

{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page">
    <h1>{$pageTitle|escape}</h1>

    <p>
        {translate key="plugins.generic.deiaSurvey.questionnairePage.externalId.{$authorExternalType}" externalId=$authorExternalId}
    </p>
    
    <form class="pkp_form" id="deiaSurveyForm" method="post" action="{url op="saveQuestionnaire" authorId=$authorId authorToken=$authorToken}" role="form">
        {csrf}

        <fieldset class="fields">
            {foreach $questionBlocks as $questionBlock}
                <section class="questionBlock">
                    <h2>{$questionBlock['title']|escape}</h2>
                    {if $questionBlock['description']}
                        <p>{$questionBlock['description']|strip_unsafe_html}</p>
                    {/if}
                    {foreach $questionBlock['questions'] as $question}
                        {include file="../../../plugins/generic/deiaSurvey/templates/questionnairePage/question.tpl" question=$question}
                    {/foreach}
                </section>
            {/foreach}
        </fieldset>

        <p class="privacyStatement">
            {translate key="plugins.generic.deiaSurvey.questionnairePage.privacyStatement"}
        </p>

        <div class="buttons">
            <button id="submitDeiaQuestionnaire" class="submit" type="submit">
                {translate key="common.save"}
            </button>
        </div>
    </form>
</div>

{include file="frontend/components/footer.tpl"}
