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


	static public function addLabel( $thing, fxFormElement &$e, $for_id )
	{
		if( $e->_inMeta('nolabel') )
			return $thing;

		$label = '<label for="'.htmlspecialchars($e->id).'">'.htmlspecialchars($e->_name).'</label>';

		return ($e->_label_right) ? $thing . "\n" . $label : $label . "\n" . $thing;
	}


	static public function getClasses(fxFormElement &$e)
	{
		$classes = array();
		if( $e->class )
			$classes[] = htmlspecialchars($e->class);
		if( $e->_inData('required') )
			$classes[] = 'required';
		if( $e->_inMeta('invalid') )
			$classes[] = 'error';
		if( empty( $classes ) )
			return '';

		return ' class="'.implode(' ',$classes).'"';
	}

	static public function renderString( $string )
	{
		return ( $string );
	}
}


#eof
