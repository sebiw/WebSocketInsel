<?php

namespace WebSocketInsel\Protocol;

class RFC6455 {
	
	/**
	 * %x0 denotes a continuation frame
	 * @var int
	 */
	const OPCODE_CONTINUATION_FRAME 		= 0b0000000;
	
	/**
	 * %x1 denotes a text frame
	 * @var int
	 */
	const OPCODE_TEXT_FRAME         		= 0b00000001;
	
	/**
	 * %x2 denotes a binary frame
	 * @var int
	 */
	const OPCODE_BINARY_FRAME       		= 0b00000010;
	
	/**
	 * %x8 denotes a connection close
	 * @var int
	 */
	const OPCODE_CONNECTION_CLOSE   		= 0b00001000;
	
	/**
	 * %x9 denotes a ping
	 * @var int
	 */
	const OPCODE_PING               		= 0b00001001;
	
	/**
	 * %xA denotes a pong
	 * @var int
	 */
	const OPCODE_PONG              			= 0b00001010;
	
	/**
	 * 1000 indicates a normal closure, meaning that the purpose for
     * which the connection was established has been fulfilled.
	 * @var int
	 */
	const STATUS_NORMAL						= 1000;
	
	/**
	 * 1001 indicates that an endpoint is "going away", such as a server
     * going down or a browser having navigated away from a page.
	 * @var int
	 */
  	const STATUS_AWAY						= 1001;

  	/**
  	 * 1002 indicates that an endpoint is terminating the connection due
     * to a protocol error.
  	 * @var int
  	 */
	const STATUS_PROTOCOL_ERROR				= 1002;	

	/**
	 * 1003 indicates that an endpoint is terminating the connection
     * because it has received a type of data it cannot accept (e.g., an
     * endpoint that understands only text data MAY send this if it
     * receives a binary message).
	 * @var int
	 */
	const STATUS_CANNOT_ACCEPT				= 1003;

	/**
	 * 1005 is a reserved value and MUST NOT be set as a status code in a
     * Close control frame by an endpoint.  It is designated for use in
     * applications expecting a status code to indicate that no status
     * code was actually present.
	 * @var int
	 */
	const STATUS_NO_STATUS					= 1005;

    /**
     * 1006 is a reserved value and MUST NOT be set as a status code in a
     * Close control frame by an endpoint.  It is designated for use in
     * applications expecting a status code to indicate that the
     * connection was closed abnormally, e.g., without sending or
     * receiving a Close control frame.
     * @var int
     */  
  	const STATUS_NO_CLOSE_CONTROL			= 1006;
     
    /**
     * 1007 indicates that an endpoint is terminating the connection
     * because it has received data within a message that was not
     * consistent with the type of the message (e.g., non-UTF-8 [RFC3629]
     * data within a text message).
     * @var int
     */ 
    const STATUS_NOT_CONSISTENT 			= 1007;
     
	/**
	 * 1008 indicates that an endpoint is terminating the connection
     * because it has received a message that violates its policy.  This
     * is a generic status code that can be returned when there is no
     * other more suitable status code (e.g., 1003 or 1009) or if there
     * is a need to hide specific details about the policy.
	 * @var int
	 */
    const STATUS_VIOLATES_POLICY			= 1008;
      
	/**
	 * 1009 indicates that an endpoint is terminating the connection
     * because it has received a message that is too big for it to
     * process.
	 * @var int
	 */
    const STATUS_MESSAGE_TO_BIG				= 1009;

	/**
	 * 1010 indicates that an endpoint (client) is terminating the
     * connection because it has expected the server to negotiate one or
     * more extension, but the server didn't return them in the response
     * message of the WebSocket handshake.  The list of extensions that
     * are needed SHOULD appear in the /reason/ part of the Close frame.
     * Note that this status code is not used by the server, because it
     * can fail the WebSocket handshake instead.
	 * @var int
	 */
    const STATUS_MISSING_EXTENSION_SUPPORT 	= 1010;
	
    /**
     * 1011 indicates that a server is terminating the connection because
     * it encountered an unexpected condition that prevented it from
     * fulfilling the request.
     * @var int
     */
	const STATUS_UNEXPECTED_CONDITION 		= 1011;

	/**
	 * 1015 is a reserved value and MUST NOT be set as a status code in a
     * Close control frame by an endpoint.  It is designated for use in
     * applications expecting a status code to indicate that the
     * connection was closed due to a failure to perform a TLS handshake
     * (e.g., the server certificate can't be verified).
	 * @var int
	 */
	const STATUS_CANNOT_PERFORM_HANDSHAKE	= 1015;
	
	private function parseHeader( $string ){
		$result = array();
		$string = explode( "\r\n" , $string );
		foreach( $string AS $head ){
			if( !empty( $head ) && strpos( $head , ':' ) !== false ){
				$head = explode( ':' , $head , 2 );
				$result[ strtolower( trim( $head[0] ) ) ] = trim( $head[1] );
			}
		}
		return $result;
	}	
	
