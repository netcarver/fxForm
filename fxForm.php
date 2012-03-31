<?php

/**
 * Implements the containing form. You can use a fluent style to add elements or text to the form...
 *
 * $f = fxForm('Contact', '')
 * 			->add( '<h3>Contact Form</h3>' )
 * 			->add( Input('Name') )
 * 			->add( Input('Email') )
 * 			->add( Textarea('Message') )
 * 			->add( Button('Send') )
 * 			;
 **/
class fxForm extends fxFormElementSet
{
	CONST	HTML4     = 'html4';	// TODO : These are all to do with the rendering, not with form structure so this is the wrong place for them
	CONST	HTML5     = 'html5';
	CONST	BASIC     = 'BasicHTML';
	CONST	BOOTSTRAP = 'Bootstrap';


	/**
	 * Constructor for a form.
	 *
	 * Creates a named form with the given parameters.
	 * Also sets the id of the form to "form-$name" (simplifying the contents of the $name in the process).
	 * If this isn't what you want just overwrite the id by setting your own straight after the form is constructed and before you
	 * call add() to put elements into your form.
	 * This id of the form will be used as a prefix to the id of everything you then add to the form so there should be no collisions
	 * between form-generated ids and ids you use elsewhere in your pages.
	 **/
	public function __construct($name, $action, $method = "post")
	{
		fxAssert::isNonEmptyString($name, 'name', "Form must be named.");
		parent::__construct($name);

		$method_wl = array( 'post' , 'get' );
		$method    = strtolower( $method );
		fxAssert::isInArray( $method, $method_wl );
		$this->_method = $method;

		$this->_action = $action;

		$this->id = self::_simplify("form-$name");
	}


	/**
	 * Dumps the contents of the form in a way that is viewable in your browser.
	 **/
	public function dump( $wrap='h3')
	{
		echo "<$wrap>", htmlspecialchars($this->_name),"</$wrap><pre>",htmlspecialchars( var_export( $this, true ) ),"</pre>";
		return $this;
	}


	/**
	 * Can be used to convert strings such as textual labels into simpler strings suitable for use as an HTML statement's id.
	 **/
	static public function _simplify($name)
	{
		return wire()->sanitizer->pageName($name, true);
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


	/**
	 * Define which renderer is to be tasked with generating the form output for display.
	 * Whilst XML-like statements with attributes are the obvious outputs, renderers can be supplied that do
	 * not necessarily comply to this and may output new formats.
	 *
	 * A renderer needs to know how to convert the following form elements into valid output.
	 *
	 * * Input        * Password      * URL           * Email
	 * * Textarea     * Select        * Fieldset      * Radios
	 * * Checkboxes   * Submit        * Reset         * Button
	 * * Hidden
	 *
	 **/
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


	/**
	 * Processes a form and produces output dependent upon the context in which process() is called.
	 *
	 * If the form is not being submitted, then it will be rendered in its initial state using the ->value() you set on each
	 * element and showing placeholders if set (how these are shown depends on the renderer too.)
	 *
	 * If the form is being submitted, then it will validate the inputs using any supplied rules and finally validate the form
	 * as a whole unit. You can supply validation rules or even callbacks for any element or the whole form.
	 *
	 * If the validation fails, the form will be re-rendered with the previous input values and with the errors shown.
	 *
	 * If the validation passes, then the success method will be called. It can take whatever actions are needed to handle the form
	 * and it can redirect if needed or simply return some output that will then be rendered in place of the form.
	 **/
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

	// TODO move rendering into renderer classes
	protected function _render( $pre = true )
	{
//echo "<pre>", htmlspecialchars( var_export( $this, true ) ), "</pre>";

		$o = array();
		$renderer = $this->_renderer;

		$atts = $renderer::renderAtts( $this->_getInfoExcept() );
		$o[] = "<form action=\"{$this->_action}\" method=\"{$this->_method}\"$atts>";
		if( !$this->_form_id || !$this->_form_token )
			throw new exception( "Form cannot be rendered without _form_id and _form_token being defined." );

		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$this->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$this->_form_token}\" />";
		foreach( $this->_elements as $e ) {
			if( is_string($e) ) {
				$o[] = $renderer::renderString($e);
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
