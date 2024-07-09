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
        $('#demographicDataSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<link rel="stylesheet" type="text/css" href="/plugins/generic/demographicData/styles/settingsForm.css">
<form class="pkp_form" id="demographicDataSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
    <div id="demographicDataSettings">
        <p id="description">
            {translate key="plugins.generic.demographicData.settings.description" }
        </p>

        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="orcidProfileSettingsFormNotification"}
        {fbvFormArea id="orcidApiSettings" title="plugins.generic.demographicData.settings.title"}
            {fbvFormSection}
                {if $globallyConfigured}
                <p>
                    {translate key="plugins.generic.demographicData.settings.globallyconfigured"}
                </p>
                {/if}
                {fbvElement id="orcidAPIPath" class="orcidAPIPath" type="select" translate="true" from=$orcidApiUrls selected=$orcidAPIPath required="true" label="plugins.generic.demographicData.settings.orcidAPIPath" disabled=$globallyConfigured}
                {fbvElement type="text" id="orcidClientId" class="orcidClientId" value=$orcidClientId required="true" label="plugins.generic.demographicData.settings.orcidClientId" maxlength="40" size=$fbvStyles.size.MEDIUM disabled=$globallyConfigured}
                {if $globallyConfigured}
                    <p>
                        {translate key="plugins.generic.demographicData.settings.orcidClientSecret"}: <i>{translate key="plugins.generic.demographicData.settings.hidden"}</i>
                    </p>
                {else}
                    {fbvElement type="text" id="orcidClientSecret" class="orcidClientSecret" value=$orcidClientSecret required="true" label="plugins.generic.demographicData.settings.orcidClientSecret" maxlength="40" size=$fbvStyles.size.MEDIUM disabled=$globallyConfigured}
                {/if}
            {/fbvFormSection}
        {/fbvFormArea}
        {fbvFormButtons}
        <p><span class="formRequired">{translate key="common.requiredField"}</span></p>
    </div>
</form>
