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
		$input = fRequest::encode($this->name);
		if( is_string( $input ) )
			$input = trim($input);
		$this->_value = $input;
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
	abstract public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id );
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




/**
 * @class fxFormString	Allows for static text to be added to forms. This is not an input widget.
 **/
class fxFormString extends fxFormElement
{
	public function __construct($text)
	{
		parent::__construct(__CLASS__);
		$this->value = $text;
	}

	public function _isValid( &$errors, fxForm &$f )
	{
		$this->_valid = true;
		return true;
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderString($this->value);
	}
}




class fxFormInput extends fxFormElement
{
	public function __construct($label, $note=null)
	{
		parent::__construct($label, $note);
		$this->type = 'text';
		$this->_html = 'input';
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->render($this, $f, $parent_id);
	}
}




class fxFormButton extends fxFormElement
{
	public function __construct($label, $note=null)
	{
		parent::__construct($label, $note);
		$this->type = 'button';
		$this->value = fxForm::_simplify($name);
		$this->_nolabel = true;
		$this->_html = 'button';
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderButton($this, $f, $parent_id );
	}
}




class fxFormTextArea extends fxFormElement
{
	public function __construct($label, $note=null)
	{
		parent::__construct($label, $note);
		$this->maxlength = 2000;
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderTextArea($this, $f, $parent_id );
	}
}




class fxFormSubmit extends fxFormButton
{
	public function __construct($text)
	{
		parent::__construct($text);
		$this->type = 'submit';
	}

	public function _getHTMLType()
	{
		return 'fxFormButton';
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderButton($this, $f, $parent_id );
	}
}




class fxFormReset extends fxFormButton
{
	public function __construct($text)
	{
		parent::__construct($text);
		$this->type = 'reset';
	}

	public function _getHTMLType()
	{
		return 'fxFormButton';
	}
}




class fxFormPassword extends fxFormInput
{
	public function __construct($label, $note)
	{
		parent::__construct($label, $note);
		$this->type = 'password';
		$this->_html = 'input';
	}

	public function _getHTMLType()
	{
		return 'fxFormInput';
	}
}




class fxFormHidden extends fxFormInput
{
	public function __construct($name,$value)
	{
		parent::__construct($name);
		$this->type = 'hidden';
		$this->value = $value;
		$this->_nolabel = true;
		$this->_html = 'input';
	}

	public function _getHTMLType()
	{
		return 'fxFormInput';
	}
}




class fxFormFieldset extends fxFormElementSet
{
	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderFieldset($this, $f, $parent_id );
	}

	public function _getSubmittedValue()
	{
		foreach( $this->_elements as $el ) if( $el instanceof fxFormElement ) $el->_getSubmittedValue();
		return $this;
	}

	public function _isValid( &$errors, fxForm &$f )
	{
		$fieldset_ok = true;
		foreach( $this->_elements as $el ) if( $el instanceof fxFormElement ) $fieldset_ok = $fieldset_ok & $el->_isValid( $errors, $f );
		return $fieldset_ok;
	}
}




class fxFormCheckboxset extends fxFormElementSet
{
	public function __construct($label, $members, $name = null)
	{
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');

		if( null === $name || '' === $name || !is_string($name) )
			$name = $label;

		parent::__construct($label);
		$this->_members = $members;
		$this->name = fxForm::_simplify($name).'[]';
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		$members = $this->_members;
		foreach( $members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = fxForm::_simplify($k);
			$el = new fxFormInput($v);
			$el
				->type('checkbox')
				->name($this->name)
				->id( fxForm::_simplify( $this->name . '-' . $simple_v ))
				->value($simple_k)
				->_label_right( $this->_label_right )
				;
			if( in_array($simple_k, $this->_value ) )
				$el->checked();
			$this->_elements[] = $el;
		}
		return $r->renderElementSet($this, $f, $parent_id );
	}
}




class fxFormRadioset extends fxFormElementSet
{
	public function __construct($label, $members, $name = null )
	{
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		if( count($members) < 2 ) throw new exception( 'There must be 2 or more members for a RadioSet to be populated.' );

		parent::__construct($label);

		if( null === $name || '' === $name || !is_string($name) )
			$name = $label;

		$this->_members = $members;
		$this->name = fxForm::_simplify($name);
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		$members = $this->_members;
		foreach( $members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$el = new fxFormInput($v);
			$el
				->type('radio')
				->name($this->name)
				->id( fxForm::_simplify( $this->name . '-' . $simple_v ) )
				->value($simple_k)
				->_label_right( $this->_label_right )
				;
			if( $this->_inData('required') )
				$el->required();
			if( $simple_k === $this->_value )
				$el->checked();
			$this->_elements[] = $el;
		}
		return $r->renderElementSet( $this, $f, $parent_id );
	}
}




class fxFormSelect extends fxFormElementSet
{
	public function __construct($label, $members, $name=null)
	{
		if( null === $name || '' === $name || !is_string($name) )
			$name = $label;

		parent::__construct($label);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members,'members');
		$this->_members = $members;
		$this->id = $tmp = fxForm::_simplify($name);
		$this->name = $tmp.'[]';
	}


	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderSelect($this, $f, $parent_id );
	}
}



class fxFormMSelect extends fxFormSelect
{
	public function __construct($label, $members, $name=null)
	{
		parent::__construct($label, $members, $name);
		$this->multiple();
	}
}




#eof
