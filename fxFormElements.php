<?php

/**
 * Basic HTML form element.
 * Adds _meta elements for behaviour control.
 *
 * Note, this extends fxNamedSet but that *doesn't* mean it *is* a set of elements; it means that
 * an element *has* an associated set of data and meta-data. In this case, that data holds an
 * element's attributes and the meta data holds other information such as the name associated with
 * the element, its submitted value and its datatype.
 **/
abstract class fxFormElement extends fxNamedSet
{
	static public $radio_types = array('radio','checkbox');



	public function __construct($name , $note = null)
	{
		$label_right = ('>' === substr($name,0,1));
		if( $label_right ) {
			$name = substr($name,1);
		}

		parent::__construct($name);
		$this->_note = $note;
		$this->name = $this->id = fxForm::_simplify( $name );
		$this->_label_right = $label_right;

	}



	public function match($pattern)
	{
		fxAssert::isNonEmptyString($pattern, 'pattern');
		fxAssert::isNotInArrayKeys('match', $this->_meta, 'Cannot redefine match $pattern for ['.$this->_name.'].');
		$this->_validator = $pattern;
		return $this;
	}



	/**
	 * Encode & store the submitted value (if any) in the meta info
	 **/
	public function _getSubmittedValue()
	{
		$this->_value = fRequest::encode($this->name);
		return $this;
	}



	protected function _setValidity($v, $msg = '', &$errors = null  )
	{
		if( $v )
			$this->_meta['valid'] = $v;
		else {
			unset($this->_meta['valid']);
			fxAssert::isNonEmptyString($msg, '$msg');
			fxAssert::isArray($errors);

			$errors[ $this->name ] = $msg;
		}
		return $v;
	}

	/**
	 * Override this in derived classes if needed.
	 **/
	public function _isValid( &$errors, fxForm &$f )
	{
		$validator = $this->_validator;
		$required  = $this->_inData('required');
		$submitted = $this->_value;

		if( !$required )
			return $this->_setValidity(true);

		/**
		 * Ok, if we get here then this is a required value. That implies, that it can't be empty so return false if it is...
		 **/
		if( '' == $submitted )
			return $this->_setValidity(false, '* requires a value.' ,$errors);

		/**
		 * Required & not empty. If there's no validator then that's all that's needed to pass validation...
		 **/
		if( !$validator )
			return $this->_setValidity(true);

		$valid = false;
		$msg   = null;
		if( is_callable( $validator ) ) {
			$r = $validator( $this, $f );
			if( $r === true || (is_string($r) && !empty($r)) ) {
				$valid = ( $r === true );
				if( !$valid )
					$msg = $r;
			}
			else {
				throw new exception( "Validator function for {$this->name} must return (bool)true or a non-empty string." );
			}
		}
		elseif( is_string( $validator ) ) {
		//	$valid = preg_match( "~$validator~", $this->_value );
		}
		else
			$msg = 'Invalid value.';

		return $this->_setValidity($valid, $msg, $errors);
	}


	/**
	 * Allow Form Elements to determine what HTML tag to use in the markup. eg fxSubmit can orchistrate the output of a button element.
	 **/
	public function _getHTMLType()
	{
		return get_class($this);
	}


	/**
	 * Each element will be asked to use the given renderer to get itself output.
	 **/
	abstract public function renderUsing( $r, fxForm &$f, $parent_id );
}


/**
 * Radiosets, Fieldsets, Checkboxes and Selects are implemented by having multiple elements.
 **/
abstract class fxFormElementSet extends fxFormElement
{
	protected $_elements;


	public function __construct( $name, $note = null )
	{
		parent::__construct($name);
		$this->_note = $note;
		$this->_elements = array();
	}




	public function getElements()
	{
		return $this->_elements;
	}


	protected function _getExpandedElements()
	{
		/**
		 * Take element-dependent action. Allows containers like RadioSets and CheckboxSets to convert
		 * themselves to a real set of normal inputs.
		 **/
		return $this->_elements;
	}


	/**
	 * Allows the addition of an element to a form
	 **/
	public function add( $element )
	{
		fxAssert::isNotEmpty( $element, 'element' );

		if( $element instanceof fxFormElement ) {
			$this->_elements[] = $element;
		}
		elseif( is_string( $element ) ) {
			$this->_elements[] = new fxFormString( $element );
		}
		else {
			throw new exception( "Added element must be a string, fxFormElement or fxFormElementSet." );
		}


		return $this;
	}


	/**
	 * Allows the conditional addition of elements to a form
	 **/
	public function addIf( $condition, $element )
	{
		if( $condition )
			$this->add( $element );
		return $this;
	}

}




#eof
