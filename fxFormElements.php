<?php

/**
 * Basic HTML form element.
 *
 * Note, this extends fxNamedSet but that *doesn't* mean it *is* a set of elements; it means that
 * an element *has* an associated set of data and meta-data. In this case, that data holds an
 * element's attributes and the meta data holds other information such as the name associated with
 * the element, its submitted value and its datatype.
 **/
abstract class fxFormElement extends fxNamedSet
{
	static public $radio_types = array('radio','checkbox');

	protected $_fvalidator = null;

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

		$this->_fvalidator = new fValidation();
	}



	/**
	 * Adds a validation callback
	 * Calls to new method type() can be used to add certain rules like URL / Email
	 **/
	public function match($cb)
	{
		fxAssert::isCallable($cb, 'cb');
		$this->_validation_cb = $cb;
		return $this;
	}



	public function whitelist($list)
	{
		if( is_string($list) )
			$list = explode( ',', $list );

		fxAssert::isArray( $list );
		fxAssert::isNotEmpty( $list );

		foreach( $list as &$v )
			$v = trim( $v );

		$this->_fvalidator->addValidValuesRule($this->name, $list);
		return $this;
	}



	public function pattern( $pattern, $msg='' )
	{
		fxAssert::isNonEmptyString($pattern);
		//fxAssert::isString($msg);
		if( '' == $msg )
			$msg = "Value must match the pattern: \"$pattern\"";
		$this->_fvalidator->addRegexRule( $this->name, $pattern, $msg );
		$this->_data['pattern'] = $pattern;	// HTML5 can take a pattern parameter!
		return $this;
	}


	public function type($t)
	{
		fxAssert::isNonEmptyString($t);

		$t = strtolower($t);
		$this->_data['type'] = $t;	// TODO Although this might be set to an HTML5 supported value, like 'email',
									// 		an html4 renderer would need to render this as an input of type=text

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


	/**
	 * Use this to test if an element has passed its validation.
	 **/
	public function _isValid()
	{
		return empty( $this->_meta['errors'] );
	}



	public function _addError( $msg = '', &$errors = null  )
	{
		fxAssert::isNonEmptyString($msg, '$msg');
		fxAssert::isArray($errors);

		$errors[ $this->name ][] = $msg;
		$this->_meta['errors'][] = $msg;
		return $this;
	}


	/**
	 * Override this in derived classes if needed.
	 **/
	public function _validate( &$errors, fxForm &$f )
	{
		$submitted = $this->_value;
		$required  = $this->_inData('required');
		$cb        = $this->_validation_cb;

		if( !$required && '' == $submitted )	// Not required and no input => always ok.
			return $this;

		if( $required && '' == $submitted )		// A required value but no input => always a fail.
			return $this->_addError( '* requires a value.' ,$errors);

		try {
			$validation_errors = $this->_fvalidator->validate( TRUE, TRUE );	// We are only getting errors for this element, so we can safely remove the names.
		} catch (fProgrammerException $e) {
			// If we get here then there were no fValidation rules on this element => ok so far now to check our callback routine...
		}

		if( !empty( $validation_errors ) ) {
			// fValidation found some error(s)...
			$this->_meta['errors'] = array_values($validation_errors);
			$errors[$this->name]   = array_values($validation_errors);
			return $this;
		}

		$valid = true;
		$msg   = null;
		if( is_callable( $cb ) ) {
			$r = $cb( $this, $f );
			if( $r === true || (is_string($r) && !empty($r)) ) {
				$valid = ( $r === true );
				if( !$valid )
					$msg = $r;
			}
			else {
				throw new exception( "Validator function for {$this->name} must return (bool)true or a non-empty string." );
			}
		}

		if( !$valid )
			$this->_addError( $msg, $errors );

		return $this;
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

	public function _validate( &$errors, fxForm &$f )
	{
		return $this;
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


	public function type($t)
	{
		parent::type($t);

		switch( $t ) {
		case 'search' :
		case 'text' :
		case 'tel' :
			$this->_fvalidator->addEmailHeaderFields($this->name); break; // HTML5 spec says text+search should exclude \r \n and that's exactly what addEmailHeaderFields() checks for, perfect!
		case 'date' :
			$this->_fvalidator->addDateFields($this->name); break;
		case 'email' :
			$this->_fvalidator->addEmailFields($this->name); break;
		case 'url' :
			$this->_fvalidator->addURLFields($this->name); break;
		case 'number' :
			$this->_fvalidator->addFloatFields($this->name); break;  // HTML5 spec says this means a float value, if present...
		default :
			break;
		}

		return $this;
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

	public function _validate( &$errors, fxForm &$f )
	{
		foreach( $this->_elements as $el ) if( $el instanceof fxFormElement ) $el->_validate( $errors, $f );
		return $this;
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
