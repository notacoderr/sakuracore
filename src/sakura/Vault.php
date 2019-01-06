<?php

namespace sakura;

use sakura\core;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};

class Vault
{
  
  	public $main;
	private $cache = [];

	public function __construct(core $core)
	{
		$this->main = $core;
	}
    
    	public function storageExists(string $id) : bool
	{
		$result = $this->main->db->query("SELECT * FROM vault WHERE name = '$id';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
  
  	public function getItems(string $id) : string
	{
		$result = $this->main->db->query("SELECT items FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["items"];
	}
	
	public function getOwner(string $id) : string
	{
		$result = $this->main->db->query("SELECT owner FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["owner"];
	}
  	
	public function getCode(string $id) : string
	{
		$result = $this->main->db->query("SELECT code FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["code"];
	}
	
	public function getMax(string $id) : int
	{
		$result = $this->main->db->query("SELECT max FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["max"];
	}
	
	public function verifyCode(string $id, string $code) : bool
	{
		$result = $this->main->db->query("SELECT code FROM vault WHERE name = '$id';");
		$resultArr = $result->fetchArray(SQLITE3_ASSOC);
		return $resultArr["code"] == $code;
	}
	
    public function getItemsInArray(string $id) : array
  	{
        return explode ( "," , $this->getItems($id) );
  	}
	
	public function getAllVaults(string $owner) : array
	{
		$result = $this->main->db->query("SELECT * FROM vault WHERE owner = '$owner';");
		$ar = [];
		while($resultArr = $result->fetchArray(SQLITE3_ASSOC))
		{
			$ar[] = $resultArr["name"];
		}
		return $ar;
	}
	
  	public function countItems(string $id) : int
   	{
        return count( $this->getItemsInArray($id) );
   	}
	
	public function countVaults(string $owner) : int
	{
		$arr = $this->getAllVaults($owner);
		return count($arr);
	}
	
 	public function addItem(string $id, int $iid, int $meta, int $count, string $name, $enchantment) : void
	{
		if( strlen($this->getItems($id)) > 5 )
		{
			$items = (string) $this->getItems($id). ",". $iid. ":". $meta. ":". $count. ":". $name. ":". $enchantment;
		} else {
			$items = (string) $this->getItems($id). $iid. ":". $meta. ":". $count. ":". $name. ":". $enchantment;
		}
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $items);
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
   
 	public function delItem(string $id, int $x) : void
	{
		$items = $this->getItemsInArray($id);
		unset($items[$x]);
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", implode("," , $items) );
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
	
	public function changeShareCode(string $id, string $code) : void
	{
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $this->getItems($id));
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $code);
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
   	}
	
    public function upgradeSlot(string $id) : void
    {
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $this->getItems($id));
		$stmt->bindValue(":max", ($this->getMax($id) + $this->main->settings->getNested("vault.upgrade.increase")));
		$stmt->bindValue(":code", $this->getCode($id));
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
    }
	
	public function newCode(string $id, string $code) : void
    {
		$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max,:code, :owner);");
		$stmt->bindValue(":name", $id);
		$stmt->bindValue(":items", $this->getItems($id));
		$stmt->bindValue(":max", $this->getMax($id));
		$stmt->bindValue(":code", $code);
		$stmt->bindValue(":owner", $this->getOwner($id));
		$result = $stmt->execute();
    }
	
	public function homepage(Player $player) : void
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$button = $data[0];
				switch($button)
				{
					case 0: $this->register($player); break;
					case 1: $this->access($player); break;
					case 2: $this->accessOther($player); break;
					case 3: $player->sendMessage("§l§7[§b!§7]§f At the moment, please use §a/cloud upload [VaultID] §fwhile holding an item."); break;
					case 4: $this->expand($player); break;
					case 5: $this->recode($player); break;
					case 6: $this->trash1($player); break;
				}
			}
		});
		$form->setTitle("§l§fSecured Cloud Storage");
		$form->setContent("§l§fVersion: §70.2-beta§r\n§l§fVault(s): §7". $this->countVaults($player->getName()). "§r\n§7[!] This is still in beta and doesn't support items with Custom Name & Enchantment");//
		$form->addButton("§fRegister new Vault"); //0
		$form->addButton("§fAccess a Vault"); //1
		$form->addButton("§fAccess other's Vault"); //2
		$form->addButton("§fUpload Item"); //3
		$form->addButton("§fExpand Capacity"); //4
		$form->addButton("§fReset ShareCode"); //5
		$form->addButton("§fDelete a Vault"); //6
		$form->sendToPlayer($player);
	}
	
	private function register(Player $player)
	{
		$form = $this->main->formapi->createCustomForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$id = $data[0];
				if(!$this->storageExists($id))
				{
					if((strlen($id) <= 10) and (strlen($id) >= 5))
					{
						$pmoney = (int) $this->main->eco->myMoney($player);
						$price = (int) $this->main->settings->getNested("vault.price");
						if($pmoney >= $price)
						{
								$code = $this->genCode();
								$stmt = $this->main->db->prepare("INSERT OR REPLACE INTO vault (name, items, max, code, owner) VALUES (:name, :items, :max, :code, :owner);");
								$stmt->bindValue(":name", $id);
								$stmt->bindValue(":items", "");
								$stmt->bindValue(":max", $this->main->settings->getNested("vault.slots"));
								$stmt->bindValue(":code", $code);
								$stmt->bindValue(":owner", $player->getName());
								$result = $stmt->execute();
											
								$player->sendMessage("§l§7[§a!§7]§f Your cloud storage is ready!");
								$player->sendMessage("§a>§f VaultID: ". $id);
								$player->sendMessage("§a>§f Random Share-Code: ". $code);
											
								$this->main->eco->reduceMoney($player, $price);
						} else {
							$player->sendMessage("§l§7[§6!§7] §fCloud storage costs: §7". $price. "§f, You have: §c". $pmoney);
						}
					} else {
						$player->sendMessage("§l§7[§6!§7] §fCloud storage VaultID should be between 5 - 10 characters. Yours had: ". strlen($id));
					}
				} else {
					$player->sendMessage("§l§7[§6!§7] §f". $id. " appears to be registered");
				}
			}
		});
		$form->setTitle("§l§fVault Registration");
		$form->addInput("Vault ID (3 - 10 Characters)");
		$form->sendToPlayer($player);
	}
	
	private function access(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$i = $this->getAllVaults($player->getName())[$data[0]];
				if(!$this->isOpened($i))
				{
					if(strlen($this->getItems($i)) >= 5) //to be sure, x:x:x (5 chars)
					{
						$this->openCloud($player, $i);
					} else {
						$player->sendMessage("§l§7[§e!§7]§f". $i. "'s storage is empty..");
					}
				} else {
					$player->sendMessage("§l§7[§c!§7] §fIt appears that your vault is currently in used");
				}
			}
		});
		$form->setTitle("§l§fVault Access");
		$array =  $this->getAllVaults($player->getName());
		if(count($array) < 1)
		{
			$player->sendMessage("§l§7[§c!§7] §fIt appears that you don't have any vault");
			return;
		} else {
			arsort($array);
			foreach($array as $vs)
			{
				if(strlen($this->getItems($vs)) >= 5)
				{
					$form->addButton("§f". $vs. " - ". $this->countItems($vs). " item(s)");
				} else {
					$form->addButton("§f". $vs. " - 0 item");
				}
			}
		}
		$form->sendToPlayer($player);
	}
	
	private function accessOther(Player $player)
	{
		$form = $this->main->formapi->createCustomForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$id = $data[0];
				$code = $data[1];
				if($this->storageExists($id))
				{
					if(!$this->isOpened($id))
					{
						if($this->verifyCode($id, $code))
						{
							if(strlen($this->getItems($id)) >= 5) //to be sure, x:x:x (5 chars)
							{
								$this->openCloud($player, $id);
							} else {
								$player->sendMessage("§l§7[§e!§7]§f". $id. "'s storage is empty..");
							}
						} else {
							$player->sendMessage("§l§7[§c!§7] §fYour code [". $code. "] is invalid, contact a staff/the owner if you forgot your code");
						}
					} else {
						$player->sendMessage("§l§7[§c!§7] §fIt appears that your vault is currently in used");
					}
				} else {
					$player->sendMessage("§l§7[§6!§7] §f". $id. " appears to be unregistered");
				}
			}
		});
		$form->setTitle("§l§fVault Access Other's");
		$form->addInput("VaultID");
		$form->addInput("ShareCode");
		$form->sendToPlayer($player);
	}
	
	private function expand(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$i = $this->getAllVaults($player->getName())[$data[0]];
				$pmoney = (int) $this->main->eco->myMoney($player);
				$price = (int) $this->main->settings->getNested("vault.upgrade.price");
				if( $pmoney >= $price )
				{
					$this->upgradeSlot($i);
					$player->sendMessage("§f§lCloud storage was updated!");
					$player->sendMessage("§f§lVaultID: §7". $i);
					$player->sendMessage("§f§lShare-Code: §7". $this->getCode($i));
					$player->sendMessage("§f§lItem(s): §7". $this->countItems($i). "/". $this->getMax($i));
					$this->main->eco->reduceMoney($player, $price);
				} else {
					$player->sendMessage("§f§lCloud storage expand costs: §7". $price. "§f, You have: §c". $pmoney);
				}
			}
		});
		$form->setTitle("§l§fVault Expansion");
		$array =  $this->getAllVaults($player->getName());
		if(count($array) < 1)
		{
			$player->sendMessage("§l§7[§c!§7] §fIt appears that you don't have any vault");
			return;
		} else {
			arsort($array);
			foreach($array as $vs)
			{
				if(strlen($this->getItems($vs)) >= 5)
				{
					$form->addButton("§f". $vs. " - ". $this->countItems($vs). "/". $this->getMax($vs));
				} else {
					$form->addButton("§f". $vs. " - 0/". $this->getMax($vs));
				}
			}
		}
		$form->sendToPlayer($player);
	}
	
	private function recode(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$i = $this->getAllVaults($player->getName())[$data[0]];
				$code = $this->genCode();
				$this->newCode($i, $code);
				$player->sendMessage("§f§lCloud storage was updated!");
				$player->sendMessage("§f§lVaultID: §7". $i);
				$player->sendMessage("§f§lShare-Code: §7". $code);
				$player->sendMessage("§f§lItem(s): §7". $this->countItems($i). "/". $this->getMax($i));
			}
		});
		$form->setTitle("§l§fVault Reset Code");
		$array =  $this->getAllVaults($player->getName());
		if(count($array) < 1)
		{
			$player->sendMessage("§l§7[§c!§7] §fIt appears that you don't have any vault");
			return;
		} else {
			arsort($array);
			foreach($array as $vs)
			{
				if(strlen($this->getItems($vs)) >= 5)
				{
					$form->addButton("§f". $vs. " - ". $this->countItems($vs). "/". $this->getMax($vs));
				} else {
					$form->addButton("§f". $vs. " - 0/". $this->getMax($vs));
				}
			}
		}
		$form->sendToPlayer($player);
	}
	
	private function trash1(Player $player)
	{
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				$i = $this->getAllVaults($player->getName())[$data[0]];
				$this->trash2($player, $i);
			}
		});
		$form->setTitle("§l§fVault Deletion");
		$array =  $this->getAllVaults($player->getName());
		if(count($array) < 1)
		{
			$player->sendMessage("§l§7[§c!§7] §fIt appears that you don't have any vault");
			return;
		} else {
			arsort($array);
			foreach($array as $vs)
			{
				if(strlen($this->getItems($vs)) >= 5)
				{
					$form->addButton("§f". $vs. " - ". $this->countItems($vs). "/". $this->getMax($vs));
				} else {
					$form->addButton("§f". $vs. " - 0/". $this->getMax($vs));
				}
			}
		}
		$form->sendToPlayer($player);
	}
	
	private function trash2(Player $player, string $id)
	{
		$this->cache[ $player->getName() ] = $id;
		$form = $this->main->formapi->createModalForm(function (Player $player, array $data)
		{
			switch($data[0]) //apparently this is boolean
			{
				case 1:
					$id = $this->cache[ $player->getName() ];
					$this->main->db->query("DELETE FROM vault WHERE name = '$id';");
					$player->sendMessage("§f§lCloud storage update, VaultID: §7". $id. "§f, Deleted...");
				break;
				case 0:
					$this->homepage($player);
				break;
			}
			unset( $this->cache[$player->getName()] );
		});
		$form->setTitle("§l§fVault Delete Confirmation");
		$form->setContent("§f§lYou're deleting [VaultID: §7". $id. "]§f. By proceeding, you're aware that all datas stored in it will be deleted as well.");
		$form->setButton1("§7Yes, I understand");
		$form->setButton2("§7No, Please take me back!");
		$form->sendToPlayer($player);
	}
	
	public function openCloud(Player $player, string $id)
	{
		$this->cache[ $player->getName() ] = $id;
		$form = $this->main->formapi->createSimpleForm(function (Player $player, array $data)
		{
			if (isset($data[0]))
			{
				if($this->main->hasSpace($player))
				{
					$button = $data[0];
					$id = $this->cache[ $player->getName() ];
					$rawitem = $this->getItemsInArray($id)[$button];
					$i = explode(":", $rawitem);
					$item = Item::get($i[0], $i[1], $i[2])->setCustomName($i[3]);
					//example item string: 1:0:2:Precious Stone:1x3_1x4_5x7
					if($i[4] != "no_enchantment")
					{
						$e = explode("_", $i[4]);
						foreach($e as $en)
						{
							$ench = explode("x", $en);
							$item = $this->enchantItem($item, $ench[0], $ench[1]);
						}
					}
					$player->getInventory()->addItem($item);
					$this->delItem($id, $button);
					$this->homepage($player);
				}  else {
					$player->sendMessage("§l§cPlease free a slot in your inventory");
				}
			}
			
			unset( $this->cache[$player->getName()] );
		});
		$form->setTitle("§l§fSecured Cloud Storage");
		$form->setContent("§l§fVaultID: §7" . $id. "§r\n§l§fShare Code: §7". $this->getCode($id));
		foreach( $this->getItemsInArray($id) as $items)
		{
			$i = explode(":", $items);
			$form->addButton("§f". Item::get($i[0], $i[1])->getName(). " §7- §fx". $i[2]);
		}
		$form->sendToPlayer($player);
	}
	
	public function isOpened(string $id) : bool
	{
		return (is_string(array_search($id, $this->cache)) ? true : false); //returns true if it's opened
	}
	
	public function genCode() : string
	{
	    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 6);
	}
	
	private function enchantItem(Item $item, $enchId, int $lvl) : Item
	{
		if($enchId >= 100 or is_string($enchId))
		{
			if($this->main->pce != null)
			{
				$this->main->pce->addEnchantment($item, $enchId, $lvl);
			}
		}
		if($enchId <= 32 && $enchId >= 0)
		{
			$enchantment = Enchantment::getEnchantment((int) $enchId);
			if($enchantment instanceof Enchantment)
			{
				$item->addEnchantment( new EnchantmentInstance($enchantment, $lvl) );
			}
		}
		return $item;
	}
   
}
