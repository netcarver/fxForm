<?php

require_once( 'fxAssert.php' );


class fxCSRFToken
{
	static public function get( $form_name )
	{
		$tok = fCryptography::randomString(32);
		wire()->session->set( $form_name . '.CSRFToken', $tok );
		return $tok;
	}

	static public function check( $form_name, $value )
	{
		$stored = wire()->session->get( $form_name . '.CSRFToken' );
		return ( $stored === $value );
	}

	static public function clear( $form_name )
	{
		wire()->session->remove( $form_name . '.CSRFToken' );
		return $this;
	}
}


class fxDumpable
{
	public function dump()
	{
		$html = true;
		static $bool = array( 0=>'false', 1=>'true' );
		foreach (func_get_args() as $a) {
			if( $html )
				echo "\n<pre>", (is_array($a)) ? htmlspecialchars(var_export($a, true)) : ((is_bool($a)) ? $bool[(int)$a] : htmlspecialchars($a)), "</pre>\n";
			else
				echo "\n", (is_array($a)) ? var_export($a,true) : ((is_bool($a)) ? $bool[(int)$a] : $a), "\n";
		}
		return $this;
	}
}

/**
 * Generic NamedSet class implementing setters via fluent __call() invocations.
 * eg. $set->class('xyz')->id('abc'); adds 'class' => 'xyz' and 'id' => 'abc' to the set.
 **/
class fxNamedSet extends fxDumpable
{
	protected $_data;
	protected $_ds_name;

	public function __construct($name)
	{
		fxAssert::isNonEmptyString($name, 'name', "Set must be named.");
		$this->_ds_name = $name;
		$this->_data    = array();
	}
//	public function __toString() { return var_export($this->data, true); }

	public function __call( $name, $args )
	{
		fxAssert::isNonEmptyString(@$args[0], 'name', "Please supply a value for set member [$name].");
		$this->_data[$name] = $args[0]; # Unknown methods act as setters
		return $this; # Allow chaining for multiple calls.
	}

	public function __get( $name )
	{
		if( ('_name' === $name) || ('name' === $name && !array_key_exists('name',$this->_data) ) )
			return $this->_ds_name;

		$r = @$this->_data[$name];
		if( null === $r ) $r = '';
		return $r;
	}

	public function getData()			{ return $this->_data; }
	public function getName()			{ return $this->_ds_name; }
	protected function fingerprint( )	{ return sha1( serialize($this) ); }

	public function getDataAsList( $excludes='' )
	{
		$o = '';
		if( !empty( $this->_data ) ) {
			$excludes = explode( ',', $excludes );
			foreach( $this->_data as $k => $v ) {
				if( !in_array($k, $excludes) ) {
					$k = htmlspecialchars( $k );
					$v = htmlspecialchars( $v );
					$o .= " {$k}=\"{$v}\"";
				}
			}
		}
		return $o;
	}


	static public function simplify($name)
	{
		$name = strtolower( $name );
		$name = strtr( $name, array(' '=>'') );
		return $name;
	}
}



/**
 * Basic HTML form element.
 * Adds _meta elements for behaviour control.
 **/
class fxElement extends fxNamedSet
{
	protected $_meta;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->_data['name'] = $this->_data['id'] = fxNamedSet::simplify( $name );
		$this->_meta    = array(
			'required' => true,
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
	public function getMeta()				{ return $this->_meta; }
}

class fxInput    extends fxElement {}
class fxTextArea extends fxElement {}
class fxButton   extends fxElement {}

/**
 * Radiosets and Selects pose a challenge as they are implemented by having multiple elements
 *
 * This will either mean making these containers in their own right or having them add multiple
 * atomic elements to the form.
 **/
class fxRadioSet extends fxElement
{
	public function __construct( $name, $options )
	{
	}
}


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
class fxForm extends fxNamedSet
{
	protected $_elements;
	protected $_last_added = null;
	protected $_renderer   = null;
	protected $_onSuccess  = null;
	protected $_action     = null;
	protected $_method     = null;
	protected $_validator  = null;

	public function __construct($name, $action, $method = "post")
	{
		fxAssert::isNonEmptyString($name, 'name', "Form must be named.");
		$method_wl = array( 'post' , 'get' );
		$method    = strtolower( $method );
		fxAssert::isInArray( $method, $method_wl );
		parent::__construct($name);
		$this->_elements = array();
		$this->_action = (string)$action;
		$this->_method = $method;
	}

	public function onSuccess( $fn )
	{
		fxAssert::isNull( $this->_onSuccess );
		fxAssert::isCallable( $fn );
		$this->_onSuccess = $fn;

		return $this;
	}

	public function __call( $name, $args )
	{
		fxAssert::isNotEmpty(@$args[0], 'name', "Please supply a value for member [$name].");
		if( null === $this->_last_added )
			$this->_data[$name] = $args[0]; # Unknown methods act as setters
		else
			$this->_last_added->__call($name, $args);
		return $this; # Allow chaining for multiple calls.
	}

