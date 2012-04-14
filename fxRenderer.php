<?php



interface fxRenderer
{
	public function renderString( $s );
	public function renderAtts( $atts );
	public function render( fxFormElement &$e, fxForm &$f, $parent_id );
}





abstract class fxHTMLRenderer implements fxRenderer
{
	protected $rendering_element_set = false;
	protected $submitting            = false;
	protected $element_prefix        = '';
	protected $element_suffix        = '';
	protected $label_class           = '';
	protected $target                = 'html5';
	protected $errorBlockFormatter   = null;
	protected $elementErrorFormatter = null;
	protected $affixFormatter        = null;


	public function __construct( $prefix = '', $suffix = '<br>', $label_class = '' )
	{
		$this->element_prefix = $prefix;
		$this->element_suffix = $suffix;
		$this->label_class    = $label_class;
	}



	public function setSubmitting($val)
	{
		$this->submitting = $val;
		return $this;
	}


	public function setTarget($target='html5')
	{
		$this->target = $target;
		return $this;
	}


	/**
	 * Define a custom formatter for rendering the message at the top of a form when it is submitted and there are errors.
	 *
	 * By default, if there is no callback formatter, a simple one line message will be displayed. However, if you want to list all the
	 * errors here, then you need to generate that list in your own callback.
	 *
	 * Make the callback match this signature...
	 *
	 * function myFormErrorsFormatter( fxForm &$f )
	 *
	 * And you can get the errors by calling $f->getErrors() and then iterating over the result generating your list.
	 **/
	public function setErrorBlockFormatter( $cb )
	{
		fxAssert::isCallable($cb);
		$this->errorBlockFormatter = $cb;
		return $this;
	}


	/**
	 * Tells the renderer to call this method to custom format per-element errors.
	 *
	 * The callback must match this signature...
	 *
	 * function myElementErrorsFormatter( fxFormElement &$e, fxForm &$f )
	 *
	 * Where $e is the form element in error and $f is the owning form. All errors are stored in the form so you can
	 * get the error message itself from the form by calling ($f->getErrorFor($e->name)). Don't forget to pass it through
	 * htmlspecialchars() too.
	 **/
	public function setElementErrorFormatter( $cb )
	{
		fxAssert::isCallable($cb);
		$this->elementErrorFormatter = $cb;
		return $this;
	}



	/**
	 * Configures the renderer to use a custom 'affix' formatter on radio and checkbox member elements.
	 *
	 * The callback must match the following signature...
	 *
	 * function myAffixFormatter( $element, $owner_name, $index, $max )
	 *
	 * Where, $element is the rendered control, $owner_name is the name of the owning set, $index is this elements
	 * position within the set (zero based) and $max is the index of the maximum element.
	 *
	 * Knowing the zero-based index and the maximum index is enough to allow the detection of elements in the first and last
	 * position and to allow nth-child based additions (like odd/even classes) in the affixed code.
	 **/
	public function setAffixFormatter( $cb )
	{
		fxAssert::isCallable($cb);
		$this->affixFormatter = $cb;
		return $this;
	}



	/**
	 * Takes an array of attributes ( name => values ) and creates an HTML formatted string from it.
	 **/
	public function renderAtts( $atts )
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



	public function addErrorMessage( fxFormElement &$e, fxForm &$f )
	{
		if( $this->rendering_element_set ) // Don't put the owning element's errors on each child that is used to render it.
			return;

		if( 'hidden' === $e->type )	// Never display errors for hidden elements.
			return;

		$o = '';
		if( $this->submitting && !$e->_isValid() ) {
//echo "<pre>",htmlspecialchars( var_export($e->_getMeta() , true) ),"</pre>";
			// Element is in error so format a per-element error message and add it...
			$cb = $this->elementErrorFormatter;
			if( is_callable( $cb ) ) {
				$msg = $cb( $e, $f );
				if( is_string($msg) )
					if( '' !== $msg ) $o = $msg;	// Callback can return an empty string to surpress per-element error messages.
			}
			else {
				$o = '<span class="error-msg">'.htmlspecialchars($e->_errors[0]).'</span>';
			}
		}
		return $o;
	}



	public function addLabel( $thing, fxFormElement &$e, $parent_id, $for_set = false )
	{
		if( $e->_inMeta('nolabel') ) // TODO test this branch
			return $thing;

		if( $for_set ) {
			$label  = '<span>'.htmlspecialchars($e->_label).'</span>';
			$o = $label . "\n" . $thing;
		}
		else {
			$lclass = ( '' !== $this->label_class ) ? ' class="'.$this->label_class.'"' : '';
			$id     = $this->makeId($e, $parent_id, false);
			$label  = '<label for="'.htmlspecialchars($id).'"'.$lclass.'>'.htmlspecialchars($e->_label).'</label>';
			$o = ($e->_label_right) ? $thing . "\n" . $label : $label . "\n" . $thing;
		}

		if( !$this->rendering_element_set )
			$o = $this->element_prefix . $o . $this->element_suffix;
		else {
			$cb = $this->affixFormatter;
			if( is_callable( $cb ) )
				$o = $cb( $o, fxForm::_simplify($e->name), $this->set_index, $this->set_max );
		}

		return $o;
	}


	public function checkExposure( fxFormElement &$e, $html )
	{
		if( $e->_inMeta('show_data') )
			fCore::expose( array( "Data for {$e->_name}" => $e->_getData()) );
		if( $e->_inMeta('show_meta') )
			fCore::expose( array( "Meta-data for {$e->_name}" => $e->_getMeta()) );
		if( $e->_inMeta('show_submitted') )
			fCore::expose( array("Submitted value for {$e->name}" => $e->_value) );
		if( $e->_inMeta('show_elements') )
			fCore::expose( array( "{$e->name}"=>$e ));
		if( $e->_inMeta('show_errors') )
			fCore::expose( array( "Errors for {$e->name}" => $e->_getErrors()) );
		if( $e->_inMeta('show_html') )
			fCore::expose( array("HTML for {$e->name}" => $html) );
	}


	public function getClasses(fxFormElement &$e)
	{
		$classes = array();
		if( $e->class )
			$classes[] = htmlspecialchars($e->class);

		if( !$this->rendering_element_set ) {
			if( !$this->submitting && $e->_inData('required') && empty($e->_value) )
				$classes[] = 'required';
			if( $this->submitting && !$e->_isValid() )
				$classes[] = 'error';
			if( $this->submitting && $e->_inData('required') && $e->_isValid() )
				$classes[] = 'ok';
		}

		if( empty( $classes ) )
			return '';

		return ' class="'.implode(' ',$classes).'"';
	}




	public function renderString( $string )
	{
		return ( $string );
	}




	public function makeId( fxFormElement &$e, $parent_id, $make_attr=true )
	{
		$id = fxForm::_simplify( $parent_id . '-' . $e->id );
		if( $make_attr && '' !== $id ) $id = 'id="'.$id.'"';	// Conditionally prepare it as an attribute.
		return $id;
	}
}


#eof
