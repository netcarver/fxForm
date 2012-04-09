<?php

include("./head.inc");

require_once( wire('config')->paths->root . "site/forms/forms.php" );

#
#	TODO: How to use flourish for validation? Does this make any sense?
#	TODO: Upload element.
#	TODO: Add Bootstrap renderer
#	TODO: Package as PW module
#	TODO: How to handle notes on html4 elements that don't support the placeholder tag? Perhaps use the elementError formatter?
#	TODO: Handle Multilingual strings and numbers?
#	TODO: Add population of form definition within a new admin page (public forms->contact) etc. Would allow form defs to be done in admin if.
#
#	TODO: Autogeneration of conditional enable/disable js code??
#	TODO: Add support for nested fieldsets?? (I'd prefer not to have to do this.)
#

/** ===================  Set up the arrays of members for the radios / checkboxes / selects... ===================
 *
 *	When this is packaged as a PW module, this kind of data is probably going to be populated via the wire API
 *
 **/
$departments = array(	// This sets up the values for a multi-select element in the form.
	'Inhuman Resources',
	'Sales' => array(			// This will become an optgroup in the select element.
		'bk'=>'Books',				// Keys don't have to be implicit. Use whatever key you need or makes sense to your app.
		'Records',
		'Post Complaint Therapy',
	),
	'Complaints' => array(		// Another optgroup
		'Complaints about the Books dept.',
		'Complaints about the Records dept.',
		'Complaints about the Complaints dept.',
		'Complaints about the Post Complaint Therapy dept.',
	),
);

$conditions  = array(	// This sets up the values for a radio-set in the form
	'n'=>'You must be joking.',
	'y'=>'Yes, I do, I do, I do!'
);

$checkboxes  = array(	// This sets up the values for a checkbox set in the form
	'spam_me' => 'Spam my email address',
	'extra' => 'Extra frequently'
);


/** ================================== Configure the renderer ==================================
 *
 * Set up the renderer that this form will use to generate output.
 * Even the basic HTML renderer output can be customised via callbacks for the form-level errors, element-level errors
 * and for prefix/suffix on checkbox and radiobox sets.
 **/
$r = new fxBasicHTMLFormRenderer( '', '<br>' );				// Defines the prefix (blank) and suffix ('<br>') to use on each form element. If you like divs, '<div class="blah">', '</div>' should go in here.
$r
	->setTarget('html5')									// You can use this to provide formatting hints to your renderer.
	->setAffixFormatter('myAffixFormatter')					// Provides a formatting callback that can add custom wrapping around radiobox and checkbox set elements
	//->setErrorBlockFormatter('myFormErrorsFormatter')		// Provides an error formatting routine for the head of the form. You can print a summary, or an entire list of errors -- it's up to you!
	//->setElementErrorFormatter('myElementErrorsFormatter')	// Provides a custom format routine for rendering an elements error next to it. If you don't want any output near the element, just return ''. Else format the error as you wish.
	;

/** ================================== The actual form... ==================================
 *
 * You can set any html attribute on forms and their elements using the ->attribname(value) syntax. So to add a class you'd do ->class('someclass'),
 * for a placeholder: ->placeholder('Some text'). If the attribute doesn't take a value just omit it, so : ->required()
 *
 * In addition to HTML attributes, each element takes meta-data such as the submitted value. All meta-data is set in exactly the same way but is prefixed
 * with a single underscore. For example, you can set the form's show_submitted flag with ->_show_submitted(true), or force an initial check on a specific
 * item in a radioset using ->_value('name') -- in this case you are setting up the element to appear as if that value was already submitted to it.
 *
 * This is all done to make form specification as terse and fluent as possible -- yet still giving control where needed.
 *
 **/
$contact_form = Form('contact', './')
	->setRenderer( $r )					// Tells the form which renderer to use to generate its output.
	//->_show_submitted(true)			// Causes the form to show submitted values
	//->_show_html(true)				// Causes the form's renderer to expose the generated HTML for the form.
	//->_show_form_elements(true)		// Causes the form to show its internal structure
	//->match('myContactFormValidator')	// Adds a validator to the form. You can use a form-level validator to add complex inter-item validation.
	->onSuccess('MySuccessHandler')

	// Here come the form elements...
	->add( Fieldset('About you...')
		->add( Input('Name *', 'Your name please')->required()->match('myNameValidator') )
		->add( Input('Email *', 'Your email address')->type('email')->required() )
		->add( Input('URL', 'Your URL here (optional)')->type('url') )
		//->add( Hidden('secret','123') )
		)

	->add( Fieldset('Your message...')
		->add( TextArea('Message *', 'Your message to us')->required()->matchPattern('^[^0-9]*$') )
	)

	->add( Fieldset('Legal stuff...')
		->add( Radios('>Do you agree to our terms? *', $conditions, 'agree' )
				->required()
				->match('myConditionValidator')
				//->_value('n')					// Configure the initial value. Use the key of the item you want selected from the $conditions array.
		)
		->add( Checkboxes( 'Additional Options...', $checkboxes, 'options' )
				//->_value( array( 'spam' ) )	// Initial value(s). Just add more keys from the $checkboxes array for multiple checkmarks.
		)
		->add( MSelect( 'Forward to which departments?', $departments, 'departments' ) )
	)

	->add( Submit('Send') )

	->process()
	;

