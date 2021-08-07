<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../functions/Password.php';
include_once '../database.inc.php';

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/FCMToken.fnc.php';

PerformInitialAuthChecks();

$user = GetAuthData();

if (isset($_POST['token']) && $_POST['token'] != '') {

    if (strlen($_POST['token']) <= 255) {
        $is_student = ($user->USER->PROFILE === 'student') ? 'True' : 'False';
        UpdateFCMToken($user->USER->USER_ID, DBEScapeString($_POST['token']), $is_student);
    } else {
        http_response_code(400);
    }

} else {

    $fcmTokens = array_values(GetAllFCMTokens());

    echo ToJSON([
        'FCMTokens' => $fcmTokens,
    ]);

}