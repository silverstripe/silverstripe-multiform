# MultiForm Module

[![Build Status](https://api.travis-ci.org/silverstripe/silverstripe-multiform.svg?branch=master)](https://travis-ci.org/silverstripe/silverstripe-multiform)
[![Latest Stable Version](https://poser.pugx.org/silverstripe/multiform/version.svg)](https://github.com/silverstripe/silverstripe-multiform/releases)
[![Latest Unstable Version](https://poser.pugx.org/silverstripe/multiform/v/unstable.svg)](https://packagist.org/packages/silverstripe/multiform)
[![Total Downloads](https://poser.pugx.org/silverstripe/multiform/downloads.svg)](https://packagist.org/packages/silverstripe/multiform)
[![License](https://poser.pugx.org/silverstripe/multiform/license.svg)](https://github.com/silverstripe/silverstripe-multiform/blob/master/license.md)

## Introduction

MultiForm is a SilverStripe module, allowing flow control for forms, and step 
process to be automatically determined based on configuration variables on each 
step class. It augments the existing Form class in SilverStripe.

The goal of the module is to allow greater flexibility than features, so each 
individual implementation can be customized to the project requirements.

## Maintainer Contact

 * Sean Harvey (Nickname: sharvey, halkyon) <sean (at) silverstripe (dot) com>
 * Ingo Schommer (Nickname: chillu) <ingo (at) silverstripe (dot) com>

## Requirements

SilverStripe 3.1+.

## What it does do


*  Abstracts fields, actions and validation to each individual step.
*  Maintains flow control automatically, so it knows which steps are ahead and 
behind. It also can retrieve an entire step process from start to finish, useful 
for a step list.
*  Persists data by storing it in the session for each step, once it's 
completed. The session is saved into the database.
*  Allows customisation of next, previous steps, saving, loading and 
finalisation of the entire step process
*  Allows for basic ability to branch a step by overloading the next step method
 with logic to switch based on a condition (e.g. a checkbox, or dropdown in the 
 field data).
*  Ties a user logged in who is using the step process (if applicable). This 
means you can build extended security or logging.
*  Basic flexibility on the URL presented to the user when they are using the 
forms. By default it stores an encrypted hash of the session in the URL, but you 
can reference it by the ID instead. It's recommend that additional security, 
such as checking the user who first started the session be applied if you want 
to reference by ID.

## What it doesn't do

*  Automatically handle relation saving, e.g. MembershipForm manages the Member
*  Provide a complete package out of the box (you must write a bit of code using 
the tutorial!)
*  Automatically determine what to do at the end of the process, and where to 
save it
*  Provide nicely presented URLs of each step (an enhancement, for the future)

Note: The *multiform* directory should sit in your SilverStripe root project 
directory in the file system as a sibling of cms and framework.

## Reporting bugs

If you've found a bug that should be fixed for future releases, then please make 
a ticket on https://github.com/silverstripe/silverstripe-multiform/issues. 

This helps to ensure we release less buggy software in the future!

## Tutorial

The assumption is the developer who is starting this tutorial has an 
intermediate level of knowledge in SilverStripe, understands what "run 
dev/build?flush=1" means, and has written some custom PHP code in SilverStripe 
before.

If you are not familiar with SilverStripe, it is highly recommended you run 
through the tutorials before attempting to start with this one.

*  [View a listing of all available tutorials](http://doc.silverstripe.org/tutorials)

### 1. Installing

Using [Composer](https://getcomposer.org/), you can install multiform into your
SilverStripe site using this command (while in the directory where your site is
currently located)

	composer require "silverstripe/multiform:*"

### 2. Create subclass of MultiForm

First of all, we need to create a new subclass of *MultiForm*.

For the above example, our multi-form will be called *SurveyForm*

	:::php
	<?php
	
	class SurveyForm extends MultiForm {
	
	}


### 3. Set up first step

Now that we've created our new MultiForm subclass step, we need to define what 
form is going to be the first step in our multi-step process.

Each form step must subclass MultiFormStep. This is so the multi-form can 
identify that this step is part of our step process, and is not just a standard 
form.

So, for example, if we were going to have a first step which collects the 
personal details of the form user, then we might have this class:

	:::php
	<?php
	
	class SurveyFormPersonalDetailsStep extends MultiFormStep {
	
	}


Now that we've got our first step of the form defined, we need to go back to our 
subclass of MultiForm, SurveyForm, and tell it that SurveyFormPersonalDetailsStep 
is the first step.

	:::php
	<?php
	
	class SurveyForm extends MultiForm {
	
		public static $start_step = 'SurveyFormPersonalDetailsStep';
	
	}


### 4. Define next step, and final step

We've managed to set up the basics of multi-form, but it's not very useful, as 
there's only one step!

To get more than one step, each step needs to know what it's next step is in 
order to use flow control in our system.

To let the step know what step is next in the process, we do the same as setting 
the `$start_step` variable *SurveyForm*, but we call it `$next_steps`.

	:::php
	<?php
	
	class SurveyFormPersonalDetailsStep extends MultiFormStep {
	
		public static $next_steps = 'SurveyFormOrganisationDetailsStep';

		public function getFields() {
			return new FieldList(
				new TextField('FirstName', 'First name'),
				new TextField('Surname', 'Surname')
			);
		}
	
	}


At the very least, each step also has to have a `getFields()` method returning 
a *FieldSet* with some form field objects. These are the fields that the form 
will render for the given step.

Keep in mind that our multi-form also requires an end point. This step is the 
final, and needs to have another variable set to let the multi-form system know 
this is the final step.

So, if we assume that the last step in our process is 
SurveyFormOrganisationDetailsStep, then we can do something like this:

	:::php
	<?php
	
	class SurveyFormOrganisationDetailsStep extends MultiFormStep {
	
		public static $is_final_step = true;
	
	}


### 5. Run database integrity check

We need to run *dev/build?flush=1* now, so that the classes are available to the
SilverStripe manifest builder, and to ensure that the database is up to scratch 
with all the latest tables. So you can go ahead and do that.

*Note: Whenever you add a new step, you **MUST** run dev/build?flush=1 or you 
may receive errors.*

However, we've forgotten one thing. We need to create a method on a page-type so 
that the form can be rendered into a given template.

So, if we want to render our multi-form as `$SurveyForm` in the *Page.ss* 
template, we need to create a SurveyForm method (function) on the controller:

	:::php
	<?php
	
	class Page extends SiteTree {
	
	// ...
	
	}
	
	class Page_Controller extends ContentController {
	
	// ...

		// 
		private static $allowed_actions = array(
			'SurveyForm',
			'finished'
		);

		public function SurveyForm() {
			return new SurveyForm($this, 'Form');
		}
		
		public function finished() {
			return array(
				'Title' => 'Thank you for your submission',
				'Content' => '<p>You have successfully submitted the form!</p>'
			);
		}
	
	// ...
	
	}


The `SurveyForm()` function will create a new instance our subclass of 
MultiForm, which in this example, is *SurveyForm*. This in turn will then set 
up all the form fields, actions, and validation available to each step, as well 
as the session.

You can of course, put the *SurveyForm* method on any controller class you 
like.

Your template should look something like this, to render the form in:

	:::html
	<div id="content">
		<% if $Content %>
			$Content
		<% end_if %>
		
		<% if $SurveyForm %>
			$SurveyForm
		<% end_if %>
		
		<% if $Form %>
			$Form
		<% end_if %>
	</div>


In this case, the above template example is a *sub-template* inside the *Layout* 
directory for the templates. Note that we have also included `$Form`, so 
standard forms are still able to be used alongside our multi-step form.

### 6. Adding a step indicator

By default, we include a couple of basic progress indicators which could be 
useful, out of the box.

Two of them, as of the time of writing this are:

*  Progress list (multiform/templates/Includes/MultiFormProgressList.ss)
*  Progress complete percent (multiform/templates/Includes/MultiFormProgressPercent.ss)

They are designed to be used either by themselves, or alongside each other. 
For example, the percentage could compliment the progress list to give an 
indication of completion status.

To include these with our instance of multiform, we just need to add an 
`<% include %>` statement into the template.

For example:

	:::html
	<% with $SurveyForm %>
		<% include MultiFormProgressList %>
	<% end_with %>


This means the included template is rendered within the scope of the 
SurveyForm instance returned, instead of the top level controller context. 
This gives us the data to show the progression of the steps.

Putting it together, we might have something looking like this:

	:::html
	<div id="content">
		<% if $Content %>
			$Content
		<% end_if %>
		
		<% if $SurveyForm %>
			<% with $SurveyForm %>
				<% include MultiFormProgressList %>
			<% end_with %>

			$SurveyForm
		<% end_if %>
		<% if $Form %>
			$Form
		<% end_if %>
	</div>


Feel free to play around with the progress indicators. If you need something 
specific to your project, just create a new "Include" template inside your own 
project templates directory, and include that instead. Some helpful methods to 
use on the MultiForm would be:


* `AllStepsLinear()` (which also makes use of `getAllStepsRecursive()` to 
produce a list of steps)
* `getCompletedStepCount()`
* `getTotalStepCount()`
* `getCompletedPercent()`

The default progress indicators make use of the above functions in the 
templates.

To use a custom method of your own, simply create a new method on your subclass 
of MultiForm. In this example, *SurveyForm* would be the one to customise. 
This new method you create would then become available in the progress indicator
template.


### 7. Loading values from other steps

There are several use cases were you want to pre-populate a value based
based on the submission value of another step. There are two methods supporting this:

* `getValueFromOtherStep()` load any submitted value from another step from the session
* `copyValueFromOtherStep()` saves you the repeated work of adding the same lines of code again and again.

Here is an example of how to populate the email address from step 1 in step2 :

	:::php
	<?php

	class Step1 extends MultiFormStep
	{
		public static $next_steps = 'Step2';

		public function getFields() {
			return new FieldList(
				new EmailField('Email', 'Your email')
			);
		}
	}

	class Step2 extends MultiFormStep
	{
		public static $next_steps = 'Step3';

		public function getFields() {
			$fields = new FieldList(
				new EmailField('Email', 'E-mail'),
				new EmailField('Email2', 'Verify E-Mail')
			);

			// set the email field to the input from Step 1
			$this->copyValueFromOtherStep($fields, 'Step1', 'Email');

			return $fields;
		}
	}


### 8. Finishing it up

Now that we've got a structure set up to collect form data along each step, and
progress through successfully, we need to customise what happens at the end of 
the last step.

On the final step, the `finish()` method is called to finalise all the data 
from the steps we completed. This method can be found on *MultiForm*. However, 
we cannot automatically save each step, because we don't know where to save it. 
So, we must write some code on our subclass of *MultiForm*, overloading 
`finish()` to tell it what to do at the end.

Here is an example of what we could do here:

	:::php
	<?php
	 
	class SurveyForm extends MultiForm {
	 
	   public static $start_step = 'SurveyFormPersonalDetailsStep';
	 
	   public function finish($data, $form) {
	      parent::finish($data, $form);

	      $steps = DataObject::get(
	      	'MultiFormStep', 
	      	"SessionID = {$this->session->ID}"
	      );
	      
	      if($steps) {
	         foreach($steps as $step) {
	            if($step->class == 'SurveyFormPersonalDetailsStep') {
	               $member = new Member();
	               $data = $step->loadData();

	               if($data) {
	                  $member->update($data);
	                  $member->write();
	               }
	            }
	
	            if($step->class == 'SurveyOrganisationDetailsStep') {
	               $organisation = new Organisation();
	               $data = $step->loadData();

	               if($data) {
	                  $organisation->update($data);

	                  if($member && $member->ID) {
	                  	$organisation->MemberID = $member->ID;
	                  }

	                  $organisation->write();
	               }
	            }
				// Shows the step data (unserialized by loadData)
	            // Debug::show($step->loadData());
	         }
	      }

	      $this->controller->redirect($controller->Link() . 'finished');
	   }
	}


#### 9. Organisation data model

The class Organisation is mentioned in the above example but doesn't exist at 
the moment (unlike the existing Member() class which looks after the member 
groups in SilverStripe) so we need to create it:

This example has been chosen as a separate DataObject but you may wish to change
the code and add the data to the Member class instead.

	:::php
	<?php
	 
	class Organisation extends DataObject {
		
		private static $db = array(
			// Add your Organisation fields here
		);
	 
	}

#### Warning

If you're dealing with sensitive data, it's best to delete the session and step 
data immediately after the form is successfully submitted.

You can delete it by calling this method on the finish() for your MultiForm 
subclass:

	:::php
	$this->session->delete();


This will also go through each of it's steps and delete them as well.

## Customising

Because the multi-form system doesn't magically do everything for you, although 
it does provide a sensible set of defaults, it means you need to customise 
certain aspects of it. Here are some useful methods that can be customised 
(although you can technically overload anything available on MultiForm and 
MultiFormStep!):

### Templates

The best part about this system is the freedom to customise each form step 
template.

In order, when you have a page with a multi-form rendering into it, it chooses 
which template to render that form in this order, within the context of the 
MultiForm class:

*  $this->getCurrentStep()->class (the current step class)
*  MultiFormStep
*  $this->class (your subclass of MultiForm)
*  MultiForm
*  Form

More than likely, you'll want the first one to be available when the form 
renders. To that effect, you can start placing templates in the 
*templates/Includes* directory for your project. You need to call them the same 
as the class name for each step. For example, if you want *MembershipForm*, a 
subclass of *MultiFormStep* to have it's own template, you would put 
*MembershipForm.ss* into that directory, and run *?flush=1*.

If you'd like a pre-existing template on how to customise the form step, have a 
look at Form.ss that's found within the sapphire module. Use that template, as 
a base for your new MembershipForm.ss template in your project templates.

For more information on this, please [look at the Form documentation](http://doc.silverstripe.org/framework/en/topics/forms#custom-form-templates).

### getNextStep()

If you are wanting to override the next step (so, if you want the next step to 
be something different based on a user's choice of input during the step, you 
can override getNextStep() on any given step to manually override what the next 
step should be. An example:

	:::php
	class MyStep extends MultiFormStep
	
	// ...
	
	   public function getNextStep() {
	      $data = $this->loadData();
	      if(@$data['Gender'] == 'Male') {
	         return 'TestThirdCase1Step';
	      } else {
	         return 'TestThirdCase2Step';
	      }
	   }
	
	// ...
	
	}

### Validation

To define validation on a step-by-step basis, please define getValidator() and 
return a Validator object, such as RequiredFields - for more information on form 
validation see [:form](http://doc.silverstripe.org/form-validation).

e.g.

	:::php
	class MyStep extends MultiFormStep {
	
	   ...
	
	   public function getValidator() {
	      return new RequiredFields(array(
	         'Name',
	         'Email'
	      ));
	   }
	
	   ...
	
	}


### finish()

`finish()` is the final call in the process. At this step, all the form data 
would most likely be unserialized, and saved to the database in whatever way the 
developer sees fit. By default, we have a `finish()` method on *MultiForm* which 
serializes the last step form data into the database, and that's it.

`finish()` should be overloaded onto your subclass of *MultiForm*, and 
`parent::finish()` should be called first, otherwise the last step form data 
won't be saved.

For example:

	:::php
	<?php
	
	class SurveyForm extends MultiForm {
	
	   public static $start_step = 'SurveyFormPersonalDetailsStep';
	
	   public function finish($data, $form) {
	      parent::finish($data, $form);

	      $steps =	MultiFormStep::get()->filter(array(
	      	"SessionID" => $this->session->ID
	      ));

	      if($steps) {
	         foreach($steps as $step) {
	         	// Shows the step data (unserialized by loadData)
	            Debug::show($step->loadData()); 
	         }
	      }
	   }
	}


The above is a sample bit of code that simply fetches all the steps in the 
database that were saved. Further refinement could include getting steps only 
if the Data (serialized raw form data) is set, as the above example doesn't 
respect branching of steps (multiple next steps on a given form step).

## Best practices

### Delete session after submission

If you're dealing with sensitive data, such as credit card fields, or personal 
fields that shouldn't be lying around in the session database, then it's a good 
idea to immediately delete this data after the user has submitted.

This can be easily achieved by adding the following line at the end of your 
`finish()` method on your MultiForm subclass.

	:::php
	$this->session->delete();


### Expiring old session data

Included with the MultiForm module is a class called *MultiFormPurgeTask*. This 
task can be used to purge expired session data on a regular basis. The date of 
expiry can be customised, and is given a default of 7 days to delete sessions 
after their creation.

You can run the task from the URL, by using http://mysite.com/dev/tasks/MultiFormPurgeTask?flush=1

MultiFormPurgeTask is a subclass of *BuildTask*, so can be , so can be run using the [SilverStripe CLI tools](http://doc.silverstripe.org/framework/en/topics/commandline).

One way of automatically running this on a UNIX based machine is by cron.

## TODO

*  Code example on how to use `$form->saveInto()` with MultiForm, as it doesn't have all steps in the $form context at `finish()`

*  Allowing a user to click a link, and have an email sent to them with the current state, so they can come back and use the form exactly where they left off

*  Possibly allow for different means to persist data, such as the browser session cache instead of the database.

*  Different presentation of the URL to identify each step.

*  Allow customisation of `prev()` and `next()` on each step. Currently you can only customise for the entire MultiForm subclass. There is a way to customise on a per step basis, which could be described in a small recipe.

*  More detailed explanation, and recipe example on how to make branched multistep forms. For example, clicking a different action takes you to an alternative next step than the one defined in `$next_steps`


## Related

*  [Form](/form)
     * [Form field types](http://doc.silverstripe.org/form-field-types)

*  [Tutorials](/tutorials)
     * [Tutorial 3 - Forms](http://doc.silverstripe.org/framework/en/tutorials/3-forms)

*  [Templates](http://doc.silverstripe.org/framework/en/reference/templates)
