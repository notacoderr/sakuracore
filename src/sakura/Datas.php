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
			  $result = $this->main->db->query("SELECT * FROM system WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "level" ];
			break;

			case 'exp':
			  $result = $this->main->db->query("SELECT * FROM xp WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "exp" ];
			break;

			case 'respect':
			  $result = $this->main->db->query("SELECT * FROM rp WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "respect" ];
			break;

			case 'rank':
			  $result = $this->main->db->query("SELECT * FROM r WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "rank" ];
			break;

			case 'div':
			  $result = $this->main->db->query("SELECT * FROM d WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "div" ];
			break;

			case 'gems':
			  $result = $this->main->db->query("SELECT * FROM g WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "gems" ];
			break;

			case 'type':
			  $result = $this->main->db->query("SELECT * FROM t WHERE name = '$n';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr[ "type" ];
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
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO system (name, level) VALUES (:name, :level);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":level", $add);
				$result = $stmt->execute();
		      break;
		      case 'exp':
			$f = $this->getVal($name, "exp") + $add;
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO xp (name, exp) VALUES (:name, :exp);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":exp", $f);
				$result = $stmt->execute();
				  $this->testLevel($name, $f);
				    $this->Alert($name, 1, $add);
		      break;
		      case 'respect':
			$f = $this->getVal($name, "respect") + $add;
			  $stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":respect", $f);
				$result = $stmt->execute();
				  $this->checkRank($name, $f);
				  if ($add > 0){ 
				    return $this->Alert($name, 3, $add);
				  } else { 
				    return $this->Alert($name, 4, $add); 
				  }
		      break;
		      case 'gems':
			$f = $this->getVal($name, "gems") + $add;
			  $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO g (name, gems) VALUES (:name, :gems);");
			    $stmt->bindValue(":name", $name);
			      $stmt->bindValue(":gems", $f );
				$result = $stmt->execute();
				  if ($add > 0){ 
				    return $this->Alert($name, 7, $add);
				  } else { 
				    return $this->Alert($name, 8, $add);
				  }
		      break;
		}
    	}
}
