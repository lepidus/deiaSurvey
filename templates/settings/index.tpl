<script>
	$(function() {ldelim}
		$('#deiaSurveySettingsTabs').pkpHandler('$.pkp.controllers.TabHandler');
		$('#deiaSurveySettingsTabs').tabs('option', 'cache', true);
	{rdelim});
</script>

<div id="deiaSurveySettingsTabs">
	<ul>
		<li><a href="#settings-tab">{translate key="manager.plugins.settings"}</a></li>
		<li><a href="#questionBlocks-tab">{translate key="plugins.generic.deiaSurvey.questionBlocks"}</a></li>
	</ul>
	<div id="settings-tab">
		{capture assign=settingsFormUrl}
			{url
				router=$smarty.const.ROUTE_COMPONENT
				component="grid.settings.plugins.SettingsPluginGridHandler"
				op="manage"
				plugin=$pluginName
				category="generic"
				verb="settings"
				method="form"
				escape=false
			}
		{/capture}
		{load_url_in_div id="settingsFormContainer" url=$settingsFormUrl}
	</div>
	<div id="questionBlocks-tab">
		{capture assign=deiaQuestionBlockGridUrl}
			{url
				router=$smarty.const.ROUTE_COMPONENT
				component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler"
				op="fetchGrid"
				escape=false
			}
		{/capture}
		{load_url_in_div id="deiaQuestionBlockGridContainer" url=$deiaQuestionBlockGridUrl}
	</div>
</div>
