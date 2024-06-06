<div id="demographicRequestMessage" class="pkp_notification" style="background-color: white;">
    {assign var="content" value="plugins.generic.demographicData.requestMessage"}
    {include
        file="controllers/notification/inPlaceNotificationContent.tpl"
        notificationId="demographicRequestMessage-"|uniqid
        notificationStyleClass="notifyWarning"
        notificationTitle="common.warning"|translate
        notificationContents=$content|translate
    }
</div>