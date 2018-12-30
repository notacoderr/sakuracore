<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;

class Elo
{
  
  	public $main;

	public function __construct(core $core)
	{
		$this->main = $core;
	}
  
	  /*
	  * Player $player
	  *	RANK:
	  *	Slayer
	  *	Master
	  *	Destroyer
	  *	Invader
	  *	Conqueror
	  */
  
  	public function getRank(Player $player) : string
	{
      		$name = $player->getName();
      		$result = $this->main->db->query("SELECT rank FROM elo WHERE name = '$name';");
      		return $result->fetchArray(SQLITE3_ASSOC)["rank"];
	}
	
	public function getRankPrefix(Player $player) : string
	{
		switch ($this->getRank($player))
		{
			case "Slayer": return "Slyr"; break;
			case "Master": return "Mstr"; break;
			case "Destroyer": return "Dtyr"; break;
			case "Invader": return "Invr"; break;
			case "Conqueror": return "Conqr"; break;
		}
	}
	
	public function getRankIcon(Player $player) : string
	{
		switch ($this->getRank($player))
		{
			case "Slayer": return "☗"; break;
			case "Master": return "♞"; break;
			case "Destroyer": return "♜"; break;
			case "Invader": return "♚"; break;
			case "Conqueror": return "♛"; break;
		}
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

	public function increasePoints(Player $player, int $i) : void
	{
		$old = $this->getPoints($player);
		$div = $this->getDiv($player);
		$rank = $this->getRank($player);
		$new =  $old + $i;
		if($rank !== "Conqueror" and $new >= 100)
		{
			if($div >= 2)
			{
				$this->updateDiv($player, (int) $div - 1);
				$this->updatePoints($player, 5);
				$this->notify($player, 5);
			} else {
				switch($rank)
				{
					case "Slayer": 
						$this->updateElo($player, "Master", 3, 5); $this->notify($player, 1);
						break;
					case "Master":
						$this->updateElo($player, "Destroyer", 3, 5); $this->notify($player, 1);
						break;
					case "Destroyer": 
						$this->updateElo($player, "Invader", 1, 5); $this->notify($player, 1);
						break;
					case "Invader":
						$this->updateElo($player, "Conqueror", 1, 5); $this->notify($player, 1);
						break;
				}
			}
		} else {
			$this->updatePoints($player, $new); $this->notify($player, 3);
		}
	}

	public function decreasePoints(Player $player, int $i) : void
	{
		$old = $this->getPoints($player);
		$div = $this->getDiv($player);
		$rank = $this->getRank($player);
		$new =  $old - $i;
		if($new <= 0)
		{
			if($rank === "Conqueror")
			{
				$this->updateElo($player, "Invader", 1, 50); $this->notify($player, 2);
			}
			if($rank === "Invader")
			{
				$this->updateElo($player, "Destroyer", 1, 50); $this->notify($player, 2);
			}
			if($div <= 2)
			{
				$this->updateDiv($player, (int) $div + 1);
				$this->updatePoints($player, 50);
				$this->notify($player, 6);
			} else {
				switch($rank)
				{
					case "Destroyer": 
					$this->updateElo($player, "Master", 1, 50);
					$this->notify($player, 2);
					break;
						
					case "Master":
					$this->updateElo($player, "Slayer", 1, 50);
					$this->notify($player, 2);
					break;
				}
			}
		} else {
			$this->updatePoints($player, $new); $this->notify($player, 4);
		}
	}
	
	private function notify(Player $player, int $type, int $value = 0) : void
	{
		switch($type)
		{
			case 1: 
				$player->addTitle("§l§7[ §aPromoted §7]", "§fYou have climbed into a higher rank.");
				$player->sendMessage("§l§7[§6!§7] §7Congratulations! You have proven yourself and moved into a higher rank.");
			break;
				
			case 2: 
				$player->addTitle("§l§7[ §cDemoted §7]", "§fYou have fallen into a lower rank.");
				$player->sendMessage("§l§7[§6!§7] §7Disgrace! You've let your guard down.");
			break;
			
			case 3: 
				$player->sendPopup("§l§7+§a". $value. "§7rank points!");
			break;
				
			case 4: 
				$player->sendPopup("§l§7-§c". $value. "§7rank points!");
			break;
				
			case 5: 
				$player->sendMessage("§l§7[§6!§7] §7You have moved into a §ahigher division");
			break;
				
			case 6: 
				$player->sendMessage("§l§7[§6!§7] §7You have moved into a §clower division");
			break;
				
		}
	}
  
}
