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
  
 	public function updatePoints(Player $player, int $point, bool $magic = false) : void
	{
      		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
      		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":rank", $this->getRank($player));
		$stmt->bindValue(":div", $this->getDiv($player));
      		$stmt->bindValue(":points", $point);
      		$result = $stmt->execute();
      
      		if($magic !== false) { $this->doMagic($player); }
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

    	public function isMaxPoints(Player $player) : bool
  	{
      		if($this->getRank($player) == "Apo") { return false; }
      		else { return ( $this->getPoints($player) >= 100 ? true : false ); }
  	}
  
    	public function isMinPoints(Player $player) : bool
  	{
      		return ( $this->getPoints($player) <= 0 ? true : false );
  	}

    	public function isMinDiv(Player $player) : bool
  	{
      		switch(strtolower($this->getRank($player)))
      		{
			case "gat": case "lakan": case "datu": return ( $this->getDiv($player) === 3 ? true : false); break;
        		default: return true;
      		}
  	}
  
    	public function isMaxDiv(Player $player) : bool
  	{
      		switch(strtolower($this->getRank($player)))
      		{
        		case "gat": case "lakan": case "datu": return ( $this->getDiv($player) === 1 ? true : false); break;
        		default: return true;
      		}
  	}
  
    //DIVISION: Gat, Lakan, Datu, Rajah, Apo
    
    	public function promoteElo(Player $player) : void
  	{
     		$div = $this->getDiv($player);
      		if($this->isMaxDiv($player))
      		{
        		switch(strtolower($this->getRank($player)))
       			{
          			case "gat": $this->updateRank($player, "Lakan"); break;
          			case "lakan": $this->updateRank($player, "Datu"); break;
          			case "datu": $this->updateRank($player, "Rajah"); break;
          			case "rajah": $this->updateRank($player, "Apo"); break;
        		}
      		} else {
        		if($div >= 2) $this->updateDiv($player, $div -= 1);
			$this->updatePoints($player, 10);
      		}
  	}
  
    	public function demoteElo(Player $player) : void
  	{
     		$div = $this->getDiv($player);
      		if($this->isMinDiv($player))
      		{
        		switch(strtolower($this->getRank($player)))
        		{
          			case "lakan": $this->updateRank($player, "Gat"); break;
          			case "datu": $this->updateRank($player, "Lakan"); break;
         			case "rajah": $this->updateRank($player, "Datu"); break;
          			case "apo": $this->updateRank($player, "Rajah"); break;
        		}
      		} else {
        		if($div <= 2) $this->updateDiv($player, $div += 1);
			$this->updatePoints($player, 70);
      		}
  	}
  
	public function increasePoints(Player $player, int $i) : void
	{
		$old = $this->getPoints($player);
		$new =  $old + $i;
		if() //todo
	}
    	public function doMagic(Player $player) : void
  	{
    		if($this->isMaxPoints($player))
    		{
        		$this->promoteElo($player);
    		}
    
    		if($this->isMinPoints($player))
    		{
        		$this->demoteElo($player);
    		}
  	}
  
}
