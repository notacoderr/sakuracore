<?php

namespace sakura;

use sakura\core;
use sakura\calculateExp;
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
				
			$res = calculateExp::doMagic($player, $f);//$this->main->testLevel($player, $f);
				
			//$player->sendPopup("§l§a +" . $add . " experience");
			$player->sendMessage($res);
		    break;
				
		     case 'gems':
			$f = $this->getVal($player, "gems") + $add;
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO gem (name, gems) VALUES (:name, :gems);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":gems", $f );
			$result = $stmt->execute();
			$player->sendMessage("§l§7>§a $add Gems was added to your account.");
		      break;
		}
    	}
	
	public function takeGem(Player $player, int $gem) : void
	{
    		$name = $player->getName();
		$final = $this->getVal($player, "gems") - $gem;
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO gem (name, gems) VALUES (:name, :gems);");
		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":gems", $final);
		$result = $stmt->execute();
		
		$player->sendMessage("§l§7>§c $gem Gems was taken from your account.");
    	}
}
