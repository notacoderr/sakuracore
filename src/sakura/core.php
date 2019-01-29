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
use sakura\tasks\ProcessSigns;
use pocketmine\inventory\Inventory;use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};

class core extends PluginBase implements Listener {
	
	public $db, $topsigns = [], $signinterval;
	
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
		$this->db->exec("CREATE TABLE IF NOT EXISTS exp (name TEXT PRIMARY KEY COLLATE NOCASE, exp INT, multiplier INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS lvl (name TEXT PRIMARY KEY COLLATE NOCASE, level INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS titles (name TEXT PRIMARY KEY COLLATE NOCASE, titles BLOB, inuse BLOB);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS elo (name TEXT PRIMARY KEY COLLATE NOCASE, rank TEXT, div INT, points INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS guild (guild TEXT PRIMARY KEY COLLATE NOCASE, founder INT);");
		//$this->db->exec("CREATE TABLE IF NOT EXISTS member (name TEXT PRIMARY KEY COLLATE NOCASE, guild INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS pquests (name TEXT PRIMARY KEY COLLATE NOCASE, quest TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS pcompleted (name TEXT PRIMARY KEY COLLATE NOCASE, quests TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS classes (name TEXT PRIMARY KEY COLLATE NOCASE, class INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS vault (name TEXT PRIMARY KEY COLLATE NOCASE, items BLOB, max INT, code TEXT, owner TEXT);");
		try{
			$this->db->exec("ALTER TABLE vault ADD COLUMN code TEXT default null");
			$this->db->exec("ALTER TABLE vault ADD COLUMN owner TEXT default null");
			$this->db->exec("ALTER TABLE exp ADD COLUMN multiplier INT default 0");
		    	$this->getLogger()->info("Wudafak");
		}catch(\ErrorException $ex){ }

		$this->calculate = new calculateExp($this); //Calcu handler
		$this->classes = new Classes($this); //Class Handler
		#$this->quests = new Quests($this); //Quests Handler
		$this->titles = new Titles($this); //Titles Handler
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
		
		if($this->settings->get('top-signs-enabled?'))
		{
			$signs = $this->settings->getNested("top-signs");
			$this->refresher = new ProcessSigns($this);
			$this->refresher->registerSigns($signs);
			$$this->signinterval = (int) $this->settings->getNested("top-signs-interval") * 1200;
			#$this->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), $time);
		}
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
				if (count($args) <> 3)
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
						$this->calculate->doMagic($target, $args[2]);
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

			case "mytitles":
				if ($sender instanceof Player)
				{
					$this->titles->sendForm($sender);
				}
			break;

			case "givetitle":
				if ($sender instanceof Player)
				{
					if($sender->isOp())
					{
						if(($target = $this->getServer()->getPlayer($args[0])) instanceof Player)
						{
							unset($args[0]);
							$title = implode(" ", $args);
							$this->titles->addTitle($target, $title);
							$t = TextFormat::BOLD. $title;
							$s = TextFormat::AQUA. "You have earned a new [Title]";
							$target->addTitle($t, $s);
							return true;
						} else {
							$sender->sendMessage("Player must be online");
							return true;
						}
					} else {
						return true;
					}
				} else {
					if(($target = $this->getServer()->getPlayer($args[0])) instanceof Player)
					{
						unset($args[0]);
						$title = implode(" ", $args);
						$this->titles->addTitle($target, $title);
						return true;
					} else {
						$sender->sendMessage("Player must be online");
						return true;
					}
				}
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
				if($sender->isCreative())
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
											$hand = $sender->getInventory()->getItemInHand();
											if($hand->getId() !== Item::AIR)
											{
												$ench = "no_enchantment";
												if($hand->hasEnchantments())
												{
													$ar = [];
													foreach($hand->getEnchantments() as $enchantment)
													{
														$ar[] = ((int) $enchantment->getId()). "x". ((int) $enchantment->getLevel());
													}
													$ench = implode("_", $ar);
												}
												$this->vault->addItem($args[1], $hand->getId(), $hand->getDamage(), $hand->getCount(), $hand->getName() , $ench);
												$sender->sendMessage("§l§7[§a!§7]§f Your item was uploaded in the storage!");
												$sender->getInventory()->setItemInHand( Item::get(0) );
												$sl = $this->vault->countItems( $args[1] );
												$mx = $this->vault->getMax( $args[1] );
												$sender->sendMessage("§l§f". $args[1]. "'s slot: §7[§f $sl / $mx §7]");
											} else {
												$sender->sendTip("§6§lPlease hold an item..");
											}
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

