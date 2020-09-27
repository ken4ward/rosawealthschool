<?php
/**
 * Student / User / Address / People / Medical / Enrollment fields
 * Make functions
 * Wrappers for Inputs functions
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

// OTHER INFO.
/**
 * Make Text Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Text Input
 */
function _makeTextInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	if ( $field['TYPE'] === 'numeric' )
	{
		$value[ $column ] = str_replace( '.00', '', $value[ $column ] );

		// Fix Number Field SQL column limit: type numeric(20,2).
		$options = ' type="number" step="any" max="999999999999999999" min="-999999999999999999"';
	}
	elseif ( Config( 'STUDENTS_EMAIL_FIELD' ) === str_replace( 'CUSTOM_', '', $column ) )
	{
		$options = 'maxlength=255 type="email" pattern="[^ @]*@[^ @]*" placeholder="' . _( 'Email' ) . '"';

		if ( ! empty( $_REQUEST['moodle_create_student'] ) )
		{
			// Moodle integrator, email required.
			$options .= ' required';
		}
	}
	else
		$options = 'maxlength=1000';

	// FJ text field is required.
	$options .= $field['REQUIRED'] === 'Y' ? ' required' : '';

	return TextInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$options,
		$div
	);
}


/**
 * Make Date Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Date Input
 */
function _makeDateInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// FJ date field is required.
	$required = false;

	if ( $field['REQUIRED'] === 'Y' )
	{
		$required = true;
	}

	return DateInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$div,
		true,
		$required
	);
}


/**
 * Make Select Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Select Input
 */
function _makeSelectInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	$select_options = $options = array();

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( (array) $select_options as $option )
	{
		if ( $field['TYPE'] === 'exports' )
		{
			$option = explode( '|', $option );

			if ( $option[0] != '' )
				$options[$option[0]] = $option[0];
		}
		else
			$options[ $option ] = $option;
	}

	// FJ select field is required.
	$extra = ( $field['REQUIRED'] === 'Y' ? 'required': '' );

	return SelectInput(
		issetVal( $value[ $column ], '' ),
		$request . '[' . $column . ']',
		$name,
		$options,
		'N/A',
		$extra,
		$div
	);
}


/**
 * Make Auto Select Input
 *
 * @since 4.6 Add $options_RET parameter.
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column      Field column.
 * @param  string $name        Field name.
 * @param  string $request     students|staff|values[PEOPLE]|values[ADDRESS].
 * @param  array  $options_RET Options array (optional).
 *
 * @return string          Auto Select Input
 */
