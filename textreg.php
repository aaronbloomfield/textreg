<?php

/**
 * see readme.md for usage
 * see install.md for installation instructions
 * see config.php for configuration options
 */

// is sqlite3 installed?
if ( !extension_loaded("sqlite3") )
  die("You must install the PHP sqlite3 extension; see install.md for details");

// load the config file
if ( !file_exists('config.php') )
  die("No config.php file present");
require_once("config.php");

// check that they changed the password
if ( ($password == "password") || ($password == "") )
  die("You must set the password in config.php");

// handle viewing of a CSV prior to outputting the start of the HTML page
if ( isset($_GET['viewcsv']) && hasValidPassword() )
  viewCSV();

// all non-CSV pages show the top of the web page
printTopOfWebPage();

// view the FAQ?
if ( isset($_GET['faq']) )
  viewFAQ();

// view the log file
if ( isset($_GET['viewlog']) && hasValidPassword() )
  viewLogFile();

// toggle enabled/disabled status
if ( isset($_GET['toggle']) && hasValidPassword() )
  toggleEnabled();

// view the admin page
if ( isset($_GET['admin']) && hasValidPassword() )
  viewAdminPage();

// all other (normal) views
if ( count($_POST) == 0 )
  viewFormPage();

if ( count($_POST) != 0 )
  handleFormPosting();

exit();

//------------------------------------------------------------

function viewAdminPage() {
  logEvent("DB view");
  printDB();
  printEndOfWebPage();
  exit();
}

//------------------------------------------------------------

function viewFormPage() {
  if ( isset($_GET['password']) )
    logEvent("page view (password attempt: " . $_GET['password'] . ")");
  else
    logEvent("page view");
  printFormBody();
  printEndOfWebPage();
  exit();
}

//------------------------------------------------------------

function viewLogFile() {
  global $logfile;
  logEvent("log view");
  echo "<p><a href='textreg.php?admin&amp;password=" . $_GET['password'] . "'>Return to the admin page</a></p>";
  echo "<pre>" . file_get_contents($logfile) . "</pre>";
  printEndOfWebPage();
  exit();
}  

//------------------------------------------------------------

function toggleEnabled() {
  $id = preg_replace("/[^0-9]/", "", $_GET['toggle']);
  logEvent("toggle id $id");
  $db = openDB();
  $query = "update numbers set valid=not valid where id=$id";
  $db->exec($query) or mydie("Error updating the database");
  echo "<fieldset><legend><span>Message</span></legend>Entry with ID $id had its enabled/disabled status toggled</fieldset>";
  $_GET['admin'] = true; // view the admin page; don't exit
}  

//------------------------------------------------------------

function printTopOfWebPage() {
  global $logoimage, $orgname;
  echo <<<EOT
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>$orgname text message registration form</title>
    <link rel="stylesheet" type="text/css" href="view.css" media="all">
    <script type="text/javascript" src="view.js"></script>
  </head>
  <body id="main_body">
    <img id="top" src="top.png" alt="">
    <div id="form_container">
      <h1><a>Untitled Form</a></h1>
EOT;
  if ( file_exists($logoimage) )
    echo "<img src='$logoimage'>\n";
}

//------------------------------------------------------------

function hasValidPassword() {
  global $password;
  return isset($_GET['password']) &&
    ($_GET['password'] == $password);
}

//------------------------------------------------------------

function formatNumber($num) {
  return '(' . substr($num,0,3) . ') ' . substr($num,3,3) . '-' . substr($num,6,4);
}

//------------------------------------------------------------

function viewCSV() {
  global $providers;
  logEvent("CSV view");
  $db = openDB();
  $query = "select * from numbers order by id";
  $result = $db->query($query) or mydie("Error searching the database.");
  header("Content-type: text/csv");
  header("Content-Disposition: attachment; filename=isctextreg.csv");
  echo "ID,Name,Mobile number,Provider,Email,Date entered,Enabled\n";
  while ( $row = $result->fetchArray() )
    echo $row['id'] . ',' . $row['name'] . ',' . formatNumber($row['number']) . ',' . 
      $providers[$row['provider']]['name'] . ',' . $row['number'] . '@' . 
      $providers[$row['provider']]['email'] . ',' .
      $row['thedate'] . ',' . $row['valid'] . "\n";
  exit();
}  

