<?php

/**
*	PHP TEMPLATE MAILER
*
*	@version 1.0
*	@author Matthew Colf <mattcolf@mattcolf.com>
*
*	@section LICENSE	
*
*	Copyright 2012 Matthew Colf <mattcolf@mattcolf.com>
*
*	Licensed under the Apache License, Version 2.0 (the "License");
*	you may not use this file except in compliance with the License.
*	You may obtain a copy of the License at
*
*	http://www.apache.org/licenses/LICENSE-2.0
*
*	Unless required by applicable law or agreed to in writing, software
*	distributed under the License is distributed on an "AS IS" BASIS,
*	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*	See the License for the specific language governing permissions and
*	limitations under the License.
*/

class TemplateMailer {

	private $mailer;
	private $fields;
	private $log;
	
	private $from;
	private $reply_to;
	private $cc;
	private $bcc;
	private $subject;
	private $message;
	private $headers;

	private $hostname;
	private $username;
	private $password;
	private $database;
	private $table;
	private $params;
	private $callback;
	
	private $db_object;
	private $db_statement;

	public function TemplateMailer( $mailer = "php" )
	{
		// check php version
		if ( version_compare(PHP_VERSION,"5.3.0") < 0 )
		{
			throw new Exception("Minimum PHP version 5.3.0 required.");
		}

		// check mailer
		switch ( $mailer )
		{
			case "php":
			case "queue":
			case "test":
			case "callback":
				$this->mailer = $mailer;
				break;
			default:
				throw new Exception("Unsupported mailer type.");
		}

		// set defaults
		$this->db_obj = NULL;

	}
	
	// set mailer options
	public function config( $config = array() )
	{
		foreach ( $config as $key => $value )
		{
			switch ($key)
			{
				case "log":
				case "subject":
				case "message":
				case "headers":
				case "from":
				case "reply_to":
				case "cc":
				case "bcc":
				case "hostname":
				case "username":
				case "password":
				case "database":
				case "params":
				case "callback":
					$this->{$key} = $value;
					break;
				default:
					return FALSE;
			}
		}
		return TRUE;
	}

	// send message
	public function send($to, $fields = NULL, $cc = NULL, $bcc = NULL, $reply_to = NULL)
	{
		try 
		{
			// re-use previous fields if not provided
			if ( $fields != NULL ) $this->fields = $fields;
					
			$to 			= $this->generate_email_list($to);
			$subject 		= $this->prepare($this->subject);
			$body 			= $this->prepare($this->message);
			$headers		= $this->generate_headers($cc,$bcc,$reply_to);
			$this->preflight();
			
			switch ( $this->mailer )
			{
				case "php":
					return $this->send_php($to,$subject,$headers,$body);
				case "queue":
					return $this->send_queue($to,$subject,$headers,$body);
				case "callback":
					return $this->send_callback($to,$subject,$headers,$body);
				case "test":
					return $this->send_test($to,$subject,$headers,$body);
			}
		}
		catch ( Exception $e )
		{
			$this->handle_exception("Unable to send message.",$e);
			return FALSE;
		}
	}

	private function generate_headers($cc = NULL, $bcc = NULL, $reply_to = NULL)
	{
		$headers = "X-Mailer: php\r\n";

		// from
		$headers .= $this->generate_header_line("From: ",$this->from,NULL);
		// cc
		$headers .= $this->generate_header_line("cc: ",$this->cc,$cc);
		// bcc
		$headers .= $this->generate_header_line("bcc: ",$this->bcc,$bcc);
		// reply-to
		$headers .= $this->generate_header_line("Reply-To: ",$this->reply_to,$reply_to);
		// append user headers
		$headers .= $this->headers;

		return $headers;
	}

	private function generate_header_line($header,$default,$provided)
	{	
		// choose default or provided values
		if ( $provided != NULL ) $data = $provided;
		else if ( $default != NULL ) $data = $default;
		else return "";

		return $header . $this->generate_email_list($data) . "\r\n";

	}

	private function generate_email_list($email)
	{
		if ( is_array($email) )
		{
			$list = "";
			foreach ( $email as $name => $address )
			{
				if ( is_string($name) AND is_string($address) ) $list .= "'$name' <$address>, ";
				else $list .= "$address, ";
			}
			return rtrim($list," ,");
		}
		else return $email;
	}

	// fill and format template for sending
	// use 1: $field = array("keyword" => "value")
	// use 2: $field = array("keyword" => array("format" => "%s","value" => "orange") )
	private function prepare($template)
	{
		// replace user fields
		foreach ( $this->fields as $keyword => $value )
		{
			$format = "%s";
			if ( is_array($value) )
			{
				$format = $value[0];
				$value = $value[1];
			}
			$template = $this->template_replace($keyword,$value,$format,$template);
		}
	
		return $template;
	}

