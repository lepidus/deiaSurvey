{**
 * templates/deiaQuestionBlocks/responseOptionHasInputFieldCell.tpl
 *
 * Renders the listbuilder cell used to mark response options that require
 * an additional free-text input.
 *}
{if $id}
	{assign var=cellId value="cell-"|concat:$id}
{else}
	{assign var=cellId value=""}
{/if}
<span {if $cellId}id="{$cellId|escape}" {/if}class="pkp_linkActions gridCellContainer">
	<div class="gridCellDisplay">
		{include file="controllers/grid/gridCellContents.tpl"}
	</div>
	<div class="gridCellEdit">
		<input
			type="checkbox"
			name="newRowId[{$column->getId()|escape}]"
			value="1"
			{if $selected}checked="checked"{/if}
			{if $column->getFlag('tabIndex')}tabindex="{$column->getFlag('tabIndex')}"{/if}
		/>
	</div>
</span>
