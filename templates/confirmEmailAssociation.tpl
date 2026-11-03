{include file="frontend/components/header.tpl"}

<div class="page">
    {include file="frontend/components/breadcrumbs.tpl" currentTitleKey=$pageTitle}
    <div class="page deia_email_association">
        <p>{translate key="plugins.generic.deiaSurvey.emailValidation.confirmDescription"}</p>
        <form method="post" action="{url page="deiaAssociation" op="index"}">
            {csrf}
            <input type="hidden" name="userId" value="{$associationUserId|escape}">
            <input type="hidden" name="token" value="{$associationToken|escape}">
            <button type="submit" class="button pkp_button">
                {translate key="plugins.generic.deiaSurvey.emailValidation.confirm"}
            </button>
        </form>
    </div>
</div>

{include file="frontend/components/footer.tpl"}
