<?php
// SETTINGS

// beginkapitaal. standaard 1500 zoals volgens de spelregels
$balance_at_beginning = 1500; // moet een int zijn!

// hoeveelheid die de speler krijgt als ie langs start komt. standaard 200 volgens de spelregels
$amount_received_start = 200; // moet een int zijn!

// domein waarop monopoly gespeeld wordt
$domain = "http://example.com/monopoly/";

function establishConnection(){
	$mysql_server = ""; // mysql server
	$mysql_user = ""; // mysql user
	$mysql_password = ""; // mysql password
	$mysql_database = ""; // mysql database
	$c = new mysqli($mysql_server, $mysql_user, $mysql_password, $mysql_database);
	return $c;

}

?>
