<?php

namespace RolandDev\BedWars\math;

use pocketmine\entity\Entity;
use pocketmine\entity\{EffectInstance, Effect};
use pocketmine\event\entity\{EntityDamageByEntityEvent};
use pocketmine\event\entity\EntityDamageEvent;
use RPG\RPGdungeon\Entities\FireworksRocket;
use RPG\RPGdungeon\Items\Fireworks;
use pocketmine\level\{Position, Level};
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Explosive; 
use pocketmine\level\utils\SubChunkIteratorManager;

class TNT extends Entity implements Explosive {

    public const NETWORK_ID = self::TNT;
    
    private $rays = 16;
    
	public $size = 4;
	public $affectedBlocks = [];
	
	public $stepLen = 0.3;

	private $subChunkHandler; 

	public $width = 0.98;
	public $height = 0.98;

	protected $baseOffset = 0.49;

	protected $gravity = 0.04;
	protected $drag = 0.0;
    
    protected $life = 0;
    protected $delay = 0;
    public $arena;
    public $owner;

    public function __construct(Level $level, CompoundTag $nbt) {
        parent::__construct($level, $nbt);
        $this->subChunkHandler = new SubChunkIteratorManager($this->level, false); 
    }
    
    public function initEntity(): void {
        parent::initEntity();
        $this->setGenericFlag(self::DATA_FLAG_IGNITED, true);
		$this->propertyManager->setInt(self::DATA_FUSE_LENGTH, 80);
        $this->addSound($this, 'random.fuse', 1, false);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
    }
    
    public function canCollideWith(Entity $entity) : bool{
		return false;
	}
	
    public function getOwner() {
        return Server::getInstance()->getPlayer($this->owner);
    }

    public function getName(): string{
        return "TNT";
    }
    
    public function entityBaseTick(int $tickDiff = 1) : bool {
        if ($this->closed){
            return false;
        }
        if(80 % 5 === 0){
			$this->propertyManager->setInt(self::DATA_FUSE_LENGTH, 80);
		}

        $hasUpdate = parent::entityBaseTick($tickDiff);
        $this->life++;
		if($this->life >= 15){
   
           $this->explode();
           	$this->life = 0;

		}
	
          

        if (!$this->getOwner() instanceof Player){
            $this->flagForDespawn();
            return true;
        }

        return $hasUpdate;
    }
	
    public function attack(EntityDamageEvent $source) : void{
        $source->setCancelled();
    }
	

	public function explode(): void{
	    $ev = new ExplosionPrimeEvent($this, 4);
		$ev->call();
		if(!$ev->isCancelled()){
			$explosion = new Explosion(Position::fromObject($this->add(0, $this->height / 2, 0), $this->level), $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
		$explosionSize = 2 * 2;
		$minX = (int) floor($this->x - $explosionSize - 1);
		$maxX = (int) ceil($this->x + $explosionSize + 1);
		$minY = (int) floor($this->y - $explosionSize - 1);
		$maxY = (int) ceil($this->y + $explosionSize + 1);
		$minZ = (int) floor($this->z - $explosionSize - 1);
		$maxZ = (int) ceil($this->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->level->getNearbyEntities($explosionBB, $this);
		foreach($list as $entity){
			$distance = $entity->distance($this->asVector3()) / $explosionSize;

			if($distance <= 5){
			    if($entity instanceof Player){
				$motion = $entity->subtract($this->asVector3())->normalize();

				$impact = (1 - $distance) * ($exposure = 2);

				$damage = (int) ((($impact * $impact + $impact) / 2) * 1 * $explosionSize + 0.5);

				$ev = new EntityDamageByEntityEvent($this->getOwner(), $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 6);
				$entity->attack($ev);
				$entity->setMotion($motion->multiply(1.8));
			    }
			}
		}
		$this->flagForDespawn();
	}
	
	public function addSound($player, string $sound = '', float $pitch = 1, bool $type = true){
        $pk = new PlaySoundPacket();
		$pk->x = $player->getX();
		$pk->y = $player->getY();
		$pk->z = $player->getZ();
		$pk->volume = 2;
		$pk->pitch = $pitch;
		$pk->soundName = $sound;
		if($type){
		    $player->dataPacket($pk);
		} else {
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
		}
    }
}