<?php

interface fxRenderer
{
	static public function renderString( $s );
	static public function renderAtts( $atts );
	static public function render( fxFormElement &$e, fxForm &$f, $parent_id );
}

abstract class fxHTMLRenderer implements fxRenderer
{
	static public $submitting = false;
	static public $renderingElementSet = false;

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


	static public function addErrorMessage( fxFormElement &$e, fxForm &$f )
	{
		if( self::$renderingElementSet ) // Don't put the owning element's errors on each child that is used to render it.
			return;

		if( 'hidden' === $e->type )	// Never display errors for hidden elements.
			return;

		$o = '';
		if( self::$submitting && !$e->_inMeta('valid') ) {
//echo "<pre>",htmlspecialchars( var_export($e->_getMeta() , true) ),"</pre>";
			// Element is in error so format a per-element error message and add it...
			if( is_callable( $f->_formatElementErrors ) ) {
				$cb = $f->_formatElementErrors;
				$msg = $cb( $e, $f );
				if( is_string($msg) )
					if( '' !== $msg ) $o = $msg;	// Callback can return an empty string to surpress per-element error messages.
			}
			else {
				$o = '<span class="error-msg">'.$f->getErrorFor($e->name).'</span>';
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
		if( self::$submitting && !$e->_inMeta('valid') )
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
