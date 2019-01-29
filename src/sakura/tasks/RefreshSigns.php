<?php

namespace sakura\tasks;

use sakura\core;
use pocketmine\Server;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\tile\Sign;
use pocketmine\scheduler\Task;

use pocketmine\utils\TextFormat;

class ProcessSigns extends Task
{
  
  	private $core, $topLevelSigns = [], $topEloSigns = [];
  
	public function __construct(core $core)
	{
		$this->core = $core;
	}
	
  public function onRun(int $tick)
	{
		$this->reloadSigns();
	}
  public function onSuccess(): void { $this->core->getScheduler()->cancelTask($this->getTaskId()); }
	
}
