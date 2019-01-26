<?php

namespace sakura;

use pocketmine\scheduler\Task;

class sendFT extends Task
{
  
  private $core, $worlds;
  
	public function __construct(Newspaper $core, array $worlds)
  {
	  $this->core = $core;
    $this->worlds = $worlds;
	}
  
	public function onRun(int $currentTick)
  {
		foreach($this->worlds as $world => $coords)
    {
      $xyzv = explode(":", $coords);
      $v3 = new Vector3((float) $xyzv[0],(float) $xyzv[1],(float) $xyzv[2]);
      $type = $xyzv[3];
      $this->addText($v3, $type, $world);
    } 
	}
  
  //$world = [];
  private function addText(Vector3 $location, string $type, string $world, $player = null) : void
  {
    switch ($this->getServer()->getName()) {
			case 'PocketMine-MP':
				$particle = new FloatingTextParticle($location, color::GOLD . "<<<<<>>>>>", $this->getType($type));
				$this->getServer()->getLevelByName($world)->addParticle($particle, $player);
				break;
			case 'Altay':
				$particle = new FloatingTextParticle(color::GOLD . "<<<<<>>>>>", $this->getType($type), $location);
				$this->getServer()->getLevelByName($world)->addParticle($location, $particle);
				break;
		}
  }
  
  private getType(string $type) : string
  {
    switch($type)
    {
      case "elo":
        return "test";
      break;
      default:
        return "test";
    }
  }
}
