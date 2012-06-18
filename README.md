PHP Template Mailer
===================

A simple php template based mass mailer class. Supports sendmail, smtp, and database queuing.

Features
--------

- Simple interface.
- Ability to create your own mail templates for the subject and message body.
- Ability to define your own variables in the templates to be repalced when sending each message.

Requirements
------------

Example Usage
-------------

````
<?php

require_once("TemplateMailer.class.php");
$mailer = new TemplateMailer("php");
$options = array(
  "from" => "no-reply@foo.com",
  "subject" => "A message for {to} from foo.com",
  "message" => "Hello {to},\r\nThanks for your recent order! Your order number is {order_num}.";
);
$mailer->setOptions($options);

$recipients = array(
  "andrew@foo.com",
  "burt@foo.com",
  "curtis@foo.com",
  "drew@foo.com"
);

foreach ( $recipents as $to )
{
  $fields = array(
    "order_num" => array("%s","123456789")
  )
  $mailer->send($to,$fields);
}

?>
````

Interface
---------

TemplateMailer(string $mailer)

- $mailer is a string that sets how email messages should be sent.
- 'php': send using the default PHP mail() function.
- 'smtp': send using the PHP mail() function with SMTP server settings forced.
- 'queue': send messages to a database for queue based sending.
- 'test': do not send message, write to log file instead

setOptions(array $options)

- $options is an array in the format "option" => "value".
- Allows setting of all class options.

send(string $to, array $fields)

- Sends an individual message to a single recipent.
- $to is a string containing a single email address.
- $fields is an array that contains all fields in the template subject and message that should be replaced.
- $fields can be given in several formats:
- $fields = array("field" => "value")
- $fields = array("field" => array("sprintf_format_string","value")

Template Format
---------------

The subject, message, and headers are defined as templates. Their values should be formatted strings set via the setOptions function.

Each template can contain any number of fields, which are replaced automatically when messages are sent. Fields should appear in a template string as follows.

````
$message = "This is the message I would like to send to {field_name_1}.\r\nThanks for your order of {field_name_2}";
````

These fields will be replaced by whichever values are passed to the send($to,$fields) function. 

In addition to any user defined fields, the following fields will automatically be replaced:
- to
- from
- hostname

Available Options
-----------------

The following options are available to be set. All values should be given as strings.

- to
- from
- subject
- message
- headers
- hostname (for SMTP or queue)
- username (for SMTP or queue)
- password (for SMTP or queue)
- port (for SMTP)
- params (for php)
- database (for queue)
- table (for queue)
- db_col_to (COMING SOON)
- db_col_from (COMING SOON)
- db_col_subject (COMING SOON)
- db_col_message (COMING SOON)
- db_col_headers (COMING SOON)
- cc (COMING SOON)
- bcc (COMING SOON)
- log

Legal
-----

Copyright (c) 2011, Matt Colf

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.