	// replace all instances of {keyword} in template with formatted value
	private function template_replace($keyword,$value,$format,$template)
	{
		$keyword = "{".$keyword."}";
		$value = sprintf($format,$value);
		return str_replace($keyword,$value,$template);
	}

	// preflight check
	private function preflight()
	{
		// global requirements
		if ( !isset($this->from) ) throw new Exception("From address is not set.");
		if ( !isset($this->subject) ) throw new Exception("Subject is not set.");
		if ( !isset($this->message) ) throw new Exception("Message is not set.");
	
		// queue specific requirements
		if ( $this->mailer == "queue" )
		{
			if ( !isset($this->hostname) ) throw new Exception("Queue hostname is not set.");
			if ( !isset($this->username) ) throw new Exception("Queue username is not set.");
			if ( !isset($this->password) ) throw new Exception("Queue password is not set.");
			if ( !isset($this->database) ) throw new Exception("Queue database is not set.");
		}

		// callback sepcific requirements
		if ( $this->mailer == "callback" )
		{
			if ( !isset($this->callback) ) throw new Exception("Callback function has not been set.");
			if ( !is_callable($this->callback) ) throw new Exception("Callback function is not callable.");
		}

	}

	// send with php mail() function
	private function send_php($to,$subject,$headers,$body)
	{
		error_log("php send");
		if ( !mail($to,$subject,$body,$headers,$this->params) ) 
		{
			throw new Exception("Unknown error when sending message to '$to' using mail().");
		}

		return TRUE;
	}

	// send to a queue for processing
	private function send_queue($to,$subject,$headers,$body)
	{
		// prepare database connection
		if ( $this->db_object == NULL )
		{
			$this->db_object = new mysqli($this->hostname,$this->username,$this->password,$this->database);
			
			if ( $this->db_object->connect_error ) 
			{
				throw new Exception("Unable to connect to queue server. ".$this->db_object->connect_error);
			}
			
			// prepare database statement
			$sql = "INSERT INTO ".$this->table."(to,subject,headers,body) VALUES(?,?,?,?)";
			$this->db_statement = $this->db_object->prepare($sql);
			
			// check for statement error
			if ( $this->db_statement == FALSE )
			{
				throw new Exception("Error preparing SQL query for execution. Check your syntax.");
			}			
		}

		// insert row
		$this->db_statement->bind_param("ssss",$to,$subject,$headers,$body);
		$result = $this->db_statement->execute();
		
		if ( $result == FALSE ) 
		{
			throw new Exception("Unable to insert message row into queue. ".$this->db_statement->error,1);
		}

		return TRUE;		
	}

	// send to a custom callback function
	private function send_callback($to,$subject,$headers,$body)
	{
		if ( is_callable($this->callback) )
		{
			return call_user_func($this->callback,$to,$subject,$headers,$body);
		}
		else throw new Exception("Unable to call user callback function.");
	}

	// do not send, test only
	private function send_test($to,$subject,$headers,$body)
	{
		$message = "==================================================\r\n";
		$message .= "PHP Mailer Class - TEST MODE\r\n";
		$message .= "TO: $to\r\n";
		$message .= "SUBJECT: $subject\r\n";
		$message .= "HEADERS: $headers\r\n";
		$message .= "BODY: $body\r\n";
		$message .= "==================================================\r\n";
		
		$this->log($message);

		return TRUE;
	}

	// write errors and warnings to log gile
	private function handle_exception($note,$e)
	{
		if ( (int)$e->getCode() == 1 ) $message = "Warning: $note ".$e->getMessage()."\n".$e->getTraceAsString();
		else $message = "Error: $note ".$e->getMessage()."\n".$e->getTraceAsString();

		$this->log($message);
	}
	
	// write message to log file
	private function log($message)
	{
		$message = "TemplateMailer\r\n".$message;
		if ( isset($this->log) ) 
		{
			if ( is_writeable($this->log) )
			{
				error_log($message,3,$this->log);
				return;
			}
			error_log("Unable to open user log file '".$this->log."' for writing. Redirected here.");
		}
		error_log($message);
	}
	
	public function __destruct()
	{
		// close db connection
		if ( $this->mailer == "queue" )
		{
			if ( $this->db_statement ) $this->db_statement->close();
			if ( $this->db_object ) $this->db_object->close();
		}
	}

}

?>
