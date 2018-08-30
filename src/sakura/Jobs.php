<?php

namespace sakura;

use sakura\core;

use pocketmine\Player;
use pocketmine\Server;

class Jobs
{
  
  public $main;
  
	public function __construct(core $core)
  {
        $this->main = $core;
  }
  
}
