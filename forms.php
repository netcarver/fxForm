<?php

require_once( 'fxAssert.php' );
require_once( 'fxCSRFToken.php' );
require_once( 'fxNamedSet.php' );
require_once( 'fxFormElements.php' );
require_once( 'fxForm.php' );
require_once( 'fxRenderer.php' );
require_once( 'fxBasicHTMLFormRenderer.php' );

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

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderString($this->value);
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

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::render($this, $f, $parent_id);
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

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderButton($this, $f, $parent_id );
	}
}




class fxFormTextArea extends fxFormElement
{
	public function __construct($label, $note=null)
	{
		parent::__construct($label, $note);
		$this->maxlength = 2000;
	}

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderTextArea($this, $f, $parent_id );
	}
}




/**
 * Additional utility classes that allow simpler form definitions...
 **/
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

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderButton($this, $f, $parent_id );
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
	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderFieldset($this, $f, $parent_id );
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

	public function renderUsing( $r, fxForm &$f, $parent_id )
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
		return $r::renderElementSet($this, $f, $parent_id );
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

	public function renderUsing( $r, fxForm &$f, $parent_id )
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
		return $r::renderElementSet( $this, $f, $parent_id );
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


	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::renderSelect($this, $f, $parent_id );
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



/**
 * Convenience creator functions. These allow chaining from point of creation and make for a more fluent interface...
 **/
function Form		( $form_name, $action, $method="post" )	{ return new fxForm( $form_name, $action, $method ); }
function Checkboxes	( $label, $members_array, $name=null ) 	{ return new fxFormCheckboxset( $label, $members_array, $name ); }
function Radios		( $label, $members_array, $name=null ) 	{ return new fxFormRadioset( $label, $members_array, $name ); }
function Select  	( $label, $options_array, $name=null )	{ return new fxFormSelect( $label, $options_array, $name ); }
function MSelect 	( $label, $options_array, $name=null )	{ return new fxFormMSelect( $label, $options_array, $name ); }
function Text		( $text )            					{ return new fxFormString( $text ); }
function Input		( $label, $note=null )	     			{ return new fxFormInput( $label, $note ); }
function Password	( $label )      						{ return new fxFormPassword( $label ); }
function Hidden		( $name, $value ) 						{ return new fxFormHidden( $name, $value ); }
function TextArea	( $label, $note )      					{ return new fxFormTextArea( $label, $note ); }
function Button		( $text )         						{ return new fxFormButton( $text ); }
function Submit		( $text ) 	     						{ return new fxFormSubmit( $text ); }
//function Reset 		( $text ) 	 							{ return new fxFormReset( $text ); }
function Fieldset	( $legend )     						{ return new fxFormFieldset( $legend ); }

#eof
