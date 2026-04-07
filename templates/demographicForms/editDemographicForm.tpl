<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#editDemographicFormTabs').pkpHandler(
				'$.pkp.controllers.TabHandler',
				{ldelim}
					{if !$canEdit}disabled: [0, 1]{/if}
				{rdelim}
		);
	{rdelim});
</script>
<div id="editDemographicFormTabs" class="pkp_controllers_tab">
	<ul>
		<li>
            <a href="{url router=$smarty.const.ROUTE_COMPONENT op="demographicFormBasics" demographicFormId=$demographicFormId}">
                {translate key="plugins.generic.deiaSurvey.questionBlocks.edit"}
            </a>
        </li>
		<li>
            <a href="{url router=$smarty.const.ROUTE_COMPONENT op="demographicFormElements" demographicFormId=$demographicFormId}">
                {translate key="plugins.generic.deiaSurvey.questionBlocks.demographicQuestions"}
            </a>
        </li>
	</ul>
</div>