function _makeAutoSelectInput( $column, $name, $request, $options_RET = array() )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// Build the select list...
	// Get the standard selects.
	$select_options = array();

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( (array) $select_options as $option )
	{
		if ( $option != '' )
		{
			$options[ $option ] = $option;
		}
	}

	// Add the 'new' option, is also the separator.
	$options['---'] = '-' . _( 'Edit' ) . '-';

	if ( $field['TYPE'] === 'autos'
		&& AllowEdit() ) // We don't really need the select list if we can't edit anyway.
	{
		// Add values found in current and previous year.
		if ( $request === 'values[ADDRESS]' )
		{
			$options_SQL = "SELECT DISTINCT a.CUSTOM_" . $field['ID'] . ",upper(a.CUSTOM_" . $field['ID'] . ") AS SORT_KEY
				FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS s,STUDENT_ENROLLMENT sse
				WHERE a.ADDRESS_ID=sja.ADDRESS_ID
				AND s.STUDENT_ID=sja.STUDENT_ID
				AND sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND a.CUSTOM_" . $field['ID'] . " IS NOT NULL
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'values[PEOPLE]' )
		{
			$options_SQL = "SELECT DISTINCT p.CUSTOM_" . $field['ID'] . ",upper(p.CUSTOM_" . $field['ID'] . ") AS SORT_KEY
				FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp,STUDENTS s,STUDENT_ENROLLMENT sse
				WHERE p.PERSON_ID=sjp.PERSON_ID
				AND s.STUDENT_ID=sjp.STUDENT_ID
				AND sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND p.CUSTOM_" . $field['ID'] . " IS NOT NULL
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'students' )
		{
			$options_SQL = "SELECT DISTINCT s.CUSTOM_" . $field['ID'] . ",upper(s.CUSTOM_" . $field['ID'] . ") AS SORT_KEY
				FROM STUDENTS s,STUDENT_ENROLLMENT sse
				WHERE sse.STUDENT_ID=s.STUDENT_ID
				AND (sse.SYEAR='" . UserSyear() . "' OR sse.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND s.CUSTOM_" . $field['ID'] . " IS NOT NULL
				ORDER BY SORT_KEY";
		}
		elseif ( $request === 'staff' )
		{
			$options_SQL = "SELECT DISTINCT s.CUSTOM_" . $field['ID'] . ",upper(s.CUSTOM_" . $field['ID'] . ") AS SORT_KEY
				FROM STAFF s
				WHERE (s.SYEAR='" . UserSyear() . "' OR s.SYEAR='" . ( UserSyear() - 1 ) . "')
				AND s.CUSTOM_" . $field['ID'] . " IS NOT NULL
				ORDER BY SORT_KEY";
		}

		if ( empty( $options_RET ) )
		{
			$options_RET = DBGet( $options_SQL );
		}

		foreach ( (array) $options_RET as $option )
		{
			$option_value = $option[ 'CUSTOM_' . $field['ID'] ];

			if ( $option_value != ''
				&& ! isset( $options[ $option_value ] ) )
			{
				$options[ $option_value ] = array(
					$option_value,
					'<span style="color:blue">' . $option_value . '</span>'
				);
			}
		}
	}

	// Make sure the current value is in the list.
	if ( $value[ $column ] != ''
		&& ! isset( $options[ $value[ $column ] ] ) )
	{
		$options[ $value[ $column ] ] = array(
			$value[ $column ],
			'<span style="color:' . ( $field['TYPE'] === 'autos' ? 'blue' : 'green' ) . '">' . $value[ $column ] . '</span>'
		);
	}

	if ( $value[ $column ] != '---'
		&& count( $options ) > 1 )
	{
		// FJ select field is required.
		$extra = ( $field['REQUIRED'] === 'Y' ? 'required' : '' );

		return SelectInput(
			$value[ $column ],
			$request . '[' . $column . ']',
			$name,
			$options,
			'N/A',
			$extra,
			$div
		);
	}

	// FJ new option.
	return TextInput(
		$value[ $column ] === '---' ?
			array( '---', '<span style="color:red">-' . _( 'Edit' ) . '-</span>' ) :
			$value[ $column ],
		$request . '[' . $column . ']',
		$name,
		( $field['REQUIRED'] === 'Y' ? 'required' : '' ),
		$div
	);
}


/**
 * Make Checkbox Input
 *
 * @since 6.4.2 Fix Required & div for checkbox input.
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Checkbox Input
 */
function _makeCheckboxInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	$new = _isNew( $request );

	if ( $new
		|| ( $field['REQUIRED'] === 'Y'
			&& empty( $value[ $column ] ) ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		// No div if new or if Required AND not checked.
		$div = false;
	}

	return CheckboxInput(
		issetVal( $value[ $column ] ),
		$request . '[' . $column . ']',
		$name,
		'',
		$new,
		'Yes',
		'No',
		$div,
		( $field['REQUIRED'] === 'Y' ? 'required' : '' )
	);
}


/**
 * Make Textarea Input
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Textarea Input
 */
function _makeTextAreaInput( $column, $name, $request )
{
	global $value,
		$field;

	$div = true;

	if ( $field['DEFAULT_SELECTION']
		&& _isNew( $request ) )
	{
		$value[ $column ] = $field['DEFAULT_SELECTION'];

		$div = false;
	}

	// FJ text area is required.
	// FJ textarea field maxlength=50000 (soft limit).
	return TextAreaInput(
		issetVal( $value[ $column ] ),
		$request . '[' . $column . ']',
		$name,
		'maxlength=50000' . ( $field['REQUIRED'] == 'Y' ? ' required': '' ),
		$div
	);
}


/**
 * Make Files Input
 *
 * @since 4.6
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string          Files Input
 */
