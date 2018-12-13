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
		
		$this->db = new \SQLite3($this->getDataFolder() . "coredrive-v2.db"); //creating main database
		$this->db->exec("CREATE TABLE IF NOT EXISTS gem (name TEXT PRIMARY KEY COLLATE NOCASE, gems INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS exp (name TEXT PRIMARY KEY COLLATE NOCASE, exp INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS lvl (name TEXT PRIMARY KEY COLLATE NOCASE, level INT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS elo (name TEXT PRIMARY KEY COLLATE NOCASE, rank TEXT, div INT, points INT);");
		
		//$this->db->exec("CREATE TABLE IF NOT EXISTS guild (guild TEXT PRIMARY KEY COLLATE NOCASE, founder INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS member (name TEXT PRIMARY KEY COLLATE NOCASE, guild INT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS pquests (name TEXT PRIMARY KEY COLLATE NOCASE, quest TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS pcompleted (name TEXT PRIMARY KEY COLLATE NOCASE, quests TEXT);");
		
		$this->db->exec("CREATE TABLE IF NOT EXISTS classes (name TEXT PRIMARY KEY COLLATE NOCASE, class INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS vault (name TEXT PRIMARY KEY COLLATE NOCASE, items TEXT, max INT, code TEXT, owner TEXT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS t (name TEXT PRIMARY KEY COLLATE NOCASE, type TEXT);");
		
		$this->classes = new Classes($this); //Class Handler
		$this->quests = new Quests($this); //Quests Handler
		$this->vault = new Vault($this); //Vault Handler
		$this->items = new Items($this); //Item Handler
		$this->data = new Datas($this); //Data Value Handler
		$this->elo = new Elo($this); //Elo Handler
		
		if(Server::getInstance()->getPluginManager()->getPlugin("PiggyCustomEnchants") !== null ){
			$this->pce = Server::getInstance()->getPluginManager()->getPlugin("PiggyCustomEnchants");
		}
		if(Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI") !== null ){
			$this->eco = Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI");
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
					case "pts": case "elo": case "p":
						$this->elo->increasePoints($target, (int) $args[2]);
					break;
						
					default: $sender->sendMessage("invalid command"); return true;
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
				 
			case "toplvl": //by @PTKDrake
				if(!isset($args[0])){
					$sender->sendMessage("Invalid usage, /toplvl (page)");
					return true;
				}
				if(!is_numeric($args[0])){
					$sender->sendMessage("must be an integer");
					return true;
				}
				$sender->sendMessage($this->getTop($args[0]));
			break;
				
			case "cloud":
				if(!$sender instanceof Player)
				{
					return true;
				}
				if(!$sender->getGamemode() === 1)
				{
					$sender->sendMessage("Creative mode restricted"); return true;
				}
				if(isset($args[0]))
				{
					switch($args[0])
					{
						case "upload": case "up":
							if(isset($args[1]))
							{
								if($this->vault->storageExists($args[1]))
								{
									if($sender->getName() === $this->vault->getOwner($args[1]))
									{
										if( $this->vault->countItems($args[1]) < $this->vault->getMax($args[1]) ) //to be sure, x:x:x (5 chars)
										{
											$this->vault->openCloud($sender, $args[1]);
										} else {
											$sender->sendMessage("§l§7[§e!§7]§f". $args[1]. "'s storage is full..");
										}
									} else {
										if(isset($args[2]))
										{
											if($this->vault->verifyCode($args[1], $args[2]))
											{
												$hand = $sender->getInventory()->getItemInHand();
												if($hand->getId() !== Item::AIR)
												{
													if($this->vault->countItems($args[1]) < $this->vault->getMax($args[1]))
													{
														$this->vault->addItem($args[1], $hand->getId(), $hand->getDamage(), $hand->getCount());
														$sender->sendMessage("§l§7[§a!§7]§f Your item was uploaded in the storage!");
														$sender->getInventory()->setItemInHand( Item::get(0) );
														$sl = $this->vault->countItems( $args[1] );
														$mx = $this->vault->getMax( $args[1] );
														$sender->sendMessage("§l§f". $args[1]. "'s slot: §7[§f $sl / $mx §7]");
													} else {
														$sender->sendMessage("§l§7[§e!§7]§f". $args[1]. "'s storage is full..");
													}
												} else {
													$sender->sendTip("§6§lPlease hold an item..");
												}
											} else {
												$sender->sendMessage("§l§7[§c!§7] §fYour code [". $args[2]. "] seems invalid, contact a staff/the owner if you forgot your code");
											}
										} else {
											$sender->sendMessage("§l§7[§c!§7] §fUsage /cloud upload [vaultId] [shareCode]");
										}
									}
								} else {
									$sender->sendMessage("§l§7[§6!§7] §f". $args[1]. " doesn't exist, did you typed it precisely? tip: VaultID is case senstive.");
								}
							} else {
								$sender->sendMessage("§l§7[§6!§7] §fUsage: /cloud upload [vaultID] [shareCode]");
							}
						break;
						
						case "upgrade": case "+":
							if(isset($args[1]))
							{
								if($this->vault->storageExists($args[1]))
								{
									if($sender->getName() === $this->vault->getOwner($args[1]))
									{
										$pmoney = (int) $this->eco->myMoney($sender);
										$price = (int) $this->settings->getNested("vault.upgrade.price");
										if( $pmoney >= $price )
										{
											$this->vault->upgradeSlot($sender);
											$sender->sendTip("§a§l..Processing your request..");
											$this->eco->reduceMoney($sender, $this->settings->getNested("vault.upgrade.price"));
										} else {
											$sender->sendMessage("§f§lCloud storage update costs: §7". $price. "§f, You have: §c". $pmoney);
										}
									} else {
										$sender->sendMessage("§l§7[§6!§7] §fSorry! But only the owner may upgrade the vault.");
									}
								} else {
									$sender->sendMessage("§l§7[§6!§7] §f". $args[1]. " doesn't exist, did you typed it precisely? tip: VaultID is case senstive.");
								}
							} else {
								$sender->sendMessage("§l§7[§6!§7] §fUsage: /cloud upgrade [vaultID]");
							}
						break;
							
						case "reg": case "register":
							if(isset($args[1]))
							{
								if(!$this->vault->storageExists($args[1]))
								{
									$pmoney = (int) $this->eco->myMoney($sender);
									$price = (int) $this->settings->getNested("vault.price");
									if($pmoney >= $price)
									{
										$code = $this->vault->genCode();
										$stmt = $this->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max, :code, :owner);");
										$stmt->bindValue(":name", $args[1]);
										$stmt->bindValue(":items", "");
										$stmt->bindValue(":max", $this->settings->getNested("vault.slots"));
										$stmt->bindValue(":code", $code);
										$stmt->bindValue(":owner", $sender->getName());
										$result = $stmt->execute();
										
										$sender->sendMessage("§l§7[§a!§7]§f Your cloud storage is ready!");
										$sender->sendMessage("§a>§f VaultID: ". $args[1]);
										$sender->sendMessage("§a>§f Random Share-Code: ". $code);
										
										$this->eco->reduceMoney($sender, $this->settings->getNested("vault.price"));
									} else {
										$sender->sendMessage("§l§7[§6!§7] §fCloud storage costs: §7". $price. "§f, You have: §c". $pmoney);
									}
								} else {
									$sender->sendMessage("§l§7[§6!§7] §f". $args[1]. " appears to be registered");
								}
							} else {
								$sender->sendMessage("§l§7[§6!§7] §fUsage /cloud [register/reg] [vaultId]");
							}
						break;
						
						case "dl": case "download":
							if(isset($args[1]))
							{
								if($this->vault->storageExists($args[1]))
								{
									if($sender->getName() === $this->vault->getOwner($args[1]))
									{
										if(strlen($this->vault->getItems($args[1])) >= 5) //to be sure, x:x:x (5 chars)
										{
											$this->vault->openCloud($sender, $args[1]);
										} else {
											$sender->sendMessage("§l§7[§e!§7]§f". strtoupper($args[1]). "'s storage is empty..");
										}
									} else {
										if(isset($args[2]))
										{
											if($this->vault->verifyCode($args[1], $args[2]))
											{
												if(strlen($this->vault->getItems($args[1])) >= 5) //to be sure, x:x:x (5 chars)
												{
													$this->vault->openCloud($sender, $args[1]);
												} else {
													$sender->sendMessage("§l§7[§e!§7]§f". strtoupper($args[1]). "'s storage is empty..");
												}
											} else {
												$sender->sendMessage("§l§7[§c!§7] §fYour code [". $args[2]. "] seems invalid, contact a staff/the owner if you forgot your code");
											}
										} else {
											$sender->sendMessage("§l§7[§c!§7] §fUsage /cloud download [vaultId] [shareCode]");
										}
									}
								} else {
									$sender->sendMessage("§l§7[§6!§7] §f". $args[1]. " doesn't exist, did you typed it precisely? tip: VaultID and ShareCode are both case senstive.");
								}
							} else {
								$sender->sendMessage("§l§7[§6!§7] §fUsage /cloud download [vaultId] [shareCode]");
							}
						break;
							
						default:
							$sender->sendMessage("§l§7[§6!§7] §fCloud storage cmds: register[reg], upload[up], download[dl], upgrade[+], newcode[new], delete[del]");
							$sender->sendMessage("§fRegister - §7new cloud storage");
							$sender->sendMessage("§fUpload - §7an item to the storage");
							$sender->sendMessage("§fDownload - §7a specific cloud storage");
							$sender->sendMessage("§fUpgrade - §7a cloud storage max slot");
							$sender->sendMessage("§fNewcode - §7gives you a new share-code [soon]");
							$sender->sendMessage("§fDelete - §7delete the entire storage [soon]");
					}
				}
				return true;
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

	public function getTop($amount) : string //by @PTKDrake
	{
		$result = $this->db->query("SELECT * FROM lvl ORDER BY level DESC;");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		arsort($resultArr);
		$all = [];
		$i = 1;
		$max = count($all) / 5;
		$page = (int)min($this->max, max(1, $amount));
		foreach($resultArr as $name => $level){
			$current = (int) ceil($n / 5);
			if($current === $page){
				$all[$i] = [$name, $point];
			}elseif($current > $page){
				break;
			}
			$i += 1;
		}
		$string = "§l§bTop Point §e[".$page."/".$max."]\n";
		foreach($all as $i => $data){
			$string .= "§l".$i." > §6".$data[0]." §fLv. ".$data[1]." \n";
		}
		$string .= "§6Type: /toplvl ". ($page + 1). " to see next page";
		return $string;
	}
}
