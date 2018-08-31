<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;

class Quests
{

  	public $main;
	
	public function __construct(core $core)
	{
        	$this->main = $core;
	}
	
	/* @Player $player Quests */
	public function hasQuest(Player $player) : bool
	{
		$result = $this->main->db->query("SELECT * FROM pquests WHERE name='$player->getName()';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
	
	public function getPlayerQuest(Player $player) : string
	{
		$result = $this->main->db->query("SELECT * FROM pquests WHERE name = '$player->getName()';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["quest"];
	}
	
	public function removePlayerQuest(Player $player) : void
	{
		$this->main->db->query("DELETE FROM pquests WHERE name = '$player->getName()';");
	}
 
 	public function givePlayerQuest(Player $player, string $quest) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO pquests (name, quest) VALUES (:name, :quest);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":quest", $quest);
		$result = $stmt->execute();
    	}
	
	/* Quests handling */
	public function getQuest(string $quest, string $val)
	{
		$data = $this->main->quests->getAll();
		if(array_key_exist($quest, $data))
		{
			switch($val)
			{
				case "title":
					return $this->main->quests->getNested($quest.".title");
				break;
					
				case "level":
					return $this->main->quests->getNested($quest.".level");
				break;
				
				case "item":
					return $this->main->quests->getNested($quest.".item");
				break;
					
				case "amount":
					return $this->main->quests->getNested($quest.".amount");
				break;
					
				case "cmd":
					return $this->main->quests->getNested($quest.".cmd");
				break;
					
				case "desc":
					return $this->main->quests->getNested($quest.".desc");
				break;
			}
		} else {
			$this->main->getLogger("Quest Error: can't find value : " . $quest);
			return false;
		}
	}
	
	public function isCompleted(Player $player) : bool
	{
		if($this->hasQuest($player))
		{
			$quest = $this->getPlayerQuest($player);
			$item = $this->getQuest($quest, "item");
			$amount = $this->getQuest($quest, "amount");
			$inventory = $this->getPlayer->getPlayerInventory();
			if($inventory->contains( Item::get($item, 0, $amount) ))
			{
				$inventory->remove( Item::get($item, 0, $amount) );
				
				$this->removePlayerQuest($player);
				
				foreach($this->getQuest($quest, "cmd") as $cmd)
				{
					$this->main->rac($player, $cmd);
				}
				
				return true;
				
			} else {
				
				$player->sendMessage("§l§7You don't have the required item(s)");
				return false;
				
			}
		}
	}
	
	public function addCompleted(Player $player, string $q) : void
	{
		$result = $this->main->db->query("SELECT * FROM pcompleted WHERE name = '$player->getName()';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		
		$completed = explode(".", $resultArr["quests"]);
		
		array_push($completed, $q);
		
		$newcompleted = implode(".", $completed);
		
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO pcompleted (name, quests) VALUES (:name, :quests);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":quests", $newcompleted);
		
		$result = $stmt->execute();
	}
}
