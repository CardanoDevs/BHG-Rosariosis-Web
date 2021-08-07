<?php
// Note: The 'active assignments' feature is not fully correct.  If a student has dropped and re-enrolled there can be multiple timespans for
// which the  assignemnts are 'active' for that student.  However, only the timespan of current enrollment is used for 'active' assignment
// determination.  It would be possible to include all enrollment timespans but only the current is used for simplicity.  This is not a bug
// but an accepted limitaion.

require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
require_once 'modules/Grades/includes/ClassRank.inc.php';

require_once 'modules/Grades/includes/StudentAssignments.fnc.php';
ini_set('max_input_vars', 10000);

$_REQUEST['include_inactive'] = empty( $_REQUEST['include_inactive'] ) ? '' : $_REQUEST['include_inactive'];

$_REQUEST['include_all'] = empty( $_REQUEST['include_all'] ) ? '' : $_REQUEST['include_all'];

$_REQUEST['type_id'] = empty( $_REQUEST['type_id'] ) ? '' : $_REQUEST['type_id'];

$_REQUEST['assignment_id'] = empty( $_REQUEST['assignment_id'] ) ? '' : $_REQUEST['assignment_id'];

DrawHeader( _( 'Gradebook' ) . ' - ' . ProgramTitle() . ' - ' . GetMP( UserMP() ) );
if( $_REQUEST['assignment_id'] == 'all' and !isset($_REQUEST['student_id'])){
   foreach(array_keys($_REQUEST['values']) as $id){
     $_POST['exam'][]=$id;
   }
}

//print_r($_POST);
// if running as a teacher program then rosario[allow_edit] will already be set according to admin permissions


if ( ! isset( $_ROSARIO['allow_edit'] )
	// Do not allow edit past quarter grades for Teachers according to Program Config.
	&& ( ProgramConfig( 'grades', 'GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT' )
		|| GetCurrentMP( 'QTR', DBDate(), false ) == UserMP()
		|| GetMP( 'END_DATE' ) > DBDate() ) )
{
	$_ROSARIO['allow_edit'] = true;
}

$gradebook_config = ProgramUserConfig( 'Gradebook' );

//$max_allowed = Preferences('ANOMALOUS_MAX','Gradebook')/100;
$max_allowed = ( $gradebook_config['ANOMALOUS_MAX'] ? $gradebook_config['ANOMALOUS_MAX'] / 100 : 1 );
$mkp = UserMP();
$syr = UserSyear();
$usrsc = UserSchool();
$course_period_id = UserCoursePeriod();

$course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
	cp.GRADE_SCALE_ID, credit(CAST(" . $course_period_id . " AS integer),
	CAST( '" . UserMP() . "' AS character varying)) AS CREDITS,
	DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
	FROM COURSE_PERIODS cp,COURSES c
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'" );
$course_title = $course_RET[1]['TITLE'];
$grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
$course_id = $course_RET[1]['COURSE_ID'];
$grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );

