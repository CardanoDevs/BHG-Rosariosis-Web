<?php

function GetAllAssignmentsForCoursePeriod($course_period_id)
{

    $sql = "select *
	from gradebook_assignments ga
	where ga.course_period_id in ($course_period_id)
	order by assignment_id";

    return DBGet($sql);

}

function GetMarkedAssignmentsForCoursePeriod($course_period_id, $student_id)
{

    $sql = "select gg.*
	from gradebook_assignments ga, gradebook_grades gg
	where ga.course_period_id = gg.course_period_id
	and ga.assignment_id = gg.assignment_id
	and gg.student_id = $student_id
	and gg.course_period_id in ($course_period_id)";

    return DBGet($sql);

}

function Data($course_period_id, $student_id)
{

    $sql = "select ga.assignment_id, ga.title,
            ga.points as points_total,
            (
            	select gg.points from gradebook_grades gg 
            	where gg.assignment_id = ga.assignment_id and gg.student_id = $student_id
            	and gg.course_period_id = ga.course_period_id
            ) as points_earned,
            (
            	select gg.\"comment\" from gradebook_grades gg
	            where gg.assignment_id = ga.assignment_id and gg.student_id = $student_id
	            and gg.course_period_id = ga.course_period_id
	        ) as \"comment\",
	        ga.due_date,
	        ga.assigned_date
            from gradebook_assignments ga
            where ga.course_period_id = $course_period_id";

    return DBGet($sql);

}