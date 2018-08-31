<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;

use pocketmine\utils\TextFormat as TF;

class Items
{
  
  	public $main;
  
    /*const Warrior   = 1;
    const Brawler   = 2;
    const Archer    = 3;
    const Assassin  = 4;*/
 
    public function __construct(core $core)
    {
        $this->main = $core;
    }
  
    public function isCompatible(Player $player, Item $item) : bool
    {
          $playerClass = $this->main->classes->getClass($player);
          $playerLevel = $this->main->data->getVal($player, "level");
      
          $itemLore = $item->getlore();
      
	  $itemClass = (string) TF::clean($itemLore[0]); //Lore 0 (array)
          $itemLevel = (int) TF::clean($itemLore[1]); //Lore 1
	  if($itemClass == "General")
	  {
		  if($playerLevel < $itemLevel)
		  {
		      $player->sendMessage("§c§lYour Level is too low");
		      return false;
		  } else {
		  	return true;
		  }
	  } else {
		  if($playerLevel < $itemLevel)
		  {
		      	$player->sendMessage("§c§lYour Level is too low");
		      	return false;
		  }
		  elseif(strpos($itemClass, $playerClass) !== false)
		  {
		      	return true;
		  } else {
			$player->sendMessage("§c§lThis isn't weapon is not for your Class type");
		      	return false;
		  }
	  }
          //return (strtolower($itemClass) == strtolower($playerClass)) ? true : false;
    }
  
    public function pasteData(Item $item) : Item
    {
	  $arr = array($item->getId(), $item->getDamage());
	  $data = implode(".", $arr);
	    
          $src = $this->main->itemData;
	    
          $class = $src->getNested($data .".class");
          $level = $src->getNested($data .".level");
          $rarity = $src->getNested($data .".rare");
	    
          $item->setCustomName($src->getNested($data .".name"));
          $item->setLore([
		  TF::BOLD. "Class: ". TF::GOLD. $class, //Class
		  TF::BOLD. "Level: ". TF::RED. $level, //Level
		  TF::BOLD. "Rarity: ". TF::WHITE. $rarity //Rarity
	  ]);
          
          return $item;
    }
  
}
