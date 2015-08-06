<?php

// voer dit 1 keer uit
// nothing too fancy

require 'settings.php';

$c = establishConnection();
$c->query("CREATE TABLE IF NOT EXISTS `games` (
  `gid` int(11) NOT NULL,
  `balance_govt` int(11) NOT NULL DEFAULT '0',
  `income_bank` int(11) NOT NULL DEFAULT '0',
  `date` varchar(100) NOT NULL,
  `winner` varchar(100) NOT NULL DEFAULT '0',
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$c->query("CREATE TABLE IF NOT EXISTS `players` (
  `pid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `balance` int(11) NOT NULL,
  `is_bank` tinyint(4) NOT NULL,
  `init_key` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$c->query("CREATE TABLE IF NOT EXISTS `transaction_history` (
  `tid` int(11) NOT NULL,
  `to_name` varchar(200) NOT NULL,
  `from_name` varchar(200) NOT NULL,
  `amount` int(11) NOT NULL,
  `gid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

$c->query("ALTER TABLE `games`
  ADD PRIMARY KEY (`gid`);");

$c->query("ALTER TABLE `players`
  ADD PRIMARY KEY (`pid`);");

$c->query("ALTER TABLE `transaction_history`
  ADD PRIMARY KEY (`tid`);");

$c->query("ALTER TABLE `games`
  MODIFY `gid` int(11) NOT NULL AUTO_INCREMENT;");

$c->query("ALTER TABLE `players`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT;");

$c->query("ALTER TABLE `transaction_history`
  MODIFY `tid` int(11) NOT NULL AUTO_INCREMENT;");

echo "Installatie gelukt. Ga naar <a href='newgame.php'>deze pagina</a> om een spel te starten. Vergeet dit bestand niet te verwijderen.";

?>
