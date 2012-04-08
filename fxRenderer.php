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
	static public $rendering_element_set = false;
	static public $element_prefix = '';
	static public $element_suffix = '';
	static public $label_class    = '';

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
		if( self::$rendering_element_set ) // Don't put the owning element's errors on each child that is used to render it.
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



	static public function addLabel( $thing, fxFormElement &$e, $parent_id, $for_set = false )
	{
		if( $e->_inMeta('nolabel') )
			return $thing;

		if( $for_set ) {
			$label  = '<span>'.htmlspecialchars($e->_name).'</span>';
			$o = $label . "\n" . $thing;
		}
		else {
			$lclass = ( '' !== self::$label_class ) ? ' class="'.self::$label_class.'"' : '';
			$id     = self::makeId($e, $parent_id, false);
			$label  = '<label for="'.htmlspecialchars($id).'"'.$lclass.'>'.htmlspecialchars($e->_name).'</label>';
			$o = ($e->_label_right) ? $thing . "\n" . $label : $label . "\n" . $thing;
		}

		if( !self::$rendering_element_set )
			$o = self::$element_prefix . $o . self::$element_suffix;
		return $o;
	}




	static public function getClasses(fxFormElement &$e)
	{
		$classes = array();
		if( $e->class )
			$classes[] = htmlspecialchars($e->class);

		if( !self::$rendering_element_set ) {
			if( !self::$submitting && $e->_inData('required') && empty($e->_value) )
				$classes[] = 'required';
			if( self::$submitting && !$e->_inMeta('valid') )
				$classes[] = 'error';
			if( self::$submitting && $e->_inData('required') && $e->_inMeta('valid') )
				$classes[] = 'ok';
		}

		if( empty( $classes ) )
			return '';

		return ' class="'.implode(' ',$classes).'"';
	}




	static public function renderString( $string )
	{
		return ( $string );
	}




	static public function makeId( fxFormElement &$e, $parent_id, $make_attr=true )
	{
		$id = fxForm::_simplify( $parent_id . '-' . $e->id );
		if( $make_attr && '' !== $id ) $id = ' id="'.$id.'"';	// Conditionally prepare it as an attribute.
		return $id;
	}
}


#eof