//------------------------------------------------------------

function handleFormPosting() {
  global $providers, $sqldbname, $retemail, $orgname, $debughosts;

  // error check
  $errors = array();
  if ( !isset($_POST['name']) || ($_POST['name'] == "") )
    $errors[] = "Your name must be provided";
  if ( strlen($_POST['name']) > 250 )
    $errors[] = "Please provide a name that is less than 250 characters";
  if ( !isset($_POST['phone']) || ($_POST['phone'] == "") )
    $errors[] = "Your mobile phone number must be provided";
  if ( !isset($_POST['provider']) || ($_POST['provider'] == "") )
    $errors[] = "Your provider name must be provided";
  if ( isset($_POST['provider']) && (!in_array($_POST['provider'],array_keys($providers))) )
    $errors[] = "Your must select a mobile phone provider";
  $number = preg_replace("/[^0-9]/", "", $_POST['phone']);
  if ( strlen($number) != 10 )
    $errors[] = "You must supply a 10 digit cell phone number ($number)";    
  if ( count($errors) > 0 ) {
    echo "<div class='appnitro'><div class='form_description'><h2>Text message registration error</h2><p>&nbsp;</p><p>There were errors in submitting the form.  Please hit back, correct them, and try again.  The errors were:</p><ul><li>" . implode("</li><li>",$errors) . "</ul></div></div>";
    mydie("Invalid form values",false);
  }

  $db = openDB();

  // is the number in the DB?  if so, print error and exit
  $query = "select * from numbers where number='$number'";
  $result = $db->query($query) or mydie("Error searching the database");
  while ( $row = $result->fetchArray() )
    // if the number is not in the db, then this will never happen, as there will be zero results
    mydie("That number is already in the database");

  // enter number into DB
  $query = "insert into numbers values (null, '" . mysql_escape_string($_POST['name']) . 
    "','" . $number . "','" . mysql_escape_string($_POST['provider']) . "',1,'" . 
    date("Y-m-d H:i:s") . "')";
  $ok = $db->exec($query);
  if ( !$ok ) mydie("Error inserting into the database");
  logEvent("registered number: $query");

  // send test email
  $address = $number . '@' . $providers[$_POST['provider']]['email'];
  if ( !in_array(trim(`hostname`),$debughosts) ) {
    mail($address,"$orgname test text","You will now receive text messages from $orgname on your mobile phone number","From: $retemail");
    logEvent("sent test email to $address");
  }

  // print confirmation
  echo <<<EOT
<div class='appnitro'><div class='form_description'>
<h2>$orgname text message registration: success!</h2>
    <p>&nbsp;</p>
    <p>You should shortly receive a test text message on your phone.  If you do, then your number is enabled to receive text messages from $orgname.  If you do <b>NOT</b>, then something went wrong -- please <a href="mailto:$retemail">contact us</a>.  If you would like it to be removed, please email us as well.</p>
    <p>&nbsp;</p>
<p><a href="textreg.php">Return to the main page</a>.</p>
</div></div>
EOT;
  
  printEndOfWebPage();
  if ( $db )
    $db->close();
}

//------------------------------------------------------------

$db = false;
function openDB() {
  global $db, $sqldbname;
  if ( !$db ) {
    try {
      $db = new SQLite3($sqldbname);
    } catch (Exception $e) {
      mydie("Error opening the database");
    }
  }
  return $db;
}

//------------------------------------------------------------

