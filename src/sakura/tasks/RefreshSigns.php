<?php

namespace sakura\tasks;

use pocketmine\scheduler\Task;

class RefreshSigns extends Task
{
  
  	public $signManager;
	
  	public function onRun(int $tick)
	{
		$this->signManager()->reloadSigns();
	}
	
  	//public function onSuccess(): void { $this->core->getScheduler()->cancelTask($this->getTaskId()); }
	
}
