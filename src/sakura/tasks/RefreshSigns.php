<?php

namespace sakura\tasks;

use pocketmine\Server;
use pocketmine\scheduler\Task;

class RefreshSigns extends Task
{
  
  	private $processSigns;
	
  	public function onRun(int $tick)
	{
		$this->processSigns()->reloadSigns();
	}
	
  	//public function onSuccess(): void { $this->core->getScheduler()->cancelTask($this->getTaskId()); }
	
}
