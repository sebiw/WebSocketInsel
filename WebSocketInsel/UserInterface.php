<?php

namespace WebSocketInsel;

interface UserInterface {

	/**
	 * Wird verwendet um mittels Identifier die Identität eines Nutzers zu bestimmen.
	 * @param mixed $identifier
	 */
	public function isUser( $identifier );
	
	/**
	 * Wird verwendet wenn der Server Daten versenden kann
	 * @param ExtendedDescriptor $socket
	 * @param SocketDB $socket_db
	 */
	public function onCanSend ( ExtendedDescriptor $socket , SocketDB $socket_db );
	
	/**
	 * Wird verwendet wenn die Verbindung von einem Socket abgebaut wird
	 * @param ExtendedDescriptor $socket
	 * @param SocketDB $socket_db
	 */
	public function onDisconnect( ExtendedDescriptor $socket , SocketDB $socket_db );
	
	/**
	 * Wird verwendet wenn auf einem Socket eine neue nachricht für den entsprechenden Nutzer zur verfügung gestellt wird.
	 * @param Protocol\FrameInterface $message
	 * @param ExtendedDescriptor $e_socket
	 * @param SocketDB $socket_db
	 */
	public function onMessage( Protocol\FrameInterface $message , ExtendedDescriptor $e_socket , SocketDB $socket_db );
	
}