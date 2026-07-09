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
