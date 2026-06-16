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
        {if $userIsSiteAdmin}
            <p>
                {translate key="plugins.generic.deiaSurvey.report.select"}
            </p>
        {/if}

        {include file="common/formErrors.tpl"}

        <div class="reportSection">
            <h3>{translate key="plugins.generic.deiaSurvey.report.contextReport.{$application}"}</h3>
            <p>{translate key="plugins.generic.deiaSurvey.report.contextReport.description"}</p>
            <div id="actionsButton">
                <button
                    class="pkp_button submitFormButton"
                    type="submit"
                    name="reportType"
                    value="context"
                >
                    {translate key="plugins.generic.deiaSurvey.report.generateReport.context.{$application}"}
                </button>
            </div>
        </div>

        {if $userIsSiteAdmin}
            <div class="reportSection">
                <h3>{translate key="plugins.generic.deiaSurvey.report.siteReport"}</h3>
                <p>{translate key="plugins.generic.deiaSurvey.report.siteReport.description"}</p>
                <div id="actionsButton">
                    <button
                        class="pkp_button submitFormButton"
                        type="submit"
                        name="reportType"
                        value="site"
                    >
                        {translate key="plugins.generic.deiaSurvey.report.generateReport.site"}
                    </button>
                </div>
            </div>
        {/if}
    </form>
{/block}
