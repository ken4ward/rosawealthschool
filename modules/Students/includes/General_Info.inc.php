<?php
echo '<div class=" container-fluid" style="margin-top: 5%;">
	<div class="col-md-6 offset-md-3">
		<div class="card bg-light mb-3" style="min-width: 100%;">
			<div class="card-header">CREATE USER ACCOUNT</div>
			  	<div class="card-body">
			  		<div class="container">
					  <div class="row">
					    <div class="col-sm">';

// IMAGE.

if ( AllowEdit()
	&& ! isset( $_REQUEST['_ROSARIO_PDF'] ) ):
?>
	<a href="#" onclick="$('.user-photo-form,.user-photo').toggle(); return false;"><?php
echo button( 'add', '', '', 'smaller' ) . '&nbsp;' . _( 'Student Photo' );
?></a><br />
	<div class="user-photo-form hide"><?php
echo FileInput(
	'photo',
	_( 'Student Photo' ) . ' (.jpg, .png, .gif)',
	'accept="image/*"'
);
?></div>
<?php endif;

if ( $_REQUEST['student_id'] !== 'new' && ( $file = @fopen( $picture_path = $StudentPicturesPath . UserSyear() . '/' . UserStudentID() . '.jpg', 'r' ) ) || ( $file = @fopen( $picture_path = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . UserStudentID() . '.jpg', 'r' ) ) ):
	fclose( $file );
	?>
			<img src="<?php echo $picture_path . ( ! empty( $new_photo_file ) ? '?cacheKiller=' . rand() : '' ); ?>" class="user-photo" alt="<?php echo htmlspecialchars( _( 'Student Photo' ), ENT_QUOTES ); ?>" />
		<?php endif;
// END IMAGE.

 // echo '</div><td colspan="2">';

if ( AllowEdit() && ! isset( $_REQUEST['_ROSARIO_PDF'] ) )
{
	$div = false;

	$student_name_html = '<div class="form-group">' .
	TextInput(
		issetVal( $student['FIRST_NAME'], '' ),
		'students[FIRST_NAME]',
		'',
		'size=12 maxlength=50 required class="form-control" placeholder="First name (required)"',
		$div
	) . '</div><div class="form-group">' .
	TextInput(
		issetVal( $student['MIDDLE_NAME'], '' ),
		'students[MIDDLE_NAME]',
		'',
		'maxlength=50 class="form-control" placeholder="Middle name"',
		$div
	) . '</div><div class="form-group">' .
	TextInput(
		issetVal( $student['LAST_NAME'], '' ),
		'students[LAST_NAME]',
		'',
		'size=12 maxlength=50 required class="form-control" placeholder="First name (required)"',
		$div
	) . '</div><div class="form-group">' .
	SelectInput(
		issetVal( $student['NAME_SUFFIX'], '' ), 'students[NAME_SUFFIX]', '', 
		array(
			'Jr' => _( 'Jr' ),
			'Sr' => _( 'Sr' ),
			'II' => _( 'II' ),
			'III' => _( 'III' ),
			'IV' => _( 'IV' ),
			'V' => _( 'V' ),
		),
		'',
		'class="form-control"',
		$div
	) . '</div>';

	//FJ Moodle integrator

	if ( $_REQUEST['student_id'] === 'new' || ! empty( $_REQUEST['moodle_create_student'] ) )
	{
		echo $student_name_html;
	}
	else
	{
		$id = 'student_name';

		echo InputDivOnclick($id, $student_name_html, $student['FIRST_NAME'] . ' ' . $student['MIDDLE_NAME'] . ' ' .
			$student['LAST_NAME'] . ' ' . $student['NAME_SUFFIX'], FormatInputTitle( _( 'Name' ), $id ));
	}
}
else
{
	echo NoInput(
		trim( $student['FIRST_NAME'] . ' ' . $student['MIDDLE_NAME'] . ' ' .
			$student['LAST_NAME'] . ' ' . $student['NAME_SUFFIX'] ),
		_( 'Name' )
	);
}

// echo '<div class="row"><div class="form-group">';

if ( $_REQUEST['student_id'] == 'new' )
{
	echo TextInput( '', 'assign_student_id', '', 'maxlength=9 size=9 class="form-control"' );
}
else
{
	echo NoInput( UserStudentID(), sprintf( _( '%s ID' ), Config( 'NAME' ) ) );
}

 echo '<div class="form-group">';

if ( array_key_exists( 'LAST_LOGIN', $student ) )
{
	// Hide Last Login on Create Account and Add screens.
	echo NoInput( makeLogin( $student['LAST_LOGIN'] ), _( 'Last Login' ) );
}

