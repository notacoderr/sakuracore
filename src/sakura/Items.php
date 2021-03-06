<?php

namespace sakura;

use sakura\core;
use pocketmine\{Player, Server};
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use pocketmine\inventory\Inventory;

use pocketmine\utils\TextFormat as TF;

class Items
{
    public $main;
    public function __construct(core $core)
    {
        $this->main = $core;
    }
    public function isCompatible(Player $player, Item $item) : bool
    {
	 if($player->hasPermission("corex.item.master"))
	 {
		 return true;
	 }
         $playerClass = $this->main->classes->getClass($player);
         $playerLevel = $this->main->data->getVal($player, "level");
         $itemLore = $item->getlore();
      	 $itemLevel = (int) TF::clean($itemLore[0]); //Lore 0
	 $itemClass = (string) TF::clean($itemLore[1]); //Lore 1 (array)
	 $itemSerial = (string) TF::clean($itemLore[1]); //Lore 3 (array)
	if(array_key_exists( $itemSerial, $this->main->itemData->getAll() ))
	{	
		if($playerLevel < $itemLevel)
		{
			$player->sendMessage("§c§lYour Level is too low");
			return false;
		}
		if($itemClass == "General")
		{
			  return true;
		} else {
			  if($itemClass != $playerClass)
			  {
				return true;
			  } else {
				$player->sendMessage("§c§lThe weapon was made for §f". $itemClass);
				return false;
			  }
		 }
	}
        //return (strtolower($itemClass) == strtolower($playerClass)) ? true : false;
    }
	
    public function produceItem(Player $player, string $data) : void
    {
	    $player->getLevel()->dropItem(new Vector3($player->getX(), $player->getY() + 0.75, $player->getZ()), $this->createItem($data));
    }
	
    public function createItem(string $data) : Item
    {  
          	$src = $this->main->itemData;
		$item = Item::get($src->getNested($data .".item"), 0 ,1); 
		$class = $src->getNested($data .".class");
		$level = $src->getNested($data .".level");
		$rarity = $src->getNested($data .".rare");
		$info = $src->getNested($data .".info");
		$item->setCustomName($src->getNested($data .".name"));
	  	$item->setLore([
			"Required Lv: ". TF::RED. $level, //Level #0
			"Class: ". TF::GOLD. $class, //Class #1
			"Rarity: ". TF::WHITE. $rarity, //Rarity #2
			"Serial: ". TF::WHITE. $data, //Level #3
			"- ". TF::WHITE. $info //#4
		]);
	  	if(!is_null($src->getNested($data .".enchantment")))
	  	{
			foreach($src->getNested($data .".enchantment") as $enc)
			{
				$fx = explode(":" , $enc);
				$e = $fx[0];
				$lvl = $fx[1];
				if($fx[2] == "custom")
				{
					$this->main->pce->addEnchantment($item, $e, $lvl, false);
				} else {
					$e = Enchantment::getEnchantmentByName($e);
					if($e instanceof Enchantment){
						$ench = new EnchantmentInstance($e, $lvl);
						$item->addEnchantment($ench);
					}
				}
		  	}
	  	}
          	return $item;
    }
  
}
