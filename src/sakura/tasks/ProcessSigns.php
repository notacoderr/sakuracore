<?php

namespace sakura\tasks;

use sakura\core;
use pocketmine\Server;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use sakura\tasks\RefreshSigns;
use pocketmine\utils\TextFormat;

class ProcessSigns
{
  
  	private $core, $topLevelSigns = [], $topEloSigns = [], $engine;
  
	public function __construct(core $core)
	{
		$this->core = $core;
		$this->startEngine();
	}
	
	private function startEngine()
	{
		$task = new RefreshSigns( $this->core );
		$task->signManager = $this;
		$this->core->getServer()->getScheduler()->scheduleRepeatingTask($task, $this->core->signinterval);
	}
	
	public function registerSigns(array $initSigns)
	{
		//foreach($this->core->topsigns as list($world, $coords))
		foreach($initSigns as list($world, $coords))
    		{
			$xyzv = explode(":", $coords);
			Server::getInstance()->loadLevel($world);
		      	#$this->addText($pos, $xyzv[3], $world); //(position , type, world)
			if(($level = $this->core->getServer()->getLevelByName($world)) != null)
			{
				$pos = new Position($xyzv[0], $xyzv[1], $xyzv[2], $world);
				$tile = $level->getTile($position);
				if($tile != null && $tile instanceof Sign)
				{
					switch((string) $xyzv[3])
					{
						case "level":
							array_push($this->topLevelSigns, $tile);
						break;
						case "elo":
							array_push($this->topEloSigns, $tile);
						break;
					}
					continue;
				}
			}
		}
	}
	
	private function reloadSigns()
	{
		foreach ($this->topLevelSigns as $signTile)
		{
			if($signTile->level != null)
			{
				$signTile->setText(
					"-+=Top 10 Player Levels=+-",
					$this->core->getTopBy("level", 1, 5),
					$this->core->getTopBy("level", 6, 10),
					"[]-+===+-+===+[]+===+-+===+-[]"
				);
			}
		}
		foreach ($this->topEloSigns as $signTile)
		{
			if($signTile->level != null)
			{
				$signTile->setText(
					"-+=Top 10 Plutonium Players=+-",
					$this->core->getTopBy("elo", 1, 5),
					$this->core->getTopBy("elo", 6, 10),
					"[]-+===+-+===+[]+===+-+===+-[]"
				);
			}
		}
	}
}
