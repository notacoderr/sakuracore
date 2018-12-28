<?php
namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class calculateExp
{

	public $main;
  
	public function __construct(core $core)
	{
		$this->main = $core;
	}

	function doMagic(Player $player, $expe) : string
	{
  
	$base = (int) $this->main->settings->get("baseExp");
	$plevel = (int) $this->main->data->getVal($player, "level");
    $multi = (float) ($this->main->data->getVal($player, "multiplier") / 100);
    $bonus = (int) ($experience * $multi)
    $experience = (int) ($expe + $bonus);
    
		$goal = $base * $plevel;
		if ($experience >= $goal)
		{
			$extra = $experience - $goal;
			$Ngoal = $goal + $base;
			$i = 0;
      
			do
			{
				$i += 1;
				if ($extra >= $Ngoal)
				{
					$extra = $extra - $Ngoal;
				}
			}
			while ($extra >= $Ngoal);
      
			$f = $plevel + $i;
			$this->main->data->addVal($player, "level", $plevel + $i);
      
			$player->addTitle("§l§fLevel UP §7[§6 $f §7]", "§fNext Level on §7[§f $extra §7/§d $Ngoal §7");
      
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO exp (name, exp) VALUES (:name, :exp);");
			$stmt->bindValue(":name", $player->getName() );
			$stmt->bindValue(":exp", $extra);
			$result = $stmt->execute();
		}
		$message = "§fYou received §7". $expe. " §f+§7 ". $experience. " (". $multi. "% bonus)";
		return $message;
	}

}