function printDB() {
  global $providers;
  $db = openDB();
  $query = "select * from numbers order by id";
  $result = $db->query($query) or mydie("Error searching the database.");
  $addresses = array();
  $enabled = array();
  $disabled = array();
  while ( $row = $result->fetchArray() ) {
    $address = $row['number'] . '@' . $providers[$row['provider']]['email'];
    if ( $row['valid'] )
      $addresses[] = $address;
    $tr = "<tr><td>" . $row['name'] . "</td><td><a href='mailto:" . 
      $row['number'] . '@' . $providers[$row['provider']]['email'] . "'>" . 
      formatNumber($row['number']) . "</a></td><td>" . 
      htmlentities($providers[$row['provider']]['name']) . "</td><td>" . 
      $row['thedate'] . "</td><td><a href=\"textreg.php?toggle=" . 
      $row['id'] . "&amp;password=" . $_GET['password'] . "\">";
    if ( $row['valid'] )
      $enabled[] = $tr . "disable</a></tr>\n";
    else
      $disabled[] = $tr . "enable</a></tr>\n";
  }

  $th = "<tr><td><b>Name</b></td>" .
    "<td><b>Mobile number / email</b></td><td><b>Provider</b></td>" . 
    "<td><b>Date entered</b></td><td><b>Enable / disable</b></td></tr>";
  $password = $_GET['password'];
  $addresses = implode(",",$addresses);
  $tableenabled = $th . implode("",$enabled);
  $tabledisabled = $th . implode("",$disabled);

  echo <<<EOT
<div class='appnitro'><div class='form_description'>
<h2>Actions:</h2>
<ul><li><a href='textreg.php'>Return to the main page</a></li>
<li><a href='textreg.php?viewlog&amp;password=$password'>View the log file</a></li>
<li><a href='textreg.php?viewcsv&amp;password=$password'>Download a CSV file of the entire database</a></li>
</ul><p>&nbsp;</p>
<h2>All enabled email addresses in a comma-separated format</h2>
<p>You can cut-and-paste the next line into your email client</p>
<p>$addresses</p>
<p>&nbsp;</p><h2>All enabled email addresses in a table format</h2>
<table border='1'>$tableenabled</table>
<p>&nbsp;</p><h2>All disabled email addresses in a table format</h2>
<table border='1'>$tabledisabled</table>
<p>&nbsp;</p><p><a href='textreg.php'>Return to the main page</a></p>
</div></div>
EOT;
}


//------------------------------------------------------------

