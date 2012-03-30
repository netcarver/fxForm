<?php

require_once( 'fxAssert.php' );
require_once( 'fxCSRFToken.php' );


/**
 * Generic HTMLStatement class implementing setters via fluent __call() invocations.
 *
 * eg. $s = fxHTMLStatement('NAME')->class('xyz')->id('abc'); adds 'class' => 'xyz' and 'id' => 'abc' to a set called 'NAME'.
 *
 * Intended for use in defining the attributes that will go in each element's HTML.
 **/
class fxHTMLStatement
{
	protected $_atts;
	protected $_ds_name;

	public function __construct($name)
	{
		fxAssert::isNonEmptyString($name, 'name', "Set must be named.");
		$this->_ds_name = $name;
		$this->_atts    = array();
	}

	public function __call( $name, $args )
	{
		//fxAssert::isNonEmptyString(@$args[0], 'name', "Please supply a value for set member [$name].");
		$this->_atts[$name] = $args[0]; # Unknown methods act as setters
		return $this;
	}

	public function __get( $name )
	{
		if( ('_name' === $name) || ('name' === $name && !array_key_exists('name',$this->_atts) ) )
			return $this->_ds_name;

		$r = @$this->_atts[$name];
		if( null === $r ) $r = '';
		return $r;
	}

	public function getData()			{ return $this->_atts; }
	public function getName()			{ return $this->_ds_name; }
	protected function fingerprint( )	{ return md5( serialize($this) ); }

	public function getAttrList( $excludes='' )
	{
		$o = '';
		if( !empty( $this->_atts ) ) {
			$excludes = explode( ',', $excludes );
			foreach( $this->_atts as $k => $v ) {
				if( !in_array($k, $excludes) ) {
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
		}
		return $o;
	}


	static public function simplify($name)
	{
		return wire()->sanitizer->pageName($name, true);
	}
}


/**
 * Basic HTML form element.
 * Adds _meta elements for behaviour control.
 **/
class fxFormElement extends fxHTMLStatement
{
	protected $_meta;
	static public $radio_types = array('radio','checkbox');


	public function __construct($name)
	{
		parent::__construct($name);
		$this->_atts['name'] = $this->_atts['id'] = fxHTMLStatement::simplify( $name );
		$this->_meta = array(
			'required' => true,
			'label'    => true,
		);
	}

	public function match($pattern)
	{
		fxAssert::isNonEmptyString($pattern, 'pattern');
		fxAssert::isNotInArrayKeys('match', $this->_meta, 'Cannot redefine match $pattern for ['.$this->_ds_name.'].');
		$this->_meta['match'] = $pattern;
		return $this;
	}

	public function optional() 				{ $this->_meta['required'] = false; return $this; }
	public function note($note)				{ $this->_meta['note'] = $note;     return $this; }
	public function getMeta()				{ return $this->_meta; }


	public function isValid()
	{
		//
		//	Store the submitted value in the meta info
		//
		$this->_meta['value'] = fRequest::encode($this->_atts['name']);
$subval = var_export( $this->_meta['value'], true );
echo sed_dump("Validating {$this->_ds_name} :: get({$this->_atts['name']}) gives [$subval]");

		//
		//	Store the checkedness of the element based on the submitted value
		//
		if( in_array($this->_atts['type'], self::$radio_types ) ) {
			$this->_meta['checked'] = ($this->_atts['value'] === $this->_meta['value']);
		}

		//
		//	Perform any needed validation...
		//


		return false;
	}

	/**
	 * Allow Form Elements to determine what HTML tag to use in the markup. eg fxSubmit can orchistrate the output of a button element.
	 **/
	public function getHTMLType()
	{
		return get_class($this);
	}

}

class fxInput    extends fxFormElement { public function __construct($name) { parent::__construct($name); $this->_atts['type'] = 'text'; } }
class fxButton   extends fxFormElement { public function __construct($name) { parent::__construct($name); $this->_atts['type'] = 'button'; $this->_atts['value'] = fxHTMLStatement::simplify($name); } }
class fxSubmit   extends fxButton { public function __construct($name) { parent::__construct($name); $this->_atts['type'] = 'submit'; } public function getHTMLType() { return 'fxButton'; } }
class fxReset    extends fxButton { public function __construct($name) { parent::__construct($name); $this->_atts['type'] = 'reset'; }  public function getHTMLType() { return 'fxButton'; } }
class fxTextArea extends fxFormElement { public function __construct($name) { parent::__construct($name); $this->_atts['maxlength'] = 2000; } }
//class fxUpload   extends fxFormElement {}

/**
 * Radiosets and Selects pose a challenge as they are implemented by having multiple elements
 *
 * This will either mean making these containers in their own right or having them add multiple
 * atomic elements to the form.
 **/
class fxFormElementSet extends fxHTMLStatement
{
	protected $_elements;

	public function __construct( $name )
	{
		parent::__construct($name);
		$this->_elements = array();
	}

	public function _getExpandedElements()
	{
		/**
		 * Take element-dependent action. Allows containers like RadioSets and CheckboxSets to convert
		 * themselves to a real set of normal inputs.
		 **/
		return $this->_elements;
	}

	public function add( $element )
	{
		fxAssert::isNotEmpty( $element, 'element' );
		if( $element instanceof fxFormElementSet )
			$this->_elements = array_merge( $this->_elements, $element->_getExpandedElements() );
		else
			$this->_elements[] = $element;
		return $this;
	}
}


class fxFieldset extends fxFormElementSet
{
	public function _getExpandedElements()
	{
		$r[] = "<fieldset>\n<legend>{$this->_ds_name}</legend>\n";
		$r = array_merge( $r, parent::_getExpandedElements() );
		$r[] = "</fieldset>\n";
		return $r;
	}
}
function fxFieldset( $name ) { return new fxFieldset( $name ); }



class fxCheckboxset extends fxFormElementSet
{
	protected $_members = null;
	public function __construct($name, $members)
	{
		parent::__construct($name);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		$this->_members = $members;
	}

	public function _getExpandedElements()
	{
		$r = array();
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxHTMLStatement::simplify($v);
			if( is_string( $k ) )
				$simple_k = fxHTMLStatement::simplify($k);
			$r[] = fxInput($v)
				->type('checkbox')
				->name($this->_atts['name'])
				->id($this->_atts['name'] . '-' . $simple_v )
				->value($simple_k)
				;
		}
		return $r;
	}
}
function fxCheckboxset( $name, $members = array('agree'=>'I agree') ) { return new fxCheckboxset($name, $members); }


class fxRadioset extends fxFormElementSet
{
	protected $_members = null;
	public function __construct($name, $members)
	{
		parent::__construct($name);
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		if( count($members) < 2 ) throw new exception( 'There must be 2 or more members for a RadioSet to be populated.' );
		$this->_members = $members;
	}

