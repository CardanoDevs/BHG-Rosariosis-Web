<?php

function GetParentLogin($username, $password)
{
    global $DefaultSyear;
    
    $sql = "SELECT s.USERNAME, s.PROFILE, s.STAFF_ID as USER_ID, s.PASSWORD, 
    s.FIRST_NAME, s.LAST_NAME, s.TITLE 
	FROM STAFF s 
	WHERE UPPER(s.USERNAME)=UPPER('" . $username . "') and s.PROFILE = 'parent' and s.syear = ".$DefaultSyear;

    $login_RET = DBGet($sql);

    if ($login_RET) {

        $passwordMatch = match_password($login_RET[1]['PASSWORD'], $password);

        if ($passwordMatch)
            return $login_RET;

        return null;
    }

    return null;

}

function GetStudentLogin($username, $password)
{

    // Lookup for student $username in DB.
    $login_RET = DBGet("SELECT s.USERNAME,s.STUDENT_ID as USER_ID,s.PASSWORD,
            s.FIRST_NAME, s.LAST_NAME, s.NAME_SUFFIX as TITLE, se.SCHOOL_ID
			FROM STUDENTS s,STUDENT_ENROLLMENT se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND CURRENT_DATE>=se.START_DATE
			AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')");

    if (!$login_RET) {

        // Student may be inactive or not verified, see below for corresponding errors.
        $login_RET = DBGet("SELECT s.USERNAME,s.STUDENT_ID as USER_ID, s.PASSWORD,
            s.FIRST_NAME, s.LAST_NAME, s.NAME_SUFFIX as TITLE, se.SCHOOL_ID
			FROM STUDENTS s,STUDENT_ENROLLMENT se
			WHERE se.STUDENT_ID=s.STUDENT_ID
			AND (CURRENT_DATE<=se.END_DATE OR se.END_DATE IS NULL)
			AND UPPER(s.USERNAME)=UPPER('" . $username . "')");

    }

    if ($login_RET) {

        $passwordMatch = match_password($login_RET[1]['PASSWORD'], $password);

        if ($passwordMatch)
            return $login_RET;

        return null;

    }

    return null;

}

function GetStudentsOfParent($parent_id)
{

    $sql = "select s.student_id, s.first_name, s.last_name
            from students_join_users sju, students s 
            where sju.staff_id = $parent_id
            and sju.student_id = s.student_id
            order by s.student_id";

    $students = DBGet($sql);

    if ($students) {
        return $students;
    }

    return null;

}