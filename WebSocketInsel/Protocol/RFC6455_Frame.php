<?php

namespace WebSocketInsel\Protocol;

include 'FrameInterface.php';

class RFC6455_Frame implements FrameInterface {
	
	private $fin	 		= false;
	private $mask 			= null;
	private $message 		= '';
	private $message_length = 0;
	private $opcode  		= 0;
	
	public function __construct( $frame ){
		$this->mask 			= $frame['MASK'];
		$this->fin	   			= (bool) $frame['FIN'] ;
		$this->opcode  			= (int)  $frame['opcode'];	
		$this->message 			= $frame['payload_data'];
		$this->message_length 	= $frame['payload_len'];	
	}
	
	public function getMessage(){
		return $this->message;
	}
	
	public function wasMasked(){
		return $this->mask;
	}
	
	public function getMessageLength(){
		return $this->message_length;
	}
	
	public function getOpcode(){
		return $this->opcode;
	}
	
	public function isFin(){
		return $this->fin;
	}
	
}