	private function WebSocketAcceptKey( $key ){
		return base64_encode( sha1( $key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11' , true ) );
	}
		
	public function writeFrame( $socket , $message , $opcode = self::OPCODE_TEXT_FRAME , $end = true ){
		$length = strlen( $message );
	
		$FIN  	= ( ( $end === true )? 0b10000000 : 0b00000000 );
		$RSV  	= 0b00000000;
		$MASK 	= 0b00000000;
	
		$byte_1	= chr( $FIN | $RSV | $opcode ); unset( $FIN , $RSV , $opcode );
	
		if( $length > 0xffff ){
			if( PHP_INT_SIZE <= 4 ) throw new \Exception('Payload Length requires a 64-Bit Environment');
			// Workaround to pack() with 64Bit numbers
			$left  = 0xffffffff00000000; $right = 0x00000000ffffffff;
			$left = ( $length & $left  ) >> 32; $right = ( $length & $right );
			$byte_2o5 = chr( ( $MASK << 7 ) | 127 ) . pack( 'NN' , $left , $right );
		} elseif( $length > 0x7d ){
			$byte_2o5 = chr( ( $MASK << 7 ) | 126 ) . pack('n', $length );
		} else {
			$byte_2o5 = chr( ( $MASK << 7 ) | $length );
		}
		$message = $byte_1 . $byte_2o5 . $message;
		@socket_write( $socket , $message );
		return socket_last_error( $socket );
	}
	
	public function readFrame( $socket ){
		$byte = $this->readSocket( $socket , 1 );
		if( empty( $byte ) ){
			return self::STATUS_UNEXPECTED_CONDITION;
		}
	
		/*
		 Base Framing Protocol
		http://tools.ietf.org/html/rfc6455#section-5.2
	
		0                   1                   2                   3
		0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
		+-+-+-+-+-------+-+-------------+-------------------------------+
		|F|R|R|R| opcode|M| Payload len |    Extended payload length    |
		|I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
		|N|V|V|V|       |S|             |   (if payload len==126/127)   |
		| |1|2|3|       |K|             |                               |
		+-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
		|     Extended payload length continued, if payload len == 127  |
		+ - - - - - - - - - - - - - - - +-------------------------------+
		|                               |Masking-key, if MASK set to 1  |
		+-------------------------------+-------------------------------+
		| Masking-key (continued)       |          Payload Data         |
		+-------------------------------- - - - - - - - - - - - - - - - +
		:                     Payload Data continued ...                :
		+ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
		|                     Payload Data continued ...                |
		+---------------------------------------------------------------+
		*/
	
		$frame = array();
		$byte = ord( $byte );
	
		$frame['FIN'] 			= ( ( $byte & 0b10000000 ) >> 7 ); // Indicates that this is the final fragment in a message.  The first fragment MAY also be the final fragment.
		$frame['RSV1'] 			= ( ( $byte & 0b01000000 ) >> 6 ); // MUST be 0 unless an extension is negotiated that defines meanings for non-zero values.
		$frame['RSV2']			= ( ( $byte & 0b00100000 ) >> 5 ); // MUST be 0 unless an extension is negotiated that defines meanings for non-zero values.
		$frame['RSV3']			= ( ( $byte & 0b00010000 ) >> 4 ); // MUST be 0 unless an extension is negotiated that defines meanings for non-zero values.
		// If a nonzero value is received and none of the negotiated extensions defines the meaning of such a nonzero value, the receiving endpoint MUST _Fail the WebSocket Connection_.
		$frame['opcode']		= (   $byte & 0b00001111 );
	
		$byte = $this->readSocket( $socket , 1 );
		if( empty( $byte ) ){
			return self::STATUS_UNEXPECTED_CONDITION;
		}
		$byte = ord( $byte );
	
		$frame['MASK']			= ( ( $byte & 0b10000000 ) >> 7 );
		$frame['payload_len']	= (   $byte & 0b01111111 );
		$frame['payload_data']  = '';
	
		if( $frame['RSV1'] !== 0x0 || $frame['RSV2'] !== 0x0 || $frame['RSV3'] !== 0x0 ){
			return self::STATUS_PROTOCOL_ERROR;
		}
	
		if( $frame['payload_len'] === 0 ) {
			return new RFC6455_Frame( $frame );
		} elseif( $frame['payload_len'] === 126 ) {
			$buffer = unpack('nlong',  $this->readSocket( $socket , 2 ) );
			$frame['payload_len'] = $buffer['long'];
		} elseif( $frame['payload_len'] === 127 ) {
			if( PHP_INT_SIZE != 8 ){
				return self::STATUS_MESSAGE_TO_BIG;
			}
			$buffer = unpack('N2',  $this->readSocket( $socket , 8 ) );
			$frame['payload_len'] = $buffer[1] << 32 | $buffer[2];
		}
		if( $frame['MASK'] === 0 ) {
			$frame['payload_data'] = $this->readSocket( $socket , $frame['payload_len'] );
			return new RFC6455_Frame( $frame );
		}
	
		$frame['masking_key'] = array_map( 'ord', str_split(  $this->readSocket( $socket , 4 ) ) );
	
		/*
			j                   = i MOD 4
			transformed-octet-i = original-octet-i XOR masking-key-octet-j
		*/
		for( $i = 0; $i < $frame['payload_len']; $i++ ){
			$frame['payload_data'] .= chr( ord( $this->readSocket( $socket , 1 ) ) ^ $frame['masking_key'][ ($i % 4) ] );
		}
		return new RFC6455_Frame( $frame );
	}

	private function readSocket( $socket , $length = 16384 ){
		return socket_read( $socket , $length , PHP_BINARY_READ );
	}

	public function doHandshake( $socket ){
		$header = $this->parseHeader( $this->readSocket( $socket ) );
		if( isset( $header['sec-websocket-version'] ) && intval( $header['sec-websocket-version'] ) === 13 ){
			$header	= 	'HTTP/1.1 101 Switching Protocols' . "\r\n" .
					'Upgrade: WebSocket' . "\r\n" .
					'Connection: Upgrade' . "\r\n" .
					'Sec-WebSocket-Accept: ' . $this->WebSocketAcceptKey( $header['sec-websocket-key'] ) . "\r\n\r\n";
			if( @socket_write( $socket , $header ) > 0 ){
				return  true;
			}
			return socket_last_error( $socket );
		}
		return false;
	}

}