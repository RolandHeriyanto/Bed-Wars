<?php



namespace RolandDev\BedWars\math;

use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\projectile\Egg as Gay;
use pocketmine\Player;
use pocketmine\level\particle\HeartParticle;
class EggBridge extends Gay
{


    public $owner;
    
    public $team;

    

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if (!$this->isCollided) {
            $meta = [
                "red" => 14,
                "blue" => 11,
                "yellow" => 4,
                "green" => 5
            ];
            if($this->getLevel()->getBlockAt($this->getX(),$this->getY(),$this->getZ())->getId() == 0){
            	     $this->getLevel()->setBlock($this->asVector3(),Block::get(BlockIds::WOOL,$meta[$this->team]));
            }
           
        } 
        return parent::entityBaseTick($tickDiff);
    }
}
