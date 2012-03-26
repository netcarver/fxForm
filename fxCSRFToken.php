<?php

/**
 * @class fxCSRFToken
 *
 * Implements a simple anti-CSRF token generation and validation class.
 **/
class fxCSRFToken
{
	/**
	 * Called to generate and store a random anti-CSRF token for a form.
	 *
	 * Multiple forms can be catered for within a session if needed.
	 **/
	static public function get( $form_name )
	{
		$tok = fCryptography::randomString(32);
		wire()->session->set( $form_name . '.CSRFToken', $tok );
		return $tok;
	}

	/**
	 * Called to check that the value of the token supplied in a form matches
	 * that stored in the session for this form.
	 **/
	static public function check( $form_name, $value )
	{
		$stored = wire()->session->get( $form_name . '.CSRFToken' );
		return ( $stored === $value );
	}


	/**
	 * Called to clear the stored token for the form.
	 **/
	static public function clear( $form_name )
	{
		wire()->session->remove( $form_name . '.CSRFToken' );
		return $this;
	}
}


#eof
