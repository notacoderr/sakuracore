<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;


class ItemStore
{
  
    public $main;
    private $cache = [];
	
    public function __construct(core $core)
    {
        $this->main = $core;
    }
    
   	 public function openStoreForm(Player $player)
    	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$list = array_keys( $this->main->recipesData->getAll() );
				$item = $list[ $button ];
				//$player->sendMessage($quest); //for debug
				$this->cache[ $player->getName() ] = $item;
				$this->sendRecipeInfo($player, $item);
				return true;
			}
		});
        	$form->setTitle('§l§fEnchanted weaponry');
		
		foreach( array_keys($this->main->recipesData->getAll()) as $key)
		{
			$form->addButton($key);
		}
		
        	$form->sendToPlayer($player);
    	}
	
	public function sendRecipeInfo(Player $player, string $recipe)
	{
		$form = $this->main->formapi->createModalForm(function (Player $player, array $data)
		{
			if($data[0])
			{
				$this->main->processRecipes( $player, $this->cache[ $player->getName() ]);
				if(array_key_exists($player->getName(), $this->cache))
				{
				    unset( $this->cache[$player->getName()] );
				}
				return;
			} else {
				$this->openStoreForm($player);
				if(array_key_exists($player->getName(), $this->cache))
				{
				    unset( $this->cache[$player->getName()] );
				}
				return;
			}
		});
		
        	$form->setTitle(strtoupper( $this->getQuestTitle($quest) ));
		$form->setContent("§fTitle:§a ". $this->getQuestTitle($quest). "\n§fReq. Level:§a ". $this->getQuestLevel($quest). "\n§f-§6 ". $this->getQuestInfo($quest));
		$form->setButton1("§lAccept");
		$form->setButton2("§lBack");
        	$form->sendToPlayer($player);
	}
	
	private function processRecipes(Player $player, string $recipe) : bool
	{
		/* To do:
		- check gem cost,
		- check gold cost,
		- check requirements
		*/
	}
  
}
