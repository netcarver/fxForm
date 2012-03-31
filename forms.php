<?php

require_once( 'fxAssert.php' );
require_once( 'fxCSRFToken.php' );
require_once( 'fxNamedSet.php' );
require_once( 'fxFormElements.php' );
require_once( 'fxForm.php' );
require_once( 'fxRenderer.php' );
require_once( 'fxBasicHTMLFormRenderer.php' );


class fxFormInput    extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['type'] = 'text'; } }
class fxFormButton   extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['type'] = 'button'; $this->_data['value'] = fxForm::_simplify($name); $this->_meta['nolabel'] = true; } }
class fxFormTextArea extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['maxlength'] = 2000; } }
//class fxFormUpload   extends fxFormElement {}

/**
 * Additional utility classes that allow simpler form definitions...
 **/
class fxFormSubmit   extends fxFormButton { public function __construct($text)  { parent::__construct($text);  $this->_data['type'] = 'submit'; }   public function _getHTMLType() { return 'fxFormButton'; } }
class fxFormReset    extends fxFormButton { public function __construct($text)  { parent::__construct($text);  $this->_data['type'] = 'reset'; }    public function _getHTMLType() { return 'fxFormButton'; } }
class fxFormPassword extends fxFormInput  { public function __construct($label, $note) { parent::__construct($label, $note); $this->_data['type'] = 'password'; } public function _getHTMLType() { return 'fxFormInput'; } }
class fxFormHidden   extends fxFormInput  { public function __construct($name,$value) { parent::__construct($name); $this->_data['type'] = 'hidden'; $this->_data['value'] = $value; $this->_meta['nolabel'] = true; }   public function _getHTMLType() { return 'fxFormInput'; } }

class fxFormFieldset extends fxFormElementSet
{
	public function _getExpandedElements()
	{
		$r[] = "<fieldset>\n<legend>{$this->_name}</legend>\n";
		$r = array_merge( $r, parent::_getExpandedElements() );
		$r[] = "</fieldset>\n";
		return $r;
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

	public function _getExpandedElements()
	{
		$r = array();
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxHTMLStatement::_simplify($v);
			if( is_string( $k ) )
				$simple_k = fxHTMLStatement::_simplify($k);
			$r[] = Input($v)
				->type('checkbox')
				->name($this->_data['name'])
				->id($this->_data['name'] . '-' . $simple_v )
				->value($simple_k)
				;
		}
		return $r;
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
		$r = array();
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$el = new fxFormInput($v);
			$el
				->type('radio')
				->name($this->name)
				->id( $this->_owner . '-' . $this->name . '-' . $simple_v )
				->value($simple_k)
				->_owner = $this->_owner
				;
			$r[] = $el;
		}
		return $r;
	}
}


class fxSelect extends fxFormElementSet
{
	protected $_members = null;
	public function __construct($name, $members)
	{
		parent::__construct($name);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members,'members');
		$this->_members = $members;
	}

	public function _getExpandedElements()
	{
		$r[] = "<select {$this->_name}>\n";
		$r = array_merge( $r, parent::_getExpandedElements() );
		$r[] = "</select>\n";
		return $r;
	}
}


/**
 * Convenience creator functions. These allow chaining from point of creation and make for a more fluent interface...
 **/
function Form( $form_name, $action, $method="post" )	 { return new fxForm( $form_name, $action, $method ); }
function Input( $label, $note=null )	     { return new fxFormInput    ( $label, $note ); }
function Password( $label )      { return new fxFormPassword ($label); }
function Hidden( $name, $value ) { return new fxFormHidden   ($name, $value); }
function TextArea( $label, $note )      { return new fxFormTextArea ( $label, $note ); }
function Button( $text )         { return new fxFormButton   ( $text ); }
function Submit( $text ) 	     { return new fxFormSubmit   ( $text ); }
//function Reset ( $text ) 	 { return new fxFormReset   ( $text ); }
function Fieldset( $legend )     { return new fxFormFieldset ( $legend ); }
function Radios( $group_name, $members_array ) { return new fxFormRadioset($group_name, $members_array); }
function Checkboxes( $group_name, $members_array ) { return new fxFormCheckboxset($group_name, $members_array); }

#eof
