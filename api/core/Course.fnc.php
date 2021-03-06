<?php

function GetAssignmentsForStudents($student_id, $course_period_id)
{

    $sql = "select (select 0) as is_quiz, s.student_id, ga.assignment_id, ga.title as assignment_title, ga.description, ga.assigned_date, ga.due_date, gat.assignment_type_id, gat.title as assignment_type, ga.points,
            (select points from gradebook_grades gg
            where gg.assignment_id = ga.assignment_id and gg.course_period_id = ga.course_period_id
            and gg.student_id = s.student_id  ) as points_earned
            from schedule s, course_periods cp, gradebook_assignments ga, gradebook_assignment_types gat
            where s.course_period_id = cp.course_period_id
            and cp.course_period_id = ga.course_period_id
            and ga.assignment_type_id = gat.assignment_type_id
            and s.student_id = $student_id
            and ga.submission = 'Y'
            and ga.course_period_id = $course_period_id
            order by ga.due_date asc";

    $result = DBGet($sql, array(), array());

    if ($result) {
        return $result;
    }

    return null;

}

function GetQuizForStudents($student_id, $course_period_id)
{

    $sql = "select (select 1) as is_quiz, s.student_id, ga.assignment_id, ga.title as assignment_title,
    ga.description, ga.assigned_date, ga.due_date, gat.assignment_type_id, gat.title as assignment_type, ga.points,
    (select points from gradebook_grades gg
    where gg.assignment_id = ga.assignment_id and gg.course_period_id = ga.course_period_id
    and gg.student_id = s.student_id  ) as points_earned
    from schedule s, course_periods cp, gradebook_assignments ga, gradebook_assignment_types gat, quiz q
    where s.course_period_id = cp.course_period_id
    and cp.course_period_id = ga.course_period_id
    and ga.assignment_type_id = gat.assignment_type_id
    and s.student_id = $student_id
    and ga.course_period_id = $course_period_id
    and ga.assignment_id  = q.assignment_id
    and q.\"show\"  = 'Y'
    order by ga.due_date asc";

    $result = DBGet($sql, array(), array());

    if ($result) {
        return $result;
    }

    return null;

}

function GetTheGrade($percent)
{
    global $DefaultSyear, $schoolID;
    $query = "SELECT REPORT_CARD_GRADES.title, REPORT_CARD_GRADES.break_off
    FROM REPORT_CARD_GRADES, REPORT_CARD_GRADE_SCALES 
    WHERE REPORT_CARD_GRADES.grade_scale_id =  REPORT_CARD_GRADE_SCALES.id
    AND REPORT_CARD_GRADE_SCALES.SCHOOL_ID = ".$schoolID."
    AND REPORT_CARD_GRADE_SCALES.SYEAR = ".$DefaultSyear."
    ORDER BY REPORT_CARD_GRADES.BREAK_OFF IS NOT NULL DESC, REPORT_CARD_GRADES.BREAK_OFF DESC, REPORT_CARD_GRADES.SORT_ORDER";

    $rows = DBGet($query, array(), array());

    foreach ($rows as $row) {

        if ($percent >= $row['BREAK_OFF']) {
            return $row['TITLE'];
        }

    }

}

function GetTheGPA($percent)
{

    return sprintf("%01.2f", ($percent / 100) * 4);

}
