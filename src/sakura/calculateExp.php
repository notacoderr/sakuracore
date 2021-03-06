<?php
namespace sakura;

use sakura\core;
use pocketmine\Player;

class calculateExp
{

	public $main;
  
	public function __construct(core $core)
	{
		$this->main = $core;
	}

	function doMagic(Player $player, int $expe) : void
	{
		$base = (int) $this->main->settings->get("baseExp");
		$oldExp = (int) $this->main->data->getVal($player, "exp");
		$plevel = (int) $this->main->data->getVal($player, "level");
		$multi = (int) $this->main->data->getVal($player, "multiplier");
		$multi2 = (double) ($multi / 100);
		$bonus = (int) ($expe * $multi2);
		$newExp = (int) ($bonus + $expe);
		$experience = (int) ($newExp + $oldExp);
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
      
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO exp (name, exp, multiplier) VALUES (:name, :exp, :multiplier);");
			$stmt->bindValue(":name", $player->getName() );
			$stmt->bindValue(":exp", $extra);
			$stmt->bindValue(":multiplier", $multi);
			$result = $stmt->execute();
			if (($pc = $this->main->getServer()->getPluginManager()->getPlugin("PureChat")) != null)
			{
				$player->setNameTag($pc->getNameTag($player));
			}

		} else {
			$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO exp (name, exp, multiplier) VALUES (:name, :exp, :multiplier);");
			$stmt->bindValue(":name", $player->getName() );
			$stmt->bindValue(":exp", $experience);
			$stmt->bindValue(":multiplier", $multi);
			$result = $stmt->execute();
		}
		$player->sendTip("§a+ ". $newExp. " §7exp");
		$player->sendPopup("§f". $expe. " §7+§f ". $bonus. " §7(§f". $multi. "% bonus§7)");
	}

}
