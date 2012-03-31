<?php

/**
 * Basic HTML form element.
 * Adds _meta elements for behaviour control.
 *
 * Note, this extends fxNamedSet but that *doesn't* mean it *is* a set of elements; it means that
 * an element *has* an associated set of data and meta-data. In this case, that data holds an
 * element's attributes and the meta data holds other information such as the name associated with
 * the element.
 **/
abstract class fxFormElement extends fxNamedSet
{
	static public $radio_types = array('radio','checkbox');


	public function __construct($name , $note = null)
	{
		parent::__construct($name);
		$this->_note = $note;
		$this->name = $this->id = fxForm::_simplify( $name );
	}

	public function match($pattern)
	{
		fxAssert::isNonEmptyString($pattern, 'pattern');
		fxAssert::isNotInArrayKeys('match', $this->_meta, 'Cannot redefine match $pattern for ['.$this->_name.'].');
		$this->_validator = $pattern;
		return $this;
	}


	public function _isValid()
	{
		//
		//	Encode & store the submitted value in the meta info
		//
		$this->_meta['value'] = fRequest::encode($this->_data['name']);
$subval = var_export( $this->_meta['value'], true );
//echo sed_dump("Validating {$this->_name} :: get({$this->_data['name']}) gives [$subval]");

		//
		//	Store the checkedness of the element based on the submitted value
		//
		if( in_array($this->_data['type'], self::$radio_types ) ) {
			$this->_meta['checked'] = ($this->_data['value'] === $this->_meta['value']);	// Why is this happening at this level?
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
abstract class fxFormElementSet extends fxNamedSet
{
	protected $_elements;

	public function __construct( $name, $note = null )
	{
		parent::__construct($name);
		$this->_note = $note;
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
	 * Allows the addition of an element to a form
	 **/
	public function add( $element )
	{
		fxAssert::isNotEmpty( $element, 'element' );

		if( $element instanceof fxNamedSet ) {
			$element->_owner = $this->id;
			/* TODO : defer id/name calcs to render() time? */
			/* if( $this->id ) { */
			/* 	$element->name = $element->id = $this->id . '-' . $element->id; */
			/* } */
		}

		if( $element instanceof fxFormElementSet ) {
			$this->_elements = array_merge( $this->_elements, $element->_getExpandedElements() );
		}
		else
			$this->_elements[] = $element;

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
}




#eof