if(isset($_POST['commentsBx'])) {
    $studids = array_keys($_POST['commentsBx']);
    $parentsem = DBGet("SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP() . "'", array(), array());
    $mlxx = $parentsem[1]['PARENT_ID'];
    $current_commentsB_RET = DBGet("SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID, g.NOX
		FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $mlxx . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM REPORT_CARD_COMMENTS WHERE COURSE_ID IS NULL) ORDER BY g.NOX", array(), array('STUDENT_ID'));

    foreach ($_POST['commentsBx'] as $studid => $studx) {
        $toupd = array_keys($_POST['commentsBx'][$studid]);


        if (isset($_POST['commentsBx'][$studid])) {

            $updxa = $_POST['commentsBx'][$studid][1];
            $delex = $current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID'];

            if ($_POST['commentsBx'][$studid][1] != $current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID'] AND $updxa != null AND $_POST['commentsBx'][$studid][1] != 'N/A'  AND count($_POST['commentsBx'][$studid][1]) >= 1 AND !empty($current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID']) AND $updxa != 'N/A' and !empty($current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID']) AND $current_commentsB_RET[$studid][1]['NOX']=='1') {
                if (!empty($current_commentsB_RET[$studid][1]['NOX'])) {
                    $nox=1;
                    $sql = "UPDATE STUDENT_REPORT_CARD_COMMENTS SET REPORT_CARD_COMMENT_ID='{$updxa}' WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}' AND
            nox='{$nox}'";

                } else {
                    $sql = "UPDATE STUDENT_REPORT_CARD_COMMENTS SET REPORT_CARD_COMMENT_ID='{$updxa}' WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}'";
                }
                DBQuery($sql);

            } elseif (isset($current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID']) AND empty($_POST['commentsBx'][$studid][1]) AND array_key_exists(1,$_POST['commentsBx'][$studid])) {
                $delex = $current_commentsB_RET[$studid][1]['REPORT_CARD_COMMENT_ID'];
                $nox = 1;
                if (!empty($current_commentsB_RET[$studid][1]['NOX'])) {
                    $sql = "DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}' AND
            nox = '{$nox}'";
                } else {
                    $sql = "DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}'";

                }
                DBQuery($sql);
            } elseif ($current_commentsB_RET[$studid][1]['NOX'] != '1' AND ($_POST['commentsBx'][$studid][1] != 'N/A' AND !empty($_POST['commentsBx'][$studid][1]))) {
                $nox = 1;
                $sql = "INSERT INTO STUDENT_REPORT_CARD_COMMENTS (report_card_comment_id,syear,school_id,student_id,course_period_id,marking_period_id,nox) VALUES
            ('{$updxa}',{$syr},{$usrsc},{$studid},{$course_period_id},{$mlxx},{$nox})";
                DBQuery($sql);
            }


            $updxa = $_POST['commentsBx'][$studid][2];
            $delex = $current_commentsB_RET[$studid][2]['REPORT_CARD_COMMENT_ID'];
            $nox = 2;

            if ($_POST['commentsBx'][$studid][2] != $current_commentsB_RET[$studid][2]['REPORT_CARD_COMMENT_ID'] AND !empty($_POST['commentsBx'][$studid][2])  AND $_POST['commentsBx'][$studid][2] != 'N/A'   AND ($current_commentsB_RET[$studid][2]['NOX']=='2' OR $current_commentsB_RET[$studid][1]['NOX']=='2' )) {
                if (!empty($current_commentsB_RET[$studid][2]['NOX'])) {
                $nox=2;
                    $sql = "UPDATE STUDENT_REPORT_CARD_COMMENTS SET REPORT_CARD_COMMENT_ID='{$updxa}' WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}' AND 
            nox = '{$nox}'";
                    DBQuery($sql);
                } else {

                    $sql = "UPDATE STUDENT_REPORT_CARD_COMMENTS SET REPORT_CARD_COMMENT_ID='{$updxa}' WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}'";
                    DBQuery($sql);
                }

            } elseif (isset($current_commentsB_RET[$studid][2]['REPORT_CARD_COMMENT_ID']) AND empty($_POST['commentsBx'][$studid][2]) AND array_key_exists(2,$_POST['commentsBx'][$studid])) {

                $delex = $current_commentsB_RET[$studid][2]['REPORT_CARD_COMMENT_ID'];
                $nox = 2;
                if (!empty($current_commentsB_RET[$studid][2]['NOX'])) {
                    $sql = "DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}' AND
            nox = '{$nox}'";
                } else {
                    $sql = "DELETE FROM STUDENT_REPORT_CARD_COMMENTS WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mlxx}' AND 
            report_card_comment_id = '{$delex}'";

                }
                DBQuery($sql);
            } elseif ($current_commentsB_RET[$studid][1]['NOX'] != '2' AND ($_POST['commentsBx'][$studid][2] != 'N/A' AND !empty($_POST['commentsBx'][$studid][2]))) {
                $nox = 2;
                $sql = "INSERT INTO STUDENT_REPORT_CARD_COMMENTS (report_card_comment_id,syear,school_id,student_id,course_period_id,marking_period_id,nox) VALUES
            ('{$updxa}',{$syr},{$usrsc},{$studid},{$course_period_id},{$mlxx},{$nox})";
                DBQuery($sql);
            }


        }

    }
}
if ( ! empty( $_REQUEST['student_id'] ) )
{
	if ( $_REQUEST['student_id'] !== UserStudentID() )
	{
		SetUserStudentID( $_REQUEST['student_id'] );

		//FJ bugfix SQL bug course period
		/*if ( $_REQUEST['period'] && $_REQUEST['period']!=UserCoursePeriod())
		$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/

		if ( ! empty( $_REQUEST['period'] ) )
		{
			list( $CoursePeriod, $CoursePeriodSchoolPeriod ) = explode( '.', $_REQUEST['period'] );

			if ( $CoursePeriod != UserCoursePeriod() )
			{
				$_SESSION['UserCoursePeriod'] = $CoursePeriod;
			}
		}
	}
}
elseif ( UserStudentID() )
{
	unset( $_SESSION['student_id'] );
	//FJ bugfix SQL bug course period
	/*if ( $_REQUEST['period'] && $_REQUEST['period']!=UserCoursePeriod())
	$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/

	if ( ! empty( $_REQUEST['period'] ) )
	{
		list( $CoursePeriod, $CoursePeriodSchoolPeriod ) = explode( '.', $_REQUEST['period'] );

		if ( $CoursePeriod != UserCoursePeriod() )
		{
			$_SESSION['UserCoursePeriod'] = $CoursePeriod;
		}
	}
}

if ( ! empty( $_REQUEST['period'] ) )
{
	//FJ bugfix SQL bug course period
	/*if ( $_REQUEST['period']!=UserCoursePeriod())
	{
	$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];*/
	list( $CoursePeriod, $CoursePeriodSchoolPeriod ) = explode( '.', $_REQUEST['period'] );

	if ( $CoursePeriod != UserCoursePeriod() )
	{
		$_SESSION['UserCoursePeriod'] = $CoursePeriod;

		if ( ! empty( $_REQUEST['student_id'] ) )
		{
			if ( $_REQUEST['student_id'] != UserStudentID() )
			{
				SetUserStudentID( $_REQUEST['student_id'] );
			}
		}
		else
		{
			unset( $_SESSION['student_id'] );
		}
	}
}

$types_RET = DBGet( "SELECT ASSIGNMENT_TYPE_ID,TITLE,FINAL_GRADE_PERCENT,COLOR
FROM GRADEBOOK_ASSIGNMENT_TYPES gt
WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
AND COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND (SELECT count(1) FROM GRADEBOOK_ASSIGNMENTS WHERE STAFF_ID=gt.STAFF_ID
AND ((COURSE_ID=gt.COURSE_ID AND STAFF_ID=gt.STAFF_ID) OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND MARKING_PERIOD_ID='" . UserMP() . "'
AND ASSIGNMENT_TYPE_ID=gt.ASSIGNMENT_TYPE_ID)>0
ORDER BY SORT_ORDER,TITLE", array(), array( 'ASSIGNMENT_TYPE_ID' ) );
//echo '<pre>'; var_dump($types_RET); echo '</pre>';

if ( $_REQUEST['type_id']
	&& ! $types_RET[$_REQUEST['type_id']] )
{
	// Unset type ID & redirect URL.
	RedirectURL( 'type_id' );
}

//FJ default points
$assignments_RET = DBGet( "SELECT ASSIGNMENT_ID,ASSIGNMENT_TYPE_ID,TITLE,POINTS,ASSIGNED_DATE,DUE_DATE,DEFAULT_POINTS,extract(EPOCH FROM DUE_DATE) AS DUE_EPOCH,
CASE WHEN (ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ASSIGNED_DATE) AND (DUE_DATE IS NULL OR CURRENT_DATE>=DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=gradebook_assignments.MARKING_PERIOD_ID) THEN 'Y' ELSE NULL END AS DUE
FROM GRADEBOOK_ASSIGNMENTS
WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
AND ((COURSE_ID=(SELECT COURSE_ID FROM COURSE_PERIODS WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "') AND STAFF_ID='" . User( 'STAFF_ID' ) . "') OR COURSE_PERIOD_ID='" . UserCoursePeriod() . "')
AND MARKING_PERIOD_ID='" . UserMP() . "'" . ( $_REQUEST['type_id'] ? "
AND ASSIGNMENT_TYPE_ID='" . $_REQUEST['type_id'] . "'" : '' ) . "
ORDER BY " . Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) . " DESC,ASSIGNMENT_ID DESC,TITLE", array(), array( 'ASSIGNMENT_ID' ) );
//echo '<pre>'; var_dump($assignments_RET); echo '</pre>';

// when changing course periods the assignment_id will be wrong except for '' (totals) and 'all'

if ( $_REQUEST['assignment_id']
	&& $_REQUEST['assignment_id'] !== 'all'
	&& ! $assignments_RET[$_REQUEST['assignment_id']] )
{
	// Unset assignment ID & redirect URL.
	RedirectURL( 'assignment_id' );
}

//else
//	$_REQUEST['type_id'] = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID'];

if ( UserStudentID()
	&& ! $_REQUEST['assignment_id'] )
{
	$_REQUEST['assignment_id'] = 'all';
}
$current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
	g.REPORT_CARD_COMMENT_ID,g.COMMENT
	FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp
	WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
	AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );

$current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

$grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );

$categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER
	FROM REPORT_CARD_COMMENT_CATEGORIES rc
	WHERE rc.COURSE_ID='" . $course_id . "'
	AND (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE COURSE_ID=rc.COURSE_ID
		AND CATEGORY_ID=rc.ID)>0
	UNION
	SELECT 0,'" . DBEscapeString( _( 'All Courses' ) ) . "',NULL,2,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID='0'
		AND SYEAR='" . UserSyear() . "')>0
	UNION
	SELECT -1,'" . DBEscapeString( _( 'General' ) ) . "',NULL,3,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID IS NULL
		AND SYEAR='" . UserSyear() . "')>0
	ORDER BY 4,SORT_ORDER", array(), array( 'ID' ) );
if(isset($_REQUEST['exam'])){

    if(1==1 and empty($_REQUEST['assignment_id'])) {

        $_REQUEST['mp'] = UserMP();

        $course_period_id = UserCoursePeriod();

        //set input final grades for student x // an4rei


        if ( ! empty( $_REQUEST['mp'] ) )
        {


            $gradebook_config = ProgramUserConfig( 'Gradebook' );

            $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );

            require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'QTR' || GetMP( $_REQUEST['mp'], 'MP' ) == 'PRO' )
            {
                // Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
                // as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
                $extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS='0' THEN '0'  ELSE gg.POINTS END) AS PARTIAL_POINTS,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS IS NULL THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";
                $usermp=UserStudentID();
                $extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON
				((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						OR ga.COURSE_ID=cp.COURSE_ID
						AND ga.STAFF_ID=cp.TEACHER_ID)
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN GRADEBOOK_GRADES gg ON
				(gg.STUDENT_ID=s.STUDENT_ID
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";

                // Check Current date.
                $extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
				AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
					OR CURRENT_DATE>(SELECT END_DATE
						FROM SCHOOL_MARKING_PERIODS
						WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";

                // Check Student enrollment.
                $extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
				OR ga.DUE_DATE IS NULL
				OR ((ga.DUE_DATE>=ss.START_DATE
					AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
				AND (ga.DUE_DATE>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";


                if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'PRO' )
                {
                    // FJ: limit Assignments to the ones due during the Progress Period.
                    $extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
					FROM SCHOOL_MARKING_PERIODS
					WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL
						OR (SELECT END_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.DUE_DATE
						AND (SELECT START_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')<=ga.DUE_DATE))";
                }

                $extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

                $extra['group'] = array( 'STUDENT_ID' );

                $points_RET = GetStuList( $extra );
                //  echo '<pre>'; var_dump($points_RET); echo '</pre>';

                unset( $extra );

                if ( ! empty( $points_RET ) )
                {
                    foreach ( (array) $points_RET as $student_id => $student )
                    {
                        $total = $total_percent = 0;


                        foreach ( (array) $student as $partial_points )
                        {
                            if ( $partial_points['PARTIAL_TOTAL'] != 0
                                || $gradebook_config['WEIGHT'] != 'Y' )
                            {
                                $total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config['WEIGHT'] == 'Y' ?
                                        $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
                                        1
                                    );

                                $total_percent += ( $gradebook_config['WEIGHT'] == 'Y' ?
                                    $partial_points['FINAL_GRADE_PERCENT'] :
                                    $partial_points['PARTIAL_TOTAL']
                                );
                            }
                        }

                        if ( $total_percent != 0 )
                        {
                            $total /= $total_percent;
                        }

                        $import_RET[$student_id] = array(
                            1 => array(
                                'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $course_period_id, 0, 'ID' ),
                                'GRADE_PERCENT' => round( 100 * $total ),
                            ),
                        );
                    }
                }
            }
            elseif ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' || GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' )
            {
                if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' )
                {
                    $mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='QTR'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                    $prefix = 'SEM-';
                }
                else
                {
                    $mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS q,SCHOOL_MARKING_PERIODS s
				WHERE q.MP='QTR'
				AND s.MP='SEM'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID
				AND s.PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='FY'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                    $prefix = 'FY-';
                }

                $mps = '';

                foreach ( (array) $mp_RET as $mp )
                {
                    if ( $mp['DOES_GRADES'] === 'Y' )
                    {
                        $mps .= "'" . $mp['MARKING_PERIOD_ID'] . "',";
                    }
                }

                $mps = mb_substr( $mps, 0, -1 );

                $percents_RET = DBGet( "SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID
				FROM STUDENT_REPORT_CARD_GRADES
				WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
				AND MARKING_PERIOD_ID IN (" . $mps . ")", array(), array( 'STUDENT_ID' ) );

                foreach ( (array) $percents_RET as $student_id => $percents )
                {
                    $total = $total_percent = 0;

                    foreach ( (array) $percents as $percent )
                    {
                        $total += $percent['GRADE_PERCENT'] * $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];

                        $total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                    }

                    if ( $total_percent != 0 )
                    {
                        $total /= $total_percent;
                    }

                    $import_RET[$student_id] = array(
                        1 => array(
                            'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $course_period_id, 0, 'ID' ),
                            'GRADE_PERCENT' => round( $total ),
                        ),
                    );

                    // FJ automatic comment on yearly grades.

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY' )
                    {
                        // FJ use Report Card Grades comments.
                        $comment = _makeLetterGrade( $total / 100, $course_period_id, 0, 'COMMENT' );
                        $import_comments_RET[$student_id][1]['COMMENT'] = $comment;
                    }
                }
            }
        }
        //qtr




        foreach($import_RET as $trox => $student){

            $_POST['values2'][$trox]=array('grade'=> $import_RET[$trox][1]['REPORT_CARD_GRADE_ID'],
                'percent'=>$import_RET[$trox][1]['GRADE_PERCENT']);
        }

        $_POST['values2']= array_filter($_POST['values2']);



        $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

        if ( $_POST['values2'])
        {
            require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
            require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

            $completed = true;

            //FJ add precision to year weighted GPA if not year course period.
            $course_period_mp = DBGetOne( "SELECT MP
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );


            foreach ( (array) $_POST['values2'] as $student_id => $columns )
            {
                $sql = $sep = '';
                $testx= DBGetOne( "SELECT STUDENT_ID
		FROM STUDENT_REPORT_CARD_GRADES
		WHERE COURSE_PERIOD_ID='" . $course_period_id . "' AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "' AND STUDENT_ID=".$student_id." AND REPORT_CARD_GRADE_ID=".$columns['grade'] );


                if ( $current_RET[$student_id] and !empty($testx))
                {



                    if ( $columns['percent'] != ''  )
                    {



                        // FJ bugfix SQL error invalid input syntax for type numeric.
                        $percent = trim( $columns['percent'], '%' );

                        if ( ! is_numeric( $percent ) )
                        {
                            $percent = (float) $percent;
                        }

                        if ( $percent > 999.9 )
                        {
                            $percent = '999.9';
                        }
                        elseif ( $percent < 0 )
                        {
                            $percent = '0';
                        }

                        if ( $columns['grade']
                            || $percent != '' )
                        {
                            $grade = ( $columns['grade'] ?
                                $columns['grade'] :
                                _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' )
                            );

                            $letter = $grades_RET[$grade][1]['TITLE'];
                            $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                            $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                            // FJ add precision to year weighted GPA if not year course period.

                            if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                                && $course_period_mp !== 'FY' )
                            {
                                $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                            }

                            $scale = $grades_RET[$grade][1]['GP_SCALE'];

                            $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                        }
                        else
                        {
                            $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                        }


                        $sql .= "GRADE_PERCENT='" . $percent . "'";
                        $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                            "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                            "',GP_SCALE='" . $scale . "'";

                        // bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                        $sep = ',';
                    }
                    elseif ( $columns['grade'] )
                    {

                        $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                        $grade = $columns['grade'];
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                        // FJ add precision to year weighted GPA if not year course period.

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                            && $course_period_mp !== 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

                        $sql .= "GRADE_PERCENT='" . $percent . "'";
                        $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                            "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                            "',GP_SCALE='" . $scale . "'";
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                        $sep = ',';
                    }
                    elseif ( isset( $columns['percent'] )
                        || isset( $columns['grade'] ) )
                    {

                        $percent = $grade = '';
                        $sql .= "GRADE_PERCENT=NULL";
                        // FJ bugfix SQL bug 'NULL' instead of NULL.
                        //$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP='NULL',UNWEIGHTED_GP='NULL',GP_SCALE='NULL'";
                        $sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP=NULL,
					UNWEIGHTED_GP=NULL,GP_SCALE=NULL";
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='0'";
                        $sep = ',';
                    }
                    else
                    {
                        $percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
                        $grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
                    }

                    if ( isset( $columns['comment'] ) )
                    {
                        $sql .= $sep . "COMMENT='" . $columns['comment'] . "'";
                    }

                    if ( $sql )
                    {

                        // Reset Class Rank based on current CP Does Class Rank parameter.
                        $sql .= ",CLASS_RANK='" . $course_RET[1]['CLASS_RANK'] . "'";

                        $sql = "UPDATE STUDENT_REPORT_CARD_GRADES
					SET " . $sql . "
					WHERE STUDENT_ID='" . $student_id . "'
					AND COURSE_PERIOD_ID='" . $course_period_id . "'
					AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'";

                    }
                }
                elseif ( $columns['percent'] != ''
                    || $columns['grade']
                    || $columns['comment']  AND empty($testx))
                {

                    if ( $columns['percent'] != '' )
                    {
                        // FJ bugfix SQL error invalid input syntax for type numeric.
                        $percent = trim( $columns['percent'], '%' );

                        if ( ! is_numeric( $percent ) )
                        {
                            $percent = (float) $percent;
                        }

                        if ( $percent > 999.9 )
                        {
                            $percent = '999.9';
                        }
                        elseif ( $percent < 0 )
                        {
                            $percent = '0';
                        }

                        if ( $columns['grade'] || $percent != '' )
                        {
                            $grade = ( $columns['grade'] ? $columns['grade'] : _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' ) );
                            $letter = $grades_RET[$grade][1]['TITLE'];
                            $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                            $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                            //FJ add precision to year weighted GPA if not year course period

                            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                            {
                                $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                            }

                            $scale = $grades_RET[$grade][1]['GP_SCALE'];

                            $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                        }
                        else
                        {
                            $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                        }
                    }
                    elseif ( $columns['grade'] )
                    {
                        $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                        $grade = $columns['grade'];
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                        //FJ add precision to year weighted GPA if not year course period

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $percent = $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }

                    //FJ fix bug SQL ID=NULL
                    //FJ add CLASS_RANK
                    //FJ add Credit Hours

                    $sql = "INSERT INTO STUDENT_REPORT_CARD_GRADES (
				ID,
				SYEAR,
				SCHOOL_ID,
				STUDENT_ID,
				COURSE_PERIOD_ID,
				MARKING_PERIOD_ID,
				REPORT_CARD_GRADE_ID,
				GRADE_PERCENT,
				COMMENT,
				GRADE_LETTER,
				WEIGHTED_GP,
				UNWEIGHTED_GP,
				GP_SCALE,
				COURSE_TITLE,
				CREDIT_ATTEMPTED,
				CREDIT_EARNED,
				CLASS_RANK,
				CREDIT_HOURS
			) values (
				" . db_seq_nextval( 'student_report_card_grades_seq' ) . ",'" .
                        UserSyear() . "','" .
                        UserSchool() . "','" .
                        $student_id . "','" .
                        $course_period_id . "','" .
                        $_REQUEST['mp'] . "','" .
                        $grade . "','" .
                        $percent . "','" .
                        $columns['comment'] . "','" .
                        $grades_RET[$grade][1]['TITLE'] . "','" .
                        $weighted . "','" .
                        $unweighted . "','" .
                        $scale . "','" .
                        DBEscapeString( $course_RET[1]['COURSE_NAME'] ) . "','" .
                        $course_RET[1]['CREDITS'] . "','" .
                        ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "','" .
                        $course_RET[1]['CLASS_RANK'] . "'," .
                        ( is_null( $course_RET[1]['CREDIT_HOURS'] ) ? 'NULL' : $course_RET[1]['CREDIT_HOURS'] ) .
                        ")";

                }
                else
                {
                    $percent = $grade = '';
                }

                if ( $sql )
                {
                    DBQuery( $sql );
                }

                //DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");

                if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
                {
                    $completed = (bool) $grade;
                }
                elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
                {
                    $completed = $percent != '';
                }
                else
                {
                    $completed = $percent != '' && $grade;
                }

                /*if ( !( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? $grade : ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? $percent != '' : $percent != '' && $grade ) ) )
                $completed = false;*/

            }

            // @since 4.7 Automatic Class Rank calculation.
            ClassRankCalculateAddMP( $_REQUEST['mp'] );


            if ( $completed )
            {
                if ( ! $current_completed )
                {
                    DBQuery( "INSERT INTO GRADES_COMPLETED (STAFF_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID)
				values('" . User( 'STAFF_ID' ) . "','" . $_REQUEST['mp'] . "','" . $course_period_id . "')" );
                }
            }
            elseif ( $current_completed )
            {
                DBQuery( "DELETE FROM GRADES_COMPLETED
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
			AND COURSE_PERIOD_ID='" . $course_period_id . "'" );
            }

            $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
		g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM STUDENT_REPORT_CARD_GRADES g
		WHERE g.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );



            $current_completed = count( (array) DBGet( "SELECT 1
		FROM GRADES_COMPLETED
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
		AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

            // Unset values & redirect URL.
            // RedirectURL( 'values' );
        }



        $parentsem = DBGet( "SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP(). "'", array(), array( ));
        unset($_REQUEST['mp']);
        $_REQUEST['mp']=$parentsem[1]['PARENT_ID'];
        //for parent semester
        if ( ! empty( $_REQUEST['mp'] ) )
        {


            $gradebook_config = ProgramUserConfig( 'Gradebook' );

            $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );

            require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'QTR' || GetMP( $_REQUEST['mp'], 'MP' ) == 'PRO' )
            {
                // Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
                // as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
                $extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS='0' THEN '0'  ELSE gg.POINTS END) AS PARTIAL_POINTS,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS IS NULL THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";
                $usermp=UserStudentID();
                $extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON
				((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						OR ga.COURSE_ID=cp.COURSE_ID
						AND ga.STAFF_ID=cp.TEACHER_ID)
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN GRADEBOOK_GRADES gg ON
				(gg.STUDENT_ID=s.STUDENT_ID
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";

                // Check Current date.
                $extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
				AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
					OR CURRENT_DATE>(SELECT END_DATE
						FROM SCHOOL_MARKING_PERIODS
						WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) AND (s.STUDENT_ID ={$usermp})";

                // Check Student enrollment.
                $extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
				OR ga.DUE_DATE IS NULL
				OR ((ga.DUE_DATE>=ss.START_DATE
					AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
				AND (ga.DUE_DATE>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";


                if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'PRO' )
                {
                    // FJ: limit Assignments to the ones due during the Progress Period.
                    $extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
					FROM SCHOOL_MARKING_PERIODS
					WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL
						OR (SELECT END_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.DUE_DATE
						AND (SELECT START_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')<=ga.DUE_DATE))";
                }

                $extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

                $extra['group'] = array( 'STUDENT_ID' );

                $points_RET = GetStuList( $extra );
                //  echo '<pre>'; var_dump($points_RET); echo '</pre>';

                unset( $extra );

                if ( ! empty( $points_RET ) )
                {
                    foreach ( (array) $points_RET as $student_id => $student )
                    {
                        $total = $total_percent = 0;


                        foreach ( (array) $student as $partial_points )
                        {
                            if ( $partial_points['PARTIAL_TOTAL'] != 0
                                || $gradebook_config['WEIGHT'] != 'Y' )
                            {
                                $total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config['WEIGHT'] == 'Y' ?
                                        $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
                                        1
                                    );

                                $total_percent += ( $gradebook_config['WEIGHT'] == 'Y' ?
                                    $partial_points['FINAL_GRADE_PERCENT'] :
                                    $partial_points['PARTIAL_TOTAL']
                                );
                            }
                        }

                        if ( $total_percent != 0 )
                        {
                            $total /= $total_percent;
                        }

                        $import_RET2[$student_id] = array(
                            1 => array(
                                'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $course_period_id, 0, 'ID' ),
                                'GRADE_PERCENT' => round( 100 * $total),
                            ),
                        );
                    }
                }
            }
            elseif ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' || GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' )
            {
                if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' )
                {
                    $mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='QTR'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                    $prefix = 'SEM-';
                }
                else
                {
                    $mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS q,SCHOOL_MARKING_PERIODS s
				WHERE q.MP='QTR'
				AND s.MP='SEM'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID
				AND s.PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='FY'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                    $prefix = 'FY-';
                }

                $mpeuri=array();
                foreach ( (array) $mp_RET as $mp )
                {
                    if ( $mp['DOES_GRADES'] === 'Y' )
                    {
                        $mps .= "'" . $mp['MARKING_PERIOD_ID'] . "',";
                        $mpeuri[]=$mp['MARKING_PERIOD_ID'];
                    }
                }

                $mps = mb_substr( $mps, 0, -1 );

                $_GET['mp']= $mp_RET[1]['MARKING_PERIOD_ID'];
                $doex = DBGet( "SELECT does_exam
				FROM school_marking_periods WHERE marking_period_id=".$_GET['mp']);
                if(isset($_POST['exam'])) {

                    $course_period_id = UserCoursePeriod();

                    foreach ($_POST['exam'] as $studid => $notaexamen) {

                        $sql = "UPDATE student_report_card_grades SET exam='{$notaexamen}' 
            WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$studid}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mkp}' ";
                        DBQuery($sql);

                    }

                }
                $percents_RET = DBGet( "SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID,EXAM
				FROM STUDENT_REPORT_CARD_GRADES
				WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
				AND MARKING_PERIOD_ID IN (" . $mps . ")", array(), array( 'STUDENT_ID' ) );

                $numar_qtr=count($mps);

                foreach ( (array) $percents_RET as $student_id => $percents )
                {
                    $total = $total_percent = 0;

                    foreach ( (array) $percents as $percent )
                    {
                        /*
                        $total += $percent['GRADE_PERCENT'] * $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                        $total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                    */
                        $total = $total_percent = 0;


                        if(!empty($percent['EXAM'])) {

                            $total += $gradebook_config[$prefix . 'E' . $_GET['mp']] * ($percent['EXAM'] / 100);
                            $total+=$gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']] *($percent['GRADE_PERCENT']/100);
                            // echo 'aici-.'.$total.'<br>';
                        }else{
                            //$gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']]= 100;
                            $total+=100 *($percent['GRADE_PERCENT']/100);
                            //  echo 'aici2-.'.$total.'<br>';

                        }

//                    $total+=$gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']] *($percent['GRADE_PERCENT']/100);






                    }

                    if ( $total_percent != 0 )
                    {
                        $total /= $total_percent;
                    }


                    $import_RET2[$student_id] = array(
                        1 => array(
                            'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $course_period_id, 0, 'ID' ),
                            'GRADE_PERCENT' => round( $total ),
                        ),
                    );



                    // FJ automatic comment on yearly grades.

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY' )
                    {
                        // FJ use Report Card Grades comments.
                        $comment = _makeLetterGrade( $total / 100, $course_period_id, 0, 'COMMENT' );
                        $import_comments_RET[$student_id][1]['COMMENT'] = $comment;
                    }
                }
            }
        }

        //sem
        $course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
	cp.GRADE_SCALE_ID, credit(CAST(" . $course_period_id . " AS integer),
	CAST( '" . UserMP() . "' AS character varying)) AS CREDITS,
	DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
	FROM COURSE_PERIODS cp,COURSES c
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'" );
        $course_title = $course_RET[1]['TITLE'];
        $grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
        $course_id = $course_RET[1]['COURSE_ID'];
        $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );
        $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
	g.REPORT_CARD_COMMENT_ID,g.COMMENT
	FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp
	WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
	AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );

        $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

        $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );

        $categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER
	FROM REPORT_CARD_COMMENT_CATEGORIES rc
	WHERE rc.COURSE_ID='" . $course_id . "'
	AND (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE COURSE_ID=rc.COURSE_ID
		AND CATEGORY_ID=rc.ID)>0
	UNION
	SELECT 0,'" . DBEscapeString( _( 'All Courses' ) ) . "',NULL,2,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID='0'
		AND SYEAR='" . UserSyear() . "')>0
	UNION
	SELECT -1,'" . DBEscapeString( _( 'General' ) ) . "',NULL,3,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID IS NULL
		AND SYEAR='" . UserSyear() . "')>0
	ORDER BY 4,SORT_ORDER", array(), array( 'ID' ) );



        foreach($import_RET2 as $trox => $student){
            $_POST['values3'][$trox]=array('grade'=> $import_RET2[$trox][1]['REPORT_CARD_GRADE_ID'],
                'percent'=>$import_RET2[$trox][1]['GRADE_PERCENT']);
        }
        $_POST['values3']=array_filter($_POST['values3']);



        $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

        if ( $_POST['values3'])
        {
            require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
            require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

            $completed = true;

            //FJ add precision to year weighted GPA if not year course period.
            $course_period_mp = DBGetOne( "SELECT MP
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );


            foreach ( (array) $_POST['values3'] as $student_id => $columns )
            {
                $sql = $sep = '';

                if ( $current_RET[$student_id] )
                {

                    if ( $columns['percent'] != '' )
                    {
                        // FJ bugfix SQL error invalid input syntax for type numeric.
                        $percent = trim( $columns['percent'], '%' );

                        if ( ! is_numeric( $percent ) )
                        {
                            $percent = (float) $percent;
                        }

                        if ( $percent > 999.9 )
                        {
                            $percent = '999.9';
                        }
                        elseif ( $percent < 0 )
                        {
                            $percent = '0';
                        }

                        if ( $columns['grade']
                            || $percent != '' )
                        {
                            $grade = ( $columns['grade'] ?
                                $columns['grade'] :
                                _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' )
                            );

                            $letter = $grades_RET[$grade][1]['TITLE'];
                            $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                            $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                            // FJ add precision to year weighted GPA if not year course period.

                            if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                                && $course_period_mp !== 'FY' )
                            {
                                $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                            }

                            $scale = $grades_RET[$grade][1]['GP_SCALE'];

                            $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                        }
                        else
                        {
                            $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                        }

                        // echo '<br>'.$percent;
                        $sql .= "GRADE_PERCENT='" . $percent . "'";
                        $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                            "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                            "',GP_SCALE='" . $scale . "'";

                        // bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                        $sep = ',';
                    }
                    elseif ( $columns['grade'] )
                    {
                        $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                        $grade = $columns['grade'];
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                        // FJ add precision to year weighted GPA if not year course period.

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                            && $course_period_mp !== 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

                        $sql .= "GRADE_PERCENT='" . $percent . "'";
                        $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                            "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                            "',GP_SCALE='" . $scale . "'";
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                        $sep = ',';
                    }
                    elseif ( isset( $columns['percent'] )
                        || isset( $columns['grade'] ) )
                    {
                        $percent = $grade = '';
                        $sql .= "GRADE_PERCENT=NULL";
                        // FJ bugfix SQL bug 'NULL' instead of NULL.
                        //$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP='NULL',UNWEIGHTED_GP='NULL',GP_SCALE='NULL'";
                        $sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP=NULL,
					UNWEIGHTED_GP=NULL,GP_SCALE=NULL";
                        $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                        $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                        $sql .= ",CREDIT_EARNED='0'";
                        $sep = ',';
                    }
                    else
                    {
                        $percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
                        $grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
                    }

                    if ( isset( $columns['comment'] ) )
                    {
                        $sql .= $sep . "COMMENT='" . $columns['comment'] . "'";
                    }

                    if ( $sql )
                    {
                        // Reset Class Rank based on current CP Does Class Rank parameter.
                        $sql .= ",CLASS_RANK='" . $course_RET[1]['CLASS_RANK'] . "'";

                        $sql = "UPDATE STUDENT_REPORT_CARD_GRADES
					SET " . $sql . "
					WHERE STUDENT_ID='" . $student_id . "'
					AND COURSE_PERIOD_ID='" . $course_period_id . "'
					AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'";
                    }
                }
                elseif ( $columns['percent'] != ''
                    || $columns['grade']
                    || $columns['comment'] )
                {
                    if ( $columns['percent'] != '' )
                    {
                        // FJ bugfix SQL error invalid input syntax for type numeric.
                        $percent = trim( $columns['percent'], '%' );

                        if ( ! is_numeric( $percent ) )
                        {
                            $percent = (float) $percent;
                        }

                        if ( $percent > 999.9 )
                        {
                            $percent = '999.9';
                        }
                        elseif ( $percent < 0 )
                        {
                            $percent = '0';
                        }

                        if ( $columns['grade'] || $percent != '' )
                        {
                            $grade = ( $columns['grade'] ? $columns['grade'] : _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' ) );
                            $letter = $grades_RET[$grade][1]['TITLE'];
                            $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                            $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                            //FJ add precision to year weighted GPA if not year course period

                            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                            {
                                $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                            }

                            $scale = $grades_RET[$grade][1]['GP_SCALE'];

                            $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                        }
                        else
                        {
                            $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                        }
                    }
                    elseif ( $columns['grade'] )
                    {
                        $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                        $grade = $columns['grade'];
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                        //FJ add precision to year weighted GPA if not year course period

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $percent = $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }

                    //FJ fix bug SQL ID=NULL
                    //FJ add CLASS_RANK
                    //FJ add Credit Hours

                    $sql = "INSERT INTO STUDENT_REPORT_CARD_GRADES (
				ID,
				SYEAR,
				SCHOOL_ID,
				STUDENT_ID,
				COURSE_PERIOD_ID,
				MARKING_PERIOD_ID,
				REPORT_CARD_GRADE_ID,
				GRADE_PERCENT,
				COMMENT,
				GRADE_LETTER,
				WEIGHTED_GP,
				UNWEIGHTED_GP,
				GP_SCALE,
				COURSE_TITLE,
				CREDIT_ATTEMPTED,
				CREDIT_EARNED,
				CLASS_RANK,
				CREDIT_HOURS
			) values (
				" . db_seq_nextval( 'student_report_card_grades_seq' ) . ",'" .
                        UserSyear() . "','" .
                        UserSchool() . "','" .
                        $student_id . "','" .
                        $course_period_id . "','" .
                        $_REQUEST['mp'] . "','" .
                        $grade . "','" .
                        $percent . "','" .
                        $columns['comment'] . "','" .
                        $grades_RET[$grade][1]['TITLE'] . "','" .
                        $weighted . "','" .
                        $unweighted . "','" .
                        $scale . "','" .
                        DBEscapeString( $course_RET[1]['COURSE_NAME'] ) . "','" .
                        $course_RET[1]['CREDITS'] . "','" .
                        ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "','" .
                        $course_RET[1]['CLASS_RANK'] . "'," .
                        ( is_null( $course_RET[1]['CREDIT_HOURS'] ) ? 'NULL' : $course_RET[1]['CREDIT_HOURS'] ) .
                        ")";
                }
                else
                {
                    $percent = $grade = '';
                }

                if ( $sql )
                {
                    DBQuery( $sql );
                }

                //DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");

                if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
                {
                    $completed = (bool) $grade;
                }
                elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
                {
                    $completed = $percent != '';
                }
                else
                {
                    $completed = $percent != '' && $grade;
                }

                /*if ( !( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? $grade : ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? $percent != '' : $percent != '' && $grade ) ) )
                $completed = false;*/

            }

            // @since 4.7 Automatic Class Rank calculation.
            ClassRankCalculateAddMP( $_REQUEST['mp'] );

            if ( $completed )
            {
                if ( ! $current_completed )
                {
                    DBQuery( "INSERT INTO GRADES_COMPLETED (STAFF_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID)
				values('" . User( 'STAFF_ID' ) . "','" . $_REQUEST['mp'] . "','" . $course_period_id . "')" );
                }
            }
            elseif ( $current_completed )
            {
                DBQuery( "DELETE FROM GRADES_COMPLETED
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
			AND COURSE_PERIOD_ID='" . $course_period_id . "'" );
            }

            $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
		g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM STUDENT_REPORT_CARD_GRADES g
		WHERE g.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );



            $current_completed = count( (array) DBGet( "SELECT 1
		FROM GRADES_COMPLETED
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
		AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

            // Unset values & redirect URL.
            RedirectURL( 'exam' );
            RedirectURL( 'commentsBx' );
        }




        // Unset values & redirect URL.

        RedirectURL( 'exam' );
        RedirectURL( 'commentsBx' );

    }
}

if ( ! empty( $_REQUEST['values'] )
	&& ! empty( $_POST['values'] )
	// Fix use weak comparison "==" operator as $_SESSION['type_id'] maybe null.
	 && $_SESSION['type_id'] == $_REQUEST['type_id']
	&& $_SESSION['assignment_id'] == $_REQUEST['assignment_id'] )
{
	include 'ProgramFunctions/_makePercentGrade.fnc.php';

	if ( UserStudentID() )
	{
		$current_RET[UserStudentID()] = DBGet( "SELECT g.ASSIGNMENT_ID
			FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a
			WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
			AND a.MARKING_PERIOD_ID='" . UserMP() . "'
			AND g.STUDENT_ID='" . UserStudentID() . "'
			AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" .
			( $_REQUEST['assignment_id'] === 'all' ? '' :
				" AND g.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'" ),
			array(),
			array( 'ASSIGNMENT_ID' )
		);
	}
	elseif ( $_REQUEST['assignment_id'] === 'all' )
	{
		$current_RET = DBGet( "SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS
			FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a
			WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
			AND a.MARKING_PERIOD_ID='" . UserMP() . "'
			AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'",
			array(),
			array( 'STUDENT_ID', 'ASSIGNMENT_ID' )
		);
	}
	else
	{
		$current_RET = DBGet( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID
			FROM GRADEBOOK_GRADES
			WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'
			AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'",
			array(),
			array( 'STUDENT_ID', 'ASSIGNMENT_ID' )
		);
	}

	foreach ( (array) $_REQUEST['values'] as $student_id => $assignments )
	{
		foreach ( (array) $assignments as $assignment_id => $columns )
		{
			if ( $columns['POINTS'] )
			{
				if ( $columns['POINTS'] == '*' )
				{
					$columns['POINTS'] = '-1';
				}
				else
				{
					if ( mb_substr( $columns['POINTS'], -1 ) == '%' )
					{
						$columns['POINTS'] = mb_substr( $columns['POINTS'], 0, -1 ) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					}
					elseif ( ! is_numeric( $columns['POINTS'] ) )
					{
						$columns['POINTS'] = _makePercentGrade( $columns['POINTS'], UserCoursePeriod() ) * $assignments_RET[$assignment_id][1]['POINTS'] / 100;
					}

					if ( $columns['POINTS'] < 0 )
					{
						$columns['POINTS'] = '0';
					}
					elseif ( $columns['POINTS'] > 9999.99 )
					{
						$columns['POINTS'] = '9999.99';
					}
				}
			}

			$sql = '';
			
			////////////////////
			

			if ( !$columns['FILE'] )
			{		

				if ( isset( $_FILES["FILE".$student_id."_".$assignment_id] ) )
				{
					for($idx=0; $idx<count($_FILES["FILE".$student_id."_".$assignment_id]['name']); $idx++){
					
						$file_id = DBSeqNextID( 'GRADEBOOK_MARKED_ASSIGNMENT_FILES_SEQ' );

						$file = UploadMarkedAssignmentFile(
							$assignment_id, User( 'STAFF_ID' ), $student_id,"FILE".$student_id."_".$assignment_id, $idx,$file_id );

						if ( $file )
						{
							DBQuery( "INSERT INTO GRADEBOOK_MARKED_ASSIGNMENT_FILES (ASSIGNMENT_FILE_ID,STUDENT_ID,ASSIGNMENT_ID,FILE) values('" . $file_id . "','" . $student_id . "','" . $assignment_id . "','" .$file. "')");
							
						}
					}
				}
			
			}
			
			
			////////////////////

			if ( $current_RET[$student_id][$assignment_id] )
			{
				if(count($columns)>1){
					$sql = "UPDATE GRADEBOOK_GRADES SET ";

					foreach ( (array) $columns as $column => $value )
					{
						if(strtoupper($column)=="TMP") continue;
						$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
					}

					$sql = mb_substr( $sql, 0, -1 ) . " WHERE STUDENT_ID='" . $student_id . "' AND ASSIGNMENT_ID='" . $assignment_id . "' AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'";
				}
			}
			elseif ( $columns['POINTS'] != '' || $columns['COMMENT'] )
			{
				$sql = "INSERT INTO GRADEBOOK_GRADES (STUDENT_ID,PERIOD_ID,COURSE_PERIOD_ID,ASSIGNMENT_ID,POINTS,COMMENT) values('" . $student_id . "','" . UserPeriod() . "','" . UserCoursePeriod() . "','" . $assignment_id . "','" . $columns['POINTS'] . "','" . $columns['COMMENT'] . "')";
			}

			if ( $sql )
			{
				DBQuery( $sql );
			}
		}
	}




if(1==1 and !empty($_REQUEST['assignment_id']) ) {


    $_REQUEST['mp'] = UserMP();

    $course_period_id = UserCoursePeriod();

    //set input final grades for student x // an4rei
    $course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
	cp.GRADE_SCALE_ID, credit(CAST(" . $course_period_id . " AS integer),
	CAST( '" . UserMP() . "' AS character varying)) AS CREDITS,
	DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
	FROM COURSE_PERIODS cp,COURSES c
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'" );
    $course_title = $course_RET[1]['TITLE'];
    $grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
    $course_id = $course_RET[1]['COURSE_ID'];
    $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );
    $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
	g.REPORT_CARD_COMMENT_ID,g.COMMENT
	FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp
	WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
	AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );

    $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

    $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );

    $categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER
	FROM REPORT_CARD_COMMENT_CATEGORIES rc
	WHERE rc.COURSE_ID='" . $course_id . "'
	AND (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE COURSE_ID=rc.COURSE_ID
		AND CATEGORY_ID=rc.ID)>0
	UNION
	SELECT 0,'" . DBEscapeString( _( 'All Courses' ) ) . "',NULL,2,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID='0'
		AND SYEAR='" . UserSyear() . "')>0
	UNION
	SELECT -1,'" . DBEscapeString( _( 'General' ) ) . "',NULL,3,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID IS NULL
		AND SYEAR='" . UserSyear() . "')>0
	ORDER BY 4,SORT_ORDER", array(), array( 'ID' ) );

    if ( ! empty( $_REQUEST['mp'] ) )
    {


        $gradebook_config = ProgramUserConfig( 'Gradebook' );

        $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );

        require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'QTR' || GetMP( $_REQUEST['mp'], 'MP' ) == 'PRO' )
        {
            // Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
            // as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
            $extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS='0' THEN '0'  ELSE gg.POINTS END) AS PARTIAL_POINTS,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS IS NULL THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";
            $usermp=UserStudentID();
            $extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON
				((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						OR ga.COURSE_ID=cp.COURSE_ID
						AND ga.STAFF_ID=cp.TEACHER_ID)
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN GRADEBOOK_GRADES gg ON
				(gg.STUDENT_ID=s.STUDENT_ID
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";

            // Check Current date.
            $extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
				AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
					OR CURRENT_DATE>(SELECT END_DATE
						FROM SCHOOL_MARKING_PERIODS
						WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))";

            // Check Student enrollment.
            $extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
				OR ga.DUE_DATE IS NULL
				OR ((ga.DUE_DATE>=ss.START_DATE
					AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
				AND (ga.DUE_DATE>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";


            if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'PRO' )
            {
                // FJ: limit Assignments to the ones due during the Progress Period.
                $extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
					FROM SCHOOL_MARKING_PERIODS
					WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL
						OR (SELECT END_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.DUE_DATE
						AND (SELECT START_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')<=ga.DUE_DATE))";
            }

            $extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

            $extra['group'] = array( 'STUDENT_ID' );

            $points_RET = GetStuList( $extra );
            //  echo '<pre>'; var_dump($points_RET); echo '</pre>';

            unset( $extra );

            if ( ! empty( $points_RET ) )
            {
                foreach ( (array) $points_RET as $student_id => $student )
                {
                    $total = $total_percent = 0;


                    foreach ( (array) $student as $partial_points )
                    {
                        if ( $partial_points['PARTIAL_TOTAL'] != 0
                            || $gradebook_config['WEIGHT'] != 'Y' )
                        {
                            $total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config['WEIGHT'] == 'Y' ?
                                    $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
                                    1
                                );

                            $total_percent += ( $gradebook_config['WEIGHT'] == 'Y' ?
                                $partial_points['FINAL_GRADE_PERCENT'] :
                                $partial_points['PARTIAL_TOTAL']
                            );
                        }
                    }

                    if ( $total_percent != 0 )
                    {
                        $total /= $total_percent;
                    }

                    $import_RET[$student_id] = array(
                        1 => array(
                            'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $course_period_id, 0, 'ID' ),
                            'GRADE_PERCENT' => round( 100 * $total ),
                        ),
                    );
                }
            }
        }
        elseif ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' || GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' )
        {
            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' )
            {
                $mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='QTR'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                $prefix = 'SEM-';
            }
            else
            {
                $mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS q,SCHOOL_MARKING_PERIODS s
				WHERE q.MP='QTR'
				AND s.MP='SEM'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID
				AND s.PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='FY'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                $prefix = 'FY-';
            }

            $mps = '';

            foreach ( (array) $mp_RET as $mp )
            {
                if ( $mp['DOES_GRADES'] === 'Y' )
                {
                    $mps .= "'" . $mp['MARKING_PERIOD_ID'] . "',";
                }
            }

            $mps = mb_substr( $mps, 0, -1 );

            $percents_RET = DBGet( "SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID
				FROM STUDENT_REPORT_CARD_GRADES
				WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
				AND MARKING_PERIOD_ID IN (" . $mps . ")", array(), array( 'STUDENT_ID' ) );

            foreach ( (array) $percents_RET as $student_id => $percents )
            {
                $total = $total_percent = 0;

                foreach ( (array) $percents as $percent )
                {
                    $total += $percent['GRADE_PERCENT'] * $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];

                    $total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                }

                if ( $total_percent != 0 )
                {
                    $total /= $total_percent;
                }

                $import_RET[$student_id] = array(
                    1 => array(
                        'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $course_period_id, 0, 'ID' ),
                        'GRADE_PERCENT' => round( $total ),
                    ),
                );

                // FJ automatic comment on yearly grades.

                if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY' )
                {
                    // FJ use Report Card Grades comments.
                    $comment = _makeLetterGrade( $total / 100, $course_period_id, 0, 'COMMENT' );
                    $import_comments_RET[$student_id][1]['COMMENT'] = $comment;
                }
            }
        }
    }
    //qtr

    //  $_REQUEST['values2']=array(array_keys($import_RET)[$_GET['student_id']]=>array('grade'=> $import_RET[array_keys($import_RET)[$_GET['student_id']]][1]['REPORT_CARD_GRADE_ID'],'percent'=>$import_RET[array_keys($import_RET)[$_GET['student_id']]][1]['GRADE_PERCENT']));
    /* $_REQUEST['values2']=array($_GET['student_id']=>array('grade'=> $import_RET[$_GET['student_id']][1]['REPORT_CARD_GRADE_ID'],
         'percent'=>$import_RET[$_GET['student_id']][1]['GRADE_PERCENT'])); */

    foreach($import_RET as $trox => $student){

        $_POST['values2'][$trox]=array('grade'=> $import_RET[$trox][1]['REPORT_CARD_GRADE_ID'],
            'percent'=>$import_RET[$trox][1]['GRADE_PERCENT']);
    }


    $_POST['values2']= array_filter($_POST['values2']);


    $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

    if ( $_POST['values2'])
    {
        require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
        require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

        $completed = true;

        //FJ add precision to year weighted GPA if not year course period.
        $course_period_mp = DBGetOne( "SELECT MP
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );


        foreach ( (array) $_POST['values2'] as $student_id => $columns )
        {
            $sql = $sep = '';




            if ( $current_RET[$student_id] )
            {




                if ( $columns['percent'] != ''  )
                {



                    // FJ bugfix SQL error invalid input syntax for type numeric.
                    $percent = trim( $columns['percent'], '%' );

                    if ( ! is_numeric( $percent ) )
                    {
                        $percent = (float) $percent;
                    }

                    if ( $percent > 999.9 )
                    {
                        $percent = '999.9';
                    }
                    elseif ( $percent < 0 )
                    {
                        $percent = '0';
                    }

                    if ( $columns['grade']
                        || $percent != '' )
                    {
                        $grade = ( $columns['grade'] ?
                            $columns['grade'] :
                            _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' )
                        );

                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        // FJ add precision to year weighted GPA if not year course period.

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                            && $course_period_mp !== 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }


                    $sql .= "GRADE_PERCENT='" . $percent . "'";
                    $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                        "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                        "',GP_SCALE='" . $scale . "'";

                    // bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                    $sep = ',';
                }
                elseif ( $columns['grade'] )
                {

                    $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                    $grade = $columns['grade'];
                    $letter = $grades_RET[$grade][1]['TITLE'];
                    $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                    // FJ add precision to year weighted GPA if not year course period.

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                        && $course_period_mp !== 'FY' )
                    {
                        $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                    }

                    $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                    $scale = $grades_RET[$grade][1]['GP_SCALE'];

                    $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

                    $sql .= "GRADE_PERCENT='" . $percent . "'";
                    $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                        "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                        "',GP_SCALE='" . $scale . "'";
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                    $sep = ',';
                }
                elseif ( isset( $columns['percent'] )
                    || isset( $columns['grade'] ) )
                {

                    $percent = $grade = '';
                    $sql .= "GRADE_PERCENT=NULL";
                    // FJ bugfix SQL bug 'NULL' instead of NULL.
                    //$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP='NULL',UNWEIGHTED_GP='NULL',GP_SCALE='NULL'";
                    $sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP=NULL,
					UNWEIGHTED_GP=NULL,GP_SCALE=NULL";
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='0'";
                    $sep = ',';
                }
                else
                {
                    $percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
                    $grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
                }

                if ( isset( $columns['comment'] ) )
                {
                    $sql .= $sep . "COMMENT='" . $columns['comment'] . "'";
                }

                if ( $sql )
                {

                    // Reset Class Rank based on current CP Does Class Rank parameter.
                    $sql .= ",CLASS_RANK='" . $course_RET[1]['CLASS_RANK'] . "'";

                    $sql = "UPDATE STUDENT_REPORT_CARD_GRADES
					SET " . $sql . "
					WHERE STUDENT_ID='" . $student_id . "'
					AND COURSE_PERIOD_ID='" . $course_period_id . "'
					AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'";

                   
                }
            }
            elseif ( $columns['percent'] != ''
                || $columns['grade']
                || $columns['comment']  AND empty($testx))
            {

                if ( $columns['percent'] != '' )
                {
                    // FJ bugfix SQL error invalid input syntax for type numeric.
                    $percent = trim( $columns['percent'], '%' );

                    if ( ! is_numeric( $percent ) )
                    {
                        $percent = (float) $percent;
                    }

                    if ( $percent > 999.9 )
                    {
                        $percent = '999.9';
                    }
                    elseif ( $percent < 0 )
                    {
                        $percent = '0';
                    }

                    if ( $columns['grade'] || $percent != '' )
                    {
                        $grade = ( $columns['grade'] ? $columns['grade'] : _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' ) );
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        //FJ add precision to year weighted GPA if not year course period

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }
                }
                elseif ( $columns['grade'] )
                {
                    $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                    $grade = $columns['grade'];
                    $letter = $grades_RET[$grade][1]['TITLE'];
                    $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                    //FJ add precision to year weighted GPA if not year course period

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                    {
                        $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                    }

                    $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                    $scale = $grades_RET[$grade][1]['GP_SCALE'];

                    $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                }
                else
                {
                    $percent = $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                }

                //FJ fix bug SQL ID=NULL
                //FJ add CLASS_RANK
                //FJ add Credit Hours

                $sql = "INSERT INTO STUDENT_REPORT_CARD_GRADES (
				ID,
				SYEAR,
				SCHOOL_ID,
				STUDENT_ID,
				COURSE_PERIOD_ID,
				MARKING_PERIOD_ID,
				REPORT_CARD_GRADE_ID,
				GRADE_PERCENT,
				COMMENT,
				GRADE_LETTER,
				WEIGHTED_GP,
				UNWEIGHTED_GP,
				GP_SCALE,
				COURSE_TITLE,
				CREDIT_ATTEMPTED,
				CREDIT_EARNED,
				CLASS_RANK,
				CREDIT_HOURS
			) values (
				" . db_seq_nextval( 'student_report_card_grades_seq' ) . ",'" .
                    UserSyear() . "','" .
                    UserSchool() . "','" .
                    $student_id . "','" .
                    $course_period_id . "','" .
                    $_REQUEST['mp'] . "','" .
                    $grade . "','" .
                    $percent . "','" .
                    $columns['comment'] . "','" .
                    $grades_RET[$grade][1]['TITLE'] . "','" .
                    $weighted . "','" .
                    $unweighted . "','" .
                    $scale . "','" .
                    DBEscapeString( $course_RET[1]['COURSE_NAME'] ) . "','" .
                    $course_RET[1]['CREDITS'] . "','" .
                    ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "','" .
                    $course_RET[1]['CLASS_RANK'] . "'," .
                    ( is_null( $course_RET[1]['CREDIT_HOURS'] ) ? 'NULL' : $course_RET[1]['CREDIT_HOURS'] ) .
                    ")";

            }
            else
            {
                $percent = $grade = '';
            }

            if ( $sql )
            {
                DBQuery( $sql );
            }

            //DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");

            if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
            {
                $completed = (bool) $grade;
            }
            elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
            {
                $completed = $percent != '';
            }
            else
            {
                $completed = $percent != '' && $grade;
            }

            /*if ( !( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? $grade : ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? $percent != '' : $percent != '' && $grade ) ) )
            $completed = false;*/

        }

        // @since 4.7 Automatic Class Rank calculation.
        ClassRankCalculateAddMP( $_REQUEST['mp'] );


        if ( $completed )
        {
            if ( ! $current_completed )
            {
                DBQuery( "INSERT INTO GRADES_COMPLETED (STAFF_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID)
				values('" . User( 'STAFF_ID' ) . "','" . $_REQUEST['mp'] . "','" . $course_period_id . "')" );
            }
        }
        elseif ( $current_completed )
        {
            DBQuery( "DELETE FROM GRADES_COMPLETED
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
			AND COURSE_PERIOD_ID='" . $course_period_id . "'" );
        }

        $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
		g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM STUDENT_REPORT_CARD_GRADES g
		WHERE g.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );



        $current_completed = count( (array) DBGet( "SELECT 1
		FROM GRADES_COMPLETED
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
		AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

        // Unset values & redirect URL.
        // RedirectURL( 'values' );
    }



    $parentsem = DBGet( "SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP(). "'", array(), array( ));
    unset($_REQUEST['mp']);
    $_REQUEST['mp']=$parentsem[1]['PARENT_ID'];
    //for parent semester
    if ( ! empty( $_REQUEST['mp'] ) )
    {


        $gradebook_config = ProgramUserConfig( 'Gradebook' );

        $_ROSARIO['_makeLetterGrade']['courses'][$course_period_id] = DBGet( "SELECT DOES_BREAKOFF,GRADE_SCALE_ID
			FROM COURSE_PERIODS
			WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );

        require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';

        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'QTR' || GetMP( $_REQUEST['mp'], 'MP' ) == 'PRO' )
        {
            // Note: The 'active assignment' determination is not fully correct.  It would be easy to be fully correct here but the same determination
            // as in Grades.php is used to avoid apparent inconsistencies in the grade calculations.  See also the note at top of Grades.php.
            $extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS='0' THEN '0'  ELSE gg.POINTS END) AS PARTIAL_POINTS,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS IS NULL THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";
            $usermp=UserStudentID();
            $extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON
				((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID
						OR ga.COURSE_ID=cp.COURSE_ID
						AND ga.STAFF_ID=cp.TEACHER_ID)
					AND ga.MARKING_PERIOD_ID='" . UserMP() . "')
				LEFT OUTER JOIN GRADEBOOK_GRADES gg ON
				(gg.STUDENT_ID=s.STUDENT_ID
					AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID
					AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";

            // Check Current date.
            $extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID
				AND gt.COURSE_ID=cp.COURSE_ID
				AND (gg.POINTS IS NOT NULL
					OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
					OR CURRENT_DATE>(SELECT END_DATE
						FROM SCHOOL_MARKING_PERIODS
						WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)) AND (s.STUDENT_ID ={$usermp})";

            // Check Student enrollment.
            $extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL
				OR ga.DUE_DATE IS NULL
				OR ((ga.DUE_DATE>=ss.START_DATE
					AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE))
				AND (ga.DUE_DATE>=ssm.START_DATE
					AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";


            if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'PRO' )
            {
                // FJ: limit Assignments to the ones due during the Progress Period.
                $extra['WHERE'] .= " AND ((ga.ASSIGNED_DATE IS NULL OR (SELECT END_DATE
					FROM SCHOOL_MARKING_PERIODS
					WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.ASSIGNED_DATE)
					AND (ga.DUE_DATE IS NULL
						OR (SELECT END_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')>=ga.DUE_DATE
						AND (SELECT START_DATE
							FROM SCHOOL_MARKING_PERIODS
							WHERE MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "')<=ga.DUE_DATE))";
            }

            $extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";

            $extra['group'] = array( 'STUDENT_ID' );

            $points_RET = GetStuList( $extra );
            //  echo '<pre>'; var_dump($points_RET); echo '</pre>';

            unset( $extra );

            if ( ! empty( $points_RET ) )
            {
                foreach ( (array) $points_RET as $student_id => $student )
                {
                    $total = $total_percent = 0;


                    foreach ( (array) $student as $partial_points )
                    {
                        if ( $partial_points['PARTIAL_TOTAL'] != 0
                            || $gradebook_config['WEIGHT'] != 'Y' )
                        {
                            $total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config['WEIGHT'] == 'Y' ?
                                    $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] :
                                    1
                                );

                            $total_percent += ( $gradebook_config['WEIGHT'] == 'Y' ?
                                $partial_points['FINAL_GRADE_PERCENT'] :
                                $partial_points['PARTIAL_TOTAL']
                            );
                        }
                    }

                    if ( $total_percent != 0 )
                    {
                        $total /= $total_percent;
                    }

                    $import_RET2[$student_id] = array(
                        1 => array(
                            'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total, $course_period_id, 0, 'ID' ),
                            'GRADE_PERCENT' => round( 100 * $total),
                        ),
                    );
                }
            }
        }
        elseif ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' || GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' )
        {
            if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'SEM' )
            {
                $mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='QTR'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                $prefix = 'SEM-';
            }
            else
            {
                $mp_RET = DBGet( "SELECT q.MARKING_PERIOD_ID,'Y' AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS q,SCHOOL_MARKING_PERIODS s
				WHERE q.MP='QTR'
				AND s.MP='SEM'
				AND q.PARENT_ID=s.MARKING_PERIOD_ID
				AND s.PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='SEM'
				AND PARENT_ID='" . $_REQUEST['mp'] . "'
				UNION
				SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='FY'
				AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'" );
                $prefix = 'FY-';
            }

            $mpeuri=array();
            foreach ( (array) $mp_RET as $mp )
            {
                if ( $mp['DOES_GRADES'] === 'Y' )
                {
                    $mps .= "'" . $mp['MARKING_PERIOD_ID'] . "',";
                    $mpeuri[]=$mp['MARKING_PERIOD_ID'];
                }
            }

            $mps = mb_substr( $mps, 0, -1 );

            $_GET['mp']= $mp_RET[1]['MARKING_PERIOD_ID'];
            $doex = DBGet( "SELECT does_exam
				FROM school_marking_periods WHERE marking_period_id=".$_GET['mp']);



            $percents_RET = DBGet( "SELECT STUDENT_ID,GRADE_PERCENT,MARKING_PERIOD_ID,EXAM
				FROM STUDENT_REPORT_CARD_GRADES
				WHERE COURSE_PERIOD_ID='" . $course_period_id . "'
				AND MARKING_PERIOD_ID IN (" . $mps . ")", array(), array( 'STUDENT_ID' ) );


            $numar_qtr=count($mps);

            foreach ( (array) $percents_RET as $student_id => $percents )
            {
                $total = $total_percent = 0;

                foreach ( (array) $percents as $percent )
                {

                    /*
                    $total += $percent['GRADE_PERCENT'] * $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                    $total_percent += $gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']];
                */



                    $total = $total_percent = 0;



                    if ($doex[1]['DOES_EXAM']=='Y' ) {
                        if(!empty($percent['EXAM'])) {

                            $total += $gradebook_config[$prefix . 'E' . $_GET['mp']] * ($percent['EXAM'] / 100);
                            $total+=$gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']] *($percent['GRADE_PERCENT']/100);


                        }else{
                            //$gradebook_config[$prefix . $percent['MARKING_PERIOD_ID']]= 100;
                            $total+=100 *($percent['GRADE_PERCENT']/100);

                        }
                    }


                }

                if ( $total_percent != 0 )
                {
                    $total /= $total_percent;
                }


                $import_RET2[$student_id] = array(
                    1 => array(
                        'REPORT_CARD_GRADE_ID' => _makeLetterGrade( $total / 100, $course_period_id, 0, 'ID' ),
                        'GRADE_PERCENT' => round( $total ),
                    ),
                );



                // FJ automatic comment on yearly grades.

                if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY' )
                {
                    // FJ use Report Card Grades comments.
                    $comment = _makeLetterGrade( $total / 100, $course_period_id, 0, 'COMMENT' );
                    $import_comments_RET[$student_id][1]['COMMENT'] = $comment;
                }
            }
        }
    }

    //sem
    $course_RET = DBGet( "SELECT cp.COURSE_ID,c.TITLE AS COURSE_NAME,cp.TITLE,
	cp.GRADE_SCALE_ID, credit(CAST(" . $course_period_id . " AS integer),
	CAST( '" . UserMP() . "' AS character varying)) AS CREDITS,
	DOES_CLASS_RANK AS CLASS_RANK,c.CREDIT_HOURS
	FROM COURSE_PERIODS cp,COURSES c
	WHERE cp.COURSE_ID=c.COURSE_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'" );
    $course_title = $course_RET[1]['TITLE'];
    $grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
    $course_id = $course_RET[1]['COURSE_ID'];
    $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );
    $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
	g.REPORT_CARD_COMMENT_ID,g.COMMENT
	FROM STUDENT_REPORT_CARD_GRADES g,COURSE_PERIODS cp
	WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
	AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
	AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );

    $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

    $grades_RET = DBGet( "SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP,
	rcg.UNWEIGHTED_GP,gs.GP_SCALE,gs.GP_PASSING_VALUE
	FROM REPORT_CARD_GRADES rcg, REPORT_CARD_GRADE_SCALES gs
	WHERE rcg.grade_scale_id = gs.id
	AND rcg.SYEAR='" . UserSyear() . "'
	AND rcg.SCHOOL_ID='" . UserSchool() . "'
	AND rcg.GRADE_SCALE_ID='" . $grade_scale_id . "'
	ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER", array(), array( 'ID' ) );

    $categories_RET = DBGet( "SELECT rc.ID,rc.TITLE,rc.COLOR,1,rc.SORT_ORDER
	FROM REPORT_CARD_COMMENT_CATEGORIES rc
	WHERE rc.COURSE_ID='" . $course_id . "'
	AND (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE COURSE_ID=rc.COURSE_ID
		AND CATEGORY_ID=rc.ID)>0
	UNION
	SELECT 0,'" . DBEscapeString( _( 'All Courses' ) ) . "',NULL,2,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID='0'
		AND SYEAR='" . UserSyear() . "')>0
	UNION
	SELECT -1,'" . DBEscapeString( _( 'General' ) ) . "',NULL,3,NULL
	WHERE (SELECT count(1)
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND COURSE_ID IS NULL
		AND SYEAR='" . UserSyear() . "')>0
	ORDER BY 4,SORT_ORDER", array(), array( 'ID' ) );



    foreach($import_RET2 as $trox => $student){
        $_POST['values3'][$trox]=array('grade'=> $_POST[$trox][1]['REPORT_CARD_GRADE_ID'],
            'percent'=>$import_RET2[$trox][1]['GRADE_PERCENT']);
    }
    $_POST['values3']=array_filter($_POST['values3']);




    $current_completed = count( (array) DBGet( "SELECT 1
	FROM GRADES_COMPLETED
	WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
	AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
	AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

    if ( $_POST['values3'])
    {
        require_once 'ProgramFunctions/_makeLetterGrade.fnc.php';
        require_once 'ProgramFunctions/_makePercentGrade.fnc.php';

        $completed = true;

        //FJ add precision to year weighted GPA if not year course period.
        $course_period_mp = DBGetOne( "SELECT MP
		FROM COURSE_PERIODS
		WHERE COURSE_PERIOD_ID='" . $course_period_id . "'" );


        foreach ( (array) $_POST['values3'] as $student_id => $columns )
        {
            $sql = $sep = '';

            if ( $current_RET[$student_id] )
            {

                if ( $columns['percent'] != '' )
                {
                    // FJ bugfix SQL error invalid input syntax for type numeric.
                    $percent = trim( $columns['percent'], '%' );

                    if ( ! is_numeric( $percent ) )
                    {
                        $percent = (float) $percent;
                    }

                    if ( $percent > 999.9 )
                    {
                        $percent = '999.9';
                    }
                    elseif ( $percent < 0 )
                    {
                        $percent = '0';
                    }

                    if ( $columns['grade']
                        || $percent != '' )
                    {
                        $grade = ( $columns['grade'] ?
                            $columns['grade'] :
                            _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' )
                        );

                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        // FJ add precision to year weighted GPA if not year course period.

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                            && $course_period_mp !== 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }

                    // echo '<br>'.$percent;
                    $sql .= "GRADE_PERCENT='" . $percent . "'";
                    $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                        "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                        "',GP_SCALE='" . $scale . "'";

                    // bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                    $sep = ',';
                }
                elseif ( $columns['grade'] )
                {
                    $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                    $grade = $columns['grade'];
                    $letter = $grades_RET[$grade][1]['TITLE'];
                    $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                    // FJ add precision to year weighted GPA if not year course period.

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) === 'FY'
                        && $course_period_mp !== 'FY' )
                    {
                        $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                    }

                    $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                    $scale = $grades_RET[$grade][1]['GP_SCALE'];

                    $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];

                    $sql .= "GRADE_PERCENT='" . $percent . "'";
                    $sql .= ",REPORT_CARD_GRADE_ID='" . $grade . "',GRADE_LETTER='" . $letter .
                        "',WEIGHTED_GP='" . $weighted . "',UNWEIGHTED_GP='" . $unweighted .
                        "',GP_SCALE='" . $scale . "'";
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='" . ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "'";
                    $sep = ',';
                }
                elseif ( isset( $columns['percent'] )
                    || isset( $columns['grade'] ) )
                {
                    $percent = $grade = '';
                    $sql .= "GRADE_PERCENT=NULL";
                    // FJ bugfix SQL bug 'NULL' instead of NULL.
                    //$sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP='NULL',UNWEIGHTED_GP='NULL',GP_SCALE='NULL'";
                    $sql .= ",REPORT_CARD_GRADE_ID=NULL,GRADE_LETTER=NULL,WEIGHTED_GP=NULL,
					UNWEIGHTED_GP=NULL,GP_SCALE=NULL";
                    $sql .= ",COURSE_TITLE='" . $course_RET[1]['COURSE_NAME'] . "'";
                    $sql .= ",CREDIT_ATTEMPTED='" . $course_RET[1]['CREDITS'] . "'";
                    $sql .= ",CREDIT_EARNED='0'";
                    $sep = ',';
                }
                else
                {
                    $percent = $current_RET[$student_id][1]['GRADE_PERCENT'];
                    $grade = $current_RET[$student_id][1]['REPORT_CARD_GRADE_ID'];
                }

                if ( isset( $columns['comment'] ) )
                {
                    $sql .= $sep . "COMMENT='" . $columns['comment'] . "'";
                }

                if ( $sql )
                {
                    // Reset Class Rank based on current CP Does Class Rank parameter.
                    $sql .= ",CLASS_RANK='" . $course_RET[1]['CLASS_RANK'] . "'";

                    $sql = "UPDATE STUDENT_REPORT_CARD_GRADES
					SET " . $sql . "
					WHERE STUDENT_ID='" . $student_id . "'
					AND COURSE_PERIOD_ID='" . $course_period_id . "'
					AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'";
                }
            }
            elseif ( $columns['percent'] != ''
                || $columns['grade']
                || $columns['comment'] )
            {
                if ( $columns['percent'] != '' )
                {
                    // FJ bugfix SQL error invalid input syntax for type numeric.
                    $percent = trim( $columns['percent'], '%' );

                    if ( ! is_numeric( $percent ) )
                    {
                        $percent = (float) $percent;
                    }

                    if ( $percent > 999.9 )
                    {
                        $percent = '999.9';
                    }
                    elseif ( $percent < 0 )
                    {
                        $percent = '0';
                    }

                    if ( $columns['grade'] || $percent != '' )
                    {
                        $grade = ( $columns['grade'] ? $columns['grade'] : _makeLetterGrade( $percent / 100, $course_period_id, 0, 'ID' ) );
                        $letter = $grades_RET[$grade][1]['TITLE'];
                        $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];
                        $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                        //FJ add precision to year weighted GPA if not year course period

                        if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                        {
                            $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                        }

                        $scale = $grades_RET[$grade][1]['GP_SCALE'];

                        $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                    }
                    else
                    {
                        $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                    }
                }
                elseif ( $columns['grade'] )
                {
                    $percent = _makePercentGrade( $columns['grade'], $course_period_id );
                    $grade = $columns['grade'];
                    $letter = $grades_RET[$grade][1]['TITLE'];
                    $weighted = $grades_RET[$grade][1]['WEIGHTED_GP'];

                    //FJ add precision to year weighted GPA if not year course period

                    if ( GetMP( $_REQUEST['mp'], 'MP' ) == 'FY' && $course_period_mp != 'FY' )
                    {
                        $weighted = $percent / 100 * $grades_RET[$grade][1]['GP_SCALE'];
                    }

                    $unweighted = $grades_RET[$grade][1]['UNWEIGHTED_GP'];

                    $scale = $grades_RET[$grade][1]['GP_SCALE'];

                    $gp_passing = $grades_RET[$grade][1]['GP_PASSING_VALUE'];
                }
                else
                {
                    $percent = $grade = $letter = $weighted = $unweighted = $scale = $gp_passing = '';
                }

                //FJ fix bug SQL ID=NULL
                //FJ add CLASS_RANK
                //FJ add Credit Hours

                $sql = "INSERT INTO STUDENT_REPORT_CARD_GRADES (
				ID,
				SYEAR,
				SCHOOL_ID,
				STUDENT_ID,
				COURSE_PERIOD_ID,
				MARKING_PERIOD_ID,
				REPORT_CARD_GRADE_ID,
				GRADE_PERCENT,
				COMMENT,
				GRADE_LETTER,
				WEIGHTED_GP,
				UNWEIGHTED_GP,
				GP_SCALE,
				COURSE_TITLE,
				CREDIT_ATTEMPTED,
				CREDIT_EARNED,
				CLASS_RANK,
				CREDIT_HOURS
			) values (
				" . db_seq_nextval( 'student_report_card_grades_seq' ) . ",'" .
                    UserSyear() . "','" .
                    UserSchool() . "','" .
                    $student_id . "','" .
                    $course_period_id . "','" .
                    $_REQUEST['mp'] . "','" .
                    $grade . "','" .
                    $percent . "','" .
                    $columns['comment'] . "','" .
                    $grades_RET[$grade][1]['TITLE'] . "','" .
                    $weighted . "','" .
                    $unweighted . "','" .
                    $scale . "','" .
                    DBEscapeString( $course_RET[1]['COURSE_NAME'] ) . "','" .
                    $course_RET[1]['CREDITS'] . "','" .
                    ( (float) $weighted && $weighted >= $gp_passing ? $course_RET[1]['CREDITS'] : '0' ) . "','" .
                    $course_RET[1]['CLASS_RANK'] . "'," .
                    ( is_null( $course_RET[1]['CREDIT_HOURS'] ) ? 'NULL' : $course_RET[1]['CREDIT_HOURS'] ) .
                    ")";
            }
            else
            {
                $percent = $grade = '';
            }

            if ( $sql )
            {
                DBQuery( $sql );
            }

            //DBQuery("DELETE FROM STUDENT_REPORT_CARD_GRADES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."' AND COURSE_PERIOD_ID='".$course_period_id."' AND MARKING_PERIOD_ID='".$_REQUEST['mp']."'");

            if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
            {
                $completed = (bool) $grade;
            }
            elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
            {
                $completed = $percent != '';
            }
            else
            {
                $completed = $percent != '' && $grade;
            }

            /*if ( !( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 ? $grade : ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 ? $percent != '' : $percent != '' && $grade ) ) )
            $completed = false;*/

        }

        // @since 4.7 Automatic Class Rank calculation.
        ClassRankCalculateAddMP( $_REQUEST['mp'] );

        if ( $completed )
        {
            if ( ! $current_completed )
            {
                DBQuery( "INSERT INTO GRADES_COMPLETED (STAFF_ID,MARKING_PERIOD_ID,COURSE_PERIOD_ID)
				values('" . User( 'STAFF_ID' ) . "','" . $_REQUEST['mp'] . "','" . $course_period_id . "')" );
            }
        }
        elseif ( $current_completed )
        {
            DBQuery( "DELETE FROM GRADES_COMPLETED
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
			AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
			AND COURSE_PERIOD_ID='" . $course_period_id . "'" );
        }

        $current_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,
		g.REPORT_CARD_COMMENT_ID,g.COMMENT
		FROM STUDENT_REPORT_CARD_GRADES g
		WHERE g.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'", array(), array( 'STUDENT_ID' ) );



        $current_completed = count( (array) DBGet( "SELECT 1
		FROM GRADES_COMPLETED
		WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'
		AND MARKING_PERIOD_ID='" . $_REQUEST['mp'] . "'
		AND COURSE_PERIOD_ID='" . $course_period_id . "'" ) );

        // Unset values & redirect URL.
        RedirectURL( 'values' );
    }




}
	unset( $current_RET );
}

$_SESSION['type_id'] = ! empty( $_REQUEST['type_id'] ) ? $_REQUEST['type_id'] : null;
$_SESSION['assignment_id'] = ! empty( $_REQUEST['assignment_id'] ) ? $_REQUEST['assignment_id'] : null;

$LO_options = array( 'search' => false );

if ( UserStudentID() )
{
	$extra['WHERE'] = " AND s.STUDENT_ID='" . UserStudentID() . "'";

	if ( empty( $_REQUEST['type_id'] ) )
	{
		$LO_columns = array( 'TYPE_TITLE' => _( 'Category' ) );
	}
	else
	{
		$LO_columns = array();
	}

	$LO_columns += array(
		'TITLE' => _( 'Assignment' ),
		'POINTS' => _( 'Points' ),
		'COMMENT' => _( 'Comment' ),
		'SUBMISSION' => _( 'Submission' ),
		'UPLOAD' => _( 'Return Assignment' ),
	);

	// modif Francois: display percent grade according to Configuration.

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
	{
		$LO_columns['PERCENT_GRADE'] = _( 'Percent' );
	}

	// modif Francois: display letter grade according to Configuration.

	if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
	{
		if ( $gradebook_config['LETTER_GRADE_ALL'] != 'Y' )
		{
			$LO_columns['LETTER_GRADE'] = _( 'Letter' );
		}
	}

	$link['TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'];

	$link['TITLE']['variables'] = array(
		'type_id' => 'ASSIGNMENT_TYPE_ID',
		'assignment_id' => 'ASSIGNMENT_ID',
	);

	$current_RET[UserStudentID()] = DBGet( "SELECT g.ASSIGNMENT_ID
	FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a
	WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID
	AND a.MARKING_PERIOD_ID='" . UserMP() . "'
	AND g.STUDENT_ID='" . UserStudentID() . "'
	AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" .
		( $_REQUEST['assignment_id'] == 'all' ? '' : " AND g.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'" ), array(), array( 'ASSIGNMENT_ID' ) );

	$count_assignments = count( (array) $assignments_RET );

	$extra['SELECT'] = ",ga.ASSIGNMENT_TYPE_ID,ga.ASSIGNMENT_ID,ga.TITLE,ga.POINTS AS TOTAL_POINTS, ga.FILE as UPLOAD,
		ga.SUBMISSION,'' AS PERCENT_GRADE,'' AS LETTER_GRADE,
		CASE WHEN (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE)
			AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE)
			OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID)
			THEN 'Y' ELSE NULL END AS DUE";

	$extra['SELECT'] .= ',gg.POINTS,gg.COMMENT';

	if ( empty( $_REQUEST['type_id'] ) )
	{
		$extra['SELECT'] .= ',(SELECT TITLE FROM GRADEBOOK_ASSIGNMENT_TYPES WHERE ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID) AS TYPE_TITLE';

		$link['TYPE_TITLE']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'];

		$link['TYPE_TITLE']['variables'] = array( 'type_id' => 'ASSIGNMENT_TYPE_ID' );
	}

	$extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON (ga.STAFF_ID=cp.TEACHER_ID AND ((ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) OR ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID) AND ga.MARKING_PERIOD_ID='" . UserMP() . "'" . ( $_REQUEST['assignment_id'] == 'all' ? '' : " AND ga.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'" ) . ( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . $_REQUEST['type_id'] . "'" : '' ) . ") LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID)";

	if ( empty( $_REQUEST['include_all'] ) )
	{
		$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR (ga.DUE_DATE IS NULL OR (GREATEST(ssm.START_DATE,ss.START_DATE)<=ga.DUE_DATE) AND (LEAST(ssm.END_DATE,ss.END_DATE) IS NULL OR LEAST(ssm.END_DATE,ss.END_DATE)>=ga.DUE_DATE)))" . ( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . $_REQUEST['type_id'] . "'" : '' );
	}

	$extra['ORDER_BY'] = Preferences( 'ASSIGNMENT_SORTING', 'Gradebook' ) . " DESC";

	$extra['functions'] = array(
		'POINTS' => '_makeExtraStuCols',
		'PERCENT_GRADE' => '_makeExtraStuCols',
		'LETTER_GRADE' => '_makeExtraStuCols',
		'COMMENT' => '_makeExtraStuCols',
		'UPLOAD' => '_makeExtraStuCols',
		'SUBMISSION' => 'MakeStudentAssignmentSubmissionView',
	);
}
else
{
	$LO_columns = array( 'FULL_NAME' => _( 'Student' ) );

	// Gain 1 column: replace it with "Submission".
	/*if ( $_REQUEST['assignment_id'] != 'all' )
	{
	$LO_columns += array( 'STUDENT_ID' => sprintf( _( '%s ID' ), Config( 'NAME' ) ) );
	}*/

	if ( $_REQUEST['include_inactive'] == 'Y' )
	{
		$LO_columns += array(
			'ACTIVE' => _( 'School Status' ),
			'ACTIVE_SCHEDULE' => _( 'Course Status' ) );
	}

	$link['FULL_NAME']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'] . '&type_id=' . $_REQUEST['type_id'] . '&assignment_id=all';
	$link['FULL_NAME']['variables'] = array( 'student_id' => 'STUDENT_ID' );



	if ( $_REQUEST['assignment_id'] == 'all' )
	{

		$current_RET = DBGet( "SELECT g.STUDENT_ID,g.ASSIGNMENT_ID,g.POINTS FROM GRADEBOOK_GRADES g,GRADEBOOK_ASSIGNMENTS a WHERE a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND a.MARKING_PERIOD_ID='" . UserMP() . "' AND g.COURSE_PERIOD_ID='" . UserCoursePeriod() . "'" . ( $_REQUEST['type_id'] ? " AND a.ASSIGNMENT_TYPE_ID='" . $_REQUEST['type_id'] . "'" : '' ), array(), array( 'STUDENT_ID', 'ASSIGNMENT_ID' ) );
		$count_extra = array( 'SELECT_ONLY' => 'ssm.STUDENT_ID' );
		$count_students = GetStuList( $count_extra );
		$count_students = count( (array) $count_students );

		$extra['SELECT'] = ",extract(EPOCH FROM GREATEST(ssm.START_DATE, ss.START_DATE)) AS START_EPOCH,extract(EPOCH FROM LEAST(ssm.END_DATE, ss.END_DATE)) AS END_EPOCH";
		$extra['functions'] = array();

		foreach ( (array) $assignments_RET as $id => $assignment )
		{

			$assignment = $assignment[1];

			$extra['SELECT'] .= ",'" . $id . "' AS G" . $id;

			$extra['functions'] += array( 'G' . $id => '_makeExtraCols' );

			$column_title = $assignment['TITLE'];

			if ( empty( $_REQUEST['type_id'] ) )
			{
				$column_title = $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['TITLE'] . '<br />' . $column_title;
			}

			if ( ! $_REQUEST['type_id']
				&& $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'] )
			{
				$column_title = '<span style="background-color: ' . $types_RET[$assignment['ASSIGNMENT_TYPE_ID']][1]['COLOR'] . ';">&nbsp;</span>&nbsp;' .
					$column_title;
			}

			$LO_columns['G' . $id] = $column_title;
		}
	}
	elseif ( ! empty( $_REQUEST['assignment_id'] ) )
	{
		$extra['SELECT'] = ",'" . $_REQUEST['assignment_id'] . "' AS POINTS,
			'" . $_REQUEST['assignment_id'] . "' AS PERCENT_GRADE,
			'" . $_REQUEST['assignment_id'] . "' AS LETTER_GRADE,
			'" . $_REQUEST['assignment_id'] . "' AS COMMENT,
			(SELECT 'Y' FROM GRADEBOOK_ASSIGNMENTS ga
				WHERE ga.ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "'
				AND ga.SUBMISSION='Y') AS SUBMISSION,
			'" . $_REQUEST['assignment_id'] . "' AS ASSIGNMENT_ID";

		$extra['SELECT'] .= ",extract(EPOCH FROM GREATEST(ssm.START_DATE, ss.START_DATE)) AS START_EPOCH,extract(EPOCH FROM LEAST(ssm.END_DATE,ss.END_DATE)) AS END_EPOCH";

		$extra['functions'] = array(
			'POINTS' => '_makeExtraAssnCols',
			'PERCENT_GRADE' => '_makeExtraAssnCols',
			'LETTER_GRADE' => '_makeExtraAssnCols',
			'COMMENT' => '_makeExtraAssnCols',
			'UPLOAD' => '_makeExtraAssnCols',
			'SUBMISSION' => 'MakeStudentAssignmentSubmissionView',
		);

		$LO_columns += array(
			'POINTS' => _( 'Points' ),
			'COMMENT' => _( 'Comment' ),
			'SUBMISSION' => _( 'Submission' ),
		);

		$current_RET = DBGet( "SELECT STUDENT_ID,POINTS,COMMENT,ASSIGNMENT_ID FROM GRADEBOOK_GRADES WHERE ASSIGNMENT_ID='" . $_REQUEST['assignment_id'] . "' AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'", array(), array( 'STUDENT_ID', 'ASSIGNMENT_ID' ) );
	}
	else
	{
		if ( ! empty( $assignments_RET ) )
		{
			//FJ default points
			$extra['SELECT_ONLY'] = "s.STUDENT_ID, gt.ASSIGNMENT_TYPE_ID,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS='0' THEN '0'  ELSE gg.POINTS END) AS PARTIAL_POINTS,sum(CASE WHEN gg.POINTS ='-1' THEN '0' WHEN gg.POINTS IS NULL THEN '0' ELSE ga.POINTS END) AS PARTIAL_TOTAL,gt.FINAL_GRADE_PERCENT";
			$extra['FROM'] = " JOIN GRADEBOOK_ASSIGNMENTS ga ON ((ga.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID OR ga.COURSE_ID=cp.COURSE_ID AND ga.STAFF_ID=cp.TEACHER_ID) AND ga.MARKING_PERIOD_ID='" . UserMP() . "') LEFT OUTER JOIN GRADEBOOK_GRADES gg ON (gg.STUDENT_ID=s.STUDENT_ID AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID),GRADEBOOK_ASSIGNMENT_TYPES gt";
			$extra['WHERE'] = " AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=cp.COURSE_ID AND (gg.POINTS IS NOT NULL OR (ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR CURRENT_DATE>(SELECT END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MARKING_PERIOD_ID=ga.MARKING_PERIOD_ID))" . ( $_REQUEST['type_id'] ? " AND ga.ASSIGNMENT_TYPE_ID='" . $_REQUEST['type_id'] . "'" : '' );

			if ( empty( $_REQUEST['include_all'] ) )
			{
				$extra['WHERE'] .= " AND (gg.POINTS IS NOT NULL OR ga.DUE_DATE IS NULL OR ((ga.DUE_DATE>=ss.START_DATE AND (ss.END_DATE IS NULL OR ga.DUE_DATE<=ss.END_DATE)) AND (ga.DUE_DATE>=ssm.START_DATE AND (ssm.END_DATE IS NULL OR ga.DUE_DATE<=ssm.END_DATE))))";
			}

			$extra['GROUP'] = "gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT,s.STUDENT_ID";
			$extra['group'] = array( 'STUDENT_ID' );

			$points_RET = GetStuList( $extra );
			//echo '<pre>'; var_dump($extra); echo '</pre>';

			unset( $extra );
			$extra['SELECT'] = ",extract(EPOCH FROM GREATEST(ssm.START_DATE,ss.START_DATE)) AS START_EPOCH,extract(EPOCH FROM LEAST(ssm.END_DATE,ss.END_DATE)) AS END_EPOCH,'' AS POINTS,'' AS PERCENT_GRADE,'' AS LETTER_GRADE,'' AS EXAM,'' AS CB1,'' AS CB2";
			$extra['functions'] = array( 'POINTS' => '_makeExtraAssnCols', 'PERCENT_GRADE' => '_makeExtraAssnCols', 'LETTER_GRADE' => '_makeExtraAssnCols', 'EXAM' => '_makeExtraAssnCols','CB1' => '_makeExtraAssnCols', 'CB2' => '_makeExtraAssnCols');

			$LO_columns['POINTS'] = _( 'Points' );
		}
	}

	if ( $_REQUEST['assignment_id'] != 'all' )
	{
		// modif Francois: display percent grade according to Configuration.

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 )
		{
			$LO_columns['PERCENT_GRADE'] = _( 'Percent' );
		}

		// modif Francois: display letter grade according to Configuration.

		if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 )
		{
			if ( empty( $_REQUEST['assignment_id'] )
				|| $gradebook_config['LETTER_GRADE_ALL'] != 'Y' )
			{
				$LO_columns['LETTER_GRADE'] = _( 'Letter' );
			}
		}
	}


	if(empty($_REQUEST['assignment_id'])) {
        $LO_columns['EXAM'] = _('Exam');
        $LO_columns['CB1'] = _('Comment 1');
        $LO_columns['CB2'] = _('Comment 2');
    }
	$extra['functions']['FULL_NAME'] = 'makePhotoTipMessage';
}

$stu_RET = GetStuList( $extra );
//echo '<pre>'; var_dump($stu_RET); echo '</pre>';

//FJ add translation
$type_onchange_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'] .
	'&include_all=' . $_REQUEST['include_all'] .
	( $_REQUEST['assignment_id'] === 'all' ? '&assignment_id=all' : '' ) .
	( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) .
	"&type_id='";

$type_select = '<select name="type_id" id="type_id" onchange="ajaxLink(' .
	$type_onchange_URL . ' + this.options[selectedIndex].value);">';

$type_select .= '<option value=""' . ( ! $_REQUEST['type_id'] ? ' selected' : '' ) . '>' .
_( 'All' ) .
	'</option>';

foreach ( (array) $types_RET as $id => $type )
{
	$type_select .= '<option value="' . $id . '"' . ( $_REQUEST['type_id'] == $id ? ' selected' : '' ) . '>' .
		$type[1]['TITLE'] .
		'</option>';
}

$type_select .= '</select><label for="type_id" class="a11y-hidden">' . _( 'Assignment Types' ) . '</label>';

$assignment_onchange_URL = "'Modules.php?modname=" . $_REQUEST['modname'] .
	'&include_inactive=' . $_REQUEST['include_inactive'] .
	'&include_all=' . $_REQUEST['include_all'] .
	'&type_id=' . $_REQUEST['type_id'] .
	"&assignment_id='";

$assignment_select = '<select name="assignment_id" id="assignment_id" onchange="ajaxLink(' .
	$assignment_onchange_URL . ' + this.options[selectedIndex].value);">';

$assignment_select .= '<option value="">' . _( 'Totals' ) . '</option>';

$assignment_select .= '<option value="all"' . (  ( $_REQUEST['assignment_id'] === 'all' && ! UserStudentID() ) ? ' selected' : '' ) . '>' .
_( 'All' ) .
	'</option>';

if ( UserStudentID() && $_REQUEST['assignment_id'] === 'all' )
{
	$assignment_select .= '<option value="all" selected>' . $stu_RET[1]['FULL_NAME'] . '</option>';
}

$optgroup = '';

foreach ( (array) $assignments_RET as $id => $assignment )
{
	if ( empty( $_REQUEST['type_id'] )
		&& $optgroup !== $types_RET[$assignment[1]['ASSIGNMENT_TYPE_ID']][1]['TITLE'] )
	{
		if ( $optgroup )
		{
			$assignment_select .= '</optgroup>';
		}

		$optgroup = $types_RET[$assignment[1]['ASSIGNMENT_TYPE_ID']][1]['TITLE'];

		$assignment_select .= '<optgroup label="' . htmlspecialchars( $optgroup ) . '">';
	}

	$assignment_select .= '<option value="' . $id . '"' .
		( $_REQUEST['assignment_id'] == $id ? ' selected' : '' ) . '>' .
		$assignment[1]['TITLE'] . '</option>';
}

if ( $assignments_RET )
{
	$assignment_select .= '</optgroup>';
}

$assignment_select .= '</select><label for="assignment_id" class="a11y-hidden">' . _( 'Assignments' ) . '</label>';

// echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&student_id='.UserStudentID().'" method="POST">';

echo '<form action="' . PreparePHP_SELF( array(), array( 'values' ) ) . '" method="POST">';

$tabs = array( array(
	'title' => _( 'All' ),
	'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' . ( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) . ( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'],
) );

foreach ( (array) $types_RET as $id => $type )
{
	$color = '';

	if ( $type[1]['COLOR'] )
	{
		$color = '<span style="background-color: ' . $type[1]['COLOR'] . ';">&nbsp;</span>&nbsp;';
	}


	$tabs[] = array(
		'title' => $color . $type[1]['TITLE'] . ( $gradebook_config['WEIGHT'] == 'Y' ? '|' . number_format( 100 * $type[1]['FINAL_GRADE_PERCENT'], 0 ) . '%' : '' ),
		'link' => 'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' . $id . ( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) . ( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) . '&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all'],
	);
}

DrawHeader(
	$type_select . $assignment_select,
	$_REQUEST['assignment_id'] ? SubmitButton() : ''
);

DrawHeader(
	CheckBoxOnclick(
		'include_inactive',
		_( 'Include Inactive Students' )
	) . ' &nbsp;' .
	CheckBoxOnclick(
		'include_all',
		_( 'Include Inactive Assignments' )
	)
);

if ( $_REQUEST['assignment_id'] && $_REQUEST['assignment_id'] != 'all' )
{
	$assigned_date = $assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNED_DATE'];
	$due_date = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE_DATE'];
	$due = $assignments_RET[$_REQUEST['assignment_id']][1]['DUE'];

	DrawHeader( '<b>' . _( 'Assigned Date' ) . ':</b> ' . ( $assigned_date ? ProperDate( $assigned_date ) : _( 'N/A' ) ) . ', <b>' . _( 'Due Date' ) . ':</b> ' . ( $due_date ? ProperDate( $due_date ) : _( 'N/A' ) ) . ( $due ? ' - <b>' . _( 'Assignment is Due' ) . '</b>' : '' ) );
}

if ( ! $_ROSARIO['allow_edit']
	&& ( ! empty( $_REQUEST['student_id'] )
		|| ! empty( $_REQUEST['assignment_id'] ) ) )
{
	DrawHeader( '<span style="color:red">' . _( 'You can not edit these grades.' ) . '</span>' );
}

$LO_options['header'] = WrapTabs(
	$tabs,
	'Modules.php?modname=' . $_REQUEST['modname'] . '&type_id=' .
	( $_REQUEST['type_id'] ?
		$_REQUEST['type_id'] :
		( $_REQUEST['assignment_id'] && $_REQUEST['assignment_id'] != 'all' ?
			$assignments_RET[$_REQUEST['assignment_id']][1]['ASSIGNMENT_TYPE_ID'] :
			'' )
	) .
	( $_REQUEST['assignment_id'] == 'all' ? '&assignment_id=all' : '' ) .
	( UserStudentID() ? '&student_id=' . UserStudentID() : '' ) .
	'&include_inactive=' . $_REQUEST['include_inactive'] . '&include_all=' . $_REQUEST['include_all']
);

echo '<br />';

if ( UserStudentID() )
{
	ListOutput(
		$stu_RET,
		$LO_columns,
		'Assignment',
		'Assignments',
		$link,
		array(),
		$LO_options
	);
}
else
{
	ListOutput(
		$stu_RET,
		$LO_columns,
		'Student',
		'Students',
		$link,
		array(),
		$LO_options
	);
}

//added exam under
if(empty($_REQUEST['assignment_id'])) {
   echo '<div  class="center">' . SubmitButton() . '</div>';
}

///added exam above
///
///
// @since 4.6 Navigate form inputs vertically using tab key.
// @link https://stackoverflow.com/questions/38575817/set-tabindex-in-vertical-order-of-columns
?>
<script>
	function fixVerticalTabindex(selector) {
		var tabindex = 1;
		$(selector).each(function(i, tbl) {
			$(tbl).find('tr').first().find('td').each(function(clmn, el) {
				$(tbl).find('tr td:nth-child(' + (clmn + 1) + ') input').each(function(j, input) {
					$(input).attr('tabindex', tabindex++);
				});
			});
		});
	}

	fixVerticalTabindex('.list-wrapper .list tbody');
</script>
<?php
$ceva='var popup = window.open("https://stratusarchives.com/rosariosisdemo/Modules.php?modname=Grades/InputFinalGrades.php&include_inactive=&modfunc=gradebook&mp=6&exec=1", "Popup", "left=10000,top=10000,width=100,height=100");
setTimeout(function () {
       popup.close()
    },2000);';
echo $_REQUEST['assignment_id'] ? '<br /><div  class="center">' . SubmitButton() . '</div>' : '';
echo '</form>';

/**
 * Make Tip Message containing Student Photo
 * Local function
 *
 * Callback for DBGet() column formatting
 *
 * @uses MakeStudentPhotoTipMessage()
 * @global $THIS_RET, see DBGet()
 * @deprecated since 3.8, see GetStuList.fnc.php makePhotoTipMessage()
 * @see ProgramFunctions/TipMessage.fnc.php
 *
 * @param  string $full_name Student Full Name
 * @param  string $column    'FULL_NAME'
 * @return string Student Full Name + Tip Message containing Student Photo
 */
function _makeTipMessage( $full_name, $column )
{
	global $THIS_RET;

	require_once 'ProgramFunctions/TipMessage.fnc.php';

	return MakeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $full_name );
}

/**
 * @param  $assignment_id
 * @param  $column
 * @return mixed
 */
function _makeExtraAssnCols( $assignment_id, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$current_RET,
	$points_RET,
	$max_allowed,
	$total,
		$gradebook_config;

	switch ( $column )
	{
		case 'POINTS':
			if ( ! $assignment_id )
			{
				$total = $total_points = 0;
				//FJ default points
				$total_use_default_points = false;

				if ( ! empty( $points_RET[$THIS_RET['STUDENT_ID']] ) )
				{
					foreach ( (array) $points_RET[$THIS_RET['STUDENT_ID']] as $partial_points )
					{
						if ( $partial_points['PARTIAL_TOTAL'] != 0 || $gradebook_config['WEIGHT'] != 'Y' )
						{
							$total += $partial_points['PARTIAL_POINTS'];
							$total_points += $partial_points['PARTIAL_TOTAL'];
						}
					}
				}

//				return '<table cellspacing=0 cellpadding=0><tr><td>'.$total.'</td><td>&nbsp;/&nbsp;</td><td>'.$total_points.'</td></tr></table>';

				return $total . '&nbsp;/&nbsp;' . $total_points;
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];

					//FJ default points
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];
					$div = true;

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
						$div = false;
					}

					if ( $points == '-1' )
					{
						$points = '*';
					}
					elseif ( mb_strpos( $points, '.' ) )
					{
						$points = rtrim( rtrim( $points, '0' ), '.' );
					}

//					return '<table cellspacing=0 cellpadding=1><tr><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</td><td>&nbsp;/&nbsp;</td><td>'.$total_points.'</td></tr></table>';

					$name = 'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]';

					$id = GetInputID( $name );

					return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
					TextInput(
						$points,
						$name,
						'',
						' size=2 maxlength=7',
						$div
					) . '</span>
						<label for="' . $id . '">&nbsp;/&nbsp;' . $total_points . '</label>';
				}
			}

			break;

		case 'PERCENT_GRADE':
			if ( ! $assignment_id )
			{
				$total = $total_percent = 0;

				if ( ! empty( $points_RET[$THIS_RET['STUDENT_ID']] ) )
				{
					foreach ( (array) $points_RET[$THIS_RET['STUDENT_ID']] as $partial_points )
					{
						if ( $partial_points['PARTIAL_TOTAL'] != 0 || $gradebook_config['WEIGHT'] != 'Y' )
						{
							$total += $partial_points['PARTIAL_POINTS'] * ( $gradebook_config['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'] : 1 );
							$total_percent += ( $gradebook_config['WEIGHT'] == 'Y' ? $partial_points['FINAL_GRADE_PERCENT'] : $partial_points['PARTIAL_TOTAL'] );
						}
					}

					if ( $total_percent != 0 )
					{
						$total /= $total_percent;
					}
				}

				return ( $total > $max_allowed ? '<span style="color:red">' : '' ) . _Percent( $total, 0 ) . ( $total > $max_allowed ? '</span>' : '' );
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					//FJ default points
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
					}

					if ( $total_points != 0 )
					{
						if ( $points != '-1' )
						{
							return ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ? ( $points > $total_points * $max_allowed ? '<span style="color:red">' : '<span>' ) : '<span>' ) . _Percent( $points / $total_points, 0 ) . '</span>';
						}
						else
						{
							return _( 'N/A' );
						}
					}
					else
					{
						return _( 'E/C' );
					}
				}
			}

			break;

		case 'LETTER_GRADE':
			if ( ! $assignment_id )
			{
				return '<b>' . _makeLetterGrade( $total ) . '</b>';
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					$total_points = $assignments_RET[$assignment_id][1]['POINTS'];
					//FJ default points
					$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];

					if ( is_null( $points ) )
					{
						$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
					}

					if ( $total_points != 0 )
					{
						if ( $points != '-1' )
						{
							return ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ? '' : '<span style="color:gray">' ) . '<b>' . _makeLetterGrade( $points / $total_points ) . '</b>' . ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ? '' : '</span>' );
						}
						else
						{
							return _( 'N/A' );
						}
					}
					else
					{
						return _( 'N/A' );
					}
				}
			}

			break;

		case 'COMMENT':
			if ( ! $assignment_id )
			{
			}
			else
			{
				if ( ! empty( $_REQUEST['include_all'] )
					|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
						|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
						|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
						&& ( ! $THIS_RET['END_EPOCH']
							|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
				{
					return TextInput(
						$current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['COMMENT'],
						'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][COMMENT]',
						'',
						' maxlength=100'
					);
				}
			}

			break;
        case 'EXAM':


            $parentsem = DBGet( "SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP(). "'", array(), array( ));
            $tesa = DBGet("SELECT grade_percent FROM student_report_card_grades WHERE exam='Y' and student_id=".$THIS_RET['STUDENT_ID']." AND marking_period_id='".$parentsem[1]['PARENT_ID']."'");
            $course_period_id = UserCoursePeriod();
            $mkp = UserMP();
            $syr = UserSyear();
            $usrsc = UserSchool();
            $exa = DBGet("SELECT exam FROM student_report_card_grades 
            WHERE 
            syear='{$syr}' AND 
            school_id='{$usrsc}' AND 
            student_id='{$THIS_RET['STUDENT_ID']}' AND 
            course_period_id='{$course_period_id}' AND
            marking_period_id='{$mkp}' ");

           if(isset($exa[1]['EXAM'])){
               $examen=$exa[1]['EXAM'];
               $div=true;
           }else{
               $examen='';
               $div=false;
           }

         /*   foreach($quarterele as $quarter){
                $total+=$quarter['percent']*($quarter['grade']/100);
            }

           $grade_sem = $percent_qtr * ($qtr_grade / 100) + $percent_exam * ($exam_grade / 100);

return "<span class=\"span-grade-points\"><script>var htmlvalues{$THIS_RET['STUDENT_ID']}EXAM='<input type=\"text\" id=\"{$THIS_RET['STUDENT_ID']}exam\" name=\"exam[".$THIS_RET["STUDENT_ID"]."]\" value=\"".$examen."\"  size=2 maxlength=7 >';</script><div id=\"divvalues{$THIS_RET['STUDENT_ID']}exam\">
		<div class=\"onclick\" tabindex=\"0\" onfocus=\"addHTML(htmlvalues{$THIS_RET['STUDENT_ID']}EXAM,'divvalues{$THIS_RET['STUDENT_ID']}exam',true); $('#{$THIS_RET['STUDENT_ID']}exam').focus(); $('#divvalues{$THIS_RET['STUDENT_ID']}exam').click();\"><span class=\"underline-dots\">{$examen}</span></div></div></span><span>&nbsp;/&nbsp;100</span>";
             // return '<span class="span-grade-points"> <input value="'.$examen.'" name="exam['.$THIS_RET['STUDENT_ID'].']" type="number"></span>';
*/

            return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
                TextInput(
                    $examen,
                    'exam[' . $THIS_RET['STUDENT_ID'] . ']',
                    '',
                    ' size=2 maxlength=7',
                    $div
                ) . '</span>
				<span>&nbsp;/&nbsp;' . 100 . '</span>';


            break;
        case 'CB1':
            $parentsem = DBGet( "SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP(). "'", array(), array( ));
            $value=1;
            $course_period_id = UserCoursePeriod();
            $current_commentsB_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID, g.NOX
		FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $parentsem[1]['PARENT_ID'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID
			FROM REPORT_CARD_COMMENTS
			WHERE COURSE_ID IS NULL) ORDER BY g.NOX", array(), array( 'STUDENT_ID' ) );


            $commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL
		ORDER BY SORT_ORDER", array(), array( 'ID' ) );

            $commentsB_select = array();

            if ( is_array( $commentsB_RET ) )
            {
                foreach ( (array) $commentsB_RET as $id => $comment )
                {
                    $commentsB_select += array( $id => array( $comment[1]['SORT_ORDER'] . ' - ' . ( mb_strlen( $comment[1]['TITLE'] ) > 99 + 3 ? mb_substr( $comment[1]['TITLE'], 0, 99 ) . '...' : $comment[1]['TITLE'] ), $comment[1]['TITLE'] ) );
                }
            }

            if(!empty($current_commentsB_RET[$THIS_RET['STUDENT_ID']][2]) AND $current_commentsB_RET[$THIS_RET['STUDENT_ID']][2]['NOX']=='1'){
                $select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][2]['REPORT_CARD_COMMENT_ID'];
                $div=true;
            }elseif(!empty($current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]) AND $current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]['NOX']=='1'){
                $select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]['REPORT_CARD_COMMENT_ID'];
                $div=true;
            }else{
                $select= _( 'N/A' );
                $div=false;
            }

            return  SelectInput( $select, 'commentsBx[' . $THIS_RET['STUDENT_ID'] . '][' . $value . ']', '', $commentsB_select, _( 'N/A' ), '', $div );

            break;
        case 'CB2':
            $parentsem = DBGet( "SELECT parent_id FROM school_marking_periods WHERE marking_period_id='" . UserMP(). "'", array(), array( ));
            $value=2;
            $course_period_id = UserCoursePeriod();
            $current_commentsB_RET = DBGet( "SELECT g.STUDENT_ID,g.REPORT_CARD_COMMENT_ID, g.NOX
		FROM STUDENT_REPORT_CARD_COMMENTS g,COURSE_PERIODS cp
		WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID
		AND cp.COURSE_PERIOD_ID='" . $course_period_id . "'
		AND g.MARKING_PERIOD_ID='" . $parentsem[1]['PARENT_ID'] . "'
		AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID
			FROM REPORT_CARD_COMMENTS
			WHERE COURSE_ID IS NULL) ORDER BY g.NOX", array(), array( 'STUDENT_ID' ) );

            $commentsB_RET = DBGet( "SELECT ID,TITLE,SORT_ORDER
		FROM REPORT_CARD_COMMENTS
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		AND COURSE_ID IS NULL
		ORDER BY SORT_ORDER", array(), array( 'ID' ) );

            $commentsB_select = array();

            if ( is_array( $commentsB_RET ) )
            {
                foreach ( (array) $commentsB_RET as $id => $comment )
                {
                    $commentsB_select += array( $id => array( $comment[1]['SORT_ORDER'] . ' - ' . ( mb_strlen( $comment[1]['TITLE'] ) > 99 + 3 ? mb_substr( $comment[1]['TITLE'], 0, 99 ) . '...' : $comment[1]['TITLE'] ), $comment[1]['TITLE'] ) );
                }
            }

            if(!empty($current_commentsB_RET[$THIS_RET['STUDENT_ID']][2])  AND $current_commentsB_RET[$THIS_RET['STUDENT_ID']][2]['NOX']=='2'){ /// 2 deoarece e Comment 2
                $select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][2]['REPORT_CARD_COMMENT_ID'];
                $div=true;
            }elseif(!empty($current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]) AND $current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]['NOX']=='2'){
                $select = $current_commentsB_RET[$THIS_RET['STUDENT_ID']][1]['REPORT_CARD_COMMENT_ID'];
                $div=true;
            }else{
                $select= _( 'N/A' );
                $div=false;
            }

            return  SelectInput( $select, 'commentsBx[' . $THIS_RET['STUDENT_ID'] . '][' . $value . ']', '', $commentsB_select, _( 'N/A' ), '', $div );

            break;
	}
}

