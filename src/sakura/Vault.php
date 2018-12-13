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
    
    	public function storageExists(string $id) : bool
	{
		$result = $this->main->db->query("SELECT * FROM vault WHERE name = '$id';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
  
  	public function getItems(string $id) : string
	{
		$result = $this->main->db->query("SELECT items FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["items"];
	}
	
	public function getOwner(string $id) : string
	{
		$result = $this->main->db->query("SELECT owner FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["owner"];
	}
  	
	public function getCode(string $id) : string
	{
		$result = $this->main->db->query("SELECT code FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["code"];
	}
	
	public function getMax(string $id) : int
	{
		$result = $this->main->db->query("SELECT max FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["max"];
	}
	
	public function verifyCode(string $id, string $code) : bool
	{
		$result = $this->main->db->query("SELECT code FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["code"] == $code;
	}
	
    	public function getItemsInArray(string $id) : array
  	{
        	return explode ( "," , $this->getItems($id) );
  	}
	
  	public function countItems(string $id) : int
   	{
        	return count( $this->getItemsInArray($id) );
   	}
	
 	public function addItem(string $id, int $iid, int $meta, int $count) : void
	{
		if( strlen($this->getItems($id)) > 5 )
		{
			$items = (string) $this->getItems($id). ",". $iid. ":". $meta. ":". $count;
		} else {
			$items = (string) $this->getItems($id). $iid. ":". $meta. ":". $count;
		}
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $items);
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
   
 	public function delItem(string $id, int $x) : void
	{
		$items = $this->getItemsInArray($id);
		unset($items[$x]);
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", implode("," , $items) );
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
	
	public function changeShareCode(string $id, string $code) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $this->getItems($id));
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $code);
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
	
    	public function upgradeSlot($id) : void
    	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $this->getItems($id));
		$stmt->bindValue(":max", ($this->getMax($id) + $this->main->settings->getNested('vault.upgrade.increase')));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
    	}
	
	public function openCloud(Player $player, string $id)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				if($this->main->hasSpace($player))
				{
					$button = $data[0];
					$rawitem = $this->getItemsInArray($id)[$button];
					$i = explode(":", $rawitem);
					$item = Item::get($i[0], $i[1], $i[2]);
					$player->getInventory()->addItem($item);
					$this->delItem($id, $button);
				}  else {
					$player->sendMessage("§l§cPlease free a slot in your inventory");
				}
			}
		});
        	$form->setTitle("§l§fSCloud Storage ID: ". $id);
		foreach( $this->getItemsInArray($id) as $items)
		{
			$i = explode(":", $items);
			$form->addButton("§f". Item::get($i[0], $i[1])->getName(). " §7- §fx". $i[2]);
		}
		$form->sendToPlayer($player);
	}
	
	public function genCode() : string
	{
	    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 6);
	}
   
}
