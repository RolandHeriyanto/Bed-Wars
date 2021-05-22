<?php

namespace RolandDev\BedWars\math;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\Player;
use RolandDev\BedWars\math\Vector3;
use RolandDev\BedWars\BedWars;

class TowerEast {

    public function __construct($arena)
    {
        $this->arena = $arena;
        
    }

     public function  Tower (Player $player,$team) {
 
     $list = [];
     $list[] = $player->asPosition()->add(2,0,-1);
     $list[] = $player->asPosition()->add(1, 0, -2);
     $list[] = $player->asPosition()->add(0, 0, -2);
     $list[] = $player->asPosition()->add(-1, 0, -1);
     $list[] = $player->asPosition()->add(-1, 0, 0);
     $list[] = $player->asPosition()->add(-1, 0, 1);
     $list[] = $player->asPosition()->add(0,0,2);
     $list[] = $player->asPosition()->add(1,0,2);
     $list[] = $player->asPosition()->add(2,0,1);
     //

     $list[] = $player->asPosition()->add(2,1,-1);
     $list[] = $player->asPosition()->add(1,1,-2);
     $list[] = $player->asPosition()->add(0,1,-2);
     $list[] = $player->asPosition()->add(-1,1,-1);
     $list[] = $player->asPosition()->add(-1, 1, 0);
     $list[] = $player->asPosition()->add(-1, 1, 1);
     $list[] = $player->asPosition()->add(0, 1, 2);
     $list[] = $player->asPosition()->add(1, 1, 2);
     $list[] = $player->asPosition()->add(2, 1, 1);
     //

     $list[] = $player->asPosition()->add(2, 2, -1);
     $list[] = $player->asPosition()->add(1, 2, -2);
     $list[] = $player->asPosition()->add(0, 2, -2);
     $list[] = $player->asPosition()->add(-1, 2, -1);
     $list[] = $player->asPosition()->add(-1, 2, 0);
     $list[] = $player->asPosition()->add(-1, 2, 1);
     $list[] = $player->asPosition()->add(0, 2, 2);
     $list[] = $player->asPosition()->add(1, 2, 2);
     $list[] = $player->asPosition()->add(2, 2, 1);

     //
     $list[] = $player->asPosition()->add(2, 3, 0);
     $list[] = $player->asPosition()->add(2, 3, -1);
     $list[] = $player->asPosition()->add(1, 3, -2);
     $list[] = $player->asPosition()->add(0, 3, -2);
     $list[] = $player->asPosition()->add(-1, 3, -1);
     $list[] = $player->asPosition()->add(-1, 3, 0);
     $list[] = $player->asPosition()->add(-1, 3, 1);
     $list[] = $player->asPosition()->add(0, 3, 2);
     $list[] = $player->asPosition()->add(1, 3, 2);
     $list[] = $player->asPosition()->add(2, 3, 1);

     //

     $list[] = $player->asPosition()->add(-1, 4, -2);
     $list[] = $player->asPosition()->add(0, 4, -2);
     $list[] = $player->asPosition()->add(1, 4, -2);
     $list[] = $player->asPosition()->add(2, 4, -2);
     $list[] = $player->asPosition()->add(-1, 4, -1);
     $list[] = $player->asPosition()->add(0, 4, -1);
     $list[] = $player->asPosition()->add(1, 4, -1);
     $list[] = $player->asPosition()->add(2, 4, -1);
     $list[] = $player->asPosition()->add(-1, 4, 0);
     $list[] = $player->asPosition()->add(1, 4, 0);
     $list[] = $player->asPosition()->add(2, 4, 0);
     $list[] = $player->asPosition()->add(-1, 4, 1);
     $list[] = $player->asPosition()->add(0, 4, 1);
     $list[] = $player->asPosition()->add(1, 4, 1);
     $list[] = $player->asPosition()->add(2, 4, 1);
     $list[] = $player->asPosition()->add(-1, 4, 2);
     $list[] = $player->asPosition()->add(0, 4, 2);
     $list[] = $player->asPosition()->add(1, 4, 2);
     $list[] = $player->asPosition()->add(2, 4, 2);

     //
     $list[] = $player->asPosition()->add(2, 4, -3);
     $list[] = $player->asPosition()->add(2, 5, -3);
     $list[] = $player->asPosition()->add(2, 6, -3);
     $list[] = $player->asPosition()->add(1, 5, -3);
     $list[] = $player->asPosition()->add(0, 5, -3);
     $list[] =  $player->asPosition()->add(-1, 4, -3);
     $list[] =  $player->asPosition()->add(-1, 5, -3);
     $list[] =  $player->asPosition()->add(-1, 6, -3);
     $list[] =  $player->asPosition()->add(2, 4, 3);
     $list[] =  $player->asPosition()->add(2, 5, 3);
     $list[] =  $player->asPosition()->add(2, 6, 3);
     $list[] =  $player->asPosition()->add(1, 5, 3);
     $list[] =  $player->asPosition()->add(0, 5, 3);
     $list[] =  $player->asPosition()->add(-1, 4, 3);
     $list[] =  $player->asPosition()->add(-1, 5, 3);
     $list[] =  $player->asPosition()->add(-1, 6, 3);
     $list[] =  $player->asPosition()->add(-2, 4, -2);
     $list[] =  $player->asPosition()->add(-2, 5, -2);
     $list[] =  $player->asPosition()->add(-2, 6, -2);
     $list[] =  $player->asPosition()->add(-2, 5, -1);
     $list[] =  $player->asPosition()->add(-2, 4, 0);
     $list[] =  $player->asPosition()->add(-2, 5, 0);
     $list[] =  $player->asPosition()->add(-2, 6, 0);
     $list[] =  $player->asPosition()->add(-2, 5, 1);
     $list[] =  $player->asPosition()->add(-2, 4, 2);
     $list[] =  $player->asPosition()->add(-2, 5, 2);
     $list[] =  $player->asPosition()->add(-2, 6, 2);
     $list[] =  $player->asPosition()->add(3, 4, -2);
     $list[] =  $player->asPosition()->add(3, 5, -2);
     $list[] =  $player->asPosition()->add(3, 6, -2);
     $list[] =  $player->asPosition()->add(3, 5, -1);
     $list[] =  $player->asPosition()->add(3, 4, 0);
     $list[] =  $player->asPosition()->add(3, 5, 0);
     $list[] =  $player->asPosition()->add(3, 6, 0);
     $list[] =  $player->asPosition()->add(3, 5, 1);
     $list[] =  $player->asPosition()->add(3, 4, 2);
     $list[] =  $player->asPosition()->add(3, 5, 2);
     $list[] =  $player->asPosition()->add(3, 6, 2);

       $this->createTower($player,$list,$team);
    }

    public function createTower(Player $player,$list,$team){
      $meta = [
        "red" => 14,
        "blue" => 11,
        "yellow" => 4,
        "green" => 5
      ];

      foreach($this->arena->data["location"] as $spawn){
        $v = Vector3::fromString($spawn);
        foreach($list as $p){
          if(!$player->getLevel()->getBlockAt($p->getX(),$p->getY(),$p->getZ())->distance($v) < 8){
            BedWars::getInstance()->getArenaByPlayer($player)->addPlacedBlock($player->getLevel()->getBlockAt($p->getX(),$p->getY(),$p->getZ())); 
            $player->getLevel()->setBlock($p,Block::get(BlockIds::WOOL,$meta[$team]));
          }
        }
      }
       $ladermeta = 5;
       $this->arena->spawnLadder($player,0,$ladermeta);
       $this->arena->spawnLadder($player,1,$ladermeta);
       $this->arena->spawnLadder($player,2,$ladermeta);
       $this->arena->spawnLadder($player,3,$ladermeta);
       $this->arena->spawnLadder($player,4,$ladermeta);


     }
                          
}