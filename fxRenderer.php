<?php

interface fxRenderer
{
	static public function renderString( $s );
	static public function renderAtts( $atts );
	static public function render( fxFormElement &$e, $parent_id );
}

abstract class fxHTMLRenderer implements fxRenderer
{
	/**
	 * Takes an array of attributes ( name => values ) and creates an HTML formatted string from it.
	 **/
	static public function renderAtts( $atts )
	{
		fxAssert::isArray( $atts, '$atts' );
		$o = '';
		if( !empty( $atts ) ) {
			foreach( $atts as $k=>$v ) {
				$k = htmlspecialchars( $k );

				// NULL values lead to output like <XYZ ... readonly ...>
				if( NULL === $v ) {
					$o .= " $k";
				}

				// Otherwise we get <XYZ ... class="abc" ... >
				else {
					$v = htmlspecialchars( $v );
					$o .= " $k=\"$v\"";
				}
			}
		}
		return $o;
	}

	static public function renderString( $string )
	{
		return ( $string );
	}
}


#eof