function _makeFilesInput( $column, $name, $request, $remove_url = '' )
{
	global $value,
		$field;

	require_once 'ProgramFunctions/FileUpload.fnc.php';

	$div = true;

	$file_paths = explode( '||', trim( $value[ $column ], '||' ) );

	$files = array();

	foreach ( (array) $file_paths as $file_path )
	{
		if ( ! file_exists( $file_path ) )
		{
			continue;
		}

		$file_name = mb_substr( mb_strrchr( $file_path, '/' ), 1 );

		$file_size = HumanFilesize( filesize( $file_path ) );

		// Truncate file name if > 36 chars.
		$file_name_display = mb_strlen( $file_name ) <= 36 ?
			$file_name :
			mb_substr( $file_name, 0, 30 ) . '..' . mb_strrchr( $file_name, '.' );

		$file = button(
			'download',
			$file_name_display,
			'"' . $file_path . '" target="_blank" title="' . $file_name . ' (' . $file_size . ')"',
			'bigger'
		);

		if ( AllowEdit() && $remove_url )
		{
			$file = button(
				'remove',
				'',
				'"' . $remove_url . urlencode( $file_name ) . '" title="' . _( 'Delete' ) . '"'
			) . '&nbsp;' . $file;
		}

		$files[] = $file;
	}

	$files_html = '';

	if ( $files )
	{
		$files_html = '<table class="widefat"><tbody><tr><td>' .
			implode( '</td></tr><tr><td>', $files ) . '</td></tr></tbody></table>';
	}

	$required = $field['REQUIRED'] == 'Y' && AllowEdit() && ! $files;

	if ( AllowEdit() )
	{
		$files_html .= FileInput(
			$request . $column,
			'',
			( $required ? ' required': '' )
		);
	}
	elseif ( ! $files )
	{
		$files_html = '-';
	}

	$files_html .= FormatInputTitle(
		$name,
		( AllowEdit() ? $request . $column : '' ),
		$required,
		( AllowEdit() || ! $files ? '<br />' : '' )
	);

	return $files_html;
}


/**
 * Make Multiple Input
 *
 * @since 6.1 Return HTML instead of echo.
 *
 * @global array  $value
 * @global array  $field
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return string Multiple Input
 */
function _makeMultipleInput( $column, $name, $request )
{
	global $value,
		$field;

	if ( ! AllowEdit()
		|| isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return ( ! empty( $value[ $column ] ) ?
			str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ) :
			'-' ) .
			FormatInputTitle( $name );
	}

	$select_options = array();

	if ( $field['SELECT_OPTIONS'] )
	{
		$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );
	}

	foreach ( (array) $select_options as $option )
	{
		$options[ $option ] = $option;
	}

	$table = '<table class="cellpadding-5">';

	if ( count( $options ) > 12 )
	{
		$table .= '<tr><td colspan="2">';
		$table .= FormatInputTitle( $name, '', false, '' );
		$table .= '<table class="width-100p" style="height: 7px; border:1;border-style: solid solid none solid;"><tr><td></td></tr></table>';
		$table .= '</td></tr>';
	}

	$table .= '<tr>';

	$i = 0;

	foreach ( (array) $options as $option )
	{
		if ( $i % 2 === 0 )
		{
			$table .= '</tr><tr>';
		}

		// FJ add <label> on checkbox.
		$table .= '<td><label>
			<input type="checkbox" name="' . $request . '[' . $column . '][]" value="' .
				htmlspecialchars( $option, ENT_QUOTES ) . '"' .
				( ! empty( $value[ $column ] )
					&& mb_strpos( $value[ $column ], '||' . $option . '||' ) !== false ? ' checked' : '' ) . ' /> ' .
				$option .
		'</label></td>';

		$i++;
	}

	$table .= '</tr><tr><td colspan="2">';

	// FJ fix bug none selected not saved.
	$table .= '<input type="hidden" name="' . $request . '[' . $column . '][none]" value="" />';

	$table .= '<table class="width-100p" style="height:7px; border:1; border-style:none solid solid solid;"><tr><td></td></tr></table>';

	$table .= '</td></tr></table>';

	$table .= FormatInputTitle( $name, '', false, '' );

	if ( empty( $value[ $column ] ) )
	{
		return $table;
	}

	return InputDivOnclick(
		GetInputID( $request . $column ),
		$table,
		str_replace( '||', ', ', mb_substr( $value[ $column ], 2, -2 ) ),
		FormatInputTitle( $name )
	);
}


