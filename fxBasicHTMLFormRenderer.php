<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{
	static public function render( fxFormElement &$e, fxForm &$f, $parent_id )
	{
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value,id' ) );
		$label  = htmlspecialchars($e->_name);
		$subval = $e->_value;
		$elval  = htmlspecialchars($e->value);
		$id     = self::makeId($e, $parent_id);
		$plce   = (string)$e->_note;
		if( '' !== $plce )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';

		$o = array();

		$class = self::getClasses($e);
		$type  = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		$o[] = "<$type$id$attr$plce$class";

		if( 'submit' == $e->type || 'reset' == $e->type )
			$o[] = "value=\"$elval\" />$label</button>";
		elseif( in_array( $e->type, fxFormElement::$radio_types) || 'hidden' === $e->type )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		$errmsg = self::addErrorMessage( $e, $f );
		if( '' !== $errmsg ) $o[] = $errmsg;

		$o = join( " ", $o );
		return self::addLabel( $o, $e, $parent_id );
	}


	static public function renderForm( fxForm &$f )
	{
		self::$rendering_element_set = false;
		$o = array();
		$atts = self::renderAtts( $f->_getInfoExcept('name') );
		$o[] = "<form action=\"{$f->_action}\" method=\"{$f->_method}\"$atts>";
		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$f->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$f->_form_token}\" />";


		if( $f->hasErrors() ) {
			// Use a form errors formatting callback to override basic message.
			$formErrorsCB = $f->_formatFormErrors;
			if( is_callable( $formErrorsCB ) ) {
				$msg = $formErrorsCB( $f );
				if( is_string($msg) && '' !== $msg )
					$o[] = $msg;
			}
			else {
				$o[] = '<div class="form-errors"><p>There was a problem with your form. Please correct any errors and try again.</p></div>';
			}
		}

		foreach( $f->getElements() as $child ) {
			if( is_string($child) )
				$o[] = $child;
			else {
				$o[] = $child->renderUsing( __CLASS__, $f, $f->id );
			}
		}
		$o[] = "</form>";
		$o = implode( "\n", $o );
//echo "<pre>",htmlspecialchars( var_export( $o, true ) ), "</pre>\n";
//fCore::expose($f);
		return $o;
	}


	static public function renderElementSet( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
//echo "<pre>",htmlspecialchars( var_export( $e, true ) ), "</pre>\n";
		$o = array();
		$class = self::getClasses($e);
		$o[] = "<div$class>";

		self::$rendering_element_set = true;
		foreach( $e->getElements() as $el ) {
//echo "<pre>",htmlspecialchars( var_export( $el, true ) ), "</pre>\n";
			$o[] = $el->renderUsing( __CLASS__, $f, $parent_id );
		}
		self::$rendering_element_set = false;

		$o[] = '</div>';

		$errmsg = self::addErrorMessage( $e, $f );
		if( '' !== $errmsg ) $o[] = $errmsg;

		$o = implode( "\n", $o );
		return self::addLabel( $o, $e, $parent_id, true );
	}


	static public function renderFieldSet( fxFormFieldset &$e, fxForm &$f, $parent_id )
	{
//echo "<pre>",htmlspecialchars( var_export( $e, true ) ), "</pre>\n";
		$o = array();
		$class = self::getClasses($e);
		$o[] = "\n<fieldset $class><legend>{$e->_name}</legend>";

		foreach( $e->getElements() as $el ) {
//echo "<pre>",htmlspecialchars( var_export( $el, true ) ), "</pre>\n";
			$o[] = $el->renderUsing( __CLASS__, $f, $parent_id );
		}

		$o[] = "</fieldset>\n";
		$o = implode( "\n", $o );
		return $o;
	}


	static public function renderOptions( $options, fxFormElementSet &$e, fxForm &$f, $parent = '' )
	{
		$html5 = $f->_target === 'html5';
		$o = array();
		if( '' != $parent ) $parent .= '-';
		if( !empty( $options ) ) {
			foreach( $options as $k => $v ) {
				if( is_array( $v ) ) {
					$o[] = '<optgroup label="'.htmlspecialchars($k).'">';
					$o[] = self::renderOptions($v, $e, $f, $parent.fxForm::_simplify($k) );
					if( !$html5 ) $o[] = "</optgroup>";
				}
				else {
					$selected = in_array( $parent.fxForm::_simplify($k), $e->_value) ? ' selected' : '' ;
					$o[] = "<option$selected value=\"".$parent.fxForm::_simplify($k)."\">".htmlspecialchars($v)."</option>";
				}
			}
		}
		return implode("\n",$o);
	}

	static public function renderSelect( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value,id' ) );
		$id     = self::makeId($e, $parent_id);
		$label  = htmlspecialchars( $e->_name );

		$o[] = "<select$id$attr>";
		$o[] = self::renderOptions( $e->_members, $e, $f );
		$o[] = '</select>';

		$o = implode( "\n", $o );
		return self::addLabel( $o, $e, $parent_id );
	}


	static public function renderTextarea( fxFormElement &$e, fxForm &$f, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value,id' ));
		$id    = self::makeId($e, $parent_id);
		$class = self::getClasses($e);
		return self::addLabel( "<textarea$id$attr$class>{$e->_value}</textarea>".self::addErrorMessage( $e, $f ), $e, $parent_id );
	}


	static public function renderButton( fxFormButton &$e, fxForm &$f, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value,id' ));
		$id    = self::makeId($e, $parent_id);
		$class = self::getClasses($e);
		$label = htmlspecialchars($e->_name);
		return "<button$id$attr$class>$label</button>";
	}
}


#eof
