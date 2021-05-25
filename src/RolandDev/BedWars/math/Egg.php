<?php



namespace RolandDev\BedWars\math;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\projectile\Egg as Sarkas;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\math\RayTraceResult;
use pocketmine\level\particle\HeartParticle;
use RolandDev\BedWars\BedWars;


class Egg extends Sarkas
{


    public $owner;
    
    public $team;

    public $arena;

    public $everbody = [];

    protected $gravity = 0;

    public $timer = 0;

    public $pos = 0;

    public $timerToSpawn = 0;
  


    public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {

         $this->spawnBlock();
        
        parent::onHitBlock($blockHit, $hitResult); // TODO: Change the autogenerated stub
    }

    public function spawnBlock(){
        $meta = [
            "red" => 14,
            "blue" => 11,
            "yellow" => 4,
            "green" => 5
        ];

        foreach($this->everbody as $body){
                             
          if(!$this->getLevel()->getBlockAt($body->x,$body->y,$body->z) instanceof Air){
              return;
          }
            foreach($this->arena->data["location"] as $spawn){
                $v = Vector3::fromString($spawn);
                if($body->distance($v->asVector3()) < 6){
                    return;
                }

                    if(BedWars::getInstance()->isInGame($this->owner)){
           
                        
                        BedWars::getInstance()->getArenaByPlayer($this->owner)->addPlacedBlock($this->getLevel()->getBlockAt($body->x,$body->y,$body->z));
                        
                        $this->getLevel()->setBlock($body,Block::get(BlockIds::WOOL,$meta[$this->team]),false,true);
                      
                    }
                  
                           
                    foreach($this->getLevel()->getPlayers() as $p){
                        if($p->distance($body) < 3){
                         $this->addSound($p);
                        }
                    }
                }
                
          
         
         }
    }

    public function addSound($player){
        $pk = new PlaySoundPacket();
        $pk->x = $player->getX();
        $pk->y = $player->getY();
        $pk->z = $player->getZ();
        $pk->volume = 100;
        $pk->pitch = 1;
        $pk->soundName = 'random.pop';
        $player->dataPacket($pk);
        //Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
    }


    
    

    public function entityBaseTick(int $tickDiff = -1): bool
    {
        if($this->getLevel()->getBlockAt($this->x,$this->y,$this->z) instanceof Air){
            $this->everbody[] = $this->asPosition();
            $this->everbody[] = $this->asPosition()->add(2);
        }
   
            if($this->pos >= 3){
                $this->pos--;
            }
            $this->timer++;
            if($this->timer >= 40){
                $this->timer = 0;
                $this->timerToSpawn++;

            }
            if($this->timerToSpawn >= 5){
            $this->spawnBlock();
            }
   
            
        return parent::entityBaseTick($tickDiff);
    }
}