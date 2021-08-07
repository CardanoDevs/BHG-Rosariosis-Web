<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once '../functions/DBGet.fnc.php';
include_once '../functions/Password.php';
include_once '../database.inc.php';

include_once 'core/functions.php';
include_once 'core/jwt.php';
include_once 'core/Quiz.fnc.php';

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

// var_dump(IsStudent());

if (isset($_GET['assignment_id']) && ($student_id > 0)) {

    $assignment_id = intval($_GET['assignment_id']);

    $quizobj = GetQuizByAssignmentId($assignment_id);

    if (!$quizobj) {
        http_response_code(404);
        exit();
    }

    $quiz_id = $quizobj['ID'];

    if (isset($_POST['Quiz'])) {

        $quiz = json_decode($_POST['Quiz']);

        $save_answers_sql = "";

        foreach ($quiz->QUESTIONS as $key => $value) {

            $value = (array) $value;

            $answer_post = null;

            if ($value['TYPE'] === 'textarea'
                && isset($value['Answers'])) {
                $answer_post = $value['Answers'];
            } elseif (isset($value['Answers'])) {
                $answer_post = $value['Answers'];
            }

            $query = StudentQuizSaveAnswerSQL($quizobj, $student_id, ((array) $value), $answer_post);
            // print_r($query);
            // echo "<br>";
            $save_answers_sql .= $query;
        }

        // print_r($save_answers_sql);

        if ($save_answers_sql) {
            DBQuery($save_answers_sql);

            echo ToJSON([
                'SUCCESS' => true,
            ]);

        } else {
            echo ToJSON([
                'SUCCESS' => false,
            ]);
        }

    } else {

        $quiz_questions = QuizGetQuestions($quiz_id);

        foreach ($quiz_questions as $key => $question) {

            $options = explode(
                "\r",
                str_replace(array("\r\n", "\n"), "\r", $question['ANSWER'])
            );

            $options_clean = array_map(function ($option) {
                // Remove * to mark correct answers.
                return (mb_substr($option, 0, 1) === '*' ? mb_substr($option, 1) : $option);
            },
                $options
            );

            switch ($question['TYPE']) {
                case "multiple":
                    $quiz_questions[$key]['OPTIONS'] = $options_clean;
                    break;
                case "select":
                    $quiz_questions[$key]['OPTIONS'] = $options_clean;
                    break;
                case "gap":

                    foreach ($options as $key2 => $option) {
                        $options[$key2] = GapOptionToNormal($option);
                    }

                    $quiz_questions[$key]['OPTIONS'] = $options;

                    break;
                case "text":
                    $quiz_questions[$key]['OPTIONS'] = [];
                    break;
                case "textarea":
                    $quiz_questions[$key]['OPTIONS'] = [];
                    break;
            }

            unset($quiz_questions[$key]['ANSWER']);

        }

        $quiz = GetQuiz($quiz_id, $student_id);

        $hours = QuizGetOption($quiz, 'HOURS');
        $minues = QuizGetOption($quiz, 'MINUTES');

        $hours = empty($hours) ? 0 : $hours;
        $minutes = empty($minutes) ? 0 : $minutes;

        echo ToJSON([
            'ASSIGNMENT_ID' => $assignment_id,
            'QUIZ' => $quiz,
            'HOURS' => $hours,
            'MINUTES' => $minues,
            'QUESTIONS' => array_values($quiz_questions),
        ]);

    }

} else {
    http_response_code(400);
    exit();
}
