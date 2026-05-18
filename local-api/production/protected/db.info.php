<?php
//Developer:	Charles Palmer
//Created:		2019.01.08
//Revision:		2019.01.08
$domain		=	getenv('PERTECH_DB_HOST') ?: 'localhost';
$username	=	getenv('PERTECH_DB_USER') ?: 'prinet';
$password	=	getenv('PERTECH_DB_PASSWORD') !== false ? getenv('PERTECH_DB_PASSWORD') : 'Pri7680';
$database	=	getenv('PERTECH_DB_NAME') ?: 'pertech';
?>
