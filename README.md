PHP Template Mailer
===================

A simple php template based mass mailer class. Supports sendmail, callback, and database queuing.

Features
--------

- Simple interface.
- Ability to create your own mail templates for the subject and message body.
- Ability to define your own variables in the templates to be repalced when sending each message.

Requirements
------------

- PHP 5.3 and above.

Starting the Mailer
-------------------

The TemplateMailer constructor requires only one parameter, the mailer type to use.

- ```php```: send generated messages to PHP's mail() function
- ```callback```: send generated messages to a user provided callback function
- ```queue```: send generated messages to a SQL database for queued sending later
- ```test```: send messages to a log file, do not send

Configurable Options
--------------------

The following options can be set with the ```config($array) ``` function. Options should be passed to ```config()``` as an array with format ```$config = array("key" => "value")```.

- ```log```: string, optional, log file to use, logged to PHP default otherwise, full path required
- ```subject```: string, required, the subject template to use for all messages
- ```message```: string, required, the message template to use for all messages
- ```headers```: string, optional, formatted headers to include with each message
- ```from```: string, required, email address all emails should be sent "from"
- ```reply_to```: string, optional, email address to appear in reply-to field
- ```cc```: string or array, optional, address(es) to be CC'd on all messages
- ```bcc```: string or array, optional, address(es) to be BCC'd on all messages
- ```hostname```: string, required for queue, hostname of SQL server
- ```username```: string, required for queue, username for SQL server
- ```password```: string, required for queue, password for SQL server
- ```database```: string, required for queue, database to use for SQL server
- ```params```: string, optional, extra parameters to pass to sendmail
- ```callback```: callback, required for callback sending, user callback function

Template Format
---------------

The subject and message submited to ```config()``` are templates. As such, you can define varibles to be replaced when each message is sent. Each template should be sent as a string with varibales formatted as ```{variablename}```. When you can send, you will provide an array of variable values that will replace those bracketed entries. Here's an example.

```php
$mailer = new TemplateMailer("test");
$config = array(
  "message" => "Hello {name},\r\n\r\nThanks for your recent {item} purchase.";
);
$mailer->config($config);
$fields = array(
  "name" => "Tim",
  "item" => array("%s","clothing")
);
$mailer->send("test@test.test",$fields);
```
In the example above, the variable "name" is replaced with the string "Tim" and the variable "item" is replaced by the string "clothing". Note that you can also pass a sprintf compatible formatting string with the replacement text, as was done in the example above for the variable item.

You can create as many variable as you want with whatever names you want, with the exception of "to", which is a reserved word.

Accepted Email Formats
----------------------

The ```$to```, ```$cc```, and ```$bcc``` fields can accept email addresses in a multitude of formats. The same rules apply to the 'cc' and 'bcc' options set with ```config()```.

- ```$email = "test@test.test"```
- ```$email = "test@test.test, anne@test.test"```
- ```$email = "test <test@test.test>, anne <ann@test.test>```
- ```$email = array("test@test.test","anne@test.test")```
- ```$email = array("test"=>"test@test.test","anne"=>"anne@test.test")```

Providing a Callback Function
-----------------------------

The ```'callback'``` config option will be called when the mailer type is set to 'callback'.

Function names must be formatted according to the PHP callable type specifications (http://php.net/manual/en/language.types.callable.php). Typically, callback functions will be formatted in one of the following ways.

- Static Function: ```$callback = "myfunctionname"```
- Static Class Method: ```$callback = array("myclass","method_name_in_myclass")```
- Static Class Method: ```$callback = "myclass::method_name_in_myclass"```
- Object Method: ```$callback = array($myclass,"method_name_in_myclass")```

The provided callback must have four parameters - ```$to```, ```$subject```, ```$headers```, and ```$body```, in that order.

Sending a Message
-----------------

Once the templates are defined and the configuration details have been set, you're ready to send messages. This is done by calling the ```send()``` function.

```
$mailer->send($to, $fields = NULL, $cc = NULL, $bcc = NULL, $reply_to = NULL);
```

The only required parameter is ```$to```, although if you don't provide ```$fields```, you won't be able to replace variables included in your subject or message templates.

In addition to the default CC, BCC, and Reply-To that were set with ```config()```, you can override those values here for one message.

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