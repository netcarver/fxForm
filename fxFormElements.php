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
		$this->_value = fRequest::encode($this->_data['name']);
//$subval = var_export( $this->_meta['value'], true );
//echo sed_dump("Validating {$this->_name} :: get({$this->_data['name']}) gives [$subval]");


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


	/**
	 * Each element will be asked to use the given renderer to get itself output.
	 **/
	abstract public function renderUsing( $r, fxForm &$f, $parent_id );
}


/**
 * Radiosets, checkboxes and Selects are implemented by having multiple elements.
 **/
abstract class fxFormElementSet extends fxFormElement
{
	protected $_elements;

	public function __construct( $name, $note = null )
	{
		parent::__construct($name);
		$this->_note = $note;
		$this->_elements = array();
	}

	public function getElements()
	{
		return $this->_elements;
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

		/* if( $element instanceof fxNamedSet ) { */
		/* 	$element->_owner = $this->id; */
		/* 	/1* TODO : defer id/name calcs to render() time? << might have to. *1/ */
		/* 	/1* if( $this->id ) { *1/ */
		/* 	/1* 	$element->name = $element->id = $this->id . '-' . $element->id; *1/ */
		/* 	/1* } *1/ */
		/* } */

		if( $element instanceof fxFormElementSet ) {
			$this->_elements = array_merge( $this->_elements, $element->_getExpandedElements() );
		}
		elseif( $element instanceof fxFormElement ) {
			$this->_elements[] = $element;
		}
		elseif( is_string( $element ) ) {
			$this->_elements[] = new fxFormString( $element );
		}
		else {
			throw new exception( "Added element must be a string, fxFormElement or fxFormElementSet." );
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
	 * Each element will be asked to use the given renderer to get itself output.
	 **/
//	abstract public function renderUsing( $r, fxForm &$f, $parent_id );

}




#eof
