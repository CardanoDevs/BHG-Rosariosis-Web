<?php

require 'vendor/autoload.php';

include_once '../config.inc.php';
include_once '../functions/Actions.php';
include_once('../functions/DBGet.fnc.php');
include_once('../database.inc.php');
include_once('../functions/Password.php');

include_once 'core/functions.php';
include_once 'core/jwt.php';
require_once 'core/Assignment.fnc.php';
require_once '../ProgramFunctions/FileUpload.fnc.php';
//require_once '../ProgramFunctions/MarkDownHTML.fnc.php';

PerformInitialAuthChecks();

$user = GetAuthData();


$student_id = 0;

if (IsParent()) {
    $student_ids = GetStudentIdsFromJWT();

    $sql        = "SELECT current_school_id FROM staff WHERE staff_id = ".$user->USER->USER_ID;
    $data       = DBGet($sql);
    $schoolID   = $data[1]['CURRENT_SCHOOL_ID'] ?: 1;

    if (isset($_GET['student_id']) && in_array($_GET['student_id'], $student_ids)) {
        $student_id = $_GET['student_id'];
    } else {
        $student_id = $student_ids[0];
    } 
} else if (IsStudent()) {
    $schoolID   = $user->USER->SCHOOL_ID;
    $student_id = $user->USER->USER_ID;
}


if (($student_id > 0) && isset($_POST['submit_assignment']) && isset($_GET['assignment_id'])) {

    $assignment_id = intval($_GET['assignment_id']);

    $assignment = GetAssignment($assignment_id, $student_id);

    if ($assignment) {

        $old_submission = GetAssignmentSubmission($assignment_id, $student_id);

        $timestamp = date('Y-m-d H:i:s');
        $ftimestamp = str_replace(":", "", $timestamp);

        $assignments_path = GetAssignmentsFilesPath($assignment['STAFF_ID']);

        $files = [];

        if (isset($_FILES['submission_file'])) {

            $file_count = isset($_FILES['submission_file']) ? count($_FILES['submission_file']['name']) : 0;

            $student_name = DBGetOne("select concat(first_name, ' ', last_name)
                from students
                where student_id = " . $student_id);

            $file_name_no_ext = no_accents($assignment['COURSE_TITLE'] . '_' . $assignment_id . '_' .

                    $student_name) . '_' . $ftimestamp;

            for ($i = 0; $i < $file_count; $i++) {

                $file = MultiFileUpload(
                    'submission_file',
                    '../' . $assignments_path,
                    FileExtensionWhiteList(),
                    0,
                    $error,
                    '',
                    $file_name_no_ext . '_' . ($i + 1),
                    $i
                );

                if ($file) {

                    $file = substr($file, 3, strlen($file));

                    $files[] = $file;

                }

            }

        }

        if ($old_submission) {

            $old_data = unserialize($old_submission['DATA']);

            $old_data = $old_data['files'];

            $count_old_files = ($old_data) ? count($old_data) : 0;

            for ($i = 0; $i < $count_old_files; $i++) {

                $old_file = $old_data[$i];

                if ($old_file) {

                    $old_file = '../' . $old_file;

                    if (file_exists($old_file)) {

                        // Delete old file if any.

                        unlink($old_file);

                    }

                }

            }

        }

        // Check if HMTL submitted.

        $message = isset($_POST['message']) ? SanitizeHTML($_POST['message'], $assignments_path) : '';

        // Serialize Assignment Data.

        $data = [
            'files' => null,
            'message' => $message,
            'date' => $timestamp,
        ];

        if (count($files))
            $data['files'] = $files;

        if (strlen($message) > 0)
            $data['message'] = $message;

        $data = DBEScapeString(serialize($data));

        // Save assignment submission.
        // Update or insert?

        if ($old_submission) {

            // Update.
            $assignment_submission_sql = "UPDATE STUDENT_ASSIGNMENTS
			SET DATA='" . $data . "'
			WHERE STUDENT_ID='" . $student_id . "'
			AND ASSIGNMENT_ID='" . $assignment_id . "'";

        } else {

            // If no file & no message.

            if (($message = '') && !$files) {

                return false;

            }

            // Insert.

            $assignment_submission_sql = "INSERT INTO STUDENT_ASSIGNMENTS
			(STUDENT_ID, ASSIGNMENT_ID, DATA)
			VALUES ('" . $student_id . "', '" . $assignment_id . "', '" . $data . "')";

        }

        if (DBQuery($assignment_submission_sql)) {

            $data = unserialize($data);

            $response = [
                'Files' => null,
                'Message' => '',
            ];

            $files = $data['files'];

            if ($files != null && count($files) > 0) {

                foreach ($files as $key => $value) {

                    $response['Files'][] = [
                        'Path' => $value,
                        'Name' => basename($value),
                    ];

                }

            }

            if (isset($data['message'])) {
                $response['Message'] = strip_tags($data['message']);
            }

            echo ToJSON([
                'Assignment' => (object)$response,
            ]);

        }

    }

} else if (($student_id > 0) && isset($_GET['assignment_id'])) {

    $assignment_id = intval($_GET['assignment_id']);

    $assignment = GetAssignmentSubmission($assignment_id, $student_id);

    if ($assignment) {

        $data = unserialize($assignment['DATA']);

        $response = [
            'Files' => null,
            'Message' => '',
        ];

        $files = $data['files'];

        if ($files != null && count($files) > 0) {

            foreach ($files as $key => $value) {

                $response['Files'][] = [
                    'Path' => $value,
                    'Name' => basename($value),
                ];

            }

        }

        if (isset($data['message'])) {
            $response['Message'] = strip_tags($data['message']);
        }

        echo ToJSON([
            'Assignment' => (object)$response,
        ]);

    }

} else {

    http_response_code(400);

}