/**
 * @param $value
 * @param $column
 */
function _makeExtraStuCols( $value, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$assignment_count,
	$count_assignments,
		$max_allowed;

	//FJ default points

	if ( is_null( $THIS_RET['POINTS'] ) )
	{
		$THIS_RET['POINTS'] = $assignments_RET[$THIS_RET['ASSIGNMENT_ID']][1]['DEFAULT_POINTS'];
	}

	switch ( $column )
	{
		case 'POINTS':
			$assignment_count++;

			//FJ default points
			$div = true;

			if ( is_null( $value ) )
			{
				$value = $assignments_RET[$THIS_RET['ASSIGNMENT_ID']][1]['DEFAULT_POINTS'];
				$div = false;
			}

			if ( $value == '-1' )
			{
				$value = '*';
			}
			elseif ( mb_strpos( $value, '.' ) )
			{
				$value = rtrim( rtrim( $value, '0' ), '.' );
			}

//			return '<table cellspacing=0 cellpadding=1><tr><td>'.TextInput($value,'values['.$THIS_RET['STUDENT_ID'].']['.$THIS_RET['ASSIGNMENT_ID'].'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'</td><td>&nbsp;/&nbsp;</td><td>'.$THIS_RET['TOTAL_POINTS'].'</td></tr></table>';

			return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
			TextInput(
				$value,
				'values[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['ASSIGNMENT_ID'] . '][POINTS]',
				'',
				' size=2 maxlength=7',
				$div
			) . '</span>
				<span>&nbsp;/&nbsp;' . $THIS_RET['TOTAL_POINTS'] . '</span>';
			break;

		case 'PERCENT_GRADE':
			if ( $THIS_RET['TOTAL_POINTS'] != 0 )
			{
				if ( $THIS_RET['POINTS'] != '-1' )
				{
					return ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' ? ( $THIS_RET['POINTS'] > $THIS_RET['TOTAL_POINTS'] * $max_allowed ? '<span style="color:red">' : '<span>' ) : '<span>' ) . _Percent( $THIS_RET['POINTS'] / $THIS_RET['TOTAL_POINTS'], 0 ) . '</span>';
				}
				else
				{
					return _( 'N/A' );
				}
			}
			else
			{
				return _( 'E/C' );
			}

			break;

		case 'LETTER_GRADE':
			if ( $THIS_RET['TOTAL_POINTS'] != 0 )
			{
				if ( $THIS_RET['POINTS'] != '-1' )
				{
					return ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' ? '' : '<span style="color:gray">' ) . '<b>' . _makeLetterGrade( $THIS_RET['POINTS'] / $THIS_RET['TOTAL_POINTS'] ) . '</b>' . ( $THIS_RET['DUE'] || $THIS_RET['POINTS'] != '' ? '' : '</span>' );
				}
				else
				{
					return _( 'N/A' );
				}
			}
			else
			{
				return _( 'N/A' );
			}

			break;

		case 'COMMENT':
			return TextInput(
				$value,
				'values[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['ASSIGNMENT_ID'] . '][COMMENT]',
				'',
				' maxlength=100'
			);
			break;
			
			case 'UPLOAD':
	
	//GetInputID('tables[' . $_REQUEST['assignment_id'] . '][SUBMISSION]').'\')
	
			$markedassigns_column_html = '';
			$markedAssignments = DBGet( "SELECT ma.FILE
				FROM GRADEBOOK_MARKED_ASSIGNMENT_FILES ma
				WHERE ma.ASSIGNMENT_ID='" . $THIS_RET['ASSIGNMENT_ID'] . "'
				AND ma.STUDENT_ID='" . $THIS_RET['STUDENT_ID'] . "'"
			);
			
			
			
			foreach((array)$markedAssignments as $markedAssignment){
				$filelink = GetAssignmentFileLink( $markedAssignment['FILE'] );
				if(!empty($filelink))
				$markedassigns_column_html .= '<br/>'.GetAssignmentFileLink( $markedAssignment['FILE'] );
			}
			
			$browsebutton = '
				<script>
					$(document).ready(function(){
						
						$(\'.wrapper'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'\').each(function() {
							var $wrapper = $(\'.'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-fields\', this);
							$(".'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'add-field", $(this)).click(function(e) {
								$(\'.'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-field:first-child\', $wrapper).clone(true).appendTo($wrapper).find(\'input\').val(\'\').focus();
							});
							$(\'.'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-field .remove-field\', $wrapper).click(function() {
								if ($(\'.'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-field\', $wrapper).length > 1)
									$(this).parent(\'.'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-field\').remove();
							});
						});
					});
				</script>
		
				<div class="wrapper'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'">
				<div class="'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-fields">
				<div class="'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'multi-field">
				  '.FileInput( 'FILE'. $THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID'] . '[]', _( '' ))
				  . '
				</div>
				</div>
				<button type="button" class="'.GetInputID($THIS_RET['STUDENT_ID'] .'_'. $THIS_RET['ASSIGNMENT_ID']).'add-field">+</button>'.$markedassigns_column_html.'
				</div>
				<input type="hidden" name="'.'values[' . $THIS_RET['STUDENT_ID'] . '][' . $THIS_RET['ASSIGNMENT_ID'] . '][TMP]'.'" value="true"/>
				';
			return $browsebutton;
			
			break;	
	}
}

