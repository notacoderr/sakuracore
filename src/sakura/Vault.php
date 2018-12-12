<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\Server;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

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
  
    	public function getItemsInArray(Player $player) : array
  	{
        	return explode ( "," , $this->getItems($player) );
  	}
	
  	public function countItems(Player $player) : int
   	{
        	return count( $this->getItemsInArray($player) );
   	}
	
    	public function getMax(Player $player) : int
	{
        	$name = $player->getName();
		$result = $this->main->db->query("SELECT max FROM vault WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["max"];
	}
	
 	public function addItem(Player $player, int $id, int $meta, int $count) : void
	{
		if( strlen($this->getItems($player)) > 5 )
		{
			$items = (string) $this->getItems($player). ",". $id. ":". $meta. ":". $count;
		} else {
			$items = (string) $this->getItems($player). $id. ":". $meta. ":". $count;
		}
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
		$stmt->bindValue(":max", ($this->getMax($player) + $this->main->settings->getNested('vault.upgrade.increase')));
		$result = $stmt->execute();
    	}
	
	public function openCloud(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				if($this->main->hasSpace($player))
				{
					$button = $data[0];
					if($this->main->hasSpace($player))
					{
						$rawitem = $this->getItemsInArray($player)[$button];
						$i = explode(":", $rawitem);
						$item = Item::get($i[0], $i[1], $i[2]);
						$player->getInventory()->addItem($item);
						$this->delItem($player, $button);
					} else {
						$player->sendTip("§l§cPlease free a slot in your inventory");
					}
				}
			}
		});
        	$form->setTitle('§l§fApply for Quest');
		foreach( $this->getItemsInArray($player) as $items)
		{
			$i = explode(":", $items);
			$form->addButton("§f." Item::get($i[0], $i[1], $i[2])->getName(). " §7- §fx". $i[2]);
		}
		$form->sendToPlayer($player);
	}
   
}
