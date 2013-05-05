<?php

namespace WebSocketInsel\Protocol;

interface FrameInterface {
	
	public function __construct( $frame );
	public function getMessage();
	public function getMessageLength();
	public function getOpcode();
	public function wasMasked();
	public function isFin();
		
}