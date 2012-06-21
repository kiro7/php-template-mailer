<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once("TemplateMailer.php");

//// TEST SEND

$mailer = new TemplateMailer("test");

// configure defaults
$config = array(
  "log" => "/var/www/share/mailer.log",
	"from" => "no-reply@foo.com",
	"subject" => "A message from Foo concerning {concern}",
	"reply_to" => "help@foo.com",
	"message" => "Dear {name},\r\n\r\nThis is a message from Foo concerning {concern}.\r\n\r\nBest Wishes,\r\nFoo"
);
$mailer->config($config);

// simple message
$fields = array(
	"name" => "Don",
	"concern" => array("%s","your recent horse purchase")
);
$mailer->send("don@test.test",$fields);

// simple CC
$fields = array(
	"name" => "Andrew",
	"concern" => array("%s","your recent shoe purchase")
);
$mailer->send("andrew@test.test",$fields,"jimmy@test.test");

// simple BCC
$fields = array(
	"name" => "Ben",
	"concern" => array("%s","your recent apparel purchase")
);
$mailer->send("ben@test.test",$fields,NULL,"bob@test.test");

// array based CC with some names
$fields = array(
	"name" => "Cindy",
	"concern" => array("%s","your recent vehicle purchase")
);
$cc = array(
	"abe@test.test",
	"bob" => "bob@test.test",
	"sara" => "sara@test.test",
	"rob@test.test"
);
$mailer->send("cindy@test.test",$fields,$cc);

// array based TO with some names
$fields = array(
	"name" => "Don",
	"concern" => array("%s","your recent horse purchase")
);
$to = array(
	"link" => "link@test.test",
	"zelda@test.test",
	"Mario" => "mario@test.test"
);
$mailer->send($to,$fields);

//// CALLBACK SEND

unset($mailer);
$mailer = new TemplateMailer("callback");

// configure defaults
$config = array(
	"callback" => "mycallback",
	"log" => "/var/www/share/mailer.log",
	"from" => "no-reply@foo.com",
	"subject" => "A message from Foo concerning {concern}",
	"reply_to" => "help@foo.com",
	"message" => "Dear {name},\r\n\r\nThis is a message from Foo concerning {concern}.\r\n\r\nBest Wishes,\r\nFoo"
);
$mailer->config($config);

// simple message
$fields = array(
	"name" => "Don",
	"concern" => array("%s","your recent horse purchase")
);
$mailer->send("mcolf@nabancard.com",$fields);

function mycallback($to,$subject,$headers,$body)
{
	echo "User Callback Called.\r\n";
}

?>