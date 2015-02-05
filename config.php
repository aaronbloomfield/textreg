<?php

//------------------------------------------------------------
// You definitely have to edit these values

// the password to access the admin page
$password = "password";

// the email address to contact when something goes wrong
$retemail = "name@example.com";

// the *short* name (or abbreviation) of the organization
$orgname = "Org Name";

// an introductory paragraph to be shown on the main page; it should
// have HTML tags, and be enclosed in <p> and </p>
$introparagraph = "<p>If you would like to receive <i>occassional</i> text messages from $orgname, please fill out the information below.  These messages are limited to [specify type of messages that will be sent].  We do/do not intend to see your number.</p><p>If your cell phone provider is not listed, please <a href='mailto:$retemail'>let us know</a>.  Likewise, if you would like to be removed, please send us an email as well.</p>";

//------------------------------------------------------------
// You *might* have to edit these values

// the logo file name; make sure it's in the same directory
$logoimage = "logo.png";

// any additional FAQ questions and answers, such as the fact that you
// will not sell their number.  This should be in HTML format.  The
// question should be enclosed in <h3> tags, and the answer in <p>
// tags
$faqs = "<h3>FAQ Question Set in config.php</h3>
<p>Edit config.php to update or remove this FAQ question and answer.</p>
";

//------------------------------------------------------------
// You likely will *not* have to edit these values

// the database name
$sqldbname = "numbers.db";

// the log file name
$logfile = "textreg.log";

// the list of providers
$providers = array ('att'     => array('name'=>'AT&T',     'email'=>'txt.att.net'),
		    'verizon' => array('name'=>'Verizon',  'email'=>'vtext.com'),
		    'tmobile' => array('name'=>'T-Mobile', 'email'=>'tmomail.net'),
		    'sprint'  => array('name'=>'Sprint',   'email'=>'messaging.sprintpcs.com'),
		    );

// if you are developing, then these are the hostnames from which it
// will *not* send an email message
$debughosts = array ('localhost');

?>
