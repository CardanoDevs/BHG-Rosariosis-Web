<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once('../functions/DBGet.fnc.php');
include_once('../database.inc.php');
include_once('../functions/Password.php');

include_once 'core/functions.php';
include_once 'core/Login.fnc.php';
include_once 'core/jwt.php';

if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $app_version = '1.0';
    $device_id = '12345123';

    $user_details = [];

    $staffLogin = GetParentLogin($username, $password);

    if ($staffLogin != null) {

        unset($staffLogin[1]['PASSWORD']);
        $user_details = $staffLogin[1];

        $students = GetStudentsOfParent($user_details['USER_ID']);

        $user_details['STUDENTS'] = [];

        if ($students) {

            $user_details['STUDENTS'] = array_values($students);

        }

    } else {

        $studentLogin = GetStudentLogin($username, $password);

        if ($studentLogin != null) {

            $studentLogin[1]['PROFILE'] = 'student';
            unset($studentLogin[1]['PASSWORD']);
            $user_details = $studentLogin[1];

        }

    }

    if (count($user_details)) {

        $user_details['FIRST_NAME'] = ucwords($user_details['FIRST_NAME']);
        $user_details['LAST_NAME'] = ucwords($user_details['LAST_NAME']);

        $jwt = CreateJWT([
            'APP_VERSION' => $app_version,
            'DEVICE_ID' => $device_id,
            'USER' => $user_details,
        ]);

        echo ToJSON([
            'CLIENT_TOKEN' => $jwt,
            'USER' => $user_details,
        ]);

    } else {

        http_response_code(401);
        echo ToJSON([
            'ERROR' => 'Username or Password is incorrect'
        ]);

    }

}