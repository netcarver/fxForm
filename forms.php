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

	public function _isValid()
	{
		return $true;
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
		return $r::render($this, $parent_id);
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
		return $r::renderButton($this, $parent_id );
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
		return $r::renderTextArea($this, $parent_id );
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
		return $r::render($this, $parent_id );
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
	public function _getExpandedElements()
	{
		$r[] = "<fieldset>\n<legend>{$this->_name}</legend>\n";
		$r = array_merge( $r, parent::_getExpandedElements() );
		$r[] = "</fieldset>\n";
		return $r;
	}

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::render($this, $parent_id );
	}
}




class fxFormCheckboxset extends fxFormElementSet
{
	protected $_members = null;
	public function __construct($name, $members)
	{
		parent::__construct($name);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		$this->_members = $members;
		$this->name = fxHTMLStatement::_simplify($name);
	}

	/* public function _getExpandedElements() */
	/* { */
	/* 	$r = array(); */
	/* 	foreach( $this->_members as $k => $v ) { */
	/* 		$simple_v = $simple_k = fxHTMLStatement::_simplify($v); */
	/* 		if( is_string( $k ) ) */
	/* 			$simple_k = fxHTMLStatement::_simplify($k); */
	/* 		$el = fxFormInput($v); */
	/* 		$el */
	/* 			->type('checkbox') */
	/* 			->name($this->_data['name']) */
	/* 			->id( $this->_owner . '-' . $this->_data['name'] . '-' . $simple_v ) */
	/* 			->value($simple_k) */
	/* 			->_owner = $this->_owner; */
	/* 			; */
	/* 		$r[] = $el; */
	/* 	} */
	/* 	return $r; */
	/* } */

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		return $r::render($this, $parent_id );
	}
}




class fxFormRadioset extends fxFormElementSet
{
	protected $_members = null;

	public function __construct($name, $members)
	{
		parent::__construct($name);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		if( count($members) < 2 ) throw new exception( 'There must be 2 or more members for a RadioSet to be populated.' );
		$this->_members = $members;
		$this->_data['name'] = fxForm::_simplify($name);
	}

	public function _getExpandedElements()
	{
		return array( $this );
	}

	public function renderUsing( $r, fxForm &$f, $parent_id )
	{
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$el = new fxFormInput($v);
			$el
				->type('radio')
				->name($this->name)
				->id( $parent_id . '-' . $this->name . '-' . $simple_v )
				->value($simple_k)
				;
			if( $simple_k === $this->_value )
				$el->checked();
			$this->_elements[] = $el;
		}
		return $r::renderRadioset( $this, $f, $parent_id );
	}
}




/* class fxSelect extends fxFormElementSet */
/* { */
/* 	protected $_members = null; */
/* 	public function __construct($name, $members) */
/* 	{ */
/* 		parent::__construct($name); */
/* 		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members,'members'); */
/* 		$this->_members = $members; */
/* 	} */

/* 	public function _getExpandedElements() */
/* 	{ */
/* 		$r[] = "<select {$this->_name}>\n"; */
/* 		$r = array_merge( $r, parent::_getExpandedElements() ); */
/* 		$r[] = "</select>\n"; */
/* 		return $r; */
/* 	} */

/* 	public function renderUsing( $r, fxForm &$f, $parent_id ) */
/* 	{ */
/* 		return $r::render($this, $parent_id ); */
/* 	} */
/* } */




/**
 * Convenience creator functions. These allow chaining from point of creation and make for a more fluent interface...
 **/
function Form		( $form_name, $action, $method="post" )	{ return new fxForm( $form_name, $action, $method ); }
function Checkboxes	( $group_name, $members_array ) 		{ return new fxFormCheckboxset( $group_name, $members_array ); }
function Radios		( $group_name, $members_array ) 		{ return new fxFormRadioset( $group_name, $members_array ); }
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