/** ================================== Custom formatters follow ==================================
 *
 * These all override, or append to, some aspect of the renderer's output and should allow you
 * fine enough control over your form output not to have to resort to hand-crafted HTML.
 *
 * They are all enabled by setting values on the renderer.
 * Thay are also totally option. In fact, the default output of the renderer should be fine in most
 * cases so you can probably delete all the code in this part of the file.
 **/


/**
 * Controls the output that goes at the head of the form when there are any invalid elements. Use this only if the renderer's default markup isn't what you need.
 *
 * **Make sure the renderer's ->setErrorBlockFormatter() is uncommented and pointing here for it to get used**
 **/
function myFormErrorsFormatter( fxForm &$f )
{
	return '<h4>There was an error in your submission. Please correct and try again.</h4>';
}


/**
 * Controls the markup that goes around each element's errors. Use this only if the renderer's default markup isn't what you need.
 *
 * **Make sure the renderer's ->setElementErrorFormatter() is uncommented and pointing here for it to get used**
 **/
function myElementErrorsFormatter( fxFormElement &$e, fxForm &$f )
{
	return '<h4>'.htmlspecialchars($e->_errors[0]).'</h4><br>';
}


/**
 * Custom radio + checkbox formatter. **Make sure the renderer's ->setAffixFormatter() is uncommented and pointing here for it to be used*
 * Allows you to override the renderer's default wrapping of each radio / checkbox option.
 *
 * In this case, I've chosen to turn the 'options' checkboxes an ordered list and the 'agree' radio buttons have <br> tags between them.
 **/
function myAffixFormatter( $element, $owner_name, $index, $max )
{
	// Makes the set of checkboxes called 'options' an ordered list...
	if( 'options' == $owner_name ) {
		if( 0 == $index )
			$element = "<ol><li>" . $element;
		else
			$element = "<li>" . $element;

		if( $index == $max )
			$element .= "</li>\n</ol>\n\n";
		else
			$element .= "</li>\n";
	}

	// Adds breaks after each radiobutton...
	if( 'agree' == $owner_name ) $element .= "<br>";

	return $element;
}



/** ================================== Form validation callbacks follow ==================================
 *
 **/

/**
 * A custom validation callback for the 'agree' radioset. **Make sure the agree radioset's ->match() method is uncommented and pointing here for it to get called**
 **/
function myConditionValidator( fxFormElement &$e, fxForm &$f )
{
	if( 'y' === $e->_value )
		return true;
	return 'Sorry, but we cannot continue without your agreement.';
}


/**
 * A custom validation callback for the 'Name' input. **Make sure the name input's ->match() method is uncommented and pointing here for it to get called**
 **/
function myNameValidator( fxFormElement &$e, fxForm &$f )
{
	$ok = 'Ben';
	if( $ok === $e->_value )
		return true;
	return "Name must be '$ok'. No exceptions.";
}



/** ================================== Form submission handler follows ==================================
 *
 * A success handler for the form. **Make sure the form's ->onSuccess() method is uncommented and pointing here for it to get called**
 *
 **/
function mySuccessHandler( fxForm &$form )
{
	return "<h3>Thank you {$form->getValueOf('Name')}, your message has been sent.</h3>";
}


?>
	<section id="main-content">

<div class="content document-markup">
	<!-- Some yukky bold styles to show off the element states -->
	<style>
		div.ok, input.ok, textarea.ok {
			border: 2px solid #30cf30;
		}
		div.required, input.required, textarea.required {
			border: 2px solid #cf30cf;
		}
		div.error, input.error, textarea.error {
			border: 2px solid #cf3030;
		}
		fieldset legend {
			padding-top: 10px;
		}
		span.error-msg {
			font-weight: bold;
		}
	</style>
	<div>
		<h3>Example Contact Form</h3>
		<?= $contact_form  ?>
	</div>
</div>
	</section>
<?php
include("./foot.inc");
unset( $f );
#eof
