<?php

function GetCoursePeriodsForStudent($student_id)
{

    global $DefaultSyear;

    $currentMarkingPeriodId = CurrentMarkingPeriod(DBDate());

    $sql = "SELECT s.student_id, s.marking_period_id, c.course_id, cp.course_period_id, c.title, cp.title as cp_title, cp.room,
            cp.teacher_id, concat(t.first_name,' ', t.last_name) as teacher_name
            FROM SCHEDULE s, COURSES c, COURSE_PERIODS cp, staff t
            WHERE s.course_period_id = cp.course_period_id and cp.course_id = c.course_id and cp.teacher_id = t.staff_id
            AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID
            AND s.SYEAR = c.SYEAR
            AND s.STUDENT_ID='$student_id'
            AND s.syear = '$DefaultSyear'
            and cp.does_attendance is null
            order by course_period_id";

    $courses = DBGet($sql, array(), array());

    return $courses;

}

function GetCoursePeriodsForStudents($student_ids)
{

    global $DefaultSyear;

    $currentMarkingPeriodId = CurrentMarkingPeriod(DBDate());

    $sql = "SELECT
            s.student_id, s.marking_period_id,
            c.course_id, cp.course_period_id, c.title, cp.title as cp_title, cp.room,
            cp.teacher_id, concat(t.first_name,' ', t.last_name) as teacher_name,
            (
                select sum(ssq.points) / (
                    SELECT sum(__gat.FINAL_GRADE_PERCENT) * 100
                    FROM GRADEBOOK_ASSIGNMENT_TYPES __gat
                    WHERE __gat.COURSE_ID = c.course_id
                    AND __gat.STAFF_ID=t.staff_id
                )
                from (
                select (sum(_gg.points) / count(_ga.assignment_id)) * _gat.final_grade_percent as points
                from gradebook_assignments _ga, gradebook_grades _gg, gradebook_assignment_types _gat
                where _ga.assignment_id = _gg.assignment_id
                and _ga.course_period_id = _gg.course_period_id
                and _gg.course_period_id = cp.course_period_id
                and _gg.student_id = s.student_id
                and _ga.assignment_type_id = _gat.assignment_type_id
                and _gg.points is not null
                and _ga.marking_period_id = $currentMarkingPeriodId
                group by _gat.assignment_type_id) as ssq
			) as percentage
            FROM SCHEDULE s, COURSES c, COURSE_PERIODS cp, staff t
            WHERE s.course_period_id = cp.course_period_id and cp.course_id = c.course_id and cp.teacher_id = t.staff_id
            AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID
            AND s.SYEAR = c.SYEAR
            AND s.STUDENT_ID IN ($student_ids)
            AND s.syear = '$DefaultSyear'
            and cp.does_attendance is null
            order by course_period_id";

    $courses = DBGet($sql, array(), array());

    return $courses;

}

function GetCoursePercentage($course_period_id, $student_id)
{

    $currentMarkingPeriodId = CurrentMarkingPeriod(DBDate());
    $getTotalPercentage = GetTotalPercentage($course_period_id, $student_id);

    if (!$getTotalPercentage) {
        return 0;
    }

    $sql = "select round((sum(innerQuery.percentage) / $getTotalPercentage) * 100) as percentage
    from (
        select (sum(gg.points) / sum(ga.points)) * (gat.final_grade_percent * 100) as percentage
        from gradebook_assignments ga, gradebook_grades gg, gradebook_assignment_types gat
        where ga.assignment_id = gg.assignment_id
        and ga.course_period_id  = gg.course_period_id
        and ga.assignment_type_id = gat.assignment_type_id
        and gg.student_id = $student_id
        and ga.marking_period_id = $currentMarkingPeriodId
        and gg.points is not null
        and ga.course_period_id = $course_period_id
        group by gat.assignment_type_id
    ) as innerQuery";

    $percentage = DBGet($sql);

    if ($percentage) {
        return $percentage[1]['PERCENTAGE'];
    }

}

function GetTotalPercentage($course_period_id, $student_id)
{

    $currentMarkingPeriodId = CurrentMarkingPeriod(DBDate());

    $sql = "select sum(innerquery.final_grade_percent) * 100 as total_percentage
        from (
            select gat.final_grade_percent
            from gradebook_assignments ga, gradebook_grades gg, gradebook_assignment_types gat
            where ga.assignment_id = gg.assignment_id
            and ga.course_period_id  = gg.course_period_id
            and ga.assignment_type_id = gat.assignment_type_id
            and gg.student_id = $student_id
            and ga.marking_period_id = $currentMarkingPeriodId
            and gg.points is not null
            and ga.course_period_id = $course_period_id
            group by gat.assignment_type_id
        ) as innerQuery";

    // print_r($sql);

    $percentage = DBGet($sql);

    if ($percentage) {
        return $percentage[1]['TOTAL_PERCENTAGE'];
    }

}
