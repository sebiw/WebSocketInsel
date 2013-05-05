<?php

error_reporting(E_ALL);

include 'WebSocketInsel\Server.php';
include 'Owner.php';
include 'Anonymous.php';

$server = new WebSocketInsel\Server( 'localhost' , '1414' , new Anonymous() );

$server->start();