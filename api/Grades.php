<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once('../database.inc.php');
include_once '../functions/Actions.php';
include_once('../functions/DBGet.fnc.php');

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/Grades.fnc.php';
include_once 'core/Course.fnc.php';
include_once 'core/CoursePeriods.fnc.php';

PerformInitialAuthChecks();

$user = GetAuthData();

$student_id = 0;

if (IsParent()) {

    $student_ids = GetStudentIdsFromJWT();

    if (isset($_GET['student_id']) && in_array($_GET['student_id'], $student_ids)) {
        $student_id = $_GET['student_id'];
    }

} else if (IsStudent()) {
    $student_id = $user->USER->USER_ID;
}

if (($student_id > 0) && isset($_GET['course_period_id'])) {

    $grades = Data(DBEscapeString($_GET['course_period_id']), $student_id);

    foreach ($grades as $key => $grade) {

        $grades[$key]['POINTS_EARNED'] = intval($grades[$key]['POINTS_EARNED']);
        $grades[$key]['POINTS_TOTAL'] = intval($grades[$key]['POINTS_TOTAL']);

        $percentage = ($grade['POINTS_EARNED'] / $grade['POINTS_TOTAL']);

        $grades[$key]['PERCENT'] = round($percentage * 100);

        $grades[$key]['GRADE'] = GetTheGrade($percentage * 100);
        $grades[$key]['PERCENTAGE'] = $percentage * 100;
        $grades[$key]['GPA'] = GetTheGPA($percentage * 100);

        $grades[$key]['COMMENT'] = strlen($grades[$key]['COMMENT']) > 0 ? $grades[$key]['COMMENT'] : 'No comments';

    }

    echo ToJSON([
        'Grades' => array_values($grades),
    ]);

} else {
    http_response_code(400);
}