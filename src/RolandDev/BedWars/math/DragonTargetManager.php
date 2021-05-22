<?php

declare(strict_types=1);

namespace RolandDev\BedWars\math;

use pocketmine\block\Block;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use vixikhd\dragons\Dragons;
use RolandDev\BedWars\Game;
use RolandDev\BedWars\math\EnderDragon;
use RolandDev\BedWars\math\ThrownBlock;

/**
 * Class DragonTargetManager
 * @package vixikhd\dragons\arena
 */
class DragonTargetManager {

    public const MAX_DRAGON_MID_DIST = 100; // Dragon will rotate when will be distanced 64 blocks from map center

    /** @var Arena $plugin */
    public $plugin;
    /** @var Vector3[] $blocks */
    public $blocks = [];
    /** @var Vector3[] $baits */
    public $baits = [];
    /** @var Vector3 $mid */
    public $mid; // Used when all the blocks the are broken

    /** @var EnderDragon[] $dragons */
    public $dragons = [];

    /** @var Random $random */
    public $random;

    /**
     * DragonTargetManager constructor.
     * @param Arena $plugin
     * @param Vector3[] $blocksToDestroy
     * @param Vector3 $mid
     */
    public function __construct(Game $plugin, array $blocksToDestroy, Vector3 $mid) {
        $this->plugin = $plugin;
        $this->blocks = $blocksToDestroy;
        $this->mid = $mid;

        $this->random = new Random();
    }

    /**
     * @return Vector3
     */
    public function getDragonTarget(): Vector3 {
        foreach ($this->mid as $key) {
            $pos = $key->ceil();
            unset($this->baits[$key]);

            return $pos;
        }

        if(empty($this->blocks)) {
            return $this->mid;
        }

        $blocks = array_values($this->blocks);
     
    }

    /**
     * @param EnderDragon $dragon
     *
     * @param int $x
     * @param int $y
     * @param int $z
     */
    public function removeBlock(EnderDragon $dragon, int $x, int $y, int $z): void {
        $blockPos = new Vector3($x, $y, $z);
        $block = $this->plugin->level->getBlock($blockPos);


        $this->plugin->level->setBlock($blockPos, Block::get(Block::AIR));

        unset($this->blocks["$x:$y:$z"]);

        $dragon->changeRotation(true);
    }

    public function addDragon($team): void {
        $findSpawnPos = function (Vector3 $mid): Vector3 {
            $randomAngle = mt_rand(0, 359);
            $x = ((DragonTargetManager::MAX_DRAGON_MID_DIST - 5) * cos($randomAngle)) + $mid->getX();
            $z = ((DragonTargetManager::MAX_DRAGON_MID_DIST - 5) * sin($randomAngle)) + $mid->getZ();

            return new Vector3($x, $mid->getY(), $z);
        };

        $dragon = new EnderDragon($this->plugin->level, EnderDragon::createBaseNBT($findSpawnPos($this->mid), new Vector3()), $this,$team);
        $dragon->lookAt($this->mid->asVector3());
        $dragon->setMaxHealth(100);
        $dragon->setHealth(100);

        $dragon->spawnToAll();
    }

    /**
     * @param Vector3 $baitPos
     */
    public function addBait(Vector3 $baitPos) {
        $this->baits[] = $baitPos;
    }
}