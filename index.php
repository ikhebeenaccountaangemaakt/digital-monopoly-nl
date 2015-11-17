<?php
session_start();
require 'settings.php';
$c = establishConnection();


if (!$victorIsLelijk) {
	$victorIsLelijk = true;
}

if(isset($_SESSION['monopoly_pid'])){
	$game_in_session = true;
} else {
	$game_in_session = false;
	$init_key = $c->real_escape_string($_GET['init_key']);
	if($init_key == ""){
		$fout[] = "Je hebt een incorrecte link gekregen. Probeer opnieuw met een andere link";
	}
	$q = $c->query("SELECT * FROM players WHERE init_key = '$init_key'");
	if($c->affected_rows == 0){
		$fout[] = "Deze speler werd niet herkend. Weet je zeker dat het spel nog bezig is?";
	} else {
		$player = $q->fetch_array();
		$_SESSION['monopoly_pid'] = $player['pid'];
		$user_created = true;
	}
}
if($game_in_session || $user_created){

	$pid = $_SESSION['monopoly_pid'];
	$q_player = $c->query("SELECT * FROM players WHERE pid = '$pid'");
	if($c->affected_rows == 0){
		$fout[] = "Je bankrekening is geblokkeerd. Of het spel is afgelopen, of er is iets naars aan de hand. (foutcode: monopoly_pid niet gevonden";
	} else { 
		$r_player = $q_player->fetch_array();
		$balance = $r_player['balance'];
		$gid = $r_player['gid'];
		$name = $r_player['name'];
		$is_bank = $r_player['is_bank'];
		
		// transactiegeschiedenis van het spel, staat ook in het scherm van de speler
		$q_transhist = $c->query("SELECT * FROM transaction_history WHERE gid = '$gid' ORDER BY tid DESC LIMIT 5 ");
		if($c->affected_rows == 0){
			$no_transactions_yet = true;
		} else {
			$no_transactions_yet = false;
		}

		// settings zoals geld van vrij parkeren
		$q_settings = $c->query("SELECT balance_govt FROM games WHERE gid = '$gid'");
		$r_settings = $q_settings->fetch_array();
		$balance_govt = $r_settings['balance_govt'];

		// alle spelers ophalen
		$q_playernames = $c->query("SELECT pid, name, gid FROM players WHERE gid = '$gid'");

	}

}

if(isset($fout)){
	foreach($fout as $s_fout){
		echo $s_fout. "<br />";
		die();
	}
}
?>
<html>
<head>
<title>Bankrekening van <?php echo $name; ?> / Monopoly</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
        <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0;  user-scalable=0;" name="viewport">
</head>
<body>
<?php
if(!$no_transactions_yet){ ?>
<p><strong>Transactiegeschiedenis - laatste vijf transacties in het spel</strong></p>
<table>
<tr><td>Van:</td><td>Naar:</td><td>Bedrag:</td></tr>
<?php 
while($r_transhist = $q_transhist->fetch_array()){
	echo "<tr><td>" .$r_transhist['from_name']. "</td><td>" .$r_transhist['to_name']. "</td><td>" .$r_transhist['amount']. "</td></tr>";
}
?>
</table>
<?php 
}
?>
<p>Jouw totaal: &euro;<strong><?php echo $balance; ?></strong><br />Totaal in Vrij parkeren: &euro;<strong><?php echo $balance_govt; ?></strong></p>
<form action='overboeken.php' method='POST'>
OVERBOEKEN<br />
Laat als bank het bedrag leeg als je gebruik wilt maken van een optie. Maak alleen gebruik van ronde bedragen.<br />
<table><tr><td>Naar:</td><td><select name='pid'><option value='bank'>[De bank]</option><option value='vp'>[Vrij parkeren]</option>
<?php
while($r_playernames = $q_playernames->fetch_array()){
	if($is_bank != 1){
		if($r_playernames['pid'] != $pid){
			echo "<option value='" .$r_playernames['pid']. "'>" .$r_playernames['name']. "</option>";
		}
	} else {
		echo "<option value='" .$r_playernames['pid']. "'>" .$r_playernames['name']. "</option>";
	}
}
?>
</select></td></tr>
<tr><td>Bedrag:</td><td><input type='text' name='amount' />
<?php
if($is_bank == 1){
echo "of <select name='bank_option'><option value='start'>zakgeld van start</option><option value='vp'>Vrij parkeren uitkeren</option></select>";
}
?>
</td></tr>
<?php
if($is_bank == 1){
	echo "<tr><td>Welke rekening?</td><td><select name='enable_bank'><option value='0'>Mijn eigen</option><option value='1'>Rekening van de bank</option></td></tr>";
}
?>
<tr><td></td><td><input type='submit' value='Bevestig overboeking' name='submit' /></td></tr>
</form>
</body>


