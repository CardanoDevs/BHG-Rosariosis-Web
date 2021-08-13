<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../database.inc.php';
include_once '../functions/Password.php';

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/Course.fnc.php';

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

    $course_period_id = intval($_GET['course_period_id']);

    $assignments = [];

    $_assignments = GetAssignmentsForStudents($student_id, $course_period_id);
    $quizes = GetQuizForStudents($student_id, $course_period_id);

    if ($_assignments) {
        foreach (array_values($_assignments) as $key => $value) {
            $assignments[] = $value;
        }
    }

    if ($quizes) {
        foreach (array_values($quizes) as $key => $value) {
            $assignments[] = $value;
        }
    }

    if (count($assignments) > 0) {

        $assignments = array_values($assignments);

        foreach ($assignments as $key => $value) {

            $assignments[$key]['IS_QUIZ'] = $assignments[$key]['IS_QUIZ'] === "1";

            $assignments[$key]['DESCRIPTION'] = $assignments[$key]['DESCRIPTION'];
            $assignments[$key]['IS_CHECKED'] = isset($assignments[$key]['POINTS_EARNED']);

            $percentage = $assignments[$key]['POINTS_EARNED'] / $assignments[$key]['POINTS'];

            $assignments[$key]['GRADE'] = GetTheGrade($percentage * 100);
            $assignments[$key]['PERCENTAGE'] = round($percentage * 100);
            $assignments[$key]['GPA'] = GetTheGPA($percentage * 100);

        }

        echo ToJSON([
            'Assignments' => $assignments,
            'Empty' => false,
        ]);

    } else {
        echo ToJSON([
            'Assignments' => [],
            'Empty' => true,
        ]);
    }

} else {

    http_response_code(400);

}
