<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\inventory\PlayerInventory;

class Quests
{

  	public $main;
	
	public function __construct(core $core)
	{
        	$this->main = $core;
	}
	
	/* @Player $player Quests */
	public function hasQuest(Player $player) : string
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
 
 	public function setPlayerQuest(Player $player, string $quest) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO pquests (name, quest) VALUES (:name, :quest);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":quest", $quest);
		$result = $stmt->execute();
    	}
	
	/* Quests data handling */
	public function getQuest(string $quest, string $val)
	{
		$data = $this->main->quests;
		if(in_array($quest, $data))
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
			return true;
		}
	}
	
	public function validateCompletion(Player $player)
	{
		if($this->hasQuest($player))
		{
			$quest = $this->getPlayerQuest($player);
			$item = $this->getQuest($quest, "item");
			$amount = $this->getQuest($quest, "amount");
			$inventory = $this->getPlayer->getPlayerInventory();
			if()
			{
				//checks if the $item exists on the player's $inventory with the required $amount
			}
		}
	}
}
