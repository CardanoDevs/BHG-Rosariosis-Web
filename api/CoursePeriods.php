<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../database.inc.php';
include_once '../functions/Password.php';
include_once '../functions/Config.fnc.php';

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/CoursePeriods.fnc.php';
include_once 'core/Course.fnc.php';
include_once 'core/MarkingPeriods.fnc.php';

PerformInitialAuthChecks();

$user = GetAuthData();
if ($user->USER->PROFILE == 'student') {
    $courses = GetCoursePeriodsForStudent($user->USER->USER_ID);
    $schoolID   = $user->USER->SCHOOL_ID;
} else {

    $students   = (array) $user->USER->STUDENTS;
    
    $sql        = "SELECT current_school_id FROM staff WHERE staff_id = ".$user->USER->USER_ID;
    $data       = DBGet($sql);
    $schoolID   = $data[1]['CURRENT_SCHOOL_ID'] ?: 1;

    if ($students) {

        $student_ids = array_map(function ($item) {
            return $item->STUDENT_ID;
        }, $students);

        $courses = GetCoursePeriodsForStudents(implode(',', $student_ids));

    } else {
        $courses = [];
    }

}

if ($courses) {

    $courses = array_values($courses);

    $bgImages = ['card7.jpg', 'card1.jpg', 'card2.jpg', 'card4.jpg', 'card6.jpg'];

    foreach ($courses as $key => $course) {

        $indexOfBg = $key % count($bgImages);

        $titleParts = explode('-', $course['CP_TITLE']);

        $courses[$key]['PERIOD'] = trim($titleParts[0]);
        $courses[$key]['BgCardImage'] = $bgImages[$indexOfBg];

        $percentage = GetCoursePercentage($courses[$key]['COURSE_PERIOD_ID'], $courses[$key]['STUDENT_ID']);

        $courses[$key]['PERCENTAGE'] = $percentage;
        $courses[$key]['GRADE'] = GetTheGrade($percentage);
        $courses[$key]['GPA'] = GetTheGPA($percentage);

    }

    echo ToJSON([
        'COURSES' => $courses,
    ]);

} else {
    echo ToJSON([
        'COURSES' => array(),
    ]);
}
