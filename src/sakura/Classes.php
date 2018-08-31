<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class Classes
{

	public $main;

	public function __construct(core $core)
	{
        	$this->main = $core;
	}

	public function getClass(Player $player) : string
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT * FROM classes WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["class"];
	}
 
 	public function setClass(Player $player, string $class) : void
	{
		$name = $player->getName();
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO classes (name, class) VALUES (:name, :class);");
		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":class", $class);
		$result = $stmt->execute();
    	}
	
}
