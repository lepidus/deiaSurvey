<script>
	$(function() {ldelim}
		$('#editDeiaQuestionBlockTabs').pkpHandler(
			'$.pkp.controllers.TabHandler',
			{ldelim}
				{if !$canEdit}disabled: [0, 1]{/if}
			{rdelim}
		);
	{rdelim});
</script>

<div id="editDeiaQuestionBlockTabs" class="pkp_controllers_tab">
	<ul>
		<li>
			<a href="{url router=$smarty.const.ROUTE_COMPONENT op="deiaQuestionBlockBasics" deiaQuestionBlockId=$deiaQuestionBlockId}">
				{translate key="plugins.generic.deiaSurvey.questionBlocks.edit"}
			</a>
		</li>
		<li>
			<a href="{url router=$smarty.const.ROUTE_COMPONENT op="deiaQuestionBlockElements" deiaQuestionBlockId=$deiaQuestionBlockId}">
				{translate key="plugins.generic.deiaSurvey.questionBlocks.deiaQuestions"}
			</a>
		</li>
	</ul>
</div>
