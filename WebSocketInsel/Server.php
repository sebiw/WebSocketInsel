<?php

namespace WebSocketInsel;

use WebSocketInsel\Protocol\RFC6455;

include 'Logger.php';
include 'SocketDB.php';
include 'UserInterface.php';
include 'ExtendedDescriptor.php';

/**
 * WebSocketServer
 * @author Sebastian Wilhelm
 *
 */
class Server {
	
	private $host 		= null;
	private $port 		= null;
	
	private $master 	= null;
	private $socket_db	= null;
	
	private $run 		= true;
	
	private $tv_sec	 	= 0;
	private $tv_usec 	= 1000000;
	
	private $anonymous 	= null;
	private $protocol	= null;
	
	/**
	 * WebSocketServer
	 * @param string $host
	 * @param string|int $port
	 * @param UserInterface $anonymous
	 */
	public function __construct( $host , $port , UserInterface $anonymous ){
		$this->host 		= $host;
		$this->port 		= $port;
		$this->anonymous 	= $anonymous;
		$this->protocol 	= new Protocol\RFC6455();
	}
	
	/**
	 * Initialisiert den Server
	 */
	private function init(){
		$this->master = socket_create( AF_INET , SOCK_STREAM , SOL_TCP );
		socket_bind( $this->master , $this->host , $this->port );
		socket_set_option( $this->master , SOL_SOCKET , SO_REUSEADDR , 1 );
		socket_listen( $this->master );
		$this->socket_db = new SocketDB( $this->protocol , $this->master );
		set_time_limit(0);
	}	
	
	/**
	 * Akzeptiert eine neue Verbindung auf dem Socket
	 * @param resource $socket
	 */
	private function accept( $socket ){
		$e_socket = new ExtendedDescriptor( socket_accept( $socket ) );
		$e_socket = $this->socket_db->appendExtendedSocket( $e_socket );
		Logger::write( 'Neue Vrbindung unter der Socket-ID ' . $e_socket->getSocketId() . ' akzeptiert' );
		return $e_socket;
	}
	
	/**
	 * Verarbeitet den Socket auf dem eine Schreiboperation ausgefürt wurde
	 * @param ExtendedDescriptor $socket
	 */
	private function onReceive( ExtendedDescriptor $e_socket ){
		// Handshake
		if( !$e_socket->getHandshakeStatus() ){
			Logger::write( 'Sende Handshake...' );
			if( $this->protocol->doHandshake( $e_socket->getSocket() ) === true ){
				$e_socket->handshakeDone();
			} else {
				$this->socket_db->disconnect( $e_socket , RFC6455::STATUS_CANNOT_PERFORM_HANDSHAKE );
			}		
		} else {
			Logger::write( 'Lese erhaltenen Frame...' );
			if( !is_object( ( $frame = $this->protocol->readFrame( $e_socket->getSocket() ) ) ) ){
				$this->socket_db->disconnect( $e_socket , $frame );
				return;
			}
			switch( $frame->getOpcode() ){
				// Inhalts-Frames
				case RFC6455::OPCODE_BINARY_FRAME :
				case RFC6455::OPCODE_TEXT_FRAME :
				case RFC6455::OPCODE_CONTINUATION_FRAME :
					if( $e_socket->hasOwner() ){
						Logger::write( 'Socket hat einen Besitzer...' );
						$e_socket->getOwner()->onMessage( $frame , $e_socket , $this->socket_db );
					} else {
						Logger::write( 'Socket hat keinen Besitzer...' );
						$this->anonymous->onMessage( $frame , $e_socket , $this->socket_db );
					}
					break;
				// Command-Frames
				case RFC6455::OPCODE_CONNECTION_CLOSE :
					$this->socket_db->disconnect( $e_socket , RFC6455::STATUS_NORMAL );
				break;
		
				case RFC6455::OPCODE_PING :
					Logger::write( 'Socket erhält Ping-Frame. Sende Pong!' );
					$this->socket_db->write( $e_socket->getSocket() , $frame->getMessage() , RFC6455::OPCODE_PONG );
				break;
		
				case RFC6455::OPCODE_PONG :
					Logger::write( 'Socket erhält Pong-Frame' );
				break;
			}		
		}
	}

	/**
	 * Startet den Server
	 * @throws \ErrorException
	 * @return boolean
	 */
	public function start(){
		if( $this->master == null ){
			Logger::write( 'WebSocketInsel - Test WebSocket Server - (c) Sebastian Wilhelm' );
			$this->init();
			while( $this->run ){
				Logger::write( 'Lauschen ...' );
				do {
					$this->socket_db->fireSendHandler();
					$changed_sockets = $this->socket_db->getSocketsArray();
					$write = $expect = null;
				} while( socket_select( $changed_sockets , $write , $expect , $this->tv_sec , $this->tv_usec ) < 1 );
				foreach( $changed_sockets AS $socket ){
					try {
						if( $socket == $this->master ){
							$this->accept( $socket );
						} else {
							$this->onReceive( $this->socket_db->getExtendedSocket( $socket ) );
						}
					} catch( Exception $e ){
						Logger::write( 'FEHLER: ' . $e->getMessage() );
					}
				}
			}
			return true;
		}
		throw new \ErrorException( 'start() kann nur nach stop() oder __construct() ausgeführt werden' );
	}
	
	/**
	 * Stoppt den Server
	 * @throws \ErrorException
	 * @return boolean
	 */
	public function stop(){
		if( $this->master != null ){
			$this->run = false;
			$shutdown = socket_shutdown( $this->master , 2 );
			socket_close( $this->master );
			$this->master = null;
			return $shutdown;
		}
		throw new \ErrorException( 'stop() kann nur nach start() ausgeführt werden' );
	}
	
}