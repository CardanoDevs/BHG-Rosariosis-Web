<?php

if (!isset($AssignmentsFilesPath)) {

    $AssignmentsFilesPath = 'assets/AssignmentsFiles/';

}

function SanitizeHTML($message, $path)
{
    return $message;
}

function GetAssignment($assignment_id, $user_id)

{

    static $assignment = array();

    if (isset($assignment[$assignment_id])) {

        return $assignment[$assignment_id];

    }


    // Check Assignment ID is int > 0.


    if ($assignment_id < 1) {

        return false;

    }


    $where_user = "1";


//    if (User('PROFILE') === 'teacher') {
//
//        $where_user = "WHERE ga.STAFF_ID='" . User('STAFF_ID') . "'
//
//			AND c.COURSE_ID=gat.COURSE_ID
//
//			AND (ga.COURSE_PERIOD_ID IS NULL OR ga.COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
//
//			AND (ga.COURSE_ID IS NULL OR ga.COURSE_ID=c.COURSE_ID)";
//
//    } elseif (UserStudentID()) {

    $where_user = ",SCHEDULE ss WHERE (ga.SUBMISSION='Y')
    
            AND ss.student_id = $user_id

			AND (ga.COURSE_PERIOD_ID IS NULL OR ss.COURSE_PERIOD_ID=ga.COURSE_PERIOD_ID)

			AND (ga.COURSE_ID IS NULL OR ss.COURSE_ID=ga.COURSE_ID)

			AND (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)

			AND ( ga.DUE_DATE IS NULL

				OR ( ga.DUE_DATE>=ss.START_DATE

					AND ( ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE ) ) )

			AND c.COURSE_ID=ss.COURSE_ID";

//    }

    $assignment_sql = "SELECT ga.ASSIGNMENT_ID, ga.STAFF_ID, ga.COURSE_PERIOD_ID, ga.COURSE_ID,

		ga.TITLE, ga.ASSIGNED_DATE, ga.DUE_DATE, ga.POINTS,

		ga.DESCRIPTION, ga.FILE, ga.SUBMISSION, c.TITLE AS COURSE_TITLE,

		gat.TITLE AS CATEGORY, gat.COLOR AS ASSIGNMENT_TYPE_COLOR

		FROM GRADEBOOK_ASSIGNMENTS ga,COURSES c,GRADEBOOK_ASSIGNMENT_TYPES gat

		" . $where_user .

        " AND ga.ASSIGNMENT_ID='" . $assignment_id . "'

		AND gat.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID"; // Why not?


//    echo $assignment_sql;

    $assignment_RET = DBGet($assignment_sql, array(), array('ASSIGNMENT_ID'));


    $assignment[$assignment_id] = isset($assignment_RET[$assignment_id]) ?

        $assignment_RET[$assignment_id][1] : false;


    return $assignment[$assignment_id];

}

function GetAssignmentsFilesPath($teacher_id)

{

    global $AssignmentsFilesPath, $DefaultSyear;;

    if (!$teacher_id) {

        return $AssignmentsFilesPath;

    }


    // File path = AssignmentsFiles/[School_Year]/Quarter[1,2,3,4...]/Teacher[teacher_ID]/.

    return $AssignmentsFilesPath . $DefaultSyear . '/Quarter/Teacher' . $teacher_id . '/';

}

function GetAssignmentSubmission($assignment_id, $student_id)

{

    // Check Assignment ID is int > 0 & Student ID.


//    if (!$assignment_id
//        || (string)(int)$assignment_id !== $assignment_id
//        || $assignment_id < 1
//        || !$student_id) {
//
//        return false;
//
//    }

//    echo 1;

    $submission_sql = "SELECT DATA

		FROM STUDENT_ASSIGNMENTS

		WHERE ASSIGNMENT_ID='" . $assignment_id . "'

		AND STUDENT_ID='" . $student_id . "'";


    $submission_RET = DBGet($submission_sql);


    return isset($submission_RET[1]) ? $submission_RET[1] : false;

}

function MultiFileUpload($input, $path, $ext_white_list, $size_limit, &$error, $final_ext = '', $file_name_no_ext = '', $i)
{
    $file_name = $full_path = false;

    if (!$final_ext) {
        $final_ext = mb_strtolower(mb_strrchr($_FILES[$input]['name'][$i], '.'));
    }

    if ($file_name_no_ext) {
        $file_name = $file_name_no_ext . $final_ext;
    }

    if (!is_uploaded_file($_FILES[$input]['tmp_name'][$i])) {
        // Check the post_max_size & php_value upload_max_filesize values in the php.ini file.
        $error[] = _('File not uploaded');
    } elseif (!in_array(mb_strtolower(mb_strrchr($_FILES[$input]['name'][$i], '.')), $ext_white_list)) {
        $error[] = sprintf(
            _('Wrong file type: %s (%s required)'),
            $_FILES[$input]['type'][$i],
            implode(', ', $ext_white_list)
        );
    } elseif ($size_limit
        && $_FILES[$input]['size'][$i] > $size_limit * 1024 * 1024) {
        $error[] = sprintf(
            _('File size > %01.2fMb: %01.2fMb'),
            $size_limit,
            ($_FILES[$input]['size'][$i] / 1024) / 1024
        );
    } // If folder doesnt exist, create it!
    elseif (!is_dir($path)
        && !mkdir($path, 0755, true)) // Fix shared hosting: permission 755 for directories.
    {
        $error[] = sprintf(_('Folder not created') . ': %s', $path);
    } elseif (!is_writable($path)) {
        // See PHP / Apache user rights for folder.
        $error[] = sprintf(_('Folder not writable') . ': %s', $path);
    } // Store file.
    elseif (!move_uploaded_file(
        $_FILES[$input]['tmp_name'][$i],
        $full_path = ($path . ($file_name ?
                $file_name :
                no_accents(mb_substr(
                    $_FILES[$input]['name'][$i],
                    0,
                    mb_strrpos($_FILES[$input]['name'][$i], '.')
                )) . $final_ext
            ))
    )) {
        $error[] = sprintf(_('File invalid or not moveable') . ': %s', $_FILES[$input]['tmp_name'][$i]);
    }

    return $full_path;
}