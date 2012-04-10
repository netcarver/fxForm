<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{

	public function render( fxFormElement &$e, fxForm &$f, $parent_id )
	{
		$attr   = $this->renderAtts( $e->_getInfoExcept( 'class,value,id,type' ) );
		$label  = htmlspecialchars($e->_name);
		$subval = $e->_value;
		$elval  = htmlspecialchars($e->value);
		$itype  = $e->type;
		$id     = $this->makeId($e, $parent_id);
		$plce   = (string)$e->_note;
		if( '' !== $plce && $this->target == 'html5' )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';
		else
			$plce = ''; // HTML4 doesn't have placeholder.

		$o = array();

		if( 'html4' == $this->target ) {
			if( in_array( $itype, array('tel','number','date','search','email','url') ) )
				$itype='text';
		}

		$class = $this->getClasses($e);
		$type  = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		$o[] = "<$type type=\"$itype\" $id$attr$plce$class";

		if( 'submit' == $itype || 'reset' == $itype )
			$o[] = "value=\"$elval\" />$label</button>";
		elseif( in_array( $itype, fxFormElement::$radio_types) || 'hidden' === $itype )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		$errmsg = $this->addErrorMessage( $e, $f );
		if( '' !== $errmsg ) $o[] = $errmsg;

		$o = join( " ", $o );
		return $this->addLabel( $o, $e, $parent_id );
	}



	public function renderForm( fxForm &$f )
	{
		$this->rendering_element_set = false;
		$o = array();
		$atts = $this->renderAtts( $f->_getInfoExcept('name') );
		$o[] = "<form action=\"{$f->_action}\" method=\"{$f->_method}\"$atts>";
		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$f->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$f->_form_token}\" />";


		if( $f->hasErrors() ) {
			// Use a form errors formatting callback to override basic message.
			$formErrorsCB = $this->errorBlockFormatter;
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
				$o[] = $child->renderUsing( $this, $f, $f->id );
			}
		}
		$o[] = "</form>";
		$o = implode( "\n", $o );

		if( true == $f->_show_html )
			fCore::expose($o);

		return $o;
	}



	public function renderElementSet( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		$class = $this->getClasses($e);
		$o[] = "<div$class>";

		$elements = $e->getElements();
		$this->set_max   = count( $elements );
		$this->set_index = 0;
		if( $this->set_max ) $this->set_max--;

		$this->rendering_element_set = true;
		foreach( $elements as $el ) {
			$o[] = $el->renderUsing( $this, $f, $parent_id );
			$this->set_index++;
		}
		$this->rendering_element_set = false;

		$o[] = '</div>';

		$errmsg = $this->addErrorMessage( $e, $f );
		if( '' !== $errmsg ) $o[] = $errmsg;

		$o = implode( "\n", $o );
		return $this->addLabel( $o, $e, $parent_id, true );
	}



	public function renderFieldSet( fxFormFieldset &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		$class = $this->getClasses($e);
		$o[] = "\n<fieldset $class><legend>{$e->_label}</legend>";

		foreach( $e->getElements() as $el ) {
			$o[] = $el->renderUsing( $this, $f, $parent_id );
		}

		$o[] = "</fieldset>\n";
		$o = implode( "\n", $o );
		return $o;
	}



	public function renderOptions( $options, fxFormElementSet &$e, fxForm &$f, $parent = '' )
	{
		$html5 = $f->_target === 'html5';
		$o = array();
		if( '' != $parent ) $parent .= '-';
		if( !empty( $options ) ) {
			foreach( $options as $k => $v ) {
				if( is_array( $v ) ) {
					$o[] = '<optgroup label="'.htmlspecialchars($k).'">';
					$o[] = $this->renderOptions($v, $e, $f, $parent.fxForm::_simplify($k) );
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



	public function renderSelect( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		$attr   = $this->renderAtts( $e->_getInfoExcept( 'class,value,id' ) );
		$id     = $this->makeId($e, $parent_id);
		$label  = htmlspecialchars( $e->_name );

		$o[] = "<select$id$attr>";
		$o[] = $this->renderOptions( $e->_members, $e, $f );
		$o[] = '</select>';

		$o = implode( "\n", $o );
		return $this->addLabel( $o, $e, $parent_id );
	}



	public function renderTextarea( fxFormElement &$e, fxForm &$f, $parent_id )
	{
		$attr  = $this->renderAtts($e->_getInfoExcept( 'class,value,id' ));
		$id    = $this->makeId($e, $parent_id);
		$class = $this->getClasses($e);
		return $this->addLabel( "<textarea$id$attr$class>{$e->_value}</textarea>".$this->addErrorMessage( $e, $f ), $e, $parent_id );
	}



	public function renderButton( fxFormButton &$e, fxForm &$f, $parent_id )
	{
		$attr  = $this->renderAtts($e->_getInfoExcept( 'class,value,id' ));
		$id    = $this->makeId($e, $parent_id);
		$class = $this->getClasses($e);
		$label = htmlspecialchars($e->_label);
		return "<button$id$attr$class>$label</button>";
	}


}


#eof
