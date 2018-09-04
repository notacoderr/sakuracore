<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;


class ItemStore
{
  
    public $main;
	
    public function __construct(core $core)
    {
        $this->main = $core;
    }
    
    public function openStore(){
    }
  
}
