<?php

function GetQuizByAssignmentId($id)
{

    $sql = "select q.*
            from gradebook_assignments ga, quiz q
            where q.assignment_id = ga.assignment_id
            and ga.assignment_id  = $id";

    $quiz = DBGet($sql);

    if ($quiz) {
        return $quiz[1];
    }

}

function QuizGetQuestions($quiz_id, $functions = array(), $index = array())
{
    $functions = !empty($functions) && is_array($functions) ? $functions : array();
    $index = !empty($index) && is_array($index) ? $index : array();

    $questions_RET = DBGet(DBQuery("SELECT qqq.ID,qqq.QUESTION_ID,qqq.QUIZ_ID,qq.TITLE,qqq.POINTS,
		qq.TYPE,qq.DESCRIPTION,qq.ANSWER,qq.FILE,qqq.SORT_ORDER
		FROM QUIZ_QUIZXQUESTION qqq,QUIZ_QUESTIONS qq
		WHERE qqq.QUIZ_ID='" . $quiz_id . "'
		AND qq.ID=qqq.QUESTION_ID
		ORDER BY qqq.SORT_ORDER,qq.TITLE"), $functions, $index);

    return $questions_RET;
}

function GapOptionToNormal($value)
{

    $fvalue = nl2br($value);

    // Parse double underscores inside string and replace with SelectInput.
    $strings = explode('__', $fvalue);

    if (!$strings
        || count($strings) === 1) {
        // String is empty or does not contain double underscores.
        return $fvalue;
    }

    $underscores_count = 0;

    $i = 0;

    $return = '';

    foreach ($strings as $string) {

        if ($underscores_count++ % 2) {
            // Unsecape underscores.
            $string = str_replace('&#95;', '_', $string);

            $value = (true ? $string : '');

            $value = str_replace($string, '_', $value);

            $return .= $value;

            continue;
        }

        $return .= $string;

    }

    return $return;

}

function StudentQuizSaveAnswerSQL($quiz, $student_id, $question, $answer_post)
{
    static $old_answers = null;

    // print_r($question);

    // print_r($quiz);
    $quiz_id = $quiz['ID'];
    // $quizid = $quiz['ID'];
    if (is_null($old_answers)) {
        // Old answers.
        // echo 'GET OLD ANSWERS';
        $old_answers = QuizGetAnswers($quiz_id, $student_id, 'QUIZXQUESTION_ID');
        // print_r($old_answers);
        // echo '11';
    }

    $question_id = $question['ID'];

    $old_answer = empty($old_answers[$question_id]) ? array() : $old_answers[$question_id][1];

    // $assignments_path = GetAssignmentsFilesPath($quiz['STAFF_ID']);

    $answer = '';

    switch ($question['TYPE']) {
        case 'gap':

            $answerString = implode('|', $question['OPTIONS']);
            $answerString = str_replace('_', '__A__', $answerString);

            $question['ANSWER'] = $answerString;

            // Parse double underscores inside string and replace with SelectInput.
            $strings = explode('__', $question['ANSWER']);

            if (!$strings
                || count($strings) === 1) {
                // String is empty or does not contain double underscores.
                break;
            }

            $underscores_count = 0;

            $i = 0;

            foreach ($strings as $string) {
                if ($underscores_count++ % 2) {
                    // Remove trailing spaces.
                    $answer_pf = trim($answer_post[$i++]);

                    // Encode double underscores in answer, just in case...
                    $answer_pf = str_replace('_', '&#95;', $answer_pf);

                    $answer .= '__' . $answer_pf . '__';

                    continue;
                }

                $answer .= DBEscapeString($string);
            }

            $answer .= DBEscapeString($string);

            break;

        case 'select':

            $answer = '';

            if ($answer_post && count($answer_post)) {

                foreach ($answer_post as $__key => $__value) {
                    if ($__value == 'True') {
                        $answer = "" . $__key . "";
                        break;
                    }
                }

            }

            // $answer = (string) $answer_post;

            break;

        case 'multiple':

            foreach ((array) $answer_post as $key => $val) {
                if ($val === 'True') {
                    $answer .= $question['OPTIONS'][$key] . '||';
                    // $answer .= (string) $val . '||' . $key;
                }
            }

            if ($answer) {
                $answer = '||' . $answer;
            }

            break;

        case 'textarea':

            // Sanitize HMTL.
            // $answer = SanitizeHTML($answer_post, $assignments_path);
            $answer = trim((count($answer_post) > 0) ? $answer_post[0] : '');

            break;

        case 'text':

            // Remove trailing spaces.
            $answer = trim((count($answer_post) > 0) ? $answer_post[0] : '');

            break;
    }

    if ($old_answer
        && DBEscapeString($old_answer['ANSWER']) === $answer) {
        // Answer has not been edited, skip.
        return '';
    }

    // print_r($old_answer);
    // echo 'old answer';

    // Save quiz answer.
    // Update or insert?
    if ($old_answer) {
        // Update.
        return "UPDATE QUIZ_ANSWERS
			SET ANSWER='" . $answer . "',
			MODIFIED_AT=CURRENT_TIMESTAMP
			WHERE STUDENT_ID='" . $student_id . "'
			AND QUIZXQUESTION_ID='" . $question_id . "';";
    }

    // Insert.
    return "INSERT INTO QUIZ_ANSWERS
		(STUDENT_ID,QUIZXQUESTION_ID,ANSWER)
		VALUES ('" . $student_id . "','" . $question_id . "','" . $answer . "');";
}

function QuizGetAnswers($quiz_id, $student_id, $index = '')
{
    // Check Quiz ID is int > 0 & Student ID.
    if ($quiz_id < 1
        || !$student_id) {
        return false;
    }

    $answers_sql = "SELECT qa.ID,qa.ANSWER,qa.POINTS,qa.CREATED_AT,qa.MODIFIED_AT,qa.QUIZXQUESTION_ID
		FROM QUIZ_ANSWERS qa,QUIZ_QUIZXQUESTION qq
		WHERE qq.QUIZ_ID='" . $quiz_id . "'
		AND qq.ID=qa.QUIZXQUESTION_ID
		AND qa.STUDENT_ID='" . $student_id . "'
        ORDER BY qq.SORT_ORDER";

    // echo $answers_sql;

    $index = $index ? array($index) : array();

    $answers_RET = DBGet(DBQuery($answers_sql), array(), $index);

    return $answers_RET;
}

function GetQuiz($quiz_id, $student_id = 0)
{

    global $DefaultSyear;

    // Check Quiz ID is int > 0.
    if (!$quiz_id
        || (string) (int) $quiz_id !== $quiz_id
        || $quiz_id < 1) {
        return false;
    }

    // $assignment_file_sql = version_compare( ROSARIO_VERSION, '4.4-beta', '<' ) ?
    //     ",NULL AS ASSIGNMENT_FILE" :
    //     ",ga.FILE AS ASSIGNMENT_FILE";

    $assignment_file_sql = "";

    $quiz_sql = "SELECT q.ID,q.STAFF_ID,
		q.TITLE,q.OPTIONS,ga.ASSIGNED_DATE,ga.DUE_DATE,
		(SELECT SUM(qq.POINTS) FROM QUIZ_QUIZXQUESTION qq WHERE qq.QUIZ_ID=q.ID) AS POINTS,
		(SELECT 1
			FROM QUIZ_ANSWERS qa,QUIZ_QUIZXQUESTION qq2 WHERE
			qq2.QUIZ_ID=q.ID
			AND qq2.ID=qa.QUIZXQUESTION_ID " .
        ($student_id > 0 ? "AND qa.STUDENT_ID=ss.STUDENT_ID " : '') .
        " LIMIT 1) AS ANSWERED,
		q.DESCRIPTION,c.TITLE AS COURSE_TITLE,
		q.CREATED_AT,
		q.CREATED_BY,
		gat.COLOR AS ASSIGNMENT_TYPE_COLOR,
		ga.TITLE AS ASSIGNMENT_TITLE" . $assignment_file_sql .
        " FROM GRADEBOOK_ASSIGNMENTS ga,COURSES c,GRADEBOOK_ASSIGNMENT_TYPES gat,QUIZ q";

    if ($student_id > 0) {
        $quiz_sql .= ",SCHEDULE ss";
    }

    // add userschool and user mp
    // $quiz_sql .= " WHERE q.ID='" . $quiz_id . "'
    //     AND q.SCHOOL_ID='" . UserSchool() . "'
    //     AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
    //     AND ga.MARKING_PERIOD_ID='" . UserMP() . "'
    //     AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID";

    $quiz_sql .= " WHERE q.ID='" . $quiz_id . "'
		AND ga.ASSIGNMENT_ID=q.ASSIGNMENT_ID
		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID";

    if ($student_id > 0) {
        $quiz_sql .= " AND ss.STUDENT_ID='" . $student_id . "'
		AND ss.SYEAR='$DefaultSyear'
		AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)
		AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)
		AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
		AND (ga.DUE_DATE IS NULL
			OR (ga.DUE_DATE>=ss.START_DATE
				AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)))
		AND c.COURSE_ID=ss.COURSE_ID"; // Why not?
        // @todo Remove Due date checks, and let QuizCanSubmit handle it??
    }

    // echo $quiz_sql;

    $quiz_RET = DBGet(DBQuery($quiz_sql), array(), array('ID'));

    // $_ROSARIO['quiz'][$quiz_id] = isset($quiz_RET[$quiz_id]) ?
    // $quiz_RET[$quiz_id][1] : false;

    // return $_ROSARIO['quiz'][$quiz_id];

    return ($quiz_RET) ? $quiz_RET[$quiz_id][1] : false;
}

function QuizGetOption($quiz, $option = '')
{
    static $options_cache = array();

    if (empty($quiz['OPTIONS'])) {
        return ($option === '' ? array() : '');
    } elseif (isset($options_cache[$quiz['ID']])) {
        $options = $options_cache[$quiz['ID']];
    } else {
        $options = unserialize($quiz['OPTIONS']);

        $options_cache[$quiz['ID']] = $options;
    }

    if ($option === '') {
        return $options;
    }

    return (!isset($options[$option]) ? '' : $options[$option]);
}
