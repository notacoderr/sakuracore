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
 
 	public function useTitle(Player $player, string $title, string $titles) : void
	{
		$name = $player->getName();
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO titles (name, titles, inuse) VALUES (:name, :titles, :inuse);");
		$stmt->bindValue(":name", $name);
		$stmt->bindValue(":titles", $titles);
		$stmt->bindValue(":inuse", $title);
		$result = $stmt->execute();
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
		$form = $this->main->formapi->createSimpleForm(function(Player $player, array $data)
		{
			if (isset($data[0]))
			{
				switch($data[0])
				{
					case 0: $this->sendFormUse($player); break;
					case 1: $this->sendFormRemove($player); break;
				}
			}
		});
	    $form->setTitle('§l§fMy Titles');
		$form->setContent("§7Active title: ". $this->getTitle($player));
		$form->addButton("§l§fChange Title");
		$form->addButton("§l§fDelete a Title");
	    $form->sendToPlayer($player);
	}

	private function sendFormUse(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function(Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$titles = explode("@", $this->getAllTitles($player));
				$title = $titles[$button];
				unset($titles[$button]);
				array_push($titles, $this->getTitle($player));
				sort($titles);
				$this->useTitle($player, $title, $titles);
				$player->sendMessage("§f§lTitle selected: ".  $title);
			}
		});
	    $form->setTitle('§l§fMy Titles');
		$form->setContent("Active title: ". $this->getTitle($player));
		$titles = explode("@", $this->getAllTitles($player));
		foreach($titles as $title)
		{
			$form->addButton((string) $title);
		}
	    $form->sendToPlayer($player);
	}
	
	private function sendFormRemove(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function(Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$titles = explode("@", $this->getAllTitles($player));
				$title = $titles[$button];
				if($title == "§7§lI Love Sakura" or $title == "§7§l_Rookie_")
				{
					$player->sendMessage("§f§lYou cannot delete default title: ".  $title);
				} else {
					unset($titles[$button]);
					sort($titles);
					$name = $player->getName();
					$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO titles (name, titles, inuse) VALUES (:name, :titles, :inuse);");
					$stmt->bindValue(":name", $name);
					$stmt->bindValue(":titles", $titles);
					$stmt->bindValue(":inuse", $this->getTitle($player));
					$result = $stmt->execute();
					$player->sendMessage("§f§lTitle has been deleted: ".  $title);
				}
			}
		});
	    $form->setTitle('§l§fTitle Remover');
		$form->setContent("Active title: ". $this->getTitle($player));
		$titles = explode("@", $this->getAllTitles($player));
		foreach($titles as $title)
		{
			$form->addButton((string) $title);
		}
	    $form->sendToPlayer($player);
	}
}
