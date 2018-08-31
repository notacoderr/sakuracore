<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class Quests
{

  	public $main;
	
	public function __construct(core $core)
	{
        	$this->main = $core;
	}
	
	/* @Player $player Quests */
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
				case "name":
					return $this->main->quests->getNested("$quest.name");
				break;
					
				case "level":
					return $this->main->quests->getNested("$quest.level");
				break;
					
				case "cmd":
					return $this->main->quests->getNested("$quest.cmd");
				break;
			}
		} else {
			$this->main->getLogger("Quest Error: can't find value : " . $quest);
			return true;
		}
	}
}
