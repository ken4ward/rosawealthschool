<?php
DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'update'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			if ( $id !== 'new' )
			{
				$sql = "UPDATE SCHOOL_GRADELEVELS SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";
				DBQuery( $sql );
			}

			// New: check for Title and Short Name.
			elseif ( $columns['TITLE']
				&& $columns['SHORT_NAME'] )
			{
				$sql = "INSERT INTO SCHOOL_GRADELEVELS ";

				$fields = 'ID,SCHOOL_ID,';
				$values = db_seq_nextval( 'school_gradelevels_id_seq' ) . ",'" . UserSchool() . "',";

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go )
				{
					DBQuery( $sql );
				}
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Grade Level' ) ) )
	{
		DBQuery( "DELETE FROM SCHOOL_GRADELEVELS WHERE ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

// FJ fix SQL bug invalid sort order
echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	$grades_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME,SORT_ORDER,NEXT_GRADE_ID,
		(SELECT 1
			FROM STUDENT_ENROLLMENT se
			WHERE se.GRADE_ID=sg.ID
			AND se.SCHOOL_ID='" . UserSchool() . "'
			LIMIT 1) AS REMOVE
		FROM SCHOOL_GRADELEVELS sg
		WHERE SCHOOL_ID='" . UserSchool() . "'
		ORDER BY SORT_ORDER",
		array(
			'REMOVE' => '_makeRemoveButton',
			'TITLE' => '_makeTextInput',
			'SHORT_NAME' => '_makeTextInput',
			'SORT_ORDER' => '_makeTextInput',
			'NEXT_GRADE_ID' => '_makeGradeInput',
		)
	);

	$columns = array(
		'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>',
		'TITLE' => _( 'Title' ),
		'SHORT_NAME' => _( 'Short Name' ),
		'SORT_ORDER' => _( 'Sort Order' ),
		'NEXT_GRADE_ID' => _( 'Next Grade' ),
	);

	$link['add']['html'] = array(
		'REMOVE' => _makeRemoveButton( '', 'REMOVE' ),
		'TITLE' => _makeTextInput( '', 'TITLE' ),
		'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ),
		'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ),
		'NEXT_GRADE_ID' => _makeGradeInput( '', 'NEXT_GRADE_ID' ),
	);

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $grades_RET, $columns, 'Grade Level', 'Grade Levels', $link );

	echo '<div class="center">' . SubmitButton() . '</div></form>';
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$extra = '';

	if ( $name !== 'TITLE' )
	{
		// @since 5.8 Change short_name column type to character varying(3). Now allows French elementary grade levels.
		$extra = 'size=3 maxlength=3';
	}

	if ( $id !== 'new'
		&& ( $name === 'TITLE' || $name === 'SHORT_NAME' ) )
	{
		// Title and Short Name are required.
		$extra .= ' required';
	}

	$comment = '';

	if ( $name === 'SORT_ORDER' )
	{
		$extra .= ' type="number" step="any"';

		$comment = '<!-- ' . $value . ' -->';
	}

	return $comment .
	TextInput( $value, 'values[' . $id . '][' . $name . ']', '', $extra );
}

/**
 * @param $value
 * @param $name
 */
function _makeGradeInput( $value, $name )
{
	global $THIS_RET,
		$grades;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( ! $grades )
	{
		$grades_RET = DBGet( "SELECT ID,TITLE
			FROM SCHOOL_GRADELEVELS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			ORDER BY SORT_ORDER" );

		foreach ( (array) $grades_RET as $grade )
		{
			$grades[$grade['ID']] = $grade['TITLE'];
		}
	}

	return SelectInput( $value, 'values[' . $id . '][' . $name . ']', '', $grades, _( 'N/A' ) );
}


/**
 * Make Remove button
 *
 * Local function
 * DBGet() callback
 *
 * @since 6.0
 *
 * @param  string $value  Value.
 * @param  string $column Column name, 'REMOVE'.
 *
 * @return string Remove button or add button or none if Students are enrolled in this Grade Level.
 */
function _makeRemoveButton( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return button( 'add' );
	}

	if ( $value )
	{
		// Do NOT remove Grade Level as Students are enrolled in it.
		return '';
	}

	$button_link = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove&id=' .
		urlencode( $THIS_RET['ID'] );

	return button( 'remove', '', '"' . $button_link . '"' );
}
