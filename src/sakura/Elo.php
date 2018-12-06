<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\Server;

class Elo
{
  
  	public $main;

	public function __construct(core $core)
	{
		$this->main = $core;
	}
  
	  /*
	  * Player $player
	  *DIVISION: Gat, Lakan, Datu, Rajah, Apo
	  *
	  */
  
  	public function getRank(Player $player) : string
	{
      	$name = $player->getName();
		    $result = $this->main->db->query("SELECT rank FROM elo WHERE name = '$name';");
			  return $result->fetchArray(SQLITE3_ASSOC)["rank"];
	}
  
    	public function getDiv(Player $player) : int
	{
      	$name = $player->getName();
		    $result = $this->main->db->query("SELECT div FROM elo WHERE name = '$name';");
			  return $result->fetchArray(SQLITE3_ASSOC)["div"];
	}

    	public function getPoints(Player $player) : int
	{
      		$name = $player->getName();
      		$result = $this->main->db->query("SELECT points FROM elo WHERE name = '$name';");
      		return $result->fetchArray(SQLITE3_ASSOC)["points"];
	}
  
 	public function updatePoints(Player $player, int $point) : void
	{
      		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
      		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":rank", $this->getRank($player));
		$stmt->bindValue(":div", $this->getDiv($player));
      		$stmt->bindValue(":points", $point);
      		$result = $stmt->execute();
	}
 
   	public function updateDiv(Player $player, int $div) : void
	{
    		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
      		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":rank", $this->getRank($player));
      		$stmt->bindValue(":div", $div);
		$stmt->bindValue(":points", $this->getPoints($player));
      		$result = $stmt->execute();
 	}
  
   	public function updateRank(Player $player, string $rank) : void
	{
    		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
      		$stmt->bindValue(":name", $name);
      		$stmt->bindValue(":rank", $rank);
		$stmt->bindValue(":div", $this->getDiv($player));
		$stmt->bindValue(":points", $this->getPoints($player));
      		$result = $stmt->execute();
 	}
	
	public function updateElo(Player $player, string $rank, int $div, int $point) : void
	{
    		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
      		$stmt->bindValue(":name", $name);
      		$stmt->bindValue(":rank", $rank);
		$stmt->bindValue(":div", $div);
		$stmt->bindValue(":points", $point);
      		$result = $stmt->execute();
 	}
  
    	//DIVISION: Gat, Lakan, Datu, Rajah, Apo
  
	public function increasePoints(Player $player, int $i) : void
	{
		$old = $this->getPoints($player);
		$div = $this->getDiv($player);
		$rank = $this->getRank($player);
		$new =  $old + $i;
		if($new >= 100) //todo
		{
			if($div >= 2)
			{
				$this->updateDiv($player, (int) $div - 1);
				$this->updatePoints($player, 5);
			} else {
				switch(strtolower($rank))
				{
					case "gat": 
						$this->updateElo($player, "Lakan", 3, 5);
						break;
					case "lakan":
						$this->updateElo($player, "Datu", 3, 5);
						break;
					case "datu": 
						$this->updateElo($player, "Rajah", 1, 5);
						break;
					case "rajah":
						$this->updateElo($player, "Apo", 1, 5);
						break;
				}
			}
		} else {
			$this->updatePoints($player, $new);
		}
	}
    	
	public function decreasePoints(Player $player, int $i) : void
	{
		$old = $this->getPoints($player);
		$div = $this->getDiv($player);
		$rank = $this->getRank($player);
		$new =  $old - $i;
		if($new <= 0) //todo
		{
			if($rank === "Apo")
			{
				$this->updateElo($player, "Rajah", 1, 50);
			}
			if($rank === "Rajah")
			{
				$this->updateElo($player, "Datu", 1, 50);
			}
			if($div <= 2)
			{
				$this->updateDiv($player, (int) $div + 1);
				$this->updatePoints($player, 50);
			} else {
				switch(strtolower($rank))
				{
					case "lakan": 
					$this->updateElo($player, "Gat", 1, 50);
					break;
						
					case "Datu":
					$this->updateElo($player, "Lakan", 1, 50);
					break;
				}
			}
		} else {
			$this->updatePoints($player, $new);
		}
	}
  
}
