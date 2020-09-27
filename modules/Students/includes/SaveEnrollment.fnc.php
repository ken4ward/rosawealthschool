<?php

/*
 * save student enrollment
 * create new enrollment when adding student
 */
function SaveEnrollment()
{
	global $error, $student_id;

	if ( ! empty( $_POST['month_values']['STUDENT_ENROLLMENT'] )
		|| ! empty( $_POST['values']['STUDENT_ENROLLMENT'] ) )
	{
		//FJ check if student already enrolled on that date when updating START_DATE

		foreach ( (array) $_REQUEST['month_values']['STUDENT_ENROLLMENT'] as $stu_enrol_id => $stu_enrol_month )
		{
			if ( $stu_enrol_id == 'new' && ! $_REQUEST['values']['STUDENT_ENROLLMENT']['new']['ENROLLMENT_CODE'] && ! $_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'] )
			{
				unset( $_REQUEST['values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
				unset( $_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
			}
			elseif ( $stu_enrol_id == 'new'
				&& $_REQUEST['values']['STUDENT_ENROLLMENT']['new']['SCHOOL_ID'] )
			{
				if ( $_REQUEST['values']['STUDENT_ENROLLMENT']['new']['SCHOOL_ID'] !== UserSchool() )
				{
					// @since 5.4 Update current school to enrollment school.
					$_SESSION['UserSchool'] = $_REQUEST['values']['STUDENT_ENROLLMENT']['new']['SCHOOL_ID'];
				}
			}
			elseif ( UserStudentID() && ! empty( $stu_enrol_month['START_DATE'] ) )
			{
				$date = RequestedDate(
					$_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE'],
					$_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE'],
					$_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id]['START_DATE']
				);

				$found_RET = 1;

				if ( $date )
				{
					$found_RET = DBGet( "SELECT ID
						FROM STUDENT_ENROLLMENT
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . UserSyear() . "'
						AND '" . $date . "' BETWEEN START_DATE AND END_DATE" );
				}

				if ( $found_RET )
				{
					unset( $_REQUEST['values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['day_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['month_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );
					unset( $_REQUEST['year_values']['STUDENT_ENROLLMENT'][$stu_enrol_id] );

					$error[] = _( 'The student is already enrolled on that date, and cannot be enrolled a second time on the date you specified. Please fix, and try enrolling the student again.' );
				}
			}
			elseif ( UserStudentID()
				&& isset( $stu_enrol_month['START_DATE'] )
				&& empty( $stu_enrol_month['START_DATE'] ) )
			{
				// @since 5.4 Delete enrollment record if start date is empty.
				// Check first if Student has previous enrollment records.
				$has_previous_enrollment = DBGetOne( "SELECT 1
					FROM STUDENT_ENROLLMENT
					WHERE STUDENT_ID='" . UserStudentID() . "'
					AND SYEAR='" . UserSyear() . "'
					AND ID<>'" . $stu_enrol_id . "'
					AND START_DATE<(SELECT START_DATE
						FROM STUDENT_ENROLLMENT
						WHERE ID='" . $stu_enrol_id . "');" );

				if ( $has_previous_enrollment )
				{
					DBQuery( "DELETE FROM STUDENT_ENROLLMENT
						WHERE STUDENT_ID='" . UserStudentID() . "'
						AND SYEAR='" . UserSyear() . "'
						AND ID='" . $stu_enrol_id . "';" );
				}
			}
		}

		$iu_extra['STUDENT_ENROLLMENT'] = "STUDENT_ID='" . ( UserStudentID() ? UserStudentID() : $student_id ) . "' AND ID='__ID__'";
		$iu_extra['fields']['STUDENT_ENROLLMENT'] = 'ID,SYEAR,STUDENT_ID,';
		$iu_extra['values']['STUDENT_ENROLLMENT'] = db_seq_nextval( 'student_enrollment_id_seq' ) .
			",'" . UserSyear() . "','" . ( UserStudentID() ? UserStudentID() : $student_id ) . "',";

		SaveData( $iu_extra );
	}
}
