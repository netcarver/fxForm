<?php

/**
 * fxAssert
 *
 * Common assertions. These all throw an fProgrammerException on failure.
 *
 * Example...
 *     fxAssert::isCallable( $fn ); # Check that $fn can be called.
 **/
class fxAssert
{
	public static function isNonEmptyString( $arg, $argname = '', $msg=null )
	{
		if( !is_string($arg) || '' === $arg ) {
			if( is_string($msg) && '' !== $msg )
				throw new fProgrammerException( $msg );
			else
				throw new fProgrammerException( "Argument [$argname] should be a non-empty string." );
		}
	}

	public static function isNull( $arg, $argname = '', $msg=null )
	{
		if( null !== $arg ) {
			if( is_string($msg) && '' !== $msg )
				throw new fProgrammerException( $msg );
			else
				throw new fProgrammerException( "Argument [$argname] should be null." );
		}
	}

	public static function isCallable( $fn )
	{
		if( !is_callable( $fn ) )
			throw new fProgrammerException( "Function [$fn] should be callable." );
	}

	public static function isNotInArrayKeys( $key, &$array, $msg=null )
	{
		if( array_key_exists( $key, $array ) ) {
			if( is_string($msg) && '' !== $msg )
				throw new fProgrammerException( $msg );
			else
				throw new fProgrammerException( "Key [$key] exists in array keys." );
		}
	}

	public static function isInArrayKeys( $key, &$array, $msg=null )
	{
		if( !array_key_exists( $key, $array ) ) {
			if( is_string($msg) && '' !== $msg )
				throw new fProgrammerException( $msg );
			else
				throw new fProgrammerException( "Key [$key] must exist in array keys(".implode(', ',array_keys($array)).")." );
		}
	}

	public static function isInArray( $val, &$array, $msg=null )
	{
		if( !in_array( $val, $array ) ) {
			if( is_string($msg) && '' !== $msg )
				throw new fProgrammerException( $msg );
			else
				throw new fProgrammerException( "Value [$val] must exist in array(".implode(', ', $array).")." );
		}
	}

	public static function isArray( &$var, $var_name )
	{
		if( !is_array( $var ) )
			throw new fProgrammerException( "$var_name must be an array." );
	}

	public static function isNotEmpty( &$var, $var_name )
	{
		if( empty( $var ) )
			throw new fProgrammerException( "$var_name must not be empty." );
	}
}

#eof
