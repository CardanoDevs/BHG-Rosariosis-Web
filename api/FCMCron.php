<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../database.inc.php';

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/FCMToken.fnc.php';

$apiToken = 'AAAA_8lWi9Q:APA91bEXikMcCPJdugdbHMOlujhnCcnmuAQkQYCiLwr-QDfMBV11X9rmxtlXB8dfz3PxDtjpcr86GaIFmIx4yoPvOKaQl0uttOwUQO1w67iYK6vuyQYHTPFw3xSd9InWRPaTJTrWe10T';
$senderId = '1098594552788';

try {

    $client = new \Fcm\FcmClient($apiToken, $senderId);

    $notifications = GetAllUnsentNotifications(100);

    foreach ($notifications as $notif) {

        $user_fcm_token = $notif['TOKEN'];
        $title = 'Announcement';
        $body = $notif['MESSAGE'];
        $announcement_id = $notif['ANNOUNCEMENT_ID'];
        $user_id = $notif['USER_ID'];

        $notification = new \Fcm\Push\Notification();

        $notification
            ->addRecipient($user_fcm_token)
            ->setTitle($title)
            ->setBody($body)
            ->setSound('default');

        MarkSent($announcement_id, $user_id);

        $client->send($notification);

    }

} catch (\Fcm\Exception\FcmClientException $e) {
    LogMessage($e);
} catch (Exception $e) {
    LogMessage($e);
}

print_r($notifications);