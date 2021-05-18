<?php

declare(strict_types=1);

namespace RolandDev\BedWars\task;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use RolandDev\BedWars\Game;

class EggTask extends Task {

    private $timer = 0;
    private $entity;
    private $player;
    private $game;

	public function __construct(Player $player,Entity $entity,Game $plugin) {
      $this->player = $player;
      $this->entity = $entity;
      $this->game = $plugin;
	}

	public function onRun(int $currentTick)
    {
        $location = $this->entity->asVector3();
        if (!$this->player instanceof Player || $this->timer >= 8) {
              $this->game->plugin->getScheduler()->cancelTask($this->getTaskId());
            return;
        }

        $this->timer++;
            $block  = clone($location->subtract(0.0,2,0,0.0));
            if ($this->entity->getLevel()->getBlockAt($block->getX(),$block->getY(),$block->getZ()) instanceof Air ) {
               $this->game->placedBlock[] = $block->asVector3()->__toString();
               $this->entity->getLevel()->setBlock($block,Block::get(Block::WOOL));
            }
            $block2  = clone($location->subtract(1.0, 2.0, 0.0));
            if ($this->entity->getLevel()->getBlockAt($block2->getX(),$block2->getY(),$block2->getZ()) instanceof Air ) {
                $this->game->placedBlock[] = $block2->asVector3()->__toString();
                $this->entity->getLevel()->setBlock($block2,Block::get(Block::WOOL));
            }
            $block3 = clone($location->subtract(0.0, 2.0, 1.0));
            if ($this->entity->getLevel()->getBlockAt($block3->getX(),$block3->getY(),$block3->getZ()) instanceof Air ) {
                $this->game->placedBlock[] = $block3->asVector3()->__toString();
                $this->entity->getLevel()->setBlock($block3,Block::get(Block::WOOL));
            }



    }
}