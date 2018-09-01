<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class Quests
{

  	public $main;
	private $pquest = [];
	
	public function __construct(core $core)
	{
        	$this->main = $core;
	}
	
	/* @Player $player Quests */
	public function hasQuest(Player $player) : bool
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT * FROM pquests WHERE name= '$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
	
	public function getPlayerQuest(Player $player) : string
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT * FROM pquests WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["quest"];
	}
	
	public function removePlayerQuest(Player $player) : void
	{
		$name = $player->getName();
		$this->main->db->query("DELETE FROM pquests WHERE name = '$name';");
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
		$data = $this->main->questData;
		if(array_key_exists($quest, $data))
		{
			switch($val)
			{
				case "title":
					return $data->getNested($quest.".title");
				break;
					
				case "level":
					return $data->getNested($quest.".level");
				break;
				
				case "item":
					return $data->getNested($quest.".item");
				break;
					
				case "amount":
					return $data->getNested($quest.".amount");
				break;
					
				case "cmd":
					return $data->getNested($quest.".cmd");
				break;
					
				case "desc":
					return $data->getNested($quest.".desc");
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
			$inventory = $player->getInventory();
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
		$name = $player->getName();
		$result = $this->main->db->query("SELECT * FROM pcompleted WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		
		$completed = explode(".", $resultArr["quests"]);
		
		array_push($completed, $q);
		
		$newcompleted = implode(".", $completed);
		
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO pcompleted (name, quests) VALUES (:name, :quests);");
		$stmt->bindValue(":name", $name );
		$stmt->bindValue(":quests", $newcompleted );
		
		$result = $stmt->execute();
	}
	
	public function sendQuestApplyForm(Player $player)
    	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$quest = $this->main->questData[$button];
				$player->sendMessage( $quest );

				return true;
			}
		});
        	$form->setTitle('§l§fApply for Quest');
		
		foreach($this->main->questData->getAll() as $quest)
		{
			$form->addButton( $this->main->questData($quest.".title") );
		}
		
        	$form->sendToPlayer($player);
    	}
	
	function sendQuestInfo(Player $player, string $quest)
	{
		$form = $this->main->formapi->createModalForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$player->sendMessage($button);
			}
		});
		
		$data = $this->main->questData;
        	$form->setTitle( strtoupper($data->getNested($quest.".title")) );
		$form->setButton1("§lAccept");
		$form->setButton2("§lBack");
        	$form->sendToPlayer($player);
	}
}
