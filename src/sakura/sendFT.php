<?php

namespace sakura;

use sakura\core;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\scheduler\Task;

class sendFT extends Task
{
  
  private $core;
  
	public function __construct(core $core) { $this->core = $core; }  
	public function onRun(int $tick) { $this->reloadFT(); }
  	private function onSuccess(): void { $this->core->getScheduler()->cancelTask($this->getTaskId()); }
	private function reloadFT()
	{
		foreach($this->core->ftworlds as list($world, $coords))
    		{
		      $xyzv = explode(":", $coords);
		      $v3 = new Vector3((float) $xyzv[0],(float) $xyzv[1],(float) $xyzv[2]);
		      $type = $xyzv[3];
		      $this->addText($v3, $type, $world);
    		} 
	}
	
	public function addText(Vector3 $location, string $type, string $world)
	{
		$type = $this->getTypeBy($type);
   		switch (Server::getInstance()->getName())
		{
			case 'PocketMine-MP':
				$particle = new FloatingTextParticle($location, $type[1], $type[0]);
				Server::getInstance()->getLevelByName($world)->addParticle($particle);
				break;
			case 'Altay':
				$particle = new FloatingTextParticle(color::GOLD . "<<<<<>>>>>", $this->getType($type), $location);
				Server::getInstance()->getLevelByName($world)->addParticle($location, $particle);
			break;
		}
  	}
  	
	private function getTypeBy(string $type) : array
  	{
    		switch($type)
    		{
      			case "elo":
        			return ["§l§fTOP 10 PLUTONIUM PLAYERS", $this->core->getTopBy($type)];
      			break;
      			default:
        			return [];
    		}
  	}
}
