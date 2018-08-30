
<?php
namespace sakura;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\scheduler\PluginTask;

use pocketmine\Server;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

use pocketmine\level\level;
use pocketmine\level\particle\{HugeExplodeParticle, HappyVillagerParticle, AngryVillagerParticle};

class core extends PluginBase implements Listener {

	public $db;

	public function onEnable()
	{	
		$this->saveResource('settings.yml');
		$this->settings = new Config($this->getDataFolder() . "settings.yml", CONFIG::YAML);
		
		$this->db = new \SQLite3($this->getDataFolder() . "01100100-01100001-01110100-01100001.db"); //creating main database
		$this->db->exec("CREATE TABLE IF NOT EXISTS system (name TEXT PRIMARY KEY COLLATE NOCASE, level INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS xp (name TEXT PRIMARY KEY COLLATE NOCASE, exp INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS rp (name TEXT PRIMARY KEY COLLATE NOCASE, respect INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS d (name TEXT PRIMARY KEY COLLATE NOCASE, div INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS g (name TEXT PRIMARY KEY COLLATE NOCASE, gems INT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS r (name TEXT PRIMARY KEY COLLATE NOCASE, rank TEXT);");
		$this->db->exec("CREATE TABLE IF NOT EXISTS t (name TEXT PRIMARY KEY COLLATE NOCASE, type TEXT);");
		//$this->getServer()->getPluginManager()->registerEvents(new ev($this), $this);
		//$this->command = new cmds($this); //Commands
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) :bool 
	{	
		switch (strtolower( $command->getName() ))
		{
			case 'sys': case 'system': case '+':
				if ($sender instanceof Player)
				{
					$sender->sendMessage("Â§cCan only be used it Â§fÂ§lCONSOLE");
					return true;
				}
				if (count($args) > 3 or count($args) < 3)
				{
					$sender->sendMessage("Invalid usage, /system <playername> <+exp/+gems/+respect> <amount>");
					return true;
				}
				if(isset($args[0])){  //realc
					$target = $args[0];
					$target2 = $this->getServer()->getPlayer($args[0]);
					if($target2 instanceof Player){
						$target = $target2->getName();
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
					case "exp": case "exp": case "e":
						$this->addVal(strtolower( $target ), "exp", $args[2]);
					break;
					case "gems": case "gem": case "g":
						$this->addVal(strtolower( $target ), "gems", $args[2]);
					break;
					case "rp": case "respect": case "rp":
						$this->addVal(strtolower( $target ), "respect", $args[2]);
					break;
				}
				$sender->sendMessage("added $args[2] of $args[1] to ".$target);
			break;
			case "changetype": case "ct":
				if ($sender instanceof Player)
				{
					$sender->sendMessage("Â§cCan only be used it Â§fÂ§lCONSOLE");
					return true;
				}
				if (count($args) > 2 or count($args) < 2)
				{
					$sender->sendMessage("Invalid usage, /changetype <playername> <standard/vip/vip+>");
					return true;
				}
				if(isset($args[0])){  //realc
					$noob = $args[0];
					$noob2 = $this->getServer()->getPlayer($args[0]);
					if($noob2 instanceof Player){
						$noob = $noob2->getName();
					  }
				}
				if(!$this->isRecorded($noob)){
					  $sender->sendMessage("No Record found for $noob");
					  return true;
				}
				$pt = $this->getVal($noob, "type");
				switch (strtolower($args[1])){
					case "vip":
						if ($pt == "vip"){ 
							$sender->sendMessage("$noob is already a VIP");
							return true;
						} 	
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO t (name, type) VALUES (:name, :type);");
						$stmt->bindValue(":name", $noob);
						$stmt->bindValue(":type", 'vip');
						$result = $stmt->execute();
						if ($this->getServer()->getPlayer($noob) instanceof Player)
							{ $this->getServer()->getPlayer($noob)->sendMessage("Â§lÂ§aYour account is now VIP"); }
						return true;
					break;

					case "vip+":
						if ($pt == "vip+"){ 
							$sender->sendMessage("$noob is already a VIP+");
							return true;
						}
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO t (name, type) VALUES (:name, :type);");
						$stmt->bindValue(":name", $noob);
						$stmt->bindValue(":type", 'vvip');
						$result = $stmt->execute();
						if ($this->getServer()->getPlayer($noob) instanceof Player)
							{ $this->getServer()->getPlayer($noob)->sendMessage("Â§lÂ§aYour account is now VIP+"); }
						return true;
						return true;
					break;

					case "standard":
						if ($pt == "standard"){ 
							$sender->sendMessage("$noob is already a standard");
							return true;
						}
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO t (name, type) VALUES (:name, :type);");
						$stmt->bindValue(":name", $noob);
						$stmt->bindValue(":type", 'standard');
						$result = $stmt->execute();
						if ($this->getServer()->getPlayer($noob) instanceof Player)
							{ $this->getServer()->getPlayer($noob)->sendMessage("Â§lÂ§aYour account is now Standard"); }
						return true;
					break;
					default: return true;
				}
			break;
			case "t":
				$sender->sendMessage($this->getTopBy("HEROIC", 5, 3));
				return true;
			break;
		}
		return true;
	}
	
	public function onJoin(PlayerJoinEvent $event) 
	{
		$player = $event->getPlayer();
		$n = strtolower($player->getName());
		if (!$this->isRecorded($n)){
			return $this->register($player);
		} 
		
	}

	public function register($player)
	{
		//$player = $event->getPlayer();
			$name = strtolower( $player->getName() );
		if ($this->isRecorded($name) == false){

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO system (name, level) VALUES (:name, :level);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":level", '1');
			$result = $stmt->execute();

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO xp (name, exp) VALUES (:name, :exp);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":exp", '0');
			$result = $stmt->execute();

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":respect", '0');
			$result = $stmt->execute();

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO d (name, div) VALUES (:name, :div);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":div", '3');
			$result = $stmt->execute();

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO g (name, gems) VALUES (:name, :gems);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":gems", '35'); //free gems
			$result = $stmt->execute();

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO r (name, rank) VALUES (:name, :rank);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":rank", 'HEROIC');
			$result = $stmt->execute();
			/*/ Heroic - Disciple - Rampage - Ascended - Godlike /*/ 

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO t (name, type) VALUES (:name, :type);");
			$stmt->bindValue(":name", $name);
			$stmt->bindValue(":type", 'standard');
			$result = $stmt->execute();
			//$player->sendMessage("Â§lYour new Data has been generated... run /profile <yourname>");  
			if ($this->settings->get("run-command-on-first-join") == true){
				return $this->rac($name, $this->settings->get("command-on-first-join"));
			}
		}
	}
	public function rac($t, $cmd)
	{
		$cmd = str_replace("{player}", strtolower($t), $cmd);
		Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $cmd);
	}
	/**
	@Callable functions
	**/
	public function getVal($n, $val)
	{
		$n = strtolower($n);
		switch ($val) 
		{
			case 'level':
				$result = $this->db->query("SELECT * FROM system WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "level" ];
			break;
					
			case 'exp':
				$result = $this->db->query("SELECT * FROM xp WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "exp" ];
			break;
				
			case 'respect':
				$result = $this->db->query("SELECT * FROM rp WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "respect" ];
			break;

			case 'rank':
				$result = $this->db->query("SELECT * FROM r WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "rank" ];
			break;

			case 'div':
				$result = $this->db->query("SELECT * FROM d WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "div" ];
			break;

			case 'gems':
				$result = $this->db->query("SELECT * FROM g WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "gems" ];
			break;

			case 'type':
				$result = $this->db->query("SELECT * FROM t WHERE name = '$n';");
				$resultArr = $result->fetchArray(SQLITE3_ASSOC);
				return $resultArr[ "type" ];
			break;
		}
    }

	function testLevel($n, $xp)
	{
		$base = $this->settings->get("starting-goal-EXP"); //base EXP				132
		$plevel = $this->getVal($n, "level");//Player LEVEL							1
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
			$this->addVal($n, "level", $plevel + $i);
			$coder = $this->getServer()->getPlayer($n);
			$coder->addTitle("Â§lÂ§fLevel UP Â§7[Â§6 $f Â§7]", "Â§fNext Level on Â§7[Â§f $extra Â§7/Â§d $Ngoal Â§7");

			$stmt = $this->db->prepare("INSERT OR REPLACE INTO xp (name, exp) VALUES (:name, :exp);");
			$stmt->bindValue(":name", $n);
			$stmt->bindValue(":exp", $extra);
			$result = $stmt->execute();

			return true;
		}


	}
	public function addVal(string $n, $val,int $add)
	{
		$name = strtolower($n);
		switch ($val)
		{
		case 'level':
			//$f = $this->getVal($name, "level") + 1;
				$stmt = $this->db->prepare("INSERT OR REPLACE INTO system (name, level) VALUES (:name, :level);");
					$stmt->bindValue(":name", $name);
						$stmt->bindValue(":level", $add);
							$result = $stmt->execute();
		break;
		case 'exp':
			$f = $this->getVal($name, "exp") + $add;
				$stmt = $this->db->prepare("INSERT OR REPLACE INTO xp (name, exp) VALUES (:name, :exp);");
					$stmt->bindValue(":name", $name);
						$stmt->bindValue(":exp", $f);
							$result = $stmt->execute();
								$this->testLevel($name, $f);
									$this->Alert($name, 1, $add);
		break;
		case 'respect':
			$f = $this->getVal($name, "respect") + $add;
				$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $name);
						$stmt->bindValue(":respect", $f);
							$result = $stmt->execute();
								$this->checkRank($name, $f);
								if ($add > 0){ 
									return $this->Alert($name, 3, $add);
								} else { 
									return $this->Alert($name, 4, $add); 
								}
		break;
		case 'gems':
			$f = $this->getVal($name, "gems") + $add;
				$stmt = $this->db->prepare("INSERT OR REPLACE INTO g (name, gems) VALUES (:name, :gems);");
					$stmt->bindValue(":name", $name);
						$stmt->bindValue(":gems", $f );
							$result = $stmt->execute();
								if ($add > 0){ 
									return $this->Alert($name, 7, $add);
								} else { 
									return $this->Alert($name, 8, $add);
								}
		break;
		default: 
			return;
		}
    }
	
	public function newRank($n, $r)
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO r (name, rank) VALUES (:name, :rank);");
		$stmt->bindValue(":name", strtolower($n));
		$stmt->bindValue(":rank", $r );
		$result = $stmt->execute();
	}
	
	private function newDiv($n,int $div)
	{
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO d (name, div) VALUES (:name, :div);");
		$stmt->bindValue(":name", $n);
		$stmt->bindValue(":div", $div);
		$result = $stmt->execute();
	}
	
	function mercy() : bool
	{
		return (4 <= mt_rand(1, 10));
	}
	
	private function checkRank($n,int $rp)
	{
		/*/
			Heroic - Disciple - Rampage - Ascended - Godlike
			if ($rp < 100) return true; // Checks if the respect point is greater less than the goal, if true, exit
			type = v demote, type = ^ promote;
			l = low elo (III divisions), h = high (V divisions)
		/*/
		if((1 <= $rp) and ($rp <= 99)) return true;
		$r = strtolower( $this->getVal($n, "rank") );
		$d = $this->getVal($n, "div");
		$coder = $this->getServer()->getPlayer($n);
		switch($r)
		{
			case "heroic":
				$pro = "DISCIPLE";
			break;
			case "disciple":
				$dem = "HEROIC"; $pro = "RAMPAGE";
			break;
			case "rampage":
				$dem = "DISCIPLE"; $pro = "ASCENDED";
			break;
			case "ascended":
				$dem = "RAMPAGE"; $pro = "GODLIKE";
			break;
			case "godlike":
				$dem = "ASCENDED";
			break;
		}

		switch ( $d )
		{
			case 1:
				if($rp >= 100)
				{
					if ($r == "godlike")
					{
						return true;
					}
					if ($r == "ascended")
					{
						$this->newRank($n, $pro); //GODLIKE
						$this->newDiv($n, 5);// 5
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 0);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".$pro." 5", "Â§aYou have been promoted");
						return true;
					}
					if ($r == "rampage")
					{
						$this->newRank($n, $pro); //ASCENDED
						$this->newDiv($n, 5); //5
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 0);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".$pro." 5", "Â§aYou have been promoted");
						//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$pro." 5{SUBTITLE}Â§aYou have been promoted");
						return true;
					}
					else
					{
						$this->newDiv($n, 3);
						$this->newRank($n, $pro);
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 0);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".$pro." 3", "Â§aYou have been promoted");
						//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$pro." 3{SUBTITLE}Â§aYou have been promoted");
						return true;
					}

				} 
				else
				{
					if($this->mercy())
						{
							Server::getInstance()->getPlayer($n)->sendMessage("â€¢Â§6 You have avoided Demotion");
							return true;
						}
					$this->newDiv($n, 2);
					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 85);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 2", "Â§cYou have been demoted");
				}
			break;
			case 3:
				if($rp <= 0) // demote
				{

					if ($r == "heroic")
					{
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 0);
						$result = $stmt->execute();
						return true;
					}
					if($this->mercy())
					{
						Server::getInstance()->getPlayer($n)->sendMessage("â€¢Â§6 You have avoided Demotion");
						return true;
					}
					if ($r == "godlike" or $r == "ascended")
					{
						$this->newDiv($n, 4);
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 85);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".strtoupper($r)." 4", "Â§cYou have been demoted");
						return true;
					}
					else
					{
						$this->newDiv($n, 1);
						$this->newRank($n, $dem);
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 85);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".$dem." 1", "Â§cYou have been demoted");
						//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$dem." 1{SUBTITLE}Â§cYou have been demoted");
						return true;
					}
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$dem." ".$nd."{SUBTITLE}Â§cYou have been demoted");
				} 
				else // promote
				{
					//$this->newRank($n, $pro);
					$this->newDiv($n, 2);

					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 0);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 2", "Â§aYou have been promoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 2{SUBTITLE}Â§aYou have been promoted");
				}
			break;

			case 5:
				if($rp <= 0) //demote
				{
					if($this->mercy())
					{
						Server::getInstance()->getPlayer($n)->sendMessage("â€¢Â§6 You have avoided Demotion");
						return true;
					}
					if ($r == "ascended")
					{
						$this->newDiv($n, 1); //RAMPAGE 1
						$this->newRank($n, $dem);
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 85);
						$result = $stmt->execute();	
						$coder->addTitle("Â§l".$dem." 1", "Â§cYou have been demoted");
						//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$dem." 1{SUBTITLE}Â§cYou have been demoted");
						return true;
					}
					if ($r == "godlike")
					{
						$this->newDiv($n, 1); //1
						$this->newRank($n, $dem); //ASCENDED
						$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
						$stmt->bindValue(":name", $n);
						$stmt->bindValue(":respect", 85);
						$result = $stmt->execute();
						$coder->addTitle("Â§l".$dem." 1", "Â§cYou have been demoted");
						//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".$dem." 1{SUBTITLE}Â§cYou have been demoted");
						return true;
					}
					else
					{
						return true;
					}
				} 
				else
				{
					$this->newDiv($n, 4);

					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 0);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 4", "Â§aYou have been promoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 4{SUBTITLE}Â§aYou have been promoted");
				}
			break;

			case 4:
				if($rp <= 0) // demote
				{
					if($this->mercy())
					{
						Server::getInstance()->getPlayer($n)->sendMessage("â€¢Â§6 You have avoided Demotion");
						return true;
					}
					$this->newDiv($n, 5);
					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 85);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 5", "Â§cYou have been demoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 5{SUBTITLE}Â§cYou have been demoted");
				}
				else
				{
					$this->newDiv($n, 3);
					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 0);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 3", "Â§aYou have been promoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 3{SUBTITLE}Â§aYou have been promoted");
				}
			break;

			case 2:
				if($rp <= 0) // demote
				{
					if($this->mercy())
					{
						Server::getInstance()->getPlayer($n)->sendMessage("â€¢Â§6 You have avoided Demotion");
						return true;
					}
					$this->newDiv($n, 3);
					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 85);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 3", "Â§cYou have been demoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 3{SUBTITLE}Â§cYou have been demoted");
				
				}
				else
				{
					$this->newDiv($n, 1);
					$stmt = $this->db->prepare("INSERT OR REPLACE INTO rp (name, respect) VALUES (:name, :respect);");
					$stmt->bindValue(":name", $n);
					$stmt->bindValue(":respect", 0);
					$result = $stmt->execute();
					$coder->addTitle("Â§l".strtoupper($r)." 1", "Â§aYou have been promoted");
					//Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), "st ".$n." Â§l".." 1{SUBTITLE}Â§aYou have been promoted");
				
				}
			break;
		}
	}
	
	public function isRecorded($player)
	{
		$player = strtolower($player);
		$result = $this->db->query("SELECT * FROM system WHERE name='$player';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}

	function Alert($n, $type, $extra)
	{
		$p = $this->getServer()->getPlayer($n);
		if (!$p instanceof Player){
			return true;
		}
		switch($type)
		{
			case "1":
				return $p->sendMessage("â€¢> Â§lÂ§a+ $extra Exp");
				$p->getLevel()->addParticle(new HappyVillagerParticle($p->add(0, 2), 4));
			break;
			case "2":
				return $p->sendMessage("â€¢> Â§lÂ§c $extra Gem");
				$p->getLevel()->addParticle(new HappyVillagerParticle($p->add(0, 2), 4));
			break;
			case "3":
				return $p->sendMessage("â€¢> Â§lÂ§f $extra Respect");
				$p->getLevel()->addParticle(new HappyVillagerParticle($p->add(0, 2), 4));
			break;
			case "4":
				$p->sendMessage("â€¢> Â§lÂ§c $extra Respect");
				$p->getLevel()->addParticle(new AngryVillagerParticle($p->add(0, 2), 4));
			break;
			case "5":
				$p->sendMessage("â€¢> Â§lÂ§aYou have been promoted to Â§f". strtoupper($extra) ."Â§a Division");
				$p->getLevel()->addParticle(new HugeExplodeParticle($p->add(0, 2), 4));
			break;
			case "6":
				$p->sendMessage("â€¢> Â§lÂ§cYou have been demoted to Â§f". strtoupper($extra) ."Â§c Division");
				$p->getLevel()->addParticle(new AngryVillagerParticle($p->add(0, 2), 4));
			break;
			case "7":
				$p->addTitle("Â§lÂ§a+ $extra Gems");
				$p->getLevel()->addParticle(new HappyVillagerParticle($p->add(0, 2), 4));
			break;
			case "8":
				$p->sendMessage("â€¢> Â§lÂ§c -$extra Gems");
				$p->getLevel()->addParticle(new AngryVillagerParticle($p->add(0, 2), 4));
			break;
		}
	}

	public function getTopBy($target, $amount, $div = null) : string
	{
		switch(strtoupper($target))
		{
			case "LEVEL":
				$string = "";
				$result = $this->db->query("SELECT * FROM system ORDER BY level DESC LIMIT $amount;");
				$i = 0;
        		while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
					$j = $i + 1;
					$name = strtoupper($resultArr['name']);
					$exp = $this->getVal($name, "exp");
					$lvl = $this->getVal($name, "level");
					$string .= ("Â§l$j â€¢> Â§6$name Â§fLv. $lvl | XP : $exp Â§r\n\n");
					$i += 1;
				}
				return $string;
			break;
			default:
				$string = "";
				$result = $this->db->query("SELECT * FROM rp ORDER BY respect DESC;");
				$i = 0;
				while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
						$name = $resultArr["name"];
						$rp = $resultArr["respect"];
						$rank = $this->getVal((string) $name, "rank");
						$d = $this->getVal((string) $name, "div");
						$j = $i + 1;
						if(strtoupper($rank) == strtoupper($target) && $i < $amount && $div == $d) {
							$lvl = $this->getVal((string) $name, "level");
							$string .= "Â§l$j â€¢> Â§6".strtoupper($name)."Â§f[Â§d".$lvl."Â§f]Â§0 |Â§f Respect :Â§d $rp Â§r\n\n";
							$i += 1;
						}
						next($resultArr);
				}
			return $string;
		}
	}
}
