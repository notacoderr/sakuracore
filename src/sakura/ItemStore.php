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
    
   	 public function openStoreForm(Player $player)
    	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				$list = array_keys( $this->main->recipesData->getAll() );
				$quest = $list[ $button ];
				//$player->sendMessage($quest); //for debug
				$this->questCache[ $player->getName() ] = $quest;
				$this->sendQuestInfo($player, $quest);
				return true;
			}
		});
        	$form->setTitle('§l§fApply for Quest');
		
		foreach( array_keys($this->main->recipesData->getAll()) as $key)
		{
			$form->addButton($key);
		}
		
        	$form->sendToPlayer($player);
    	}
	
	public function sendQuestInfo(Player $player, string $quest)
	{
		$form = $this->main->formapi->createModalForm(function (Player $player, array $data)
		{
			if($data[0])
			{
				$this->validatePlayerQuest( $player, $this->questCache[ $player->getName() ]);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    unset( $this->questCache[$player->getName()] );
				}
				return;
			} else {
				$this->sendQuestApplyForm($player);
				if(array_key_exists($player->getName(), $this->questCache))
				{
				    unset( $this->questCache[$player->getName()] );
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
  
}