/**
 * @param $assignment_id
 * @param $column
 */
function _makeExtraCols( $assignment_id, $column )
{
	global $THIS_RET,
	$assignments_RET,
	$current_RET,
	$old_student_id,
	$student_count,
	$count_students,
		$max_allowed;

	if ( $THIS_RET['STUDENT_ID'] != $old_student_id )
	{
		$student_count++;

		$old_student_id = $THIS_RET['STUDENT_ID'];
	}

	$total_points = $assignments_RET[$assignment_id][1]['POINTS'];

	if ( ! empty( $_REQUEST['include_all'] )
		|| ( $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'] != ''
			|| ! $assignments_RET[$assignment_id][1]['DUE_EPOCH']
			|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] >= $THIS_RET['START_EPOCH']
			&& ( ! $THIS_RET['END_EPOCH']
				|| $assignments_RET[$assignment_id][1]['DUE_EPOCH'] <= $THIS_RET['END_EPOCH'] ) ) )
	{
		//FJ default points
		$points = $current_RET[$THIS_RET['STUDENT_ID']][$assignment_id][1]['POINTS'];
		$div = true;

		if ( is_null( $points ) )
		{
			$points = $assignments_RET[$assignment_id][1]['DEFAULT_POINTS'];
			$div = false;
		}

		if ( $points == '-1' )
		{
			$points = '*';
		}
		elseif ( mb_strpos( $points, '.' ) )
		{
			$points = rtrim( rtrim( $points, '0' ), '.' );
		}

		if ( $total_points != 0 )
		{

			if ( $points != '*' )
			{

				// modif Francois: display letter grade according to Configuration
				return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
				TextInput(
					$points,
					'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]',
					'',
					' size=2 maxlength=7',
					$div
				) . '</span>
					<span>&nbsp;/&nbsp;' . $total_points .
					( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) >= 0 ?
					'&nbsp;&minus;&nbsp;' . ( $assignments_RET[$assignment_id][1]['DUE'] || $points != '' ?
						( $points > $total_points * $max_allowed ?
							'<span style="color:red">' :
							'<span>'
						) :
						'<span>' ) .
					_Percent( $points / $total_points, 0 ) . '</span>' :
					'' ) .
					( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) <= 0 ?
					'&nbsp;&minus;&nbsp;<b>' . _makeLetterGrade( $points / $total_points ) . '</b>' :
					'' ) . '</span>';
			}

			//return '<table cellspacing=0 cellpadding=1><tr align=center><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<hr />'.$total_points.'</td><td>&nbsp;'._('N/A').'<br />&nbsp;'._('N/A').'</td></tr></table>';

			return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
			TextInput(
				$points,
				'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]',
				'',
				' size=2 maxlength=7',
				$div
			) . '</span>
				<span>&nbsp;/&nbsp;' . $total_points . '&nbsp;&minus;&nbsp;' . _( 'N/A' ) . '</span><input type="hidden" name="exam['. $THIS_RET['STUDENT_ID'].']">';
		}

		//return '<table class="cellspacing-0"><tr class="center"><td>'.TextInput($points,'values['.$THIS_RET['STUDENT_ID'].']['.$assignment_id.'][POINTS]','',' size=2 maxlength=7 tabindex='.$tabindex).'<hr />'.$total_points.'</td><td>&nbsp;E/C</td></tr></table>';

		return '<span' . ( $div ? ' class="span-grade-points"' : '' ) . '>' .
		TextInput(
			$points,
			'values[' . $THIS_RET['STUDENT_ID'] . '][' . $assignment_id . '][POINTS]',
			'',
			' size=2 maxlength=7',
			$div
		) . '</span>
			<span>&nbsp;/&nbsp;' . $total_points . '&nbsp;&minus;&nbsp;' . _( 'E/C' ) . '</span><input type="hidden" name="exam['. $THIS_RET['STUDENT_ID'].']">';

	}
}

/**
 * @param $num
 * @param $decimals
 */
function _Percent( $num, $decimals = 2 )
{
	return number_format( $num * 100 ) . '%';
}
