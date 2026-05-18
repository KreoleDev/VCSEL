<?php
// Developer:   Charles Palmer
// Created:     2022.09.08
// Revision:    2022.09.21

$domain		=	getenv('PERTECH_DB_HOST') ?: 'localhost';
$username	=	getenv('PERTECH_DB_USER') ?: 'root';
$password	=	getenv('PERTECH_DB_PASSWORD') !== false ? getenv('PERTECH_DB_PASSWORD') : 'D8ab@se!';
$database	=	getenv('PERTECH_DB_NAME') ?: 'pertech';
?>
