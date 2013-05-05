<?php

use WebSocketInsel\Protocol\FrameInterface;
use WebSocketInsel\ExtendedDescriptor;
use WebSocketInsel\UserInterface;
use WebSocketInsel\SocketDB;

class Owner implements UserInterface {

	private $uname = null;
	
	public function __construct( $uname ){
		$this->uname = $uname;
	}
	
	public function isUser( $identifier ){
		return ( $identifier == $this->uname);
	}
	
	public function onCanSend( ExtendedDescriptor $socket , SocketDB $socket_db ){
	
	}
	
	public function onDisconnect( ExtendedDescriptor $socket , SocketDB $socket_db ){
		$socket_db->writeBroadcast( $this->uname . ' hat den Channel verlassen!' );
	}
	
	public function onMessage( FrameInterface $message , ExtendedDescriptor $e_socket , SocketDB $socket_db ){
		
		$socket_db->writeBroadcast( date('H:i:s') . ' - ' . $this->uname . ' # ' . $message->getMessage() );

	}
	
	public function __destruct(){
		WebSocketInsel\Logger::write( 'Vernichte ' . $this->uname . ' da keine Verbindung mehr existiert.' );
	}
	
}