<?php

require_once 'modules/Student_Billing/functions.inc.php';

if ( empty( $_REQUEST['print_statements'] ) )
{
	DrawHeader( ProgramTitle() );

	Search( 'student_id', issetVal( $extra ) );
}

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

if ( ! empty( $_REQUEST['values'] )
	&& $_POST['values']
	&& AllowEdit()
	&& UserStudentID() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		if ( $id !== 'new' )
		{
			$sql = "UPDATE BILLING_PAYMENTS SET ";

			foreach ( (array) $columns as $column => $value )
			{
				$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
			}

			$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

			DBQuery( $sql );
		}
		elseif ( $columns['AMOUNT'] != ''
			&& $columns['PAYMENT_DATE'] )
		{
			$id = DBSeqNextID( 'billing_payments_id_seq' );

			$sql = "INSERT INTO BILLING_PAYMENTS ";

			$fields = 'ID,STUDENT_ID,SYEAR,SCHOOL_ID,';
			$values = "'" . $id . "','" . UserStudentID() . "','" . UserSyear() . "','" . UserSchool() . "',";

			$go = 0;

			foreach ( (array) $columns as $column => $value )
			{
				if ( ! empty( $value ) || $value == '0' )
				{
					if ( $column == 'AMOUNT' )
					{
						$value = preg_replace( '/[^0-9.-]/', '', $value );

						//FJ fix SQL bug invalid amount

						if ( ! is_numeric( $value ) )
						{
							$value = 0;
						}
					}

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

	// Unset values & redirect URL.
	RedirectURL( 'values' );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Payment' ) ) )
	{
		DBQuery( "DELETE FROM BILLING_PAYMENTS
			WHERE ID='" . $_REQUEST['id'] . "'
			OR REFUNDED_PAYMENT_ID='" . $_REQUEST['id'] . "'" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( $_REQUEST['modfunc'] === 'refund'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Payment' ), _( 'Refund' ) ) )
	{
		$payment_RET = DBGet( "SELECT COMMENTS,AMOUNT
			FROM BILLING_PAYMENTS
			WHERE ID='" . $_REQUEST['id'] . "'" );

		$comments = $payment_RET[1]['COMMENTS'] ?
			$payment_RET[1]['COMMENTS'] . ' &mdash; ' . _( 'Refund' ) :
			_( 'Refund' );

		DBQuery( "INSERT INTO BILLING_PAYMENTS (ID,SYEAR,SCHOOL_ID,STUDENT_ID,AMOUNT,
			PAYMENT_DATE,COMMENTS,REFUNDED_PAYMENT_ID)
			VALUES(" .
			db_seq_nextval( 'billing_payments_id_seq' ) . ",'" .
			UserSyear() . "','" .
			UserSchool() . "','" .
			UserStudentID() . "','" .
			( $payment_RET[1]['AMOUNT'] * -1 ) . "','" .
			DBDate() . "','" .
			DBEscapeString( $comments ) . "','" .
			$_REQUEST['id'] . "')" );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

if ( UserStudentID()
	&& ! $_REQUEST['modfunc'] )
{
	echo ErrorMessage( $error );

	$payments_total = 0;

	$functions = array(
		'REMOVE' => '_makePaymentsRemove',
		'AMOUNT' => '_makePaymentsAmount',
		'PAYMENT_DATE' => 'ProperDate',
		'COMMENTS' => '_makePaymentsCommentsInput',
		'LUNCH_PAYMENT' => '_lunchInput',
	);

	$refunded_payments_RET = DBGet( "SELECT '' AS REMOVE,ID,REFUNDED_PAYMENT_ID,
		AMOUNT,PAYMENT_DATE,COMMENTS
		FROM BILLING_PAYMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND (REFUNDED_PAYMENT_ID IS NOT NULL)", $functions, array( 'REFUNDED_PAYMENT_ID' ) );

	$payments_RET = DBGet( "SELECT '' AS REMOVE,ID,REFUNDED_PAYMENT_ID,
		AMOUNT,PAYMENT_DATE,COMMENTS,LUNCH_PAYMENT
		FROM BILLING_PAYMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND (REFUNDED_PAYMENT_ID IS NULL OR REFUNDED_PAYMENT_ID='') ORDER BY ID", $functions );

	$i = 1;
	$RET = array();

	foreach ( (array) $payments_RET as $payment )
	{
		$RET[$i] = $payment;

		if ( ! empty( $refunded_payments_RET[$payment['ID']] ) )
		{
			$i++;
			$RET[$i] = ( $refunded_payments_RET[$payment['ID']][1] + array( 'row_color' => 'FF0000' ) );
		}

		$i++;
	}

	$columns = array();

	if ( ! empty( $RET )
		&& empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		$columns = array( 'REMOVE' => '<span class="a11y-hidden">' . _( 'Delete' ) . '</span>' );
	}

	$columns += array(
		'AMOUNT' => _( 'Amount' ),
		'PAYMENT_DATE' => _( 'Date' ),
		'COMMENTS' => _( 'Comment' ),
		'LUNCH_PAYMENT' => _( 'Lunch Payment' ),
	);

	$link = array();

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		$link['add']['html'] = array(
			'REMOVE' => button( 'add' ),
			'AMOUNT' => _makePaymentsTextInput( '', 'AMOUNT' ),
			'PAYMENT_DATE' => _makePaymentsDateInput( DBDate(), 'PAYMENT_DATE' ),
			'COMMENTS' => _makePaymentsCommentsInput( '', 'COMMENTS' ),
			'LUNCH_PAYMENT' => _lunchInput( '', 'LUNCH_PAYMENT' ),
		);
	}

	// Do hook.
	// @since 6.5.1 Move header action hook above form.
	do_action( 'Student_Billing/StudentPayments.php|student_payments_header' );

	if ( empty( $_REQUEST['print_statements'] ) )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '" method="POST">';

		if ( AllowEdit() )
		{
			DrawHeader( '', SubmitButton() );
		}

		$options = array();
	}
	else
	{
		$options = array( 'center' => false, 'add' => false );
	}

	ListOutput(
		$RET,
		$columns,
		'Payment',
		'Payments',
		$link,
		array(),
		$options
	);

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '<div class="center">' . SubmitButton() . '</div>';
	}

	echo '<br />';

	$fees_total = DBGetOne( "SELECT SUM(f.AMOUNT) AS TOTAL
		FROM BILLING_FEES f
		WHERE f.STUDENT_ID='" . UserStudentID() . "'
		AND f.SYEAR='" . UserSyear() . "'" );

	$table = '<table class="align-right"><tr>
		<td>' . _( 'Total from Fees' ) . ': </td>
		<td>' . Currency( $fees_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Less' ) . ': ' . _( 'Total from Payments' ) . ': </td>
		<td>' . Currency( $payments_total ) . '</td></tr>';

	$table .= '<tr><td>' . _( 'Balance' ) . ': </td>
		<td><b>' . Currency(  ( $fees_total - $payments_total ), 'CR' ) . '</b></td>
		</tr></table>';

	DrawHeader( $table );

	if ( empty( $_REQUEST['print_statements'] )
		&& AllowEdit() )
	{
		echo '</form>';
	}
}
