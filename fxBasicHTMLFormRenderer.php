<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{
	static public function render( fxFormElement &$e, $parent_id )
	{
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value' ) );
		$label  = htmlspecialchars($e->_name);
		$subval = $e->_value;
		$elval  = htmlspecialchars($e->value);
		$id     = htmlspecialchars($e->id);
		$plce   = (string)$e->_note;
		if( '' !== $plce )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';

		$o = array();

		$class = self::getClasses($e);
		$type  = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		$o[] = "<$type$attr$plce";
		$o[] = $class;
		$o[] = $chckd;

		if( 'textarea' == $type )
			$o[] = ">$subval</textarea>";
		elseif( 'button' == $type || 'submit' == $e->type || 'reset' == $e->type )
			$o[] = "value=\"$elval\" />$label</button>";
		elseif( in_array( $e->type, fxFormElement::$radio_types) || 'hidden' === $e->type )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		$o = join( " ", $o );
		return self::addLabel( $o, $e );
	}


	static public function addLabel( $thing, fxFormElement &$e, $for_id )
	{
		if( $e->_nolabel )
			return $thing;

		$label = '<label for="'.htmlspecialchars($e->id).'">'.htmlspecialchars($e->_name).'</label>';

		return ($e->_label_right) ? $thing. $label : $label . $thing;
	}


	static public function renderForm( fxForm &$f )
	{
		$o = array();
		$atts = self::renderAtts( $f->_getInfoExcept() );
		$o[] = "<form action=\"{$f->_action}\" method=\"{$f->_method}\"$atts>";
		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$f->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$f->_form_token}\" />";
		foreach( $f->getElements() as $child ) {
			if( is_string($child) )
				$o[] = $child;
			else {
				$o[] = $child->renderUsing( __CLASS__, $f, $f->id );
			}
		}
		$o[] = "</form>";
		$o = implode( "\n", $o );
$f->dump();
		return $o;
	}



	static public function renderElementSet( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		foreach( $e->getElements() as $el ) {
			$o[] = $el->renderUsing( __CLASS__, $f, $parent_id );
		}
		$o = implode( "\n", $o );
		return $o;
	}


	static public function renderTextarea( fxFormElement &$e, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value' ));
		$class = self::getClasses($e);
		return self::addLabel( "<textarea $attr$class>{$e->_value}</textarea>", $e );
	}


	static public function getClasses(fxFormElement &$e)
	{
		$classes = array();
		if( $e->class )
			$classes[] = htmlspecialchars($e->class);
		if( $e->_inData('required') )
			$classes[] = 'required';
		if( empty( $classes ) )
			return '';

		return ' class="'.implode(' ',$classes).'"';
	}

	/* static public function getID(fxFormElement &$e, $parent_id) */
	/* { */
	/* 	if( $e->_inData('id') ) */
	/* 		return htmlspecialchars($e->id); */

	/* 	$id = ( $parent_id ) ? $parent_id : '' ; */
	/* 	$id = fxForm::_simplify( $id . '_' . $e->_name ); */
	/* 	return $id; */
	/* } */


	static public function renderButton( fxFormButton &$e, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value' ));
		$label = htmlspecialchars($e->_name);
		$class = self::getClasses($e);
		return "<button $attr$class>$label</button>";
	}

}


#eof