/**
 * Make Student Age
 * FJ display age next to birthdate
 *
 * @global array  $value
 *
 * @param  string $column  Field column.
 * @param  string $name    Field name.
 *
 * @return string          Student Age
 */
function _makeStudentAge( $column, $name )
{
	global $value;

	if ( $_REQUEST['student_id'] !== 'new'
		&& date_create( $value[ $column ] ) )
	{
		$datetime1 = date_create( $value[ $column ] );

		$datetime2 = date_create( 'now' );

		$interval = date_diff( $datetime1, $datetime2 );

		$age_text = _( '%Y Years %m Months %d Days' );

		$age_text = $interval->format( $age_text );

		return NoInput( $age_text, $name );
	}

	return '';
}


// MEDICAL.
/**
 * Make Medical Immunization or Physical type Select Input
 *
 * @global array  $THIS_RET
 *
 * @param  string $value    Field value.
 * @param  string $column   Field column.
 *
 * @return string Medical Immunization or Physical type Select Input
 */
function _makeType( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	return SelectInput(
		$value,
		'values[STUDENT_MEDICAL][' . $THIS_RET['ID'] . '][TYPE]',
		'',
		array( 'Immunization' => _( 'Immunization' ), 'Physical' => _( 'Physical' ) )
	);
}


/**
 * Make Medical Date Input
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Medical Date Input
 */
function _makeDate( $value, $column = 'MEDICAL_DATE' )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	return DateInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']'
	);
}


/**
 * Make Medical Comments Input
 *
 * @since 3.6 Add custom input size per column.
 *
 * @global array  $THIS_RET
 * @global string $table
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Medical Comments Input
 */
function _makeComments( $value, $column )
{
	global $THIS_RET,
		$table;

	if ( empty( $THIS_RET['ID'] ) )
	{
		$THIS_RET['ID'] = 'new';
	}

	$input_size = 12;

	if ( $column === 'TIME_IN'
		|| $column === 'TIME_OUT' )
	{
		$input_size = 5;
	}
	elseif ( $column === 'COMMENTS'
		|| $column === 'TITLE' )
	{
		$input_size = 20;
	}

	return TextInput(
		$value,
		'values[' . $table . '][' . $THIS_RET['ID'] . '][' . $column . ']',
		'',
		'size="' . $input_size . '"'
	);
}


// ENROLLMENT.
/**
 * Make Enrollment Start Date & Code Inputs
 *
 * @since 5.4 Enrollment Start: No N/A option if already has Drop date.
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment Start Date & Code Inputs
 */
