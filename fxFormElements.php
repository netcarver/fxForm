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

	public function __construct($name, $label = null, $note = null)
	{
		$label_right = ('>' === @substr($label,0,1));
		if( $label_right ) {
			$label = substr($label,1);
		}

		$name = fxForm::_simplify($name);
		parent::__construct($name);
		$this->name = $this->id = $name;	// The html form element's name and id are set from the initial, simplified, name.

		if( $label )
			$this->_label = $label;
		if( $note )
			$this->_note = $note;
		$this->_label_right = $label_right;

		$this->_fvalidator = new fValidation();
		$this->_meta['ignore_parent_fields'] = array();
	}


	/**
	 * Called once an element is added to an element set.
	 *
	 * By default this has the elements inherit the disabled, readonly and required flags from their parent set.
	 **/
	protected function addedTo( &$parent )
	{
		$this->_parent_name = $parent->name;
		if( $parent instanceof fxFormElementSet ) {
			if( $parent->_inData('disabled') && !in_array( 'disabled', $this->_ignore_parent_fields ) )
				$this->disabled();
			if( $parent->_inData('readonly') && !in_array( 'readonly', $this->_ignore_parent_fields ) )
				$this->readonly();
			if( $parent->_inData('required') && !in_array( 'required', $this->_ignore_parent_fields ) )
				$this->required();
		}

		return $this;
	}


	public function _ignore_parent_fields( $fields )
	{
		if( is_string($fields) )
			$fields = explode(',', $fields );

		fxAssert::isArray( $fields );

		$this->_meta['ignore_parent_fields'] = $fields;
		return $this;
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

		if( !$this->_inData('title') )
			$this->title = $msg;

		return $this;
	}


	public function required( $msg = null )
	{
		$this->_data['required'] = null;
		if( is_string($msg) && '' !== $msg )
			$this->_meta['required_message'] = $msg;
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


	//	Minlength is *not* an HTML5 attribute, so we store this in the _meta.
	public function minlength($min, $msg = null)
	{
		$this->_minlength = (int)$min;
		$this->_minlength_msg = $msg;
		return $this;
	}


	//	Maxlength is an HTML5 attribute so we store this in the _data.
	public function maxlength($max, $msg = null)
	{
		$this->_data['maxlength'] = (int)$max;
		$this->_maxlength_msg = $msg;
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

		if( $required && '' == $submitted )		// A required value but no input => always a fail. If a custom fail message was supplied, use that.
			return $this->_addError( (($this->_meta['required_message']) ? $this->_meta['required_message'] : '* Requires your input') ,$errors);

		try {
			$validation_errors = $this->_fvalidator->validate( TRUE, TRUE );	// We are only getting errors for this element, so we can safely remove the names.
		} catch (fProgrammerException $e) {
			// If we get here then there were no fValidation rules on this element => ok so far; now to check our callback routine...
		}

		if( !empty( $validation_errors ) ) {
			// fValidation found some error(s)...
			$this->_meta['errors'] = array_values($validation_errors);
			$errors[$this->name]   = array_values($validation_errors);
			return $this;
		}

		$min = $this->min;	// Could be null (if not present)
		$max = $this->max;	// 	"

		// Check for programming mistakes in setting of min & max values...
		if( $this->_inData('min') && $this->_inData('max') ) {
			if( $min > $max )
				throw new fProgrammerException( "Element {$this->name} has min[$min] > max[$max]" );
		}

		// Handle min value checking (if applicable)
		if( $this->_inData('min') ) {
			if( $submitted < $min )
				$this->_addError( "Value must be $min or more", $errors );
		}

		// Handle max value checking (if applcable)
		if( $this->_inData('max') ) {
			if( $submitted > $max )
				$this->_addError( "Value must be $max or less", $errors );
		}

		/**
		 * Do min/max length sanity checks and validation checks (if needed).
		 **/
		$minlength = $this->_minlength;
		$maxlength = $this->maxlength;
		$len = mb_strlen($submitted);

		if( $this->_inMeta('minlength') && $this->_inData('maxlength') ) {
			if( $minlength > $maxlength )
				throw new fProgrammerException( "Element {$this->name} has minlength[$minlength] > maxlength[$maxlength]" );
		}
		// Handle minlength checking (if applicable)
		if( $this->_inMata('minlength') ) {
			if( $len < $minlength )
				$this->_addError( (!empty($this->_minlength_msg)) ? $this->_minlength_msg : "Value must be $minlength characters or more", $errors );
		}

		// Handle maxlength checking (if applcable)
		if( $this->_inData('maxlength') ) {
			if( $len > $maxlength )
				$this->_addError( (!empty($this->_maxlength_msg )) ? $this->_maxlength_msg : "Value must be $maxlength characters or less", $errors );
		}


		if( is_callable( $cb ) ) {
			$r = $cb( $this, $f );
			if( true === $r || (is_string($r) && !empty($r)) ) {
				if( true !== $r )
					$this->_addError( $r, $errors );
			}
			else
				throw new fProgrammerException( "Validator function for {$this->name} must return (bool)true or a non-empty string." );
		}

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


	public function __construct( $name, $label, $note = null )
	{
		parent::__construct($name, $label, $note);
		//$this->_note = $note;
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
			$element->addedTo( $this );
		}
		elseif( is_string( $element ) ) {
			$this->_elements[] = $strel = new fxFormString( $element );
			$strel->addedTo( $this );
		}
		else {
			throw new fxProgrammerException( "Added element must be a string, fxFormElement or fxFormElementSet." );
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


	/**
	 * Allows the use of HTML's value attribute to set an initial value by simulating submission
	 **/
	public function value($v)
	{
		$this->_value($v);
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
	static $numberTypes = array('number','integer');

	public function __construct($name, $label, $note=null)
	{
		parent::__construct($name, $label, $note);
		$this->type = 'text';
		$this->_html = 'input';
	}


	public function min($v)
	{
		fxAssert::isInArray($this->type, self::$numberTypes );
		$this->_data['min'] = $v;
		return $this;
	}


	public function max($v)
	{
		fxAssert::isInArray($this->type, self::$numberTypes );
		$this->_data['max'] = $v;
		return $this;
	}



	/**
	 * Allows a form input to have an attached datalist.
	 **/
	public function datalist( $data, $listid=null )
	{
		fxAssert::isArray($data, 'data');
		$this->_datalist    = $data;
		$this->_datalist_id = $listid;
		return $this;
	}


	public function type($t)
	{
		$t = strtolower( $t );
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
		case 'int' :
			$t = 'integer';
		case 'integer' :
			$this->_fvalidator->addIntegerFields($this->name); break; // Not an HTML5 type so we'll evaluate it as an integer, renderers get to choose how to present this...
		case 'bool' :
			$t = 'boolean';
		case 'boolean' :
			$this->_fvalidator->addBooleanFields($this->name); break; // Not an HTML5 type so we'll evaluate it as a boolean, renderers get to choose how to present this...
		default :
			break;
		}

		parent::type($t);
		return $this;
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->render($this, $f, $parent_id);
	}
}




class fxFormButton extends fxFormElement
{
	public function __construct($name, $note=null)
	{
		parent::__construct($name, $name, $note);
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
	public function __construct($name, $label, $note=null)
	{
		parent::__construct($name, $label, $note);
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
	public function __construct($name, $label, $note)
	{
		parent::__construct($name, $label, $note);
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
	public function __construct( $label, $name=null )
	{
		if( !is_string($name) || '' === $string )
			$name = $label;
		parent::__construct( fxForm::_simplify($name) ,$label);
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderFieldset($this, $f, $parent_id );
	}

	public function _getSubmittedValue()
	{
		foreach( $this->_elements as $el )
			if( $el instanceof fxFormElement )
				$el->_getSubmittedValue();
		return $this;
	}

	public function _validate( &$errors, fxForm &$f )
	{
		foreach( $this->_elements as $el )
			if( $el instanceof fxFormElement )
				$el->_validate( $errors, $f );
		return $this;
	}
}




class fxFormCheckboxset extends fxFormElementSet
{
	public function __construct($name, $label, $members)
	{
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');

		parent::__construct($name, $label);
		$this->_members = $members;
		$this->name = fxForm::_simplify($name).'[]';
		$this->class('checkboxset');
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		$members = $this->_members;
		foreach( $members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = fxForm::_simplify($k);
			$el = new fxFormInput($this->name, $v);
			$el
				->type('checkbox')
				->name($this->name)
				->id( fxForm::_simplify( $this->name . '-' . $simple_v ))
				->value($simple_k)
				->_label_right( $this->_label_right )
				;
			if( $this->_inData('disabled') )
				$el->disabled();
			if( $this->_inData('readonly') )
				$el->readonly();
			if( in_array($simple_k, $this->_value ) )
				$el->checked();
			$this->_elements[] = $el;
		}
		return $r->renderElementSet($this, $f, $parent_id );
	}
}




class fxFormRadioset extends fxFormElementSet
{
	public function __construct($name, $label, $members)
	{
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members, 'members');
		if( count($members) < 2 ) throw new fxProgrammerException( 'There must be 2 or more members for a RadioSet to be populated.' );

		parent::__construct($name, $label);

		$this->_members = $members;
		$this->class('radioset');
	}

	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		$members = $this->_members;
		foreach( $members as $k => $v ) {
			$simple_v = $simple_k = fxForm::_simplify($v);
			if( is_string( $k ) )
				$simple_k = $k;
			$el = new fxFormInput($this->name, $v);
			$el
				->type('radio')
				->id( fxForm::_simplify( $this->name . '-' . $simple_v ) )
				->value($simple_k)
				->_label_right( $this->_label_right )
				;
			if( $this->_inData('disabled') )
				$el->disabled();
			if( $this->_inData('readonly') )
				$el->readonly();
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
	/**
	 * Static function to build a flattened mapping of compound-keys => labels
	 * from a given array.
	 *
	 * Given this input...
	 *
	 * $members = array(
	 * 		'Depts' => array(	// An optgroup
	 * 			'Books'     => 'Books',
	 * 			'Audio'     => array(
	 * 				'CDs'   => 'CDs',
	 * 				'Tapes' => 'Tapes',
	 * 				'Vinyl' => 'Records',
	 * 			)
	 * 			'Furniture' => 'Small furniture and fittings',
	 * 		),
	 * 	);
	 *
	 * 	You'd get...
	 * 	$map = array(
	 * 		'depts-books'       => 'Books',
	 * 		'depts-audio-cds'   => 'CDs',
	 * 		'depts-audio-tapes' => 'Tapes',
	 * 		'depts-audio-vinyl' => 'Records',
	 * 		'depts-furniture'   => 'Small furniture and fittings',
	 * 	);
	 *
	 * 	You can access the member map, for an element $el, using...
	 * 	$map = $el->_mmap;
	 **/
	static public function makeMemberMap( &$members, $prefix='' )
	{
		fxAssert::isArray( $members, '$members' );
		$o = array();
		if( !empty( $members ) ) {
			foreach( $members as $k => $v ) {
				$simple_k = $prefix . fxForm::_simplify( (string)$k );
				if( is_array( $v ) )
					$o += self::makeMemberMap($v, "$simple_k-");
				else
					$o[ $simple_k ] = $v;
			}
		}
		return $o;
	}



	public function __construct($name, $label, $members)
	{
		fxAssert::isArray($members,'members') && fxAssert::isNotEmpty($members,'members');

		parent::__construct($name, $label);
		$this->_members = $members;
		$this->id = $tmp = fxForm::_simplify($name);
		$this->name = $tmp.'[]';

		$this->_mmap = self::makeMemberMap( $members );
	}


	public function renderUsing( fxRenderer &$r, fxForm &$f, $parent_id )
	{
		return $r->renderSelect($this, $f, $parent_id );
	}
}



class fxFormMSelect extends fxFormSelect
{
	public function __construct($name, $label, $members)
	{
		parent::__construct($name, $label, $members);
		$this->multiple();
	}
}




#eof
