<p class="pkpNotification {if $encryptionSecretDefined}pkpNotification--success{else}pkpNotification--warning{/if}">
	{if $encryptionSecretDefined}
		{translate key="plugins.generic.deiaSurvey.settings.encryptionSecretDefined"}
	{else}
		{translate key="plugins.generic.deiaSurvey.settings.encryptionSecretNotDefined"}
	{/if}
</p>

{if $encryptionSecretDefined}
	<script src="{$questionBlockExportFeatureJsUrl|escape}"></script>

	{capture assign=deiaQuestionBlockGridUrl}
		{url
			router=$smarty.const.ROUTE_COMPONENT
			component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler"
			op="fetchGrid"
			escape=false
		}
	{/capture}
	{load_url_in_div id="deiaQuestionBlockGridContainer" url=$deiaQuestionBlockGridUrl}
{/if}
