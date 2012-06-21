<?php

class TemplateMailer {

	private $mailer;
	private $fields;
	private $log;

	private $mail_to;
	private $mail_subject;
	private $mail_message;
	private $mail_headers;	
	
	private $from;
	private $subject;
	private $message;
	private $headers;

	private $hostname;
	private $username;
	private $password;
	private $database;
	private $table;
	private $port;
	private $params;	
	
	private $db_object;
	private $db_statement;

	public function TemplateMailer( $mailer = "php" )
	{
		// check php version
		if ( version_compate(PHP_VERSION,"5.3.0") < 0 )
		{
			throw new Exception("Minimum PHP version 5.3.0 required.");
		}

		// check mailer
		switch ( $mailer )
		{
			case "php":
			case "smtp":
			case "queue":
			case "test":
				$this->mailer = $mailer;
				break;
			default:
				throw new Exception("Unsupported mailer type.");
		}

		// set defaults
		$this->db_obj = NULL;

	}
	
	// set mailer options
	public function setOptions( $options = array() )
	{
		foreach ( $options as $key => $value )
		{
			switch ( $key )
			{
				case "log":
				case "subject":
				case "message":
				case "headers":
				case "from":
				case "hostname":
				case "username":
				case "password":
				case "database":
				case "port":
				case "params":
					$this->{$key} = $value;
					break;
				default:
					return FALSE;
			}
		}
		return TRUE;
	}

	// send message
	public function send($to,$fields = NULL)
	{
		try 
		{
			// re-use previous fields if not provided
			if ( $fields != NULL ) $this->fields = $fields;
			
			$this->mail_to 			= $to;
			$this->mail_subject 		= $this->prepare($this->subject);
			$this->mail_message 		= $this->prepare($this->message);
			$base_headers			= "From: {from}\r\nX-Mailer: php\r\n";
			$this->mail_headers		= $this->prepare($base_headers.$this->headers);
			$this->preflight();
			
			switch ( $this->mailer )
			{
				case "php":
					$this->send_php();
					break;
				case "smtp":
					$this->send_smtp();
					break;
				case "queue":
					$this->send_queue();
					break;
				case "test":
					$this->send_test();
					break;
			}
			
			return TRUE;
		}
		catch ( Exception $e)
		{
			$this->handle_exception("unable to send message",$e)
			return FALSE;
		}
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
				$format = $value["format"];
				$value = $value["value"];
			}
			$template = $this->template_replace($keyword,$value,$format,$template);
		}

		// replace base fields
		$template = $this->template_replace("to",$this->mail_to ,"%s",$template);
		$template = $this->template_replace("from",$this->from,"%s",$template);
		$template = $this->template_replace("hostname",$this->hostname,"%s",$template);
		
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
		if ( !isset($this->mail_to) ) throw new Exception("To address is not set.");
		if ( !isset($this->from) ) throw new Exception("From address is not set.");
		if ( !isset($this->subject) ) throw new Exception("Subject is not set.");
		if ( !isset($this->message) ) throw new Exception("Message is not set.");

		// smtp specific requirements
		if ( $this->mailer == "smtp")
		{
			if ( !isset($this->hostname) ) throw new Exception("Mailer hostname is not set.");
			if ( !isset($this->username) ) throw new Exception("Mailer username is not set.");
			if ( !isset($this->password) ) throw new Exception("Mailer password is not set.");
			if ( !isset($this->port) ) throw new Exception("Mailer port is not set.");
		}
		
		// queue specific requirements
		if ( $this->mailer == "queue" )
		{
			if ( !isset($this->hostname) ) throw new Exception("Queue hostname is not set.");
			if ( !isset($this->username) ) throw new Exception("Queue username is not set.");
			if ( !isset($this->password) ) throw new Exception("Queue password is not set.");
			if ( !isset($this->database) ) throw new Exception("Queue database is not set.");
		}

	}

	// send with php mail() function
	private function send_php()
	{
		$to		= $this->mail_to:
		$subject 	= $this->mail_subject;
		$message 	= $this->mail_message;
		$headers 	= $this->mail_headers;
		$params 	= $this->params;

		if ( !mail($to,$subject,$message,$headers,$params) ) 
		{
			throw new Exception("Unknown error when sending message to '$to' using mail().");
		}

	}

	// send to smtp server
	private function send_smtp()
	{
		throw new Exception("SMTP sending not yet implemented.")
		
		// set SMTP parameters
		ini_set("SMTP",$this->hostname);
		ini_set("sendmail_from",$this->from);
		ini_set("smtp_port",$this->port);
		
		$this->params = "";
		
		$this->send_php();		
	}

	// send to a queue for processing
	private function send_queue()
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
			$sql = "INSERT INTO ".$this->table."(to,from,headers,subject,message) VALUES(?,?,?,?,?)";
			$this->db_statement = $this->db_object->prepare($sql)
			
			// check for statement error
			if ( $this->db_statement == FALSE )
			{
				throw new Exception("Error preparing SQL query for execution. Check your syntax.");
			}

			// bind to mailer variables
			$this->db_statement->bind_param("sssss",$this->mail_to,$this->from,$this->mail_headers,$this->mail_subject,$this->mail_message);			
		}
		
		// insert row
		$result = $this->db_statement->execute();
		
		if ( $result == FALSE ) 
		{
			throw new Exception("Unable to insert message row into queue. ".$this->db_statement->error,1);
		}		
	}

	// do not send, test only
	private function send_test()
	{
		$message = "==================================================\r\n";
		$message .= "PHP Mailer Class - TEST MODE\r\n";
		$message .= "TO: ".$this->mail_to."\r\n";
		$message .= "FROM: ".$this->from."\r\n";
		$message .= "SUBJECT: ".$this->mail_subject."\r\n";
		$message .= "HEADERS: ".$this->mail_headers."\r\n";
		$message .= "MESSAGE: ".$this->mail_message."\r\n";
		$message .= "==================================================\r\n";
		
		$this->log($message);
	}

	// write errors and warnings to log gile
	private function handle_exception($note,$e)
	{
		if ( (int)$e->getCode == 1 ) $message = "Warning: $note ".$e->getMessage."\n".$e->getTraceAsString;
		else $message = "Error: $note ".$e->getMessage."\n".$e->getTraceAsString;

		$this->log($message);
	}
	
	// write message to log file
	private function log($message)
	{
		$message = "TemplateMailer - ".$message;
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
