<?php

declare(strict_types=1);

namespace RolandDev\BedWars\math;


use pocketmine\entity\Villager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use RolandDev\BedWars\Game;
use pocketmine\Player;

class ShopVillager extends  Villager
{

  
    public $arena;


    public function getName(): string
    {
        return "ShopVillager";
    }


    public function initEntity(): void{
        parent::initEntity();
        $this->setNametag("§bITEM SHOP\n§r§eLEFT CLICK");
        $this->setNametagAlwaysVisible(true);
    }


    public function attack(EntityDamageEvent $source): void
    {
             $event = $source;
             $event->setCancelled();
             $player = $source->getEntity();
            $arena =  $this->arena;
            if($this->arena instanceof Game){
             if($arena->phase === 1){
            if($event instanceof EntityDamageByEntityEvent){
               if($event->getCause() == $source::CAUSE_ENTITY_ATTACK){
                $dmg = $event->getDamager();
                if($dmg instanceof Player){
                    if($this->arena->inGame($dmg)){
                    	if(!isset($this->arena->spectators[$dmg->getName()])) {
							$this->arena->shopMenu($dmg);
							$player->setHealth(20);
							$event->setCancelled(true);
						}
                    }
                }
               }
            } else {
                $event->setCancelled(true);
            } 
        }
      }
    
    }
}