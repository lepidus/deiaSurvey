<script>
    $(function() {ldelim}
        $('#deiaQuestionBlocksImportForm').pkpHandler(
            '$.pkp.controllers.form.FileUploadFormHandler',
            {ldelim}
                $uploader: $('#deiaQuestionBlocksImportUploader'),
                uploaderOptions: {ldelim}
                    uploadUrl: {url|json_encode
                        router=$smarty.const.ROUTE_COMPONENT
                        component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler"
                        op="uploadQuestionBlocksFile"
                        escape=false
                    },
                    baseUrl: {$baseUrl|json_encode}
                {rdelim}
            {rdelim}
        );
    {rdelim});
</script>

<form
    class="pkp_form"
    id="deiaQuestionBlocksImportForm"
    method="post"
    action="{url
        router=$smarty.const.ROUTE_COMPONENT
        component="plugins.generic.deiaSurvey.classes.controllers.grid.deiaQuestionBlock.DeiaQuestionBlockGridHandler"
        op="uploadQuestionBlocks"
    }"
>
    {csrf}

    {include file="controllers/notification/inPlaceNotification.tpl" notificationId="deiaQuestionBlocksImportNotification"}

    {fbvFormArea id="file"}
        <p>{translate key="plugins.generic.deiaSurvey.questionBlocks.import.description"}</p>

        {fbvFormSection title="plugins.generic.deiaSurvey.questionBlocks.importFile" required=true for="questionBlocksFile"}
            {fbvElement type="hidden" id="temporaryFileId" name="temporaryFileId" value=""}
            {include
                file="controllers/fileUploadContainer.tpl"
                id="deiaQuestionBlocksImportUploader"
                stringAddFile="plugins.generic.deiaSurvey.questionBlocks.import.addFile"
                stringChangeFile="plugins.generic.deiaSurvey.questionBlocks.import.changeFile"
            }
        {/fbvFormSection}
    {/fbvFormArea}

    {fbvFormButtons id="deiaQuestionBlocksImportSubmit" submitText="plugins.generic.deiaSurvey.questionBlocks.import"}
</form>
<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
