<?php

/**
 * Basic HTML form element.
 * Adds _meta elements for behaviour control.
 **/
abstract class fxFormElement extends fxHTMLStatement
{
	protected $_meta;
	static public $radio_types = array('radio','checkbox');


	public function __construct($name)
	{
		parent::__construct($name);
		$this->_atts['name'] = $this->_atts['id'] = fxHTMLStatement::_simplify( $name );
		$this->_meta = array(
			'required' => true,
			'label'    => true,
			'owner'    => null,
		);
	}

	public function match($pattern)
	{
		fxAssert::isNonEmptyString($pattern, 'pattern');
		fxAssert::isNotInArrayKeys('match', $this->_meta, 'Cannot redefine match $pattern for ['.$this->_set_name.'].');
		$this->_meta['match'] = $pattern;
		return $this;
	}

	public function optional() 				{ $this->_meta['required'] = false;    return $this; }
	public function note($note)				{ $this->_meta['note'] = $note;        return $this; }
	public function setOwner( $owner_name ) { $this->_meta['owner'] = $owner_name; return $this; }
	public function _getMeta()				{ return $this->_meta; }


	public function _isValid()
	{
		//
		//	Store the submitted value in the meta info
		//
		$this->_meta['value'] = fRequest::encode($this->_atts['name']);
$subval = var_export( $this->_meta['value'], true );
echo sed_dump("Validating {$this->_set_name} :: get({$this->_atts['name']}) gives [$subval]");

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
	public function _getHTMLType()
	{
		return get_class($this);
	}
}


/**
 * Radiosets, checkboxes and Selects are implemented by having multiple elements.
 **/
abstract class fxFormElementSet extends fxHTMLStatement
{
	protected $_elements;

	public function __construct( $name )
	{
		parent::__construct($name);
		$this->_elements = array();
	}

	protected function _getExpandedElements()
	{
		/**
		 * Take element-dependent action. Allows containers like RadioSets and CheckboxSets to convert
		 * themselves to a real set of normal inputs.
		 **/
		return $this->_elements;
	}


	/**
	 * Allows for the addition of elements to a form...
	 **/
	public function add( $element )
	{
		fxAssert::isNotEmpty( $element, 'element' );
		if( $element instanceof fxFormElementSet ) {
			$this->_elements = array_merge( $this->_elements, $element->_getExpandedElements() );
		}
		else
			$this->_elements[] = $element;
		return $this;
	}

	/**
	 * Allows for the conditional addition of elements to a form
	 **/
	public function _addIf( $condition, $element )
	{
		if( $condition ) $this->add( $element );
		return $this;
	}
}




#eof