	public function _getExpandedElements()
	{
		$r = array();
		foreach( $this->_members as $k => $v ) {
			$simple_v = $simple_k = fxHTMLStatement::simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$r[] = fxInput($v)
				->type('radio')
				->name($this->_atts['name'])
				->id($this->_atts['name'] . '-' . $simple_v )
				->value($simple_k)
				;
		}
		return $r;
	}
}
function fxRadioset( $name, $members ) { return new fxRadioset($name, $members); }





/**
 * Implements the containing form. You can use a fluent style to add elements or text to the form...
 *
 * $f = fxForm('Contact', '')
 * 			->add( '<h3>Contact Form</h3>' )
 * 			->add( fxInput('Name') )
 * 			->add( fxInput('Email') )
 * 			->add( fxTextarea('Message') )
 * 			->add( fxButton('Send') )
 * 			;
 **/
class fxForm extends fxFormElementSet
{
	CONST	HTML5     = 'html5';
	CONST	BASIC     = 'basic';
	CONST	BOOTSTRAP = 'bootstrap';

	/**
	 * Stores which renderer will be used to output the form.
	 **/
	protected $_renderer   = null;

	/**
	 * Function to call on successful form submission...
	 **/
	protected $_onSuccess  = null;

	/**
	 * The form's HTML action parameter...
	 **/
	protected $_action     = null;

	/**
	 * The form's HTML method parameter...
	 **/
	protected $_method     = null;

	/**
	 * The form's validation method (if any)...
	 **/
	protected $_validator  = null;


	public function __construct($name, $action, $method = "post")
	{
		fxAssert::isNonEmptyString($name, 'name', "Form must be named.");
		parent::__construct($name);

		$method_wl = array( 'post' , 'get' );
		$method    = strtolower( $method );
		fxAssert::isInArray( $method, $method_wl );
		$this->_method = $method;

		$this->_action = $action;
	}


	/**
	 * Sets the form's onSuccess callback function.
	 **/
	public function onSuccess( $fn )
	{
		fxAssert::isNull( $this->_onSuccess );
		fxAssert::isCallable( $fn );
		$this->_onSuccess = $fn;
		return $this;
	}


	public function match($pattern)
	{
		$this->_validator = $pattern;
		return $this;
	}


