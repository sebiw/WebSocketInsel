<?php

use WebSocketInsel\Protocol\FrameInterface;
use WebSocketInsel\ExtendedDescriptor;
use WebSocketInsel\UserInterface;
use WebSocketInsel\SocketDB;

class Anonymous implements UserInterface {
	
	public function isUser( $identifier ){
		return false;
	}
		
	public function onCanSend( ExtendedDescriptor $socket , SocketDB $socket_db ){
		
	}
	
	public function onDisconnect( ExtendedDescriptor $socket , SocketDB $socket_db ){
		
	}
	
	public function onMessage( FrameInterface $message , ExtendedDescriptor $e_socket , SocketDB $socket_db ){
		$message = json_decode( $message->getMessage() );				
		$e_socket->setOwner( new Owner( $message->auth_name ) );

		$socket_db->write( $e_socket->getSocket() , 'Willkommen ' . $message->auth_name . ' in der Testumgebung für die WebSocketInsel' );
		$socket_db->writeBroadcastExcept( $message->auth_name , $message->auth_name . ' ist beigetreten!' );
	}
	
}