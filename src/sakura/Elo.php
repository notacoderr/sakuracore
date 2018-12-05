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
      		$result = $this->main->db->query("SELECT point FROM elo WHERE name = '$name';");
      		return $result->fetchArray(SQLITE3_ASSOC)["point"];
	}
  
 	public function updatePoints(Player $player, int $point, bool $magic = false) : void
	{
      		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, point) VALUES (:name, :point);");
      		$stmt->bindValue(":name", $name);
      		$stmt->bindValue(":point", $point);
      		$result = $stmt->execute();
      
      		if($magic) { $this->doMagic($player); }
	}
 
   	public function updateDiv(Player $player, int $div) : void
	{
    		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, div) VALUES (:name, :div);");
      		$stmt->bindValue(":name", $name);
      		$stmt->bindValue(":div", $div);
      		$result = $stmt->execute();
 	}
  
   	public function updateRank(Player $player, string $rank) : void
	{
    		$name = $player->getName();
      		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO elo (name, rank) VALUES (:name, :rank);");
      		$stmt->bindValue(":name", $name);
      		$stmt->bindValue(":rank", $rank);
      		$result = $stmt->execute();
 	}

    	public function isMaxPoints(Player $player) : bool
  	{
      		if($this->getRank($player) == "Apo") { return false; }
      		else { if( $this->getPoints($player) >= 100 ){ return true; } else { return false; } }
  	}
  
    	public function isMinPoints(Player $player) : bool
  	{
      		return if( $this->getPoints($player) <= 0 );
  	}

    	public function isMinDiv(Player $player) : bool
  	{
      		switch(strtolower($this->getRank($player)))
      		{
        		case "gat": case "lakan": case "datu": return if( $this->getDiv($player) === 3); break;
        		default: return true;
      		}
  	}
  
    	public function isMaxDiv(Player $player) : bool
  	{
      		switch(strtolower($this->getRank($player)))
      		{
        		case "gat": case "lakan": case "datu": return if( $this->getDiv($player) === 1); break;
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
          			case: "gat": $this->updateRank($player, "Lakan"); break;
          			case: "lakan": $this->updateRank($player, "Datu"); break;
          			case: "datu": $this->updateRank($player, "Rajah"); break;
          			case: "rajah": $this->updateRank($player, "Apo"); break;
        		}
      		} else {
        		if($div >= 2) $this->updateDiv($player, --$div);
			$this->updatePoints($player, 0);
      		}
  	}
  
    	public function demoteElo(Player $player) : void
  	{
     		$div = $this->getDiv($player);
      		if($this->isMinDiv($player))
      		{
        		switch(strtolower($this->getRank($player)))
        		{
          			case: "lakan": $this->updateRank($player, "Gat"); break;
          			case: "datu": $this->updateRank($player, "Lakan"); break;
         			case: "rajah": $this->updateRank($player, "Datu"); break;
          			case: "apo": $this->updateRank($player, "Rajah"); break;
        		}
      		} else {
        		if($div <= 2) $this->updateDiv($player, ++$div);
			$this->updatePoints($player, 70);
      		}
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
