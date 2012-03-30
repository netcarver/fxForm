<?php

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
	CONST	HTML4     = 'html4';	// TODO : These are all to do with the rendering, not with form structure so this is the wrong place for them
	CONST	HTML5     = 'html5';
	CONST	BASIC     = 'basic';
	CONST	BOOTSTRAP = 'bootstrap';

	/**
	 * Stores which renderer will be used to output the form.
	 **/
	protected $_renderer   = null;

	/**
	 * Stores what version of HTML to target. Will be passed to renderer.
	 **/
	protected $_target     = null;

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


	public function dump()
	{
		echo "<h3>{$this->_getName()}</h3><pre>",htmlspecialchars( var_export( $this, true ) ),"</pre>";
		return $this;
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


	public function setRenderer( $name, $target = self::HTML5 )
	{
		fxAssert::isNonEmptyString($name,   'name',   "Renderer name must be a non-empty string.");
		fxAssert::isNonEmptyString($target, 'target', "Target for renderer must be a non-empty string.");

		$name = ucfirst($name);
		$className = "fx{$name}FormRenderer";
		if( !class_exists( $className ) )
			throw new exception( "Renderer $className cannot be found." );

		$this->_renderer = $className;
		$this->_target   = $target;
		return $this;
	}


	public function process($x)
	{
		$submitted      = false;
		$fields_ok      = true;
		$form_ok        = true;
		$src            = strtoupper($this->_method);
		$this->_form_id = $this->_fingerprint();

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
					$fields_ok = $fields_ok & $e->_isValid();
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
		$atts = $this->_getAttrList();
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



#eof
