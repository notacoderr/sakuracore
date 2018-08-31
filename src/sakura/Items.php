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
          if(strtolower($itemClass) !== strtolower($playerClass))
          {
              $player->sendMessage("§c§lThis isn't weapon is not for your Class type");
              return false;
          } 
      
          elseif($playerLevel < $itemLevel)
          {
              $player->sendMessage("§c§lYour Level is too low");
              return false;
            
          } else {
              return true;
          }
          //return (strtolower($itemClass) == strtolower($playerClass)) ? true : false;
    }
  
    public function pasteData(Item $item) : Item
    {
          $id = $item->getDamage();
          $src = $this->main->items;
          $item->setCustomName($src->getNested($id .".name"));
          
          $class = $src->getNested($id .".lore.class");
          $level = $src->getNested($id .".lore.level");
          $rarity = $src->getNested($id .".lore.rare");
          
          $item->setLore([$class, $level, $rarity]);
          
          return $item;
    }
  
}
