<?php

interface fxRenderer
{
	static public function renderString( $s );
	static public function renderAtts( $array, $exclude = '' );
	static public function render( fxFormElement $e );
}

abstract class fxHTMLRenderer implements fxRenderer
{
	/**
	 * Takes an array of attributes ( name => values ) and creates an HTML formatted string from it.
	 **/
	static public function renderAtts( $array, $exclude = '' )
	{
		fxAssert::isArray( $array, '$atts' );
		$o = '';
		if( !empty( $array ) ) {
			foreach( $array as $k=>$v ) {
				// NULL values lead to output like <XYZ ... readonly ...>
				if( NULL === $v )
					$o .= " $k";

				// Otherwise we get <XYZ ... class="abc" ... >
				else
					$o .= " $k=\"$v\"";
			}
		}
		return htmlspecialchars($o);
	}

	static public function renderString( $string )
	{
		return htmlspecialchars( $string );
	}
}


#eof