echo '</div><div class="form-group">';

// Moodle integrator.
// Username, password required.
$required = ! empty( $_REQUEST['moodle_create_student'] ) || basename( $_SERVER['PHP_SELF'] ) == 'index.php';

echo TextInput(issetVal( $student['USERNAME'], '' ),'students[USERNAME]', '', ( $required ? 'required ' : '' ) .( Config( 'STUDENTS_EMAIL_FIELD' ) === 'USERNAME' ? 'type="email" pattern="[^ @]*@[^ @]*" placeholder="' . _( 'Email' ) . '" ' :'' ) .'autocomplete="off"' .'class="form-control"' .'placeholder="Email (required)"', empty( $_REQUEST['moodle_create_student'] )
);

echo '</div><div class="form-group">';

echo PasswordInput(( empty( $student['PASSWORD'] )|| ! empty( $_REQUEST['moodle_create_student'] ) ? '' : str_repeat( '*', 8 ) ), 'students[PASSWORD]', '' .( ! empty( $_REQUEST['moodle_create_student'] )
		// @since 5.9 Automatic Moodle Student Account Creation.
		// Moodle creates user password.
		&& basename( $_SERVER['PHP_SELF'] ) !== 'index.php' ? '<div class="tooltip"><i>' .
		_( 'The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character' ) .
		// @since 5.9 Moodle creates user password if left empty.
		'. ' ._( 'Moodle will create a password and send an email to user if left empty.' ) .
		'</i></div>' :
		''
	),
	'maxlength="42" strength class="form-control" placeholder="Password (required)" style="min-width: 100%;"' .
	// @since 5.9 Moodle creates user password if left empty + Do not update Moodle user password.
	( basename( $_SERVER['PHP_SELF'] ) == 'index.php' ? ' required' : '' ),
	empty( $_REQUEST['moodle_create_student'] )
);

echo '</div>';

$_REQUEST['category_id'] = '1';
$separator = '<hr />';

include 'modules/Students/includes/Other_Info.inc.php';

if ( $_REQUEST['student_id'] !== 'new'
	&& ! empty( $student['SCHOOL_ID'] )
	&& $student['SCHOOL_ID'] != UserSchool() )
{
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $_ROSARIO['allow_edit'] = false;
}

if ( basename( $_SERVER['PHP_SELF'] ) !== 'index.php' )
{
	include 'modules/Students/includes/Enrollment.inc.php';
}
else
{
	// Create account.
	echo '<hr />';

	// echo '<table class="create-account width-100p valign-top fixed-col"><tr class="st"><td>';

	$schools_RET = DBGet( "SELECT ID,TITLE
		FROM SCHOOLS
		WHERE SYEAR='" . UserSyear() . "'
		ORDER BY ID" );

	$school_options = array();

	foreach ( (array) $schools_RET as $school )
	{
		$school_options[$school['ID']] = $school['TITLE'];
	}

	// @since 6.0 Reload page on School change, so we update UserSchool().
	$school_onchange_url = "'index.php?create_account=student&student_id=new&school_id='";

	// Add School select input.
	echo SelectInput(UserSchool(),'values[STUDENT_ENROLLMENT][new][SCHOOL_ID]', '', $school_options,
		false, 'autocomplete="off" class="form-control" onchange="window.location.href=' . $school_onchange_url . ' + this.options[selectedIndex].value;"', false);

	if ( Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) )
	{
		// @since 5.9 Automatic Student Account Activation.
		echo '</div><div class"form-group">';

		// Grade Levels for ALL schools.
		$gradelevels_RET = DBGet( "SELECT ID,TITLE
			FROM SCHOOL_GRADELEVELS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SCHOOL_ID,SORT_ORDER" );

		$gradelevel_options = array();

		foreach ( (array) $gradelevels_RET as $gradelevel )
		{
			$gradelevel_options[ $gradelevel['ID'] ] = $gradelevel['TITLE'];
		}

		// Add Grade Level select input.
		echo SelectInput(
			'',
			'values[STUDENT_ENROLLMENT][new][GRADE_ID]',
			_( 'Grade Level' ),
			$gradelevel_options,
			'N/A',
			'required class="form-control"'
		);
	}

	echo Config( 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION' ) ?
		'</td><td>' : '</td><td colspan="2">';

	// Add Captcha.
	echo CaptchaInput( 'captcha' . rand( 100, 9999 ), '', 'class="form-control"' );

	echo '</td></tr></table>';

	if ( $PopTable_opened )
	{
		echo '<table><tr><td>';

		PopTable( 'footer' );
	}
}
