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
      		$n = $player->getName();
		switch ($val) 
		{
			case 'level':
			  $result = $this->main->db->query("SELECT * FROM lvl WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "level" ];
			break;

			case 'exp':
			  $result = $this->main->db->query("SELECT * FROM exp WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "exp" ];
			break;

			case 'gems':
			  $result = $this->main->db->query("SELECT * FROM gem WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "gems" ];
			break;
		}
	}
  
 	public function addVal(Player $player, string $val, int $add)
	{
    		$n = $player->getName();
		switch ($val)
		{
		      case 'level':
			//$f = $this->getVal($name, "level") + 1;
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO lvl (name, level) VALUES (:name, :level);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":level", $add);
				$result = $stmt->execute();
		      break;
		      case 'exp':
			$f = $this->getVal($name, "exp") + $add;
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO exp (name, exp) VALUES (:name, :exp);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":exp", $f);
				$result = $stmt->execute();
				  $this->testLevel($name, $f);
				    $this->Alert($name, 1, $add);
		      break;
		      case 'gems':
			$f = $this->getVal($name, "gems") + $add;
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO gem (name, gems) VALUES (:name, :gems);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":gems", $f );
				$result = $stmt->execute();
				  /*if ($add > 0){ 
				    return $this->Alert($name, 7, $add);
				  } else { 
				    return $this->Alert($name, 8, $add);
				  }*/
		      break;
		}
    	}
}
