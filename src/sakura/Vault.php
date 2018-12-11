<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\Server;

class Vault
{
  
  	public $main;

	public function __construct(core $core)
	{
		$this->main = $core;
	}
    
    public function canAccess(Player $player) : bool
	{
			  $name = $player->getName();
			  $result = $this->main->db->query("SELECT * FROM vault WHERE name = '$name';");
			  $array = $result->fetchArray(SQLITE3_ASSOC);
			  return empty($array) == false;
	}
  
  	public function getItems(Player $player) : string
	{
        $name = $player->getName();
			  $result = $this->main->db->query("SELECT items FROM vault WHERE name = '$name';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr["items"];
	}
  
    public function getItemsInArray(Player) : array
  {
        return explode ( "," , $this->getItems($player) );
  }
  
    public function getMax(Player $player) : int
	{
        $name = $player->getName();
			  $result = $this->main->db->query("SELECT max FROM vault WHERE name = '$name';");
			  $resultArr = $result->fetchArray(SQLITE3_ASSOC);
			  return $resultArr["max"];
	}
  
    public function countItem(Player $player) : int
   {
        return count( $this->getItemsInArray($player) );
   }
  
 	  public function addItem(Player $player, int $id, int $meta, int $count) : void
	{
        $items = $this->getItems($player);
        $items .= ",". $id. ":". $meta. ":". $count;
        
        $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max) VALUES (:name, :items, :max);");
        $stmt->bindValue(":name", $player->getName());
        $stmt->bindValue(":items", $items);
        $stmt->bindValue(":max", $this->getMax($player));
        $result = $stmt->execute();
   }
   
 	  public function delItem(Player $player, int $x) : void
	 {
		    $items = $this->getItemsInArray($player);
        unset($items[$x]);
        $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max) VALUES (:name, :items, :max);");
        $stmt->bindValue(":name", $player->getName());
        $stmt->bindValue(":items", implode("," , $items) );
        $stmt->bindValue(":max", $this->getMax($player));
        $result = $stmt->execute();
   }
    public function upgradeSlot(Player $player) : void
    {
        $stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max) VALUES (:name, :items, :max);");
        $stmt->bindValue(":name", $player->getName());
        $stmt->bindValue(":items", $this->getItems($player));
        $stmt->bindValue(":max", ($this->getMax($player) + $this->main->settings->getNested('vault.max-add')));
        $result = $stmt->execute();
    }
   
}
