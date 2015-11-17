<?php
session_start();
require 'settings.php';
$c = establishConnection();
if(isset($_SESSION['monopoly_pid'])){
	$game_in_session = true;
} else {
	header("Location: index.php");
}

function applyTransaction($to_pid, $amount, $from_pid, $gid, $vp = false){

	$c = establishConnection();
	// heeft de gebruiker genoeg geld?
	if($from_pid != "bank"){
		// niet de bank, dus ff checken
		$c->query("START TRANSACTION");
		$q_balance_from = $c->query("SELECT balance, pid FROM players WHERE pid = '$from_pid' FOR UPDATE");
		$r_balance_from = $q_balance_from->fetch_array();
		if($amount > $r_balance_from['balance']){
			$result = "Niet genoeg op de bankrekening";
			$c->query("ROLLBACK");
			return $result;
		} else {
			# $c->query("START TRANSACTION");
			$q1 = $c->query("UPDATE players SET balance = balance - $amount WHERE pid = '$from_pid'");
			if($to_pid == "vp"){
				$q2 = $c->query("UPDATE games SET balance_govt = balance_govt + $amount WHERE gid = '$gid'");
			} elseif($to_pid == "bank") {			
				$q2 = $c->query("UPDATE games SET income_bank = income_bank + $amount WHERE gid = '$gid'");
			} else {
				$q2 = $c->query("UPDATE players SET balance = balance + $amount WHERE pid = '$to_pid'");
			}
			if($q1 && $q2){
				$c->query("COMMIT");
				if(is_numeric($to_pid)){
					$q_getplayernames_to = $c->query("SELECT pid, name FROM players WHERE pid = '$to_pid'");
					$r_getplayernames_to = $q_getplayernames_to->fetch_array();
					$to_name = $r_getplayernames_to['name'];
				} else {
					$to_name = $to_pid;
				}

				$q_getplayernames_from = $c->query("SELECT pid, name FROM players WHERE pid = '$from_pid'");
				$r_getplayernames_from = $q_getplayernames_from->fetch_array();
				$from_name = $r_getplayernames_from['name'];
				$c->query("INSERT INTO transaction_history (to_name, from_name, amount, gid) VALUES ('$to_name', '$from_name', '$amount', '$gid')");
				$result = "Betaling geslaagd";
				return $result;
			} else {
				$c->query("ROLLBACK");
				$result = "Een van de twee queries in de transactie is mislukt. Dit hoort niet te gebeuren.";
				return $result;
			}
		}
				
	} else {
		// gebruiker is de bank, ongelimiteerd geld
		if($to_pid == "vp" || $to_pid == "bank"){
			// gebruiker mag niet met het geld van de bank de rekening van vrij parkeren/bank betalen
			$result = "Valsspeler! Je mag je eigen rekeningen niet met het geld van de bank betalen.";
			return $result;
		}
		$c->query("START TRANSACTION");
		$b_q1 = $c->query("UPDATE players SET balance = balance + $amount WHERE pid = '$to_pid'");
		if($vp){
			$c->query("UPDATE games SET balance_govt = 0 WHERE gid = '$gid'");
		}
		if($b_q1){
			$c->query("COMMIT");
			$q_getplayernames = $c->query("SELECT pid, name FROM players WHERE pid = '$to_pid'");
			$r_getplayernames = $q_getplayernames->fetch_array();
			$to_name = $r_getplayernames['name'];
			$from_name = "bank";
			$c->query("INSERT INTO transaction_history (to_name, from_name, amount, gid) VALUES ('$to_name', '$from_name', '$amount', '$gid')");
			$result = "Betaling geslaagd";
			return $result;
		} else {
			$c->query("ROLLBACK");
			$result = "Een van de twee queries in de transactie is mislukt. Dit hoort niet te gebeuren.";
			return $result;
		}
	}

}

if($game_in_session){

	// data speler ophalen
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
		
		// vars ophalen
		$to_pid = $c->real_escape_string($_POST['pid']);
		$from_pid = $c->real_escape_string($_SESSION['monopoly_pid']);
		$amount = $c->real_escape_string($_POST['amount']);
		$bank_option = $c->real_escape_string($_POST['bank_option']);
	
		// eerst kijken: is er een optie ingeschakeld?
		if($is_bank == 1 && $amount == ""){
			// een optie mag niet gebruikt worden in voordeel van de bank of vrij parkeren
			if($to_pid == "vp" || $to_pid == "bank"){
				$payment = false;
			} else {
				switch($bank_option){
					case "start":
						$amount_o = 200;
						$success = applyTransaction($to_pid, $amount_o, "bank", $gid);
						$optie_enabled = true;
						if($success == "Betaling geslaagd"){
							$payment = true;
						} else {
							$foutmelding = $success;
						}
						break;

					case "vp":
						$q_vrijparkeren = $c->query("SELECT balance_govt, gid FROM games WHERE gid = '$gid'");
						$r_vrijparkeren = $q_vrijparkeren->fetch_array();
						$amount_o = $r_vrijparkeren['balance_govt'];
						$success = applyTransaction($to_pid, $amount_o, "bank", $gid, true);
						$optie_enabled = true;
						if($success == "Betaling geslaagd"){
							$payment = true;
						} else {
							$foutmelding = $success;
						}
						break;
					default:
						$payment = false; // geen optie geselecteerd + geen bedrag ingevuld = fout
						$foutmelding = "Je hebt geen optie geselecteerd en geen bedrag ingvuld";
					
				}
			}
		}

		if($is_bank != 1 && $amount == "" && $optie_enabled != true){
			// geen bank + geen bedrag ingevuld = fout
			$payment = false;
			$foutmelding = "Je hebt geen bedrag ingevuld";
		} else {
			// er is een bedrag ingevuld
			if(!is_numeric($amount) && $optie_enabled != true){
				$payment = false;
				$foutmelding = "Je hebt je bedrag niet in ronde cijfers ingevuld.";
			} else {			
				if($_POST['enable_bank'] == 1 && $is_bank == 1){
					$from_pid = "bank";
				}
				
				$amount = abs($amount);
				$success = applyTransaction($to_pid, $amount, $from_pid, $gid);
				if($success == "Betaling geslaagd"){
					$payment = true;
				} else {
					$foutmelding = $success;
				}
			}
		}
	
	}

	if($payment){
		header("Location: index.php");
		echo "<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=ISO-8859-1\">
        <meta content=\"width=device-width; initial-scale=1.0; maximum-scale=1.0;  user-scalable=0;\" name=\"viewport\"><meta http-equiv=\"refresh\" content=\"2; URL=index.php\"><title>Betaling geslaagd</title></head><body><h1>Betaling geslaagd</h1></body></html>";
	} else {
		echo "De betaling is mislukt. Foutmelding: $foutmelding";
	}
}
