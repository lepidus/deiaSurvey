{**
 * templates/settingsForm.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Demographic Data plugin settings
 *
*}

<script>
    $(function() {ldelim}
        // Attach the form handler.
        $('#deiaSurveySettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<link rel="stylesheet" type="text/css" href="/plugins/generic/deiaSurvey/styles/settingsForm.css">
<form class="pkp_form" id="deiaSurveySettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    <div id="deiaSurveySettings">
        {if $orcidConfiguration}
            <p class="pkpNotification pkpNotification--success">
                {if $orcidConfiguration['pluginName'] == 'orcidprofileplugin'}
                    {translate key="plugins.generic.deiaSurvey.settings.credentialsUsed.orcidProfile"}
                {else}
                    {translate key="plugins.generic.deiaSurvey.settings.credentialsUsed.own"}
                {/if}
            </p>
        {/if}

        <p id="description">
            {translate key="plugins.generic.deiaSurvey.settings.description" }
        </p>

        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="orcidProfileSettingsFormNotification"}
        {fbvFormArea id="orcidApiSettings" title="plugins.generic.deiaSurvey.settings.title"}
            {fbvFormSection}
                {if $globallyConfigured}
                <p>
                    {translate key="plugins.generic.deiaSurvey.settings.globallyconfigured"}
                </p>
                {/if}
                {fbvElement id="orcidAPIPath" class="orcidAPIPath" type="select" translate="true" from=$orcidApiUrls selected=$orcidAPIPath required="true" label="plugins.generic.deiaSurvey.settings.orcidAPIPath" disabled=$globallyConfigured}
                {fbvElement type="text" id="orcidClientId" class="orcidClientId" value=$orcidClientId required="true" label="plugins.generic.deiaSurvey.settings.orcidClientId" maxlength="40" size=$fbvStyles.size.MEDIUM disabled=$globallyConfigured}
                {if $globallyConfigured}
                    <p>
                        {translate key="plugins.generic.deiaSurvey.settings.orcidClientSecret"}: <i>{translate key="plugins.generic.deiaSurvey.settings.hidden"}</i>
                    </p>
                {else}
                    {fbvElement type="text" id="orcidClientSecret" class="orcidClientSecret" value=$orcidClientSecret required="true" label="plugins.generic.deiaSurvey.settings.orcidClientSecret" maxlength="40" size=$fbvStyles.size.MEDIUM disabled=$globallyConfigured}
                {/if}
            {/fbvFormSection}
        {/fbvFormArea}
        {fbvFormButtons}
        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
    </div>
</form>
