<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\item\Item;

class Items
{
  
  	public $main;
 
    public function __construct(core $core)
    {
        $this->main = $core;
    }
}
