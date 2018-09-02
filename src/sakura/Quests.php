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
	private $questCache = [];
	
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
	
	public function validatePlayerQuest(Player $player, $quest) : bool
	{
		if($this->hasQuest($player) == false) //Checks if the player is NOT on a quest
		{
			if($this->questExist($quest)) //Checks if the quest is still existing
			{
				if($this->main->data->getVal($player, "level") >= $this->getQuestLevel($quest)) //Checks if the player is equal or above the level
				{
					if($this->main->hasSpace($player)) //Now the book is important, just for the info.
					{
						$this->givePlayerQuest($player, $quest); //finally giving the quest
						return true;
					}
					$player->sendMessage("§l§7Failed to insert Quest Book.");
					return false;
				}
				$player->sendMessage("§l§7You haven't met the level requirement.");
				return false;
			}
			$player->sendMessage("§7§lAn error has occured, the quest may have been deleted on the process.");
			return false;
		}
		$player->sendMessage("§l§7You are already in a quest");
		return false;
	}

 	public function givePlayerQuest(Player $player, string $quest) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO pquests (name, quest) VALUES (:name, :quest);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":quest", $quest);
		$result = $stmt->execute();
		
		$book = Item::get(Item::WRITTEN_BOOK, 0, 1);
		$book->setTitle($this->getQuestTitle($quest));
		$book->setPageText(0, "§l§7TITLE: §c". $this->getQuestTitle($quest). "§r\n§l§7Level: §c". $this->getQuestLevel($quest). "§r\n§l§6--[§7IP§6]--§r\n§l§cPlaySakura.online§r\n§l§6--[Port§6]--§r\n§l§c25627");
		$book->setPageText(1, "§l§7[ §0Quest Info §7] §r\n§6". $this->getQuestInfo($quest) );
		$book->setAuthor("Sakura Council");
		
		$player->getInventory()->addItem($book);
    	}
	
	public function removePlayerQuest(Player $player) : void
	{
		$name = $player->getName();
		$this->main->db->query("DELETE FROM pquests WHERE name = '$name';");
	}

	/* Quest Data handling */
	public function questExist(string $quest): bool
	{
		return (array_key_exists($quest, $this->main->questData->getAll() )) ? true : false;
	}

	public function getQuestTitle(string $quest) : string
	{
		return $this->main->questData->getNested($quest.".title");
	}

	public function getQuestLevel(string $quest) : string
	{
		return $this->main->questData->getNested($quest.".level");
	}
	
	public function getQuestInfo(string $quest) : string
	{
		return $this->main->questData->getNested($quest.".desc");
	}

	public function getQuestItem(string $quest) : Item
	{
		$item = (string) $this->main->questData->getNested($quest.".item");
		$i = explode(":", $item);
		return Item::get($i[0], $i[1], $i[2]);
	}

	public function getQuestCmds(string $quest) : array
	{
		return $this->main->questData->getNested($quest.".cmd");
	}
	
	public function isCompleted(Player $player) : bool
	{
		if( $this->hasQuest($player) )
		{
			$quest = $this->getPlayerQuest($player);
			$item = $this->getQuestItem($quest);
			$inventory = $player->getInventory();
			if($inventory->contains($item))
			{
				$inventory->remove($item);
				$this->removePlayerQuest($player);
				foreach($this->getQuestCmds($quest) as $cmd)
				{
					$this->main->rac($player, $cmd);
				}
				return true;
			} else {
				$player->sendMessage("§l§7You don't have the required item(s).");
				return false;
			}
		} else {
			$player->sendMessage("§l§7You are not on a Quest.");
			return false;
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
				$list = array_keys( $this->main->questData->getAll() );
				$quest = $list[ $button ];
				//$player->sendMessage($quest); //for debug
				$this->questCache[ $player->getName() ] = $quest;
				$this->sendQuestInfo($player, $quest);
				return true;
			}
		});
        	$form->setTitle('§l§fApply for Quest');
		
		foreach( array_keys($this->main->questData->getAll()) as $questid)
		{
			$form->addButton( $this->main->questData->getNested($questid.".title") );
		}
		
        	$form->sendToPlayer($player);
    	}
	
	public function sendQuestInfo(Player $player, string $quest)
	{
		$form = $this->main->formapi->createModalForm(function (Player $player, array $data)
		{
			if($data[0])
			{
				$this->validatePlayerQuest( $player, $this->questCache[ $player->getName() ]);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    unset( $this->questCache[$player->getName()] );
				}
				return;
			} else {
				$this->sendQuestApplyForm($player);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    uunset( $this->questCache[$player->getName()] );
				}
				return;
			}
		});
		
        	$form->setTitle(strtoupper( $this->getQuestTitle($quest) ));
		$form->setContent("§fTitle:§a ". $this->getQuestTitle($quest). "\n§fReq. Level:§a ". $this->getQuestLevel($quest). "\n§f-§6 ". $this->getQuestInfo($quest));
		$form->setButton1("§lAccept");
		$form->setButton2("§lBack");
        	$form->sendToPlayer($player);
	}
	
}