	public function setRenderer( $name )
	{
		fxAssert::isNonEmptyString($name, 'name', "Renderer name must be a non-empty string.");
		$name = ucfirst($name);
		$className = "fx{$name}Renderer";
		if( !class_exists( $className ) )
			throw new exception( "Class $className cannot be found." );

		$this->_renderer = $className;
		return $this;
	}


	public function process($x)
	{
		$submitted      = false;
		$fields_ok      = true;
		$form_ok        = true;
		$src            = strtoupper($this->_method);
		$this->_form_id = $this->fingerprint();

		//
		//	Has process been called following a form submission? Submission => method matches form
		//
		if( strtoupper( $_SERVER['REQUEST_METHOD'] ) === $src ) {
			$array = "_$src";
echo sed_dump( $GLOBALS[$array], $array );
			$submitted = !empty($GLOBALS[$array]);
		}

		if( !$submitted ) {
			// Genetate form id and anti-CSRF token and store them for rendering in the form...
			$this->_form_token = fxCSRFToken::get( $this->_form_id );
//echo sed_dump( "ID[$this->_form_id], Token[$this->_form_token]" );
		}
		else {
			// Do the id and token match what is expected?
			$id_ok    = ($this->_form_id === fRequest::get('_form_id') );
			if( !$id_ok )
				return '<p>An unexpected error occured. Form id mismatch.</p>';

			$this->_form_token = fRequest::get('_form_token');
			$token_ok = CSRFToken::check( $this->_form_id, $this->_form_token );
			if( !$token_ok )
				return '<p>An unexpected error occured. Token mismatch.</p>';

			//
			//	Iterate over elements, populating their values & evaluating them
			//
			foreach( $this->_elements as $e ) {
				if( $e instanceof fxFormElement )
					$fields_ok = $fields_ok & $e->isValid();
			}

			if( $fields_ok ) {
				//
				//	Run the form validator (if any)
				//


				if( $form_ok ) {
					if( is_callable($this->_onSuccess) ) {
						$fn = $this->_onSuccess;
						return $fn($this);
					}
					else
						return sed_dump("Huzzah!");
				}
			}

		}

		return $this->_render($x);
	}

	protected function _render( $pre = true )
	{
//echo "<pre>", htmlspecialchars( var_export( $this, true ) ), "</pre>";

		$o = array();
		$renderer = $this->_renderer;
		$renderer = new $renderer();
		$atts = $this->getAttrList();
		$o[] = "<form action=\"{$this->_action}\" method=\"{$this->_method}\"$atts>";
		if( !$this->_form_id || !$this->_form_token )
			throw new exception( "Form cannot be rendered without _form_id and _form_token being defined." );

		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$this->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$this->_form_token}\" />";
		foreach( $this->_elements as $e ) {
			if( is_string($e) ) {
				$o[] = $e;
			}
			else {
				$o[] = $renderer::render( $e );
			}
		}
		$o[] = "</form>";
		unset( $renderer );
		$o = implode( "\n", $o );
		if( $pre ) $o = htmlspecialchars($o);
		return $o;
	}
}


class fxBasicRenderer
{
	static public function render( fxFormElement $e, $values = array() )
	{
		$name  = htmlspecialchars($e->getName());
		$lname = htmlspecialchars($e->name);
		$meta  = $e->getMeta();
		$cls   = htmlspecialchars($e->class);
		$subval= $meta['value'];
		$elval = htmlspecialchars($e->value);
		$id    = htmlspecialchars($e->id);
		$chckd = (@$meta['checked']) ? 'checked' : '';
		$attr  = $e->getAttrList( 'class,value' );
		$o = array();

		if( $meta['required'] ) {
			$cls  .= ' required';
			$attr .= ' required'; // HTML5, client-side validation!
		}
		else
			$cls .= ' optional';
		$cls = trim( $cls );

		$type = htmlspecialchars( strtr( strtolower($e->getHTMLType()), array('fx'=>'') ) );

		$o[] = "<label for=\"$id\">$name</label><$type$attr";
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
 * Creator functions to allow chaining from point of creation...
 **/
function fxForm( $name, $action, $method="post" )	 { return new fxForm( $name, $action, $method ); }
function fxInput( $name )	 { return new fxInput   ( $name ); }
function fxTextArea( $name ) { return new fxTextArea( $name ); }
function fxButton( $name ) 	 { return new fxButton  ( $name ); }
function fxSubmit( $name ) 	 { return new fxSubmit  ( $name ); }
function fxReset ( $name ) 	 { return new fxReset   ( $name ); }

#eof
