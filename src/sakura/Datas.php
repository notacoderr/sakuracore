<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\Server;

class Datas
{
  
  	public $main;

	public function __construct(core $core)
	{
		$this->main = $core;
	}
  
	  /*
	  * Player $player
	  *
	  *
	  */
  
  	public function getVal(Player $player, string $val)
	{
      		$name = $player->getName();
		switch ($val) 
		{
			case 'level':
			  $result = $this->main->db->query("SELECT * FROM lvl WHERE name = '$name';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "level" ];
			break;

			case 'exp':
			  $result = $this->main->db->query("SELECT * FROM exp WHERE name = '$name';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "exp" ];
			break;

			case 'gems':
			  $result = $this->main->db->query("SELECT * FROM gem WHERE name = '$name';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "gems" ];
			break;
		}
	}
  
 	public function addVal(Player $player, string $val, int $add) : void
	{
    		$name = $player->getName();
		switch ($val)
		{
		      case 'level':
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO lvl (name, level) VALUES (:name, :level);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":level", $add);
			$result = $stmt->execute();
		      break;
				
		      case 'exp':
			$f = $this->getVal($player, "exp") + $add;
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO exp (name, exp) VALUES (:name, :exp);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":exp", $f);
			$result = $stmt->execute();
			$this->testLevel($name, $f);
			$player->sendPopup("§l§a +" . $add . " experience");
		      break;
				
		      case 'gems':
			$f = $this->getVal($player, "gems") + $add;
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO gem (name, gems) VALUES (:name, :gems);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":gems", $f );
			$result = $stmt->execute();
			if ($add > 0){ 
				$player->sendMessage("§l§7>§a $add was added to your account.");
			} else { 
			    	$player->sendMessage("§l§7>§c $add was deducted from your account.");
			}
		      break;
		}
    	}
}
