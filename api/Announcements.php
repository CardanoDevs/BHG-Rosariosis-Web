<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../functions/Password.php';
include_once '../database.inc.php';

include_once 'core/Announcements.fnc.php';
include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/Course.fnc.php';

PerformInitialAuthChecks();

$user = GetAuthData();

// $student_ids = [];
// error_log(json_encode($user->USER));
// if ($user->USER->PROFILE == 'student') {
//     $student_ids[] = $user->USER->USER_ID;
// } else {

//     $students = (array)$user->USER->STUDENTS;

//     if ($students) {

//         $student_ids = array_map(function ($item) {
//             return $item->STUDENT_ID;
//         }, $students);

//     }

// }
if($user->USER->PROFILE == 'student') {
    $is_student = 'TRUE';
} else {
    $is_student = 'FALSE';
}
// $data = GetUserAnnouncements(implode(',', $student_ids));
$data   = GetUserAnnouncements($user->USER->USER_ID, $is_student);
if ($data) {

    echo ToJSON([
        'Announcements' => array_values($data)
    ]);

} else {

    echo ToJSON([
        'Announcements' => []
    ]);

}


//$data = [
//    [
//        'Title' => 'New classroom enrollments open!',
//        'Text' => 'New classroom enrollments will open from 1st November.',
//        'Type' => '1',
//    ],
//    [
//        'Title' => 'Fee vouchers available',
//        'Text' => 'Please collect fee voucher for the month of June.',
//        'Type' => '2',
//    ],
//    [
//        'Title' => 'Annual Parents/Teachers meeting announcement',
//        'Text' => 'Dear concerned, this comin weekend on Saturday we are having a parents/teacher meeting to discuss progress of students and other things with the parents. You\'re requested to please visit the campus at 9 AM sharp.',
//        'Type' => '3',
//    ],
//];
//
//echo ToJSON([
//    'Announcements' => $data
//]);