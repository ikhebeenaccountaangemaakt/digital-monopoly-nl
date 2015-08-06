<?php
session_start();
require 'settings.php';
$c = establishConnection();
$step = $_GET['step'];
if(empty($step)){
	$html = "In dit scherm kan je een nieuw potje beginnen. Type hieronder de namen van de spelers. Zet de naam van elke speler op een nieuwe regel. De eerste naam die ingevuld wordt zal de bank zijn.";
	$html .= "<form action='newgame.php?step=1' method='POST'><textarea rows='5' cols='50' name='players'></textarea><br /><input type='submit' name='submit' value='Volgende' /></form>";
} else {
	switch($step){
		
		case 1:
			$players = $_POST['players'];
			if(!empty($players)){
				$date = date("Y-m-d H:i:s"); 
				$c->query("START TRANSACTION");
				$c->query("INSERT INTO games (date) VALUES ('$date')");
				$gid = $c->insert_id;
				$players_array = explode("\n", $_POST['players']);
				foreach($players_array as $id=>$single_player){
					$init_key = rand();
					if($id == 0){
						$is_bank = 1;
					} else {
						$is_bank = 0;
					}
					if($single_player == "bank" || $single_player == "vp"){
						$c->query("ROLLBACK");
						die("Je hebt een speler 'bank' of 'vp' genoemd. Dat zijn gereserveerde namen. Ga terug en probeer het opnieuw");
					} else {
						$c->query("COMMIT");
					}
					$array_laststep[$id]['init_key'] = $init_key;
					$array_laststep[$id]['playername'] = $single_player;
					$c->query("INSERT INTO players (name, gid, balance, is_bank, init_key) VALUES ('$single_player','$gid', $balance_at_beginning, $is_bank, '$init_key')");	
					
				}
				$html = "Het spel is aangemaakt. Elke speler heeft een unieke link waarmee hij of zij het spel kan starten. Stuur de spelers hun unieke linkjes.<br />";
				foreach($array_laststep as $playername){
					$html .= "<strong>" .$playername['playername']. "</strong>: <code>" .$domain. "?init_key=" .$playername['init_key']. "</code><br />";
				}
				
			} else {
				$html = "Er zijn geen namen ingevuld. Gebruik de vorige-knop van je browser om dit in orde te maken.";
			}
			break;
			
		
	}

}

echo $html;


?>
