<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class Titles
{

	public $main;

	public function __construct(core $core)
	{
        	$this->main = $core;
	}
	
	public function hasTitles(Player $player) : bool
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT * FROM titles WHERE name = '$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}

	public function getTitle(Player $player) : string
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT inuse FROM titles WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["inuse"];
	}
  
	public function getAllTitles(Player $player) : string
	{
		$name = $player->getName();
		$result = $this->main->db->query("SELECT titles FROM titles WHERE name = '$name';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["titles"];
	}
 
 	public function useTitle(Player $player, string $title) : void
	{
		$name = $player->getName();
		$old = $this->getTitle($player);
   		$titles = $this->trimTitles($player, $title);
		$titles .= "@". $old;
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO titles (name, titles, inuse) VALUES (:name, :titles, :inuse);");
		$stmt->bindValue(":name", $name);
    	$stmt->bindValue(":titles", $titles);
		$stmt->bindValue(":inuse", $title);
		$result = $stmt->execute();
  	}

	public function trimTitles(Player $player, string $x) : string
	{
		$titles = explode("@", $this->getAllTitles($player) );
		unset( $titles[$x] );
		$titles = implode("@", $titles);
		return $titles;
	}
  
	public function addTitle(Player $player, string $x) : void
	{
		$name = $player->getName();
		$titles = (string) $this->getAllTitles($player). "@". $x;

		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO titles (name, titles, inuse) VALUES (:name, :titles, :inuse);");
		$stmt->bindValue(":name", $name);
	   	$stmt->bindValue(":titles", $titles);
		$stmt->bindValue(":inuse", $this->getTitle($player));
		$result = $stmt->execute();
	}

	public function sendForm(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$arr = $this->getAllTitles($player);
				$this->useTitle($player, $arr[ $button ]);
				$player->sendMessage("§f§Title selected: ".  $arr[ $button ]);
			}
			return true;
		});
	    $form->setTitle('§l§fTitle Picker');
		$form->setContent("Active title: ". $this->getTitle($player));
		$titles = explode("@", $this->getAllTitles($player));
		foreach($titles as $title)
		{
			$form->addButton($title);
		}
	    $form->sendToPlayer($player);
	}
}
