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
	
	public function mainForm(Player $player)
    	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0])){
			$button = $data[0];

				switch ($button)
				{
					case 0: break;
					case 1:	break;
					case 2:	break;
					case 3:	break;
				}

				return true;
			}
		});
        	$form->setTitle('§l§fClass Selection');
		
		$form->addButton('§lWarrior'); //0
		$form->addButton('§lPaladin'); //1
		$form->addButton('§lAssassin'); //2
		$form->addButton('§lRanger'); //3
		/*$form->addButton('§lKid'); //4
	    	$form->addButton('§lTeen'); //5
	    	$form->addButton('§lNormal'); //6
		$form->addButton('§lGiant§r [VIP & up]'); //7
	    	$form->addButton('§lTera§r [VIP & up]'); //8
		$form->addButton('§lTitan§r [VIP & up]'); //9*/
		
        	$form->sendToPlayer($player);
    	}
}
