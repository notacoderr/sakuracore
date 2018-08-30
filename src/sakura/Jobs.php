<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class Jobs
{

	public $main;

	public function __construct(core $core)
	{
        	$this->main = $core;
	}

	public function getJob(Player $player) : string
	{
		$result = $this->main->db->query("SELECT * FROM jobs WHERE name = '$player->getName()';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr[ "job" ];
	}
 
 	public function setJob(Player $player, string $newjob) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO jobs (name, job) VALUES (:name, :job);");
		$stmt->bindValue(":name", $player->getName());
		$stmt->bindValue(":job", $newjob);
		$result = $stmt->execute();
    	}
	
}
