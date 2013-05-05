<?php

namespace WebSocketInsel;

use WebSocketInsel\Protocol\RFC6455;

include 'Protocol/RFC6455.php';
include 'Protocol/RFC6455_Frame.php';

/**
 * SocketDB
 * @author Sebastian
 *
 */
class SocketDB {
		
	private $protocol	= null;
	private $sockets	= null;
	private $e_sockets 	= null;
	
	/**
	 * SocketDB
	 * @param RFC6455 $protocol
	 * @param ressource $socket
	 */
	public function __construct( $protocol , $socket ){
		$this->sockets 		= new \ArrayObject();
		$this->sockets[ (int) $socket ] = $socket;
		$this->e_sockets 	= new \ArrayObject();
		$this->protocol 	= $protocol;
	}
	
	/**
	 * Fürt die onCanSend() Events für jeden Nutzer aus
	 */
	public function fireSendHandler(){
		foreach( $this->e_sockets AS $e_socket ){
			if( $e_socket->hasOwner() ) $e_socket->getOwner()->onCanSend( $e_socket , $this );
		}
	}
	
	/**
	 * Das verwendete Protokol
	 * @return \WebSocketInsel\Protocol\RFC6455
	 */
	private function getProtocol(){
		return $this->protocol;
	}
	
	/**
	 * Fügt einen neuen ExtendedDescriptor zur SocketDB hinzu.
	 * @param ExtendedDescriptor $e_socket
	 * @return ExtendedDescriptor
	 */
	public function appendExtendedSocket( ExtendedDescriptor $e_socket ){
		$this->sockets[ $e_socket->getSocketId() ] = $e_socket->getSocket();
		$this->e_sockets[ $e_socket->getSocketId() ] = $e_socket;
		return $e_socket;
	}
	
	/**
	 * Ermittelt den erweiteren Socket anhand des Sockets
	 * @param resource $socket
	 * @throws \Exception
	 */
	public function getExtendedSocket( $socket ){
		$id = (int) $socket;
		if( isset( $this->e_sockets[ $id ] ) )
			return $this->e_sockets[ $id ];
		throw new \Exception('Dieser Socket existiert nicht');
	}
	
	/**
	 * Gibt alle Sockets als Array-Kopie zurück.
	 * @return multitype:
	 */
	public function getSocketsArray(){
		return $this->sockets->getArrayCopy();
	}
	
	/**
	 * Ermittelt einen Nutzer anhand eines frei wählbaren identifiers
	 * @return UserInterface
	 * @param mixed $identifier
	 */
	public function getUserBy( $identifier ){
		foreach( $this->e_sockets AS $e_socket ){
			if( $e_socket->hasOwner() && $e_socket->getOwner()->isUser( $identifier ) ){
				return $e_socket->getOwner();
			}
		}
	}
	
	/**
	 * Sendet einen Broadcast an alle Sockets
	 * @param string $message
	 * @param int $opcode
	 * @param boolean $end
	 */
	public function writeBroadcast( $message , $opcode = RFC6455::OPCODE_TEXT_FRAME , $end = true ){
		foreach( $this->e_sockets AS $e_socket ){
			$this->write( $e_socket->getSocket() , $message , $opcode , $end );
		}
	}
	
	/**
	 * Sendet einen Broadcast an alle Sockets mit ausnahme der im Identifier hinterlegten User (Alle Sockets der Nutzer)
	 * @param mixed $identifier
	 * @param string $message
	 * @param int $opcode
	 * @param boolean $end
	 */
	public function writeBroadcastExcept( $identifier , $message , $opcode = RFC6455::OPCODE_TEXT_FRAME , $end = true ){
		foreach( $this->e_sockets AS $e_socket ){
			if( !$e_socket->hasOwner() || !$e_socket->getOwner()->isUser( $identifier ) )
				$this->write( $e_socket->getSocket() , $message , $opcode , $end );
		}
	}
	
	/**
	 * Sendet einen Multicast an die in Identifier hinterlegten User (Alle Sockets des Nutzers)
	 * @param array $identifiers
	 * @param string $message
	 * @param int $opcode
	 * @param boolean $end
	 */
	public function writeMulticastUser( array $identifiers , $message , $opcode = RFC6455::OPCODE_TEXT_FRAME , $end = true ){
		foreach( $identifiers AS $identifier ){
			$this->writeUser( $identifier , $message , $opcode , $end );
		}
	}
	
	/**
	 * Schreibt eine Nachricht an einen bestimmten Nutzer (Alle Sockets des Nutzers)
	 * @param mixed $identifier
	 * @param string $message
	 * @param int $opcode
	 * @param boolean $end
	 */
	public function writeUser( $identifier , $message , $opcode = RFC6455::OPCODE_TEXT_FRAME , $end = true ){
		foreach( $this->e_sockets AS $e_socket ){
			if( $e_socket->hasOwner() && $e_socket->getOwner()->isUser( $identifier ) ){
				$this->write( $e_socket->getSocket() , $message , $opcode , $end );
			}
		}
	}
	
	/**
	 * Schreibt die Antwort an einen bestimmten Socket
	 * @param ressource $socket
	 * @param String $message
	 * @param int $opcode
	 * @param boolean $end
	 */
	public function write( $socket , $message , $opcode = RFC6455::OPCODE_TEXT_FRAME , $end = true ){
		return $this->getProtocol()->writeFrame( $socket , $message , $opcode , $end );
	}
	
	/**
	 * 
	 * @param unknown $socket
	 * @param unknown $code
	 * @param string $reason
	 * @return number
	 */
	public function close( $socket , $code = RFC6455::OPCODE_TEXT_FRAME , $reason = null ) {
		return $this->getProtocol()->writeFrame( $socket , pack('n', $code) . $reason , RFC6455::OPCODE_CONNECTION_CLOSE );
	}
	
	/**
	 * Beendet die Verbindung des entsprechenden Sockets und Extended-Sockets
	 * @param resource $e_socket
	 */
	public function disconnect( ExtendedDescriptor $e_socket , $code = RFC6455::OPCODE_CONNECTION_CLOSE ){
		Logger::write( 'Schließe Verbindung zu Socket ID ' . $e_socket->getSocketId() . ' aufgrund des Fehler-Codes: ' . $code );		
		$this->close( $e_socket->getSocket() , $code );
		if( $e_socket->hasOwner() ){
			Logger::write( 'Socket hat einen Besitzer' );
			$e_socket->getOwner()->onDisconnect( $e_socket , $this );
		} else {
			Logger::write( 'Socket hat keinen besitzer' );
			$this->getAnonymous()->onDisconnect( $e_socket , $this );
		}
		$this->sockets->offsetUnset( $e_socket->getSocketId() );
		$this->e_sockets->offsetUnset( $e_socket->getSocketId() );
	}
	
	
	
}