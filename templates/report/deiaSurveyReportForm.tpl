{*
  * Copyright (c) 2024-2026 Lepidus Tecnologia
  * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
  *
  *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
		{translate key="plugins.generic.deiaSurvey.report.displayName"}
	</h1>
    <div class="app__contentPanel">
    <form id="scieloSubmissionsReportForm" method="post" action="">
        {include file="common/formErrors.tpl"}

        <div id="actionsButton">
            <input
                class="pkp_button submitFormButton"
                type="submit"
                value="{translate key="plugins.generic.deiaSurvey.report.generateReport.context.{$application}"}"
            />
        </div>

        {if $userIsSiteAdmin}
            <div id="actionsButton">
                <input
                    class="pkp_button submitFormButton"
                    type="submit"
                    value="{translate key="plugins.generic.deiaSurvey.report.generateReport.site"}"
                    class="button defaultButton"
                />
            </div>
        {/if}
    </form>
{/block}