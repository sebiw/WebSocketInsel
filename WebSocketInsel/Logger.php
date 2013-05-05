<?php

namespace WebSocketInsel;

class Logger {
	
	public static function write( $message , $ln = true ){
		echo date( 'H:i:s' ) . ' # ' .$message . (( $ln ) ? "\r\n" : '' );
	}
	
}