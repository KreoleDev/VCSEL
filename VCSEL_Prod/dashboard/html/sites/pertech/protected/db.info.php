<?php
//Developer:    Charles Palmer
//Created:      2014.04.29
//Revision:     2015.05.17

$domain		=	getenv('PERTECH_DB_HOST') ?: 'localhost';
$username	=	getenv('PERTECH_DB_USER') ?: 'prinet';
$password	=	getenv('PERTECH_DB_PASSWORD') !== false ? getenv('PERTECH_DB_PASSWORD') : 'Pri7680';
$database	=	getenv('PERTECH_DB_NAME') ?: 'pertech';
?>
