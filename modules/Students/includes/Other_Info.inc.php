<?php
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

$separator = issetVal( $separator, '' );

$columns = DBGetOne( "SELECT COLUMNS
	FROM STUDENT_FIELD_CATEGORIES
	WHERE ID='" . $_REQUEST['category_id'] . "'" );

$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
	FROM CUSTOM_FIELDS
	WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
	ORDER BY SORT_ORDER,TITLE" );

$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

$value = array();

if ( UserStudentID() )
{
	$custom_RET = DBGet( "SELECT *
		FROM STUDENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	$value = $custom_RET[1];
}

if ( ! empty( $fields_RET ) )
{
	echo $separator;

	// echo '<table class="other-info width-100p valign-top fixed-col">';
}

$i = 1;

/**
 * Number of Columns per Row
 * Default: 3
 *
 * @var int
 */
$per_row = $columns ? (int) $columns : 3;

foreach ( (array) $fields_RET as $field )
{
	//echo '<pre>'; var_dump($field); echo '</pre>';

	if ( ( $i - 1 )%$per_row === 0 )
		 echo '<div class="row">';

	echo '<div class="form-group">';

	switch ( $field['TYPE'] )
	{
		case 'text':
		case 'numeric':

			echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'autos':

			echo _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'date':

			echo _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			//FJ display age next to birthdate
			if ( $field['ID'] !== '200000004' )
				break;

		case 'age':

			echo '</div></div>';

			$i++;

			if ( ( $i - 1 )%$per_row === 0 )
				// echo '</tr><tr class="st">';

			echo '<div class="form-group">';

			echo _makeStudentAge( 'CUSTOM_' . $field['ID'], _( 'Age' ), 'class="form-control"' );

			break;

		case 'exports':
		case 'select':

			echo _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'multiple':

			echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'radio':

			echo _makeCheckboxInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'textarea':

			// Only 2 fields per row when textarea
			if ( $per_row > 2 )
			{
				// New row
				echo '</div></div class="row"><div class="row">';

				echo '<td colspan="' . round( $per_row / 2 ) . '">';

				$i = round( $per_row / 2 );
			}

			echo _makeTextAreaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'students' );

			break;

		case 'files':

			echo _makeFilesInput('CUSTOM_' . $field['ID'], $field['TITLE'], 'students','Modules.php?modname=' . $_REQUEST['modname'] .'&category_id=' . $_REQUEST['category_id'] . '&student_id=' . $_REQUEST['student_id'] .
				'&modfunc=remove_file&id=' . $field['ID'] . '&filename='
			);

			break;
	}

	echo '</div>';

	if ( $i%$per_row === 0 )
		echo '</div>';

	$i++;
}

if ( $i > 1 )
{
	if ( ( $i - 1 )%$per_row !== 0 )
		echo '</div>';

	echo '</div>';
}
