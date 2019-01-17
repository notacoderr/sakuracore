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
	  * Iron
	  *	Silver
	  *	Gold
	  *	Platinum
	  *	Titanium
	  *	Plutonium
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
			case "Iron": return "Irn"; break;
			case "Silver": return "Slv"; break;
			case "Gold": return "Gld"; break;
			case "Platinum": return "Pla"; break;
			case "Titanium": return "Ttn"; break;
			case "Plutonium": return "Plu"; break;
		}
	}
	
	public function getDivRoman(Player $player) : string
	{
		switch ($this->getDiv($player))
		{
			case 1: return "I"; break;
			case 2: return "II"; break;
			case 3: return "III"; break;
			case 4: return "IV"; break;
			case 5: return "V"; break;
		}
	}
	
	/*public function getRankIcon(Player $player) : string
	{
		switch ($this->getRank($player))
		{
			case "Iron": return "◬"; break;
			case "Silver": return "⟁"; break;
			case "Gold": return "⧋"; break;
			case "Platinum": return "Pla"; break;
			case "Titanium": return "Ttn"; break;
			case "Plutonium": return "Plu"; break;
		}
	}*/
  
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
		if($rank != "Plutonium" and $new >= 100)
		{
			if($div > 1)
			{
				$this->updateDiv($player, (int) $div - 1);
				$this->updatePoints($player, 5);
				$this->notify($player, 5);
			} else {
				switch($rank)
				{
					case "Iron": 
						$this->updateElo($player, "Silver", 5, 7); $this->notify($player, 1);
						break;
					case "Silver":
						$this->updateElo($player, "Gold", 5, 7); $this->notify($player, 1);
						break;
					case "Gold": 
						$this->updateElo($player, "Platinum", 5, 7); $this->notify($player, 1);
						break;
					case "Platinum":
						$this->updateElo($player, "Titanium", 3, 7); $this->notify($player, 1);
						break;
					case "Titanium":
						$this->updateElo($player, "Plutonium", 1, 3); $this->notify($player, 1);
						break;
					default: //This is to keep old ranks in track
						$this->updateElo($player, "Iron", 5, 5); $this->notify($player, 1);
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
			if($rank === "Plutonium")
			{
				$this->updateElo($player, "Titanium", 1, 25); $this->notify($player, 2);
			}
			if($rank === "Titanium")
			{
				if($div < 3)
				{
					$this->updateDiv($player, (int) $div + 1);
					$this->updatePoints($player, 50);
					$this->notify($player, 6);
				} else {
					$this->updateElo($player, "Platinum", 1, 30); $this->notify($player, 2);
				}
			}
			if($div < 5)
			{
				$this->updateDiv($player, (int) $div + 1);
				$this->updatePoints($player, 50);
				$this->notify($player, 6);
			} else {
				switch($rank)
				{
					case "Platinum": 
					$this->updateElo($player, "Gold", 1, 30);
					$this->notify($player, 2);
					break;
						
					case "Gold":
					$this->updateElo($player, "Silver", 1, 30);
					$this->notify($player, 2);
					break;
					
					case "Silver":
					$this->updateElo($player, "Iron", 1, 30);
					$this->notify($player, 2);
					break;
					
					default: //This is to keep old ranks in track
						$this->updateElo($player, "Iron", 1, 50); $this->notify($player, 2);
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