						default:

						$sender->sendMessage("§l§7[§6!§7] §fCloud storage cmds:");

						$sender->sendMessage("§f/cloud - §7boot up cloud portal");

						$sender->sendMessage("§f/cloud upload[up] - §7send an item into a vault");

					}

				} else {

					$this->vault->homepage($sender);

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
			case "+i":
				/**
				*Args 0 = player name
				*Args 1 = item serial
				**/
				if (count($args) <> 2)
				{
					$sender->sendMessage("Invalid usage, /+i <playername> <serial>");
					return true;
				}
				if(!array_key_exists($args[1], $this->itemData->getAll()))
				{
					$sender->sendMessage("Serial: " .$args[1]. " does not exist");
					return true;
				}
				if(!($target = $this->getServer()->getPlayer($args[0])) instanceof Player)
				{
					$sender->sendMessage("Failed to produce item: ". $args[1]. ", Player is not available");
					return true;
				}
				$this->items->produceItem($target, $args[1]);
				/*if($this->hasSpace($target) == false)
				{
					$target->sendMessage("No slot available");
					return true;
				}
				$product = $this->items->createItem( $args[1] );
				$target->getInventory()->addItem( $product );
				*/
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
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO exp (name, exp, multiplier) VALUES (:name, :exp, :multiplier);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":exp", 0);
		$stmt->bindValue(":multiplier", 0);
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
		$stmt->bindValue(":rank", "Iron");
		$stmt->bindValue(":div", 5);
		$stmt->bindValue(":points", 0);
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO classes (name, class) VALUES (:name, :class);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":class", "Recruit");
		$result = $stmt->execute();
		
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO titles (name, titles, inuse) VALUES (:name, :titles, :inuse);");
		$stmt->bindValue(":name", $player->getName() );
		$stmt->bindValue(":titles", "§7§lI Love Sakura");
		$stmt->bindValue(":inuse", "§7§l_Beginner_");
		$result = $stmt->execute();
	}

		

	public function rac(Player $player, string $string) : void
	{
		$command = str_replace("{player}", $player->getName(), $string);
		Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $command);
	}

	public function isRecorded(Player $player) : bool
	{
		$name = $player->getName();
		$result = $this->db->query("SELECT * FROM lvl WHERE name = '$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}

	public function getTopBy(string $type) : string
	{
		$i = 0;
		$string = "";
		switch($type)
		{
			case "level":
				$result = $this->db->query("SELECT * FROM lvl ORDER BY level DESC LIMIT 10;");
				while ($resultArr = $result->fetchArray(SQLITE3_ASSOC))
				{
					$name = $resultArr['name'];
					$level = $resultArr['level'];
					$num = $i + 1;
					$string .= "§6§l{$num} > §r§c{$name} §fLv:§7{$level} \n";
					$i = $i + 1;
				}
			break;
			case "elo":
				$result = $this->db->query("SELECT * FROM elo ORDER BY points DESC LIMIT 10;");
				while ($resultArr = $result->fetchArray(SQLITE3_ASSOC))
				{
					if($resultArr['rank'] == "Plutonium")
					{
						$name = $resultArr['name'];
						$points = $resultArr['points'];
						$num = $i + 1;
						$string .= "§6§l{$num} > §r§c{$name} §f- §7{$points} \n";
						$i = $i + 1;
					}
				}
			break;
		}
		return $string;
	}
}
