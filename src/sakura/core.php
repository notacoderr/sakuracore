<?php

namespace sakura;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\event\Listener;
#use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\level\level;

use pocketmine\item\Item;
use pocketmine\inventory\Inventory;
//use pocketmine\scheduler\Task;

class core extends PluginBase implements Listener {

	public $db;

	public function onEnable() : void
	{	
		$this->saveResource('settings.yml');
		$this->saveResource('quests.yml');
		$this->saveResource('items.yml');
		$this->saveResource('recipe.yml');
		
		$this->settings = new Config($this->getDataFolder() . "settings.yml", CONFIG::YAML);
		$this->questData = new Config($this->getDataFolder() . "quests.yml", CONFIG::YAML);
		$this->itemData = new Config($this->getDataFolder() . "items.yml", CONFIG::YAML);
		$this->recipeData = new Config($this->getDataFolder() . "recipe.yml", CONFIG::YAML);
		
		$this->db = new \SQLite3($this->getDataFolder() . "coredrive-v1.db"); //creating main database
		$this->db->exec("CREATE TABLE IF NOT EXISTS gem (name TEXT PRIMARY KEY COLLATE NOCASE, gems INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS exp (name TEXT PRIMARY KEY COLLATE NOCASE, exp INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS lvl (name TEXT PRIMARY KEY COLLATE NOCASE, level INT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS elo (name TEXT PRIMARY KEY COLLATE NOCASE, rank TEXT, div INT, points INT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS guild (guild TEXT PRIMARY KEY COLLATE NOCASE, founder INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS member (name TEXT PRIMARY KEY COLLATE NOCASE, guild INT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS pquests (name TEXT PRIMARY KEY COLLATE NOCASE, quest TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS pcompleted (name TEXT PRIMARY KEY COLLATE NOCASE, quests TEXT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS classes (name TEXT PRIMARY KEY COLLATE NOCASE, class INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS t (name TEXT PRIMARY KEY COLLATE NOCASE, type TEXT);");
		
		$this->classes = new Classes($this); //Class Handler
		$this->quests = new Quests($this); //Quests Handler
		$this->items = new Items($this); //Item Handler
		$this->data = new Datas($this); //Data Value Handler
		$this->elo = new Elo($this); //Elo Handler
		
		if(Server::getInstance()->getPluginManager()->getPlugin("PiggyCustomEnchants") !== null ){
			$this->pce = Server::getInstance()->getPluginManager()->getPlugin("PiggyCustomEnchants");
		}
		$this->formapi = Server::getInstance()->getPluginManager()->getPlugin("FormAPI");
		
		Server::getInstance()->getPluginManager()->registerEvents($this, $this);
		
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool 
	{	
		switch (strtolower( $command->getName() ))
		{
			case 'grant':
				if ($sender instanceof Player)
				{
					$sender->sendMessage("§cCan only be used it §f§lCONSOLE");
					return true;
				}
				if (count($args) > 3 or count($args) < 3)
				{
					$sender->sendMessage("Invalid usage, /grant <playername> <exp/gems/pts> <amount>");
					return true;
				}
				if(isset($args[0])){  //realc
					$target = $this->getServer()->getPlayer($args[0]);
					if(!$target instanceof Player)
					{
						$sender->sendMessage("Player is not online");
						return true;
					}
				}
				if(!$this->isRecorded($target)){
					  $sender->sendMessage("No Record found for $target");
					  return true;
				}
				if (!is_numeric($args[2]) )
				{
					$sender->sendMessage("must be an integer");
					return true;
				}
				switch ( strtolower($args[1]) )
				{
					case "exp": case "xp": case "e":
						$this->data->addVal($target, "exp", $args[2]);
					break;
					case "gems": case "gem": case "g":
						$this->data->addVal($target, "gems", $args[2]);
					break;
					case "pts": case "elo": case "points":
						$this->elo->increasePoints($target, (int) $args[2]);
					break;
				}
				$sender->sendMessage("added $args[2] of $args[1] to ".$target->getName());
			break;
			
			case "takegem":
				if ($sender instanceof Player)
				{
					$sender->sendMessage("§cCan only be used it §f§lCONSOLE");
					return true;
				}
				if(isset($args[0])){  //realc
					$target = $this->getServer()->getPlayer($args[0]);
					if(!$target instanceof Player)
					{
						$sender->sendMessage("Player is not online");
						return true;
					}
				}
				if(!$this->isRecorded($target)){
					  $sender->sendMessage("No Record found for $target");
					  return true;
				}
				if (!is_numeric($args[1]) )
				{
					$sender->sendMessage("must be an integer");
					return true;
				}
				$this->data->takeGem($target, $args[1]);
			break;
				
			case "test":
				$sender->sendMessage($this->getTop(5));
			break;
			
			case "quest":
				/*
				*args[0] = player name
				*/
				
				if ($sender instanceof Player)
				{
					$this->quests->sendQuestApplyForm($sender);
					return true;
				}
				
				switch($args[0]){
					case "complete":
					if (Server::getInstance()->getPlayer($args[1]) instanceof Player)
					{
						$this->quests->isCompleted( Server::getInstance()->getPlayer($args[1]) );
						break;
					}
					break;
						
					case "sendlist":
						$this->quests->sendQuestApplyForm(Server::getInstance()->getPlayer($args[1]));
					break;
						
					/*case "info":
						$this->quests->sendQuestInfo(Server::getInstance()->getPlayer($args[1]), "collectLogs1");
					break;*/
				}
			break;
				
			case "+":
				/**
				*Args 0 = player name
				*Args 1 = item serial
				*Args 2 = item serial
				**/
				if (count($args) <> 2)
				{
					$sender->sendMessage("Invalid usage, /+ <playername> <serial>");
					return true;
				}
				if( !array_key_exists( $args[1], $this->itemData->getAll() ))
				{
					$sender->sendMessage("Serial: " .$args[1]. " does not exist");
					return true;
				}
				
				$target = $this->getServer()->getPlayer($args[0]);
					
				if(!$target instanceof Player)
				{
					$sender->sendMessage("Player is not online");
					return true;
				}

				if($this->hasSpace($target) == false)
				{
					$target->sendMessage("No slot available");
					return true;
				}
				
				$product = $this->items->createItem( $args[1] );
				$target->getInventory()->addItem( $product );
				
			break;
		}
		return true;
	}
	
	public function onLogIn(PlayerLoginEvent $event) : void
	{
		if (!$this->isRecorded( $event->getPlayer() ))
		{
			$this->register( $event->getPlayer() );
		}
	}
	
	public function hasSpace(Player $player) : bool
	{
		return $player->getInventory()->canAddItem(Item::get(Item::STICK, 0, 1)) ? true : false; //Test item xD
	}
	
	public function register(Player $player) : void
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO exp (name, exp) VALUES (:name, :exp);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":exp", 0);
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO gem (name, gems) VALUES (:name, :gems);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":gems", 0);
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO lvl (name, level) VALUES (:name, :level);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":level", 1);
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO elo (name, rank, div, points) VALUES (:name, :rank, :div, :points);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":rank", "Initiate");
		$stmt->bindValue(":div", 3);
		$stmt->bindValue(":points", 0);
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO classes (name, class) VALUES (:name, :class);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":class", "Recruit");
		$result = $stmt->execute();
	}
		
	public function rac(Player $player, string $string) : void
	{
		$command = str_replace("{player}", $player->getName(), $string);
		Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command);
	}

	function testLevel(Player $player, $xp) : bool
	{
		$base = $this->settings->get("baseExp"); //base EXP			132
		$plevel = $this->data->getVal($player, "level");//Player LEVEL							1
		$goal = $base * $plevel; //Base EXP multiply by player's level = goal		132
		//print($base." - ". $plevel." | ".$xp." - ".$goal);
		if ($xp >= $goal)															//given exp 397
		{
			$extra = $xp - $goal; //Excess xp on level up							397 - 132 = 265
			$Ngoal = $goal + $base; //												132 + 132 = 264
			$i = 0; //
			do
			{
				$i += 1;//															( $i = $i + 1 ) = 2
				if ($extra >= $Ngoal)
				{
					$extra = $extra - $Ngoal; //									265 - 264 = 1
				}
				//print("\n extra is $extra \n");
				//print("new level is $i \n");
			} 
			while ($extra >= $Ngoal);//												1 >= 265 0
			$f = $plevel + $i;
			//print($plevel." -> ". $f." - ".$goal." -> ".$Ngoal." extra: $extra");
			$this->data->addVal($player, "level", $plevel + $i);
			$player->addTitle("§l§fLevel UP §7[§6 $f §7]", "§fNext Level on §7[§f $extra §7/§d $Ngoal §7");

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO exp (name, exp) VALUES (:name, :exp);");
			$stmt->bindValue(":name", $player->getName() );
			$stmt->bindValue(":exp", $extra);
			$result = $stmt->execute();

			return true;
		}
		
		return false;
	}
	
	public function isRecorded(Player $player) : bool
	{
		$name = $player->getName();
		$result = $this->db->query("SELECT * FROM lvl WHERE name = '$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}

	public function getTop($amount) : string
	{
		$string = "";
		$result = $this->db->query("SELECT * FROM lvl ORDER BY level DESC LIMIT $amount;");
		$i = 0;
		
        	while ($resultArr = $result->fetchArray(SQLITE3_ASSOC))
		{
			$j = $i + 1;
			$name = $resultArr['name'];
			$lvl = $resultArr['level'];
			$string .= ("§l$j > §6$name §fLv. $lvl \n");
			$i += 1;
		}
		
		return $string;
	}
}
