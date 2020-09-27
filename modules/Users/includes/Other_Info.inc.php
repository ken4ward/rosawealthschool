<?php

//this is for the email and phone number fields
require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';

$columns = DBGetOne( "SELECT COLUMNS
	FROM STAFF_FIELD_CATEGORIES
	WHERE ID='" . $_REQUEST['category_id'] . "'" );

$fields_RET = DBGet( "SELECT ID,TITLE,TYPE,SELECT_OPTIONS,DEFAULT_SELECTION,REQUIRED
	FROM STAFF_FIELDS
	WHERE CATEGORY_ID='" . $_REQUEST['category_id'] . "'
	ORDER BY SORT_ORDER,TITLE" );

$fields_RET = ParseMLArray( $fields_RET, 'TITLE' );

$value = array();

if ( UserStaffID() )
{
	$custom_RET = DBGet( "SELECT * FROM
		STAFF
		WHERE STAFF_ID='" . UserStaffID() . "'" );

	$value = $custom_RET[1];
}

if ( ! empty( $fields_RET ) )
{
	// echo $separator;

	echo '<div class=" container-fluid">
	<div class="col-md-8 offset-md-2">
		<div class="card bg-light mb-3" style="min-width: 100%;">

		  	<div class="card-body">
		  		<div class="container">
				  <div class="row">
				    <div class="col-sm">';
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

			if ( $field['ID'] === '200000000' )
			{
				// @since 5.9 Move Email & Phone Staff Fields to custom fields.
				// FJ Moodle integrator: email required
				echo TextInput(issetVal( $value['EMAIL'] ),//issetVal( $value['CUSTOM_' . $field['ID']] ),
					'staff[EMAIL]', '', 'class="form-control" maxlength=255 type="email" pattern="[^ @]*@[^ @]*" placeholder="' . _( 'Email (required)' ) . '"' .
						( ! empty( $_REQUEST['moodle_create_user'] ) || ! empty( $old_user_in_moodle )
							|| $field['REQUIRED'] ? ' required' : '' ),
					empty( $_REQUEST['moodle_create_user'] )
				);
			}
			else
			{
				echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );
			}

			break;

		case 'autos':

			echo _makeAutoSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

			echo _makeTextInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'date':

			echo _makeDateInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'exports':
		case 'select':

			echo _makeSelectInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'multiple':

			echo _makeMultipleInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'radio':

			echo _makeCheckboxInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'textarea':

			// Only 2 fields per row when textarea
			if ( $per_row > 2 )
			{
				// New row
				echo '</div></div><div class="row">';

				echo '<td colspan="' . round( $per_row / 2 ) . '">';

				$i = round( $per_row / 2 );
			}

			echo _makeTextAreaInput( 'CUSTOM_' . $field['ID'], $field['TITLE'], 'staff' );

			break;

		case 'files':

			echo _makeFilesInput('CUSTOM_' . $field['ID'], $field['TITLE'], 'staff','Modules.php?modname=' . $_REQUEST['modname'] .'&category_id=' . $_REQUEST['category_id'] . '&staff_id=' . $_REQUEST['staff_id'] . '&modfunc=remove_file&id=' . $field['ID'] . '&filename=');

			break;
	}

	echo '</td>';

	if ( $i%$per_row === 0 )
		echo '</tr>';

	$i++;
}

if ( $i > 1 )
{
	if ( ( $i - 1 )%$per_row !== 0 )
		echo '</tr>';

	echo '</table>';
}