	public function match($pattern)
	{
		if( null !== $this->_last_added && $this->_last_added instanceof fxNamedSet) {
			$this->_last_added->match( $pattern );
			return $this;
		}

		$this->_validator = $pattern;
		return $this;
	}

	public function add( $element )
	{
		fxAssert::isNotEmpty( $element, 'element' );
		$this->_elements[] = $this->_last_added = $element;
		return $this;
	}

	public function dump()
	{
		parent::dump("=== Data for $this->_ds_name... ===");
		parent::dump( array( '_data' => $this->_data) );
		//parent::dump( array( '_meta' => $this->_meta) );
		parent::dump( array( '_elements' => $this->_elements) );
		parent::dump( array( 'fingerprint' => $this->fingerprint()) );
		return $this;
	}

	public function optional()
	{
		if( null !== $this->_last_added && $this->_last_added instanceof fxNamedSet) {
			$this->_last_added->optional();
			return $this;
		}

		return parent::optional();
	}


	public function setRenderer( $name )
	{
		fxAssert::isNonEmptyString($name, 'name', "Renderer name must be a non-empty string.");
		$className = "fx{$name}Renderer";
		if( !class_exists( $className ) )
			throw new exception( "Class $className cannot be found." );

		$this->_renderer = $className;
		return $this;
	}


	public function process()
	{
		$o = '';
		if( fRequest::isPost() ) {
			#
			#	Form posted, validate the token matches that stored in the session for this form...
			#
			$token = fRequest::get('_form_token');
			$token_ok = fxCSRFToken::check( $this->_ds_name, $token );
			if( $token_ok ) {
				#
				#	Anti-CSRF token validated ok...
				#
				$name       = fRequest::encode('name');
				$from_email = fRequest::encode('from_email');
				$message    = fRequest::encode('message');
				try {
					$validator = new fValidation();
					$validator->addRequiredFields('name', 'from_email', 'message');
					$validator->overrideFieldName('from_email', 'Your email address');
					$validator->addEmailFields('from_email');
					$validator->addEmailHeaderFields('name', 'from_email');

					#
					#	Check the other input values. Throws an exception if validation fails...
					#
					$validator->validate();

					#
					# Validated OK, clear the token, send email and show success message on screen...
					$this->sendMessage( $name, $from_email, $message );
					fxCSRFToken::clear( $this->name );
					$o .= "<p>Thank you, your message has been sent.</p>";

				} catch (fValidationException $e) {
					#
					#	Validation of form values failed. Re-render the form with the values received...
					#
					$o = $this->render( $token, $name, $from_email, $message, $e->getMessage() );
				}
			} else {
				#
				#	Invalid CSRF token. To prevent CSRF, reject this.
				#
				$o .= "<p>Something strange happened during the submission of your form. Please try again.</p>";
			}
		} else {
			#
			#	This is a get request (landing page) so create a new token and render the empty form...
			#
			$o = $this->render( fxCSRFToken::get( $this->name ) );
		}

		return $o;
	}

	public function render( $pre = true )
	{
		//echo var_export( $this, true );

		$o = array();
		$renderer = $this->_renderer;
		$renderer = new $renderer();
		$atts = $this->getDataAsList();
		$o[] = "<form action=\"{$this->_action}\" method=\"{$this->_method}\"$atts>";
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
	static public function render( fxElement $e, $values = array() )
	{
		$name  = htmlspecialchars($e->getName());
		$lname = htmlspecialchars($e->name);
		$meta  = $e->getMeta();
		$cls   = htmlspecialchars($e->class);
		$def   = htmlspecialchars($e->value);
		$id    = htmlspecialchars($e->id);
		$attr  = $e->getDataAsList( 'class,value' );
		$o = array();

		if( $meta['required'] ) {
			$cls .= ' required';
			$attr .= ' required'; // HTML5, client-side validation!
		}
		else
			$cls .= ' optional';
		$cls = trim( $cls );

		$type = htmlspecialchars( strtr( strtolower(get_class( $e )), array('fx'=>'') ) );

		$o[] = "<label for=\"$id\">$name</label><$type$attr";
		$o[] = "class=\"$cls\"";

		if( 'textarea' == $type )
			$o[] = "></textarea>";
		elseif( 'button' == $type )
			$o[] = ">$name</button>";
		else
			$o[] = "/>";

		return join( " ", $o );
	}
}


/**
 * Creator functions to allow chaining from point of creation...
 **/
function fxForm( $name )	 { return new fxForm    ( $name ); }
function fxInput( $name )	 { return new fxInput   ( $name ); }
function fxTextArea( $name ) { return new fxTextArea( $name ); }
function fxButton( $name ) 	 { return new fxButton  ( $name ); }



#eof
