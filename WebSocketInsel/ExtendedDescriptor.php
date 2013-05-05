<?php

namespace WebSocketInsel;

/**
 * ExtendedDescriptor
 * @author Sebastian
 *
 */
class ExtendedDescriptor {
	
	private $socket 	= null;
	private $socket_id	= null;
	private $handshake 	= false;
	private $owner		= null;
	
	/**
	 * ExtendedDescriptor
	 * @param ressource $socket
	 */
	public function __construct( $socket ){
		$this->socket 	 = $socket;
		$this->socket_id = (int) $socket;
	}
	
	/**
	 * ID des Sockets
	 * @return number
	 */
	public function getSocketId(){
		return $this->socket_id;
	}
	
	/**
	 * Gibt den Socket den der ExtendedDescriptor beschreibt zurück.
	 * @return ressource
	 */
	public function getSocket(){
		return $this->socket;
	}
	
	/**
	 * Handshake-Status
	 * @return boolean
	 */
	public function getHandshakeStatus(){
		return $this->handshake;
	}
	
	/**
	 * Handshake durchgeführt
	 */
	public function handshakeDone(){
		$this->handshake = true;
	}
	
	/**
	 * Prüft ob der Socket einem Nutzer gehört
	 * @return boolean
	 */
	public function hasOwner(){
		return ( $this->owner === null )? false : true;
	}
	
	/**
	 * Setzt den Besitzer eines Sockets
	 * @param UserInterface $owner
	 */
	public function setOwner( UserInterface $owner ){
		$this->owner = $owner;
	}
	
	/**
	 * Gibt den Besitzer des Sockets zurück
	 * @return Owner
	 */
	public function getOwner(){
		return $this->owner;
	}
		
}