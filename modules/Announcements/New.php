<?php

$_ROSARIO['allow_edit'] = true;

$grades = sqlArrayToArray(getGrades());

$type_options = sqlArrayToArray(getAnnouncementTypes());

$assignment_create_success = false;
//print_r($_REQUEST);

if (isset($_POST['form_submit'])) {

    $announcement_type = $_POST['AnnouncementType'];
    $grade_id = $_POST['Grade'];
    $message = $_POST['Message'];
    $sent_to_parent = $_POST['SendToParent'];

    $create_announcements = true;

    if (!isset($type_options[$announcement_type]))
        $create_announcements = false;

    if (!isset($grades[$grade_id]))
        $create_announcements = false;

    if (strlen($message) == 0)
        $create_announcements = false;

    $sent_to_parent = ($sent_to_parent == 'Y');

    if ($create_announcements) {

        $announcementResource = createAnnouncement('School Announcement', $message, $announcement_type);
        $announcement_id = pg_fetch_result($announcementResource, null, 'announcement_id');

        if (strtolower($type_options[$announcement_type]) === 'grade') {
            sendNotificationForGrade($announcement_id, $grade_id, $sent_to_parent);
        } else {
            sendNotificationToAllAppStudents($announcement_id, $sent_to_parent);
        }

        $assignment_create_success = true;

        unset($_REQUEST['AnnouncementType']);
        unset($_REQUEST['Grade']);
        unset($_REQUEST['Message']);
        unset($_REQUEST['SendToParent']);

    }

//    print_r($create_announcements ? 'OK' : 'NOT OK');

}

DrawHeader('Announcements');

if ($assignment_create_success) {
    ?>
    <div class="updated">
        <p>
            <b>Note</b>:
            <img src="assets/themes/WPadmin/btn/check_button.png" class="button bigger"
                 alt="Check">
            &nbsp;Announcement created, will be dispatched to users shortly.
        </p>
    </div>
    <?php
}

echo "<form method='POST'>";

echo "<input type='hidden' name='form_submit' />";

$header .= '<table width="100%">';

$header .= '<tr>';
$header .= '<td style="padding-top: 10px; padding-bottom: 10px">';
$header .= SelectInput(
    $_REQUEST['AnnouncementType'],
    'AnnouncementType',
    'Announcement Type',
    $type_options,
    false,
    '',
    false
);
$header .= '</td>';

$header .= '<td>';
$header .= '<div id="grade_selector_box">';

$header .= SelectInput(
    $_REQUEST['Grade'],
    'Grade',
    'Select Grade',
    $grades,
    false,
    '',
    false
);

$header .= '</div>';
$header .= '</td>';

$header .= '<td>';
$header .= CheckboxInput(
    ($_REQUEST['SendToParent'] == 'Y'),
    'SendToParent',
    'Send To Parent',
    true,
    true,
    '',
    ''
);
$header .= '</td>';
$header .= '</tr>';

$header .= '<tr>';
$header .= '<td colspan="3" style="padding-bottom: 10px;">';

$header .= TextAreaInput(
    $_REQUEST['Message'],
    'Message',
    'Message',
    'rows="5" required',
    true,
    '1234');

$header .= '</td>';
$header .= '</tr>';

$header .= '<tr>';
$header .= '<td style="padding-bottom: 10px;">';
$header .= '<div style="margin-left: -8px">' . SubmitButton(_('Send'), 'submit') . '</div>';
$header .= '</td>';
$header .= '</tr>';

$header .= '</table>';

DrawHeader($header);

echo '</form>';

?>

    <script>

        $(document).ready(function () {

            $('#AnnouncementType').on('change', function () {

                var selectedOptionValue = $('option:selected', $(this)).text();

                if (selectedOptionValue.toLowerCase() === 'grade') {
                    $('#grade_selector_box').show();
                } else {
                    $('#grade_selector_box').hide();
                }

            });

            $('#AnnouncementType').trigger('change');

        });

    </script>

<?php

function sendNotificationForGrade($announcement_id, $grade_id, $send_to_parent = false)
{

    $announcement_id = DBEscapeString($announcement_id);
    $grade_id = DBEscapeString($grade_id);

    if ($send_to_parent) {
        $sql = "insert into announcement_audience (user_id, announcement_id)
                with students_ctx as (
                    select se.student_id
                    from student_enrollment se, user_fcm_tokens uft
                    where se.student_id = uft.user_id
                    and se.grade_id = $grade_id
                ),
                parents_ctx as (
                    select sju.staff_id
                    from student_enrollment se, students_join_users sju, user_fcm_tokens uft
                    where se.student_id = sju.student_id
                    and sju.staff_id = uft.user_id
                    and se.grade_id = $grade_id
                )
                select parents_ctx.staff_id as user_id, $announcement_id as announcement_id
                from parents_ctx
                union
                select students_ctx.student_id as user_id, $announcement_id as announcement_id
                from students_ctx";
    } else {
        $sql = "insert into announcement_audience (user_id, announcement_id)
                select se.student_id, $announcement_id as announcement_id
                from student_enrollment se, user_fcm_tokens uft
                where se.student_id = uft.user_id
                and se.grade_id = $grade_id";
    }

    DBQuery($sql);

}

function sendNotificationToAllAppStudents($announcement_id, $send_to_parent = false)
{

    $announcement_id = DBEscapeString($announcement_id);

    if ($send_to_parent) {
        $sql = "insert into announcement_audience (user_id, announcement_id) 
                select user_id, $announcement_id as announcement_id 
                from user_fcm_tokens";
    } else {
        $sql = "insert into announcement_audience (user_id, announcement_id) 
                select uft.user_id, $announcement_id as announcement_id 
                from user_fcm_tokens uft, students s
                where s.student_id = uft.user_id";
    }

    DBQuery($sql);
}

function createAnnouncement($title, $message, $type)
{

    $title = DBEscapeIdentifier($title);
    $message = DBEscapeString($message);
    $type = DBEscapeString($type);

    $sql = "insert into 
        announcements (title, \"text\", announcement_type_id)
        values('$title', '$message', $type) returning announcement_id";

    return DBQuery($sql);

}

function getGrades()
{

    return DBGet("SELECT ID,TITLE,SHORT_NAME
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER");

}

function getAnnouncementTypes()
{
    return DBGet("SELECT announcement_type_id as ID, type as title FROM ANNOUNCEMENT_TYPE");
}

function sqlArrayToArray($data)
{
    $grades = [];
    foreach ($data as $key => $value) {
        $grades[$value['ID']] = $value['TITLE'];
    }
    return $grades;
}

?>