{*
  * Copyright (c) 2024-2026 Lepidus Tecnologia
  * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
  *
  *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <p>Relatório</p>
    {if $userIsSiteAdmin}
        Is site admin
    {else}
        Is not site admin
    {/if}
{/block}