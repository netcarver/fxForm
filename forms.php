<?php

require_once( 'fxAssert.php' );
require_once( 'fxCSRFToken.php' );
require_once( 'fxHTMLStatement.php' );
require_once( 'fxFormElements.php' );
require_once( 'fxForm.php' );


class fxFormInput    extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['type'] = 'text'; } }
class fxFormButton   extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['type'] = 'button'; $this->_data['value'] = fxNamedSet::_simplify($name); } }
class fxFormTextArea extends fxFormElement { public function __construct($label, $note=null) { parent::__construct($label, $note); $this->_data['maxlength'] = 2000; } }
//class fxFormUpload   extends fxFormElement {}

/**
 * Additional utility classes that allow simpler form definitions...
 **/
class fxFormSubmit   extends fxFormButton { public function __construct($text)  { parent::__construct($text);  $this->_data['type'] = 'submit'; }   public function _getHTMLType() { return 'fxFormButton'; } }
class fxFormReset    extends fxFormButton { public function __construct($text)  { parent::__construct($text);  $this->_data['type'] = 'reset'; }    public function _getHTMLType() { return 'fxFormButton'; } }
class fxFormPassword extends fxFormInput  { public function __construct($label, $note) { parent::__construct($label, $note); $this->_data['type'] = 'password'; } public function _getHTMLType() { return 'fxFormInput'; } }
class fxFormHidden   extends fxFormInput  { public function __construct($name,$value) { parent::__construct($name); $this->_data['type'] = 'hidden'; $this->_data['value'] = $value; }   public function _getHTMLType() { return 'fxFormInput'; } }

class fxFormFieldset extends fxFormElementSet
{
	public function _getExpandedElements()
	{
		$r[] = "<fieldset>\n<legend>{$this->_getName()}</legend>\n";
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
		$this->_data['name'] = fxHTMLStatement::_simplify($name);
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
		$this->_data['name'] = fxNamedSet::_simplify($name);
	}

	public function _getExpandedElements()
	{
		$r = array();
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxNamedSet::_simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$el = new fxFormInput($v);
			$r[] = $el
				->type('radio')
				->name($this->_data['name'])
				->id($this->_data['name'] . '-' . $simple_v )
				->value($simple_k)
				;
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
		$r[] = "<select {$this->_getName()}>\n";
		$r = array_merge( $r, parent::_getExpandedElements() );
		$r[] = "</select>\n";
		return $r;
	}
}


class fxBasicFormRenderer
{
	static public function render( fxFormElement $e, $values = array() )
	{
		$name  = htmlspecialchars($e->_getName());
		$lname = htmlspecialchars($e->name);
		$meta  = $e->_getMeta();
		$cls   = htmlspecialchars($e->class);
		$subval= $meta['value'];
		$elval = htmlspecialchars($e->value);
		$id    = htmlspecialchars($e->id);
		$chckd = (@$meta['checked']) ? 'checked' : '';
		$attr  = $e->_getAttrList( 'class,value' );
		$plce  = (string)$e->_note;
		if( '' !== $plce )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';
		$o = array();

		if( $meta['required'] ) {
			$cls  .= ' required';
			$attr .= ' required'; // HTML5, client-side validation!
		}
		else
			$cls .= ' optional';
		$cls = trim( $cls );

		$type = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		$o[] = "<label for=\"$id\">$name</label><$type$attr$plce";
		$o[] = "class=\"$cls\"";
		$o[] = $chckd;

		if( 'textarea' == $type )
			$o[] = ">$subval</textarea>";
		elseif( 'button' == $type || 'submit' == $type || 'reset' == $type )
			$o[] = "value=\"$elval\" />$name</button>";
		elseif( in_array( $e->type, fxFormElement::$radio_types) )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		return join( " ", $o );
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