function logEvent($msg) {
  global $logfile;
  $fp = fopen($logfile,"a");
  fprintf ($fp, "%s from %s: $msg\n", date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR']);
  fclose($fp);
}

//------------------------------------------------------------

function mydie($s, $print = true) {
  global $retemail, $orgname;
  if ( $print )
    echo "<div class='appnitro'><div class='form_description'><h2>ERROR</h2><p>&nbsp;</p><p>" . $s . "; please <a href='mailto:$retemail'>contact $orgname</a> for help.</p><p>&nbsp;</p></div></div>";
  printEndOfWebPage();
  logEvent("ERROR: $s");
  $db = openDB();
  if ( $db )
    $db->close();
  exit();
}

//------------------------------------------------------------

function printFormBody() {
  global $retemail, $providers, $orgname, $introparagraph;
  echo <<<EOT
    <form id="form_962513" class="appnitro"  method="post" action="textreg.php">
        <div class="form_description">
    <h2>$orgname text message registration</h2><p>&nbsp;</p>
          $introparagraph
<p>A test text message will be sent to your phone when you submit this form.</p>
<p>If you have more questions, or want to know how this all works, see <a href="textreg.php?faq">here</a>.</p>
        </div>
        <ul>
          <li id="li_1">
            <label class="description" for="name">Your name </label>
            <div>
              <input id="name" name="name" class="element text medium" type="text" maxlength="255" value=""/> 
            </div>
            <p class="guidelines" id="guide_1"><small>This is so we know who the number is for</small></p>
          </li>
          <li id="li_3">
            <label class="description" for="phone">10 digit phone number </label>
            <div>
              <input id="phone" name="phone" class="element text medium" type="text" maxlength="255" value=""/> 
            </div>
            <p class="guidelines" id="guide_2"><small>Area code and seven digit number</small></p>
          </li>
          <li id="li_4">
            <label class="description" for="provider">Cell service provider</label>
            <div>
              <select class="element select medium" id="provider" name="provider">
                <option value="choose" selected="selected">-- choose one ---</option>
EOT;
  foreach ( array_keys($providers) as $provider )
    echo "<option value='" . $provider . "'>" . 
    htmlentities($providers[$provider]['name']) . "</option>\n";
  echo <<<EOT
              </select>
            </div>
            <p class="guidelines" id="guide_4"><small>Pick one; if your provider is not listed, please let us know, and we will see if it can be added</small></p>
          </li>
          <li class="buttons">
            <input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
          </li>
        </ul>
      </form>
EOT;
}

//------------------------------------------------------------

function printEndOfWebPage() {
  echo <<<EOT
      <div id="footer">
        Generated by <a href="http://www.phpform.org">pForm</a>
      </div>
    </div>
    <img id="bottom" src="bottom.png" alt="">
</body>
</html>
EOT;
}

//------------------------------------------------------------

function viewFAQ() {
  global $faqs, $providers, $retemail;
  logEvent("FAQ view");
  echo "<div class='appnitro'><div class='form_description'>\n";
  echo "<h2>Frequently Asked Questions</h2><p>&nbsp;</p><p><a href='textreg.php'>Return to the main page</a></p><p>&nbsp;</p>\n";
  echo $faqs;
  echo <<<EOT
<h3>How do I remove myself?</h3>
<p>Please <a href="mailto:$retemail">send an email</a> requesting to be removed.</p>

<h3>Why don't you allow removal by text message?</h3>
<p>Because that service would cost money, and this is meant to be a service that does not cost anything.</p>

<h3>How does this all work?</h3>
<p>All the major mobile service providers have a email-to-text service.  If you send an e-mail to a specific email address, it is delivered to your phone as a text message.  For example, if you have AT&amp;T, and if your cell phone number is (123) 456-7890, then if you email 1234567890@txt.att.net, it will be delivered to your mobile phone as a text message.</p>

<h3>What providers can this script use?</h3>
<p>The following providers are configured, and a sample email address is shown for each.</p><ul>
EOT;
  foreach ( array_keys($providers) as $provider )
    echo "<li>" . htmlentities($providers[$provider]['name']) . ": 1234567890@" . 
         $providers[$provider]['email'] . "</li>\n";
  echo "</ul>";

  echo <<<EOT

<h3>Doesn't this allow spam texts?</h3>
<p>Not really.  Anybody can email to a mobile phone's text message address already; this doesn't enable that functionality, it only uses it.  It is still the case, in the US at least, that commercial messages to cell phones are illegal.</p>

<h3>Can I add a mobile service provider?</h3>
<p>As long as the mobile provider has this email-to-text functionality, you certainly can.  One option is to directly edit config.php (the <code>providers</code> array).  Alternatively, you can submit the provider's information to the maintainer of this source code.  You can do this by submitting an <a href="
https://github.com/aaronbloomfield/textreg/issues">issue to the github repo</a> -- just fill out the form, and include as much information about the provider as you can.  If you know how to do so, you can also submit a <a href="https://help.github.com/articles/using-pull-requests/">pull request</a> to the <a href="https://github.com/aaronbloomfield/textreg">github repo</a>.</p>

<h3>Who wrote this script?</h3>
<p><a href="http://www.cs.virginia.edu/~asb">Aaron Bloomfield</a>.  The form design was by <a href="http://www.phpform.org">pForm</a>.</p>

<h3>Can I use it for my organization?</h3>
<p>Sure -- it's freely available at <a href="https://github.com/aaronbloomfield/textreg">https://github.com/aaronbloomfield/textreg</a>.  You can view the readme on that page, with installation instructions.</p>

<p>&nbsp;</p><p><a href="textreg.php">Return to the main page</a>.</p>
</div></div>
EOT;
  printEndOfWebPage();
  exit();
}


//------------------------------------------------------------

?>