function _makeStartInput( $value, $column )
{
	global $THIS_RET,
		$_ROSARIO;

	static $add_codes = false;

	$add = '';

	$na = 'N/A';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];

		if ( ! empty( $THIS_RET['END_DATE'] ) )
		{
			// @since 5.4 Enrollment Start: No N/A option if already has Drop date.
			$na = false;
		}
	}
	elseif ( $_REQUEST['student_id'] === 'new' )
	{
		$id = 'new';

		$default = DBGetOne( "SELECT min(SCHOOL_DATE) AS START_DATE
			FROM ATTENDANCE_CALENDAR
			WHERE SYEAR='" . UserSyear() . "'
			AND SCHOOL_ID='" . UserSchool() . "'" );

		if ( ! $default
			|| DBDate() > $default )
		{
			$default = DBDate();
		}

		$value = $default;
	}
	else
	{
		$add = button( 'add' ) . ' ';

		$id = 'new';
	}

	if ( ! $add_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM STUDENT_ENROLLMENT_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Add'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$add_codes[$option['ID']] = $option['TITLE'];
		}
	}

	$div = true;

	if ( $_REQUEST['student_id'] === 'new' )
	{
		$div = false;
	}

	if ( AllowEdit()
		&& ( User( 'PROFILE' ) === 'parent'
			|| User( 'PROFILE' ) === 'student' ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = false;

		$disallow_edit_parent_student = true;
	}

	$return = '<div class="nobr">' . $add .
		DateInput(
			$value,
			'values[STUDENT_ENROLLMENT][' . $id . '][' . $column . ']',
			'',
			$div,
			! empty( $na )
		) . ' - ' .
		SelectInput(
			issetVal( $THIS_RET['ENROLLMENT_CODE'] ),
			'values[STUDENT_ENROLLMENT][' . $id . '][ENROLLMENT_CODE]',
			'',
			$add_codes,
			$na,
			'style="max-width:150px;"'
		) .
	'</div>';

	if ( ! empty( $disallow_edit_parent_student ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = true;
	}

	return $return;
}


/**
 * Make Enrollment End Date & Code Inputs
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment End Date & Code Inputs
 */
function _makeEndInput( $value, $column )
{
	global $THIS_RET,
		$_ROSARIO;

	static $drop_codes;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	if ( ! $drop_codes )
	{
		$options_RET = DBGet( "SELECT ID,TITLE AS TITLE
			FROM STUDENT_ENROLLMENT_CODES
			WHERE SYEAR='" . UserSyear() . "'
			AND TYPE='Drop'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $options_RET as $option )
		{
			$drop_codes[$option['ID']] = $option['TITLE'];
		}
	}

	if ( AllowEdit()
		&& ( User( 'PROFILE' ) === 'parent'
			|| User( 'PROFILE' ) === 'student' ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = false;

		$disallow_edit_parent_student = true;
	}

	$return = '<div class="nobr">' .
		DateInput(
			$value,
			'values[STUDENT_ENROLLMENT][' . $id . '][' . $column . ']'
		) . ' - ' .
		SelectInput(
			$THIS_RET['DROP_CODE'],
			'values[STUDENT_ENROLLMENT][' . $id . '][DROP_CODE]',
			'',
			$drop_codes,
			'N/A',
			'style="max-width:150px;"'
		) .
	'</div>';

	if ( ! empty( $disallow_edit_parent_student ) )
	{
		// Do not allow Parents/Students to edit Enrollment Records.
		$_ROSARIO['allow_edit'] = true;
	}

	return $return;
}


/**
 * Make Enrollment School Select Input
 *
 * @global array  $THIS_RET
 *
 * @param  string $value   Field value.
 * @param  string $column  Field column.
 *
 * @return string          Enrollment School Select Input
 */
function _makeSchoolInput( $value, $column )
{
	global $THIS_RET;

	static $schools;

	$id = 'new';

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}

	if ( ! isset( $schools )
		|| ! is_array( $schools ) )
	{
		$schools = DBGet( "SELECT ID,TITLE
			FROM SCHOOLS
			WHERE SYEAR='" . UserSyear() . "'", array(), array( 'ID' ) );
	}

	foreach ( (array) $schools as $sid => $school )
	{
		$options[ $sid ] = $school[1]['TITLE'];
	}

	// Mab - allow school to be edited if illegal value.
	if ( $_REQUEST['student_id'] !== 'new' )
	{
		if ( $id !== 'new' )
		{
			if ( is_array( $schools[ $value ] ) )
			{
				return $schools[ $value ][1]['TITLE'];
			}

			return SelectInput(
				$value,
				'values[STUDENT_ENROLLMENT][' . $id . '][SCHOOL_ID]',
				'',
				$options
			);
		}

		return SelectInput(
			UserSchool(),
			'values[STUDENT_ENROLLMENT][' . $id . '][SCHOOL_ID]',
			'',
			$options,
			false,
			'',
			false
		);
	}

	// FJ save new Student's Enrollment in Enrollment.inc.php.
	return '<input type="hidden" name="values[STUDENT_ENROLLMENT][new][SCHOOL_ID]" value="' . UserSchool() . '" />' .
		$schools[ UserSchool() ][1]['TITLE'];
}


/**
 * Is New Student / User / People / Address?
 * Local function
 *
 * @param  string $request students|staff|values[PEOPLE]|values[ADDRESS].
 *
 * @return boolean true if new, else false
 */
function _isNew( $request )
{
	switch ( $request )
	{
		case 'students':

			$request_key = 'student_id';

		break;

		case 'staff':

			$request_key = 'staff_id';

		break;

		case 'values[PEOPLE]':

			$request_key = 'person_id';

		break;

		case 'values[ADDRESS]':

			$request_key = 'address_id';

		break;

		default:

			return false;
	}

	return isset( $_REQUEST[ $request_key ] ) && $_REQUEST[ $request_key ] === 'new';
}
