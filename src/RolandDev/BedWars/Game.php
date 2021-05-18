<?php



declare(strict_types=1);

namespace RolandDev\BedWars;

use pocketmine\block\Block;
use pocketmine\block\Bed;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;
use RolandDev\BedWars\task\EggTask;
use VipzCore\query\CheckPartyQuery;
use VipzCore\query\FetchAllParty;
use VipzCore\query\MemberPartyQuery;
use VipzCore\query\QueryQueue;
use RolandDev\BedWars\math\EggBridge;
use RolandDev\BedWars\math\DragonTargetManager;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\event\entity\{EntityExplodeEvent};
use pocketmine\utils\Config;
use pocketmine\math\Vector2;
use BlockHorizons\Fireworks\item\Fireworks;
use BlockHorizons\Fireworks\entity\FireworksRocket;
use pocketmine\block\Air;
use pocketmine\inventory\{ArmorInventory,
    PlayerInventory,
    EnderChestInventory,
    ChestInventory,
    transaction\action\SlotChangeAction};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChatEvent, PlayerItemConsumeEvent};
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\entity\{EntityMotionEvent,
    EntityDamageEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent,
    ProjectileLaunchEvent};
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\{BlockPlaceEvent, BlockUpdateEvent, LeavesDecayEvent};
use pocketmine\event\inventory\{InventoryTransactionEvent, InventoryOpenEvent, InventoryCloseEvent};
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use pocketmine\item\{Armor, ItemIds, Sword, Item, Pickaxe, Axe};
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\entity\{object\ItemEntity, Effect, Entity, projectile\Arrow,  projectile\Snowball};
use pocketmine\entity\EffectInstance;
use pocketmine\level\{particle\DestroyBlockParticle, Level};
use pocketmine\level\Position;
use pocketmine\utils\Color;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\{Furnace, Skull, EnchantTable};
use pocketmine\event\inventory\CraftItemEvent;
use RolandDev\BedWars\math\MapReset;
use RolandDev\BedWars\math\ShopVillager;
use RolandDev\BedWars\math\UpgradeVillager;
use pocketmine\entity\object\PrimedTNT;
use RolandDev\BedWars\math\{Vector3,Tower, Generator, TNT, Bedbug, Golem, Fireball};
use RolandDev\BedWars\BedWars as SkyWars;
use RolandDev\BedWars\libs\muqsit\invmenu\{
    InvMenu
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use RolandDev\BedWars\libs\muqsit\invmenu\transaction\{DeterministicInvMenuTransaction};
use RolandDev\BedWars\math\EnderDragon;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\ClosureTask;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\item\Compass;
use RolandDev\BedWars\task\TaskTick;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
/**
 * Class Game
 * @package BedWars\Game
 */
class Game implements Listener
{

	const MSG_MESSAGE = 0;
	const MSG_TIP = 1;
	const MSG_POPUP = 2;
	const MSG_TITLE = 3;

	const PHASE_LOBBY = 0;
	const PHASE_GAME = 1;
	const PHASE_RESTART = 2;

	/** @var SkyWars $plugin */
	public $plugin;
	public $baits = [];
	public $dragon;


	public $scheduler;


	public $mapReset;


	public $phase = 0;

	public $kill = [];
	public $finalkill = [];
	public $broken = [];

	/** @var array $data */
	public $data = [];
	public $spawntower;
	public $placedBlock = [];

	public $invis = [];
	public $inChest = [];
	public $teams = [];
	public $countertrap = [];
	public $itstrap = [];
	public $minertrap = [];
	public $alarmtrap = [];

	public $armor = [];
	public $axe = [];
	public $tempTeam = [];
	public $pickaxe = [];
	public $spectators = [];
	public $shear = [];
	public $utilities = [];
	public $tracking = [];
	public $shop;
	public $upgrade;

	/** @var bool $setting */
	public $setup = false;

	/** @var Player[] $players */
	public $players = [];
	public $index = [];
	private $maxPlayerPerTeam = 4;
	private $maxPlayers = 16;

	public $ghost = [];

	/** @var Player[] $toRespawn */
	public $toRespawn = [];

	/** @var Level $level */
	public $level = null;

	public $respawn = [];
	public $allexp = [];
	public $respawnC = [];
	public $milk = [];
	public $suddendeath;



	public function __construct(BedWars $plugin, array $arenaFileData)
	{
		$this->plugin = $plugin;
		$this->data = $arenaFileData;
		$this->setup = !$this->enable(false);
		$this->plugin->getScheduler()->scheduleRepeatingTask($this->scheduler = new TaskTick($this), 20);
		$this->scheduler->reloadTimer();

		if ($this->setup) {
			if (empty($this->data)) {
				$this->createBasicData();
			}
		} else {
			$this->loadArena();
			$this->initTeams();
		}
	}

	

	public function initTeams()
	{
		if (!$this->setup) {

			$this->plugin->teams[$this->level->getFolderName()]["red"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["blue"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["yellow"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["green"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["aqua"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["white"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["pink"] = [];
			$this->plugin->teams[$this->level->getFolderName()]["gray"] = [];
			$this->spawntower = new Tower($this);
		}
	}

	public function getLevels(Player $player)
	{
		$level = null;
		$pl = $this->plugin->getServer()->getPluginManager()->getPlugin("Level_System");
		$colors = $pl->color->getColor($player);
		$level = $colors;

		return $level;

	}

	public function onShopMove(PlayerMoveEvent $ev)
	{
		$player = $ev->getPlayer();
		$from = $ev->getFrom();
		$to = $ev->getTo();
		if ($from->distance($to) < 0.1) {
			return;
		}
		$maxDistance = 10;
		foreach ($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $player) as $e) {
			if ($e instanceof Player) {
				continue;
			}
			$xdiff = $player->x - $e->x;
			$zdiff = $player->z - $e->z;
			$angle = atan2($zdiff, $xdiff);
			$yaw = (($angle * 180) / M_PI) - 90;
			$ydiff = $player->y - $e->y;
			$v = new Vector2($e->x, $e->z);
			$dist = $v->distance($player->x, $player->z);
			$angle = atan2($dist, $ydiff);
			$pitch = (($angle * 180) / M_PI) - 90;
			if (!isset($this->spectators[$player->getName()])) {
				if ($e instanceof ShopVillager || $e instanceof UpgradeVillager) {
					$pk = new MoveActorAbsolutePacket();
					$pk->entityRuntimeId = $e->getId();
					$pk->position = $e->asVector3();
					$pk->xRot = $pitch;
					$pk->yRot = $yaw;
					$pk->zRot = $yaw;
				    $player->dataPacket($pk);
				}
			}
		}
	}

	public function trackCompass(Player $player) : void{
        $currentTeam = $this->tracking[$player->getName()];
        $arrayTeam = ["red","blue","yellow","green"];
        $position = array_search($currentTeam, array_keys($arrayTeam));
        $teams = array_values($arrayTeam);
        $team = null;

        if(isset($teams[$position+1])){
            $team = $teams[$position+1];
        }else{
            $team = $teams[0];
        }

        $this->tracking[$player->getName()] = $team;

      // $player->setSpawn(Vector3::fromString($this->data["location"][$team]));



        foreach($player->getInventory()->getContents() as $slot => $item){
            if($item instanceof Compass){
                $player->getInventory()->removeItem($item);
                $player->getInventory()->setItem($slot, Item::get(Item::COMPASS)->setCustomName("§aPlayer Tracker"));
            }
        }
    }

	public function joinToArena(Player $player)
	{
		if (!$this->data["enabled"]) {
			$player->sendMessage("cant join arena");

			return;
		}

		if (count($this->players) >= $this->maxPlayers) {
			$player->sendMessage("ARENA FULL");
			$this->plugin->joinToRandomArena($player);
			$player->setImmobile();
			return;
		}

		if ($this->inGame($player)) {

			return;
		}

		$selected = false;
		for ($lS = 1; $lS <= $this->maxPlayers; $lS++) {
			if (!$selected) {
				if (!isset($this->players[$lS])) {
				
		
					$this->players[$lS] = $player;
					$this->index[$player->getName()] = $lS;
					$player->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"]), $this->level));
					$this->setTeam($player, $lS);
					$selected = true;

				}
			}
		}

		$player->removeAllEffects();
		$this->kill[$player->getId()] = 0;

		$this->finalkill[$player->getId()] = 0;
		$this->broken[$player->getId()] = 0;
		$player->getInventory()->clearAll();

		$player->getArmorInventory()->clearAll();
		$player->getEnderChestInventory()->clearAll();

		unset($this->spectators[$player->getName()]);
		$player->setAllowFlight(false);
		
		$player->setFlying(false);

		foreach (Server::getInstance()->getOnlinePlayers() as $players) {
			$players->showplayer($player);

		}

		$player->getCursorInventory()->clearAll();
		$player->setAbsorption(0);
		$player->getInventory()->setItem(8, Item::get(Item::BED, 14)->setCustomName("§cBack To Lobby"));
		$player->setGamemode($player::ADVENTURE);

		$player->setHealth(20);
		$player->setFood(20);
		$player->setNameTagVisible();
		$this->broadcastMessage("§f{$player->getDisplayName()} §eHas joined (§b" . count($this->players) . "§e/§b{$this->data["slots"]}§e)!");
	
	}


	public function setColorTag(Player $player)
	{
		$color = ["red" => "§c", "blue" => "§9", "green" => "§a", "yellow" => "§e"];
		$nametag = $player->getDisplayName();
        $level = $this->getLevels($player);
		$player->setNametag($color[$this->getTeam($player)] . " " . ucfirst($this->getTeam($player)[0]) . "§r " . "$level $nametag ");
	}


	public function setTeam($player, $index)
	{
		if (in_array($index, [1, 2, 3, 4])) {

			if ($this->getCountTeam("red") <= $this->maxPlayerPerTeam) {
				$this->plugin->teams[$player->getLevel()->getFolderName()]["red"][$player->getName()] = $player;
			}
		}

		if (in_array($index, [5, 6, 7, 8])) {
			if ($this->getCountTeam("blue") <= $this->maxPlayerPerTeam) {
				$this->plugin->teams[$player->getLevel()->getFolderName()]["blue"][$player->getName()] = $player;
			}
		}

		if (in_array($index, [9, 10, 11, 12])) {
			if ($this->getCountTeam("yellow") <= $this->maxPlayerPerTeam) {
				$this->plugin->teams[$player->getLevel()->getFolderName()]["yellow"][$player->getName()] = $player;
			}
		}

		if (in_array($index, [13, 14, 15, 16])) {
			if ($this->getCountTeam("green") <= $this->maxPlayerPerTeam) {
				$this->plugin->teams[$player->getLevel()->getFolderName()]["green"][$player->getName()] = $player;
			}
		}


	}

	public function checkWinner(){
		$redcount = $this->getCountTeam("red");
		$aquacount = $this->getCountTeam("blue");
		$yellowcount =  $this->getCountTeam("yellow");
		$limecount =  $this->getCountTeam("green");
		if($redcount <= 0 && $aquacount <= 0 && $yellowcount <= 0){
			$this->Wins("green");
		}
		if($limecount <= 0 && $aquacount <= 0 && $yellowcount <= 0){
			$this->Wins("red");
		}
		if($redcount <= 0 && $aquacount <= 0 && $limecount <= 0){
			$this->Wins("yellow");
		}
		if($redcount <= 0 && $limecount <= 0 && $yellowcount <= 0){
			$this->Wins("blue");
		}
	
	}


	public function spawnshop($pos)
	{

		$nbt = Entity::createBaseNBT($pos);
		$entity = new  ShopVillager($this->level, $nbt);
		$entity->arena = $this;
		$entity->teleport($pos);
		$entity->spawnToAll();
	}

	public function spawnupgrade($pos)
	{

		$nbt = Entity::createBaseNBT($pos);
		$entity = new  UpgradeVillager($this->level, $nbt);
		$entity->arena = $this;
		$entity->teleport($pos);
		$entity->spawnToAll();
	}

	public function initshop()
	{
		$shopPos = $this->data["shop"];
		for ($a = 1; $a <= count($shopPos); $a++) {
			$pos = Vector3::fromString($this->data["shop"][$a]);
			$this->spawnshop($pos->asVector3());
		}
	}

	public function initupgrade()
	{
	
		for ($a = 1; $a <= count($this->data["upgrade"]); $a++) {
			$pos = Vector3::fromString($this->data["upgrade"][$a]);
			$this->spawnupgrade($pos->asVector3());
		}
	}


	public function getTeam(Player $player)
	{
	    $team = "";
        if(isset($this->tempTeam[$player->getName()])){
            $team = $this->tempTeam[$player->getName()];
        }
		if (isset($this->plugin->teams[$player->getLevel()->getFolderName()]["red"][$player->getName()])) {
			$team = "red";
		}
		if (isset($this->plugin->teams[$player->getLevel()->getFolderName()]["blue"][$player->getName()])) {
			$team = "blue";
		}
		if (isset($this->plugin->teams[$player->getLevel()->getFolderName()]["yellow"][$player->getName()])) {
			$team =  "yellow";
		}
		if (isset($this->plugin->teams[$player->getLevel()->getFolderName()]["green"][$player->getName()])) {
			$team =  "green";
		}
		return $team;
	}


	/**
	 * @param Player $player
	 * @param string $quitMsg
	 * @param bool $death
	 */
	public function disconnectPlayer(Player $player, string $quitMsg = "", bool $death = false)
	{
		switch ($this->phase) {
			case Game::PHASE_LOBBY:
				$this->broadcastMessage("{$player->getDisplayName()} §ehas quit!");
				$index = "";
				foreach ($this->players as $i => $pl) {
					if ($pl->getId() == $player->getId()) {
						$index = $i;
					}
				}
				if ($index != "") {
					unset($this->players[$index]);
				}
				break;
			default:
				unset($this->players[$player->getName()]);
				break;
		}
		if ($player->isOnline() && $player !== null) {
            $team = $this->getTeam($player);
            if($this->inGame($player) && $this->phase == self::PHASE_GAME) {
                $count = 0;
                foreach ($this->players as $mate) {
                    if ($this->getTeam($mate) == $team) {
                        $count++;
                    }
                }
                if ($count <= 0) {
                    $spawn = Vector3::fromString($this->data["bed"][$team]);
                    foreach ($this->level->getEntities() as $g) {
                        if ($g instanceof Generator) {
                            if ($g->asVector3()->distance($spawn) < 20) {
                                $g->close();
                            }
                        }
                    }
                    $this->breakbed($team);
                    $color = [
                        "red" => "§cRed",
                        "blue" => "§9Blue",
                        "yellow" => "§eYellow",
                        "green" => "§aGreen"
                    ];
                    $this->broadcastMessage("§l§c» §r$color[$team] §fwas §cELIMINATED!");
                }
            }
            $this->unsetPlayer($player);
			if (!$death) {
				$this->broadcastMessage("§b{$player->getDisplayName()} §fDisconnected");
			}

			if ($quitMsg != "") {
				$this->broadcastMessage("$quitMsg");
			}
			$player->getServer()->dispatchCommand($player, "lobby");
		}
	}

    /**
     * @param Player $player
     */
	public function spectator(Player $player)
	{
		switch ($this->phase) {
			case Game::PHASE_LOBBY:
				$index = "";
				foreach ($this->players as $i => $p) {
					if ($p->getId() == $player->getId()) {
						$index = $i;
					}
				}
				if ($index != "") {
					unset($this->players[$index]);
				}
				break;
			default:
				unset($this->players[$player->getName()]);
				break;
		}
		$this->dropItem($player);
		$this->unsetPlayer($player);
        $player->getInventory()->setItem(8, Item::get(Item::BED, 14)->setCustomName("§cBack To Lobby"));
        $player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName("§aPlay Again"));
        $player->getInventory()->setItem(4, Item::get(Item::COMPASS)->setCustomName("§eSpectator"));
		$this->tempTeam[$player->getName()] = $this->getTeam($player);
		$player->removeAllEffects();
		$player->setGamemode($player::SPECTATOR);
		$this->spectators[$player->getName()] = $player;
		$player->setHealth(20);
		$player->setAllowFlight(true);
		$player->setFlying(true);
		$player->setFood(20);
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getInventory()->setHeldItemIndex(4);
		$spawnLoc = $this->level->getSafeSpawn();
		$spawnPos = new Vector3(round($spawnLoc->getX()) + 0.5, $spawnLoc->getY() + 10, round($spawnLoc->getZ()) + 0.5);
		$player->teleport($spawnPos);
		$player->addTitle("§l§cYOU DIED");
		$team = $this->getTeam($player);
		if($team !== ""){
		if ($this->phase == self::PHASE_GAME) {
			$count = 0;
			foreach ($this->players as $peler) {
				if ($this->getTeam($peler) == $team) {
					if (!isset($this->spectators[$peler->getName()])) {
						$count++;
					}
				}
			}
			if ($count <= 0) {
				$spawn = Vector3::fromString($this->data["bed"][$team]);
				foreach ($this->level->getEntities() as $g) {
					if ($g instanceof Generator) {
						if ($g->asVector3()->distance($spawn) < 20) {
							$g->close();
						}
					}
				}
				$color = [
					"red" => "§cRED",
					"blue" => "§9BLUE",
					"yellow" => "§eYELLOW",
					"green" => "§aGREEN"
				];
				$this->broadcastMessage("§l§fTEAM ELIMINATED > §r§b$color[$team] §fwas §cELIMINATED!");
			}
		}
	}

	

	}

    /**
     * @param DataPacketReceiveEvent $event
     */

    public function onReceivePacket(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if(isset($this->spectators[$event->getPlayer()->getName()])){
            if($packet instanceof LevelSoundEventPacket){
                $event->setCancelled();
                $player->sendDataPacket($packet);
            }
        }
    }

    /**
     * @param DataPacketSendEvent $event
     */

    public function onSpectator(DataPacketSendEvent $event){
        $packet = $event->getPacket();
        if($packet::NETWORK_ID == AdventureSettingsPacket::NETWORK_ID){
            $packet->setFlag($packet::NO_CLIP, false);
            $event->setCancelled(false);
        }
    }

    /**
     * @param $player
     */

	public function respawn($player)
	{
		if (!($player instanceof Player)) return;
		$player->setGamemode($player::SURVIVAL);
		$player->sendTitle("§l§aRESPAWNED!");
		$player->setHealth(20);
		$player->setAllowFlight(false);
		$player->setFlying(false);
		$player->setFood(20);
		$index = $this->getTeam($player);
		$vc = Vector3::fromString($this->data["location"][$index]);
		$x = $vc->getX();
		$y = $vc->getY();
		$z = $vc->getZ();
		$player->teleport(new Vector3($x + 0.5, $y + 0.5, $z + 0.5));
		$this->setArmor($player);
		$sword = Item::get(Item::WOODEN_SWORD);
		$this->setSword($player, $sword);
		$axe = $this->getAxeByTier($player, false);
		$pickaxe = $this->getPickaxeByTier($player, false);
		if (isset($this->axe[$player->getId()])) {
			if ($this->axe[$player->getId()] > 1) {
				$player->getInventory()->addItem($axe);
			}
		}
		if (isset($this->pickaxe[$player->getId()])) {
			if ($this->pickaxe[$player->getId()] > 1) {
				$player->getInventory()->addItem($pickaxe);
			}
		}
	}


	public function removeLobby()
	{
		$pos = Position::fromObject(Vector3::fromString($this->data["lobby"]));
		for ($x = -15; $x <= 16; $x++) {
			for ($y = -4; $y <= 10; $y++) {
				for ($z = -15; $z <= 16; $z++) {
					$level = $this->level;
					$block = $level->getBlock($pos->add($x, $y, $z));
					$level->setBlock($block, Block::get(0));
				}
			}
		}
	}

    /**
     * @param Player $player
     * @param string $type
     * @return bool
     */

	public function checkBed(Player $player,$type = "death"){
		$team = $this->getTeam($player);
		if($team !== ""){
        $vc = Vector3::fromString($this->data["bed"][$team]); 
		if($type == "status"){
			if(($tr = $this->level->getBlockAt($vc->x, $vc->y, $vc->z)) instanceof Bed){
				return true;
			} else {
				return false;
			}
		}
		if($type == "death"){
           if(($tr = $this->level->getBlockAt($vc->x, $vc->y, $vc->z)) instanceof Bed){
            $this->startRespawn($player);
           } else {
			$this->Spectator($player);
		   }
	    }
	   }
		return true;
        
	}

	public function dropItem(Player $player){
		foreach($player->getInventory()->getContents() as $cont){
			if(in_array($cont->getId(),[Item::WOOL,172,49,386,264,266,265,121,65,241,5,373])){
				$player->getLevel()->dropItem($player,$cont);

			}
		}
	}


	public function teamStatus($team) : string
	{
        $count = 0;
		$vc = Vector3::fromString($this->data["bed"][$team]);
		if($this->level->getBlockAt($vc->x, $vc->y, $vc->z) instanceof Bed) {
			$status = "§a✔§r";
		} else {
			foreach ($this->players as $apc) {
				if ($this->getTeam($apc) == $team) {
						$count++;
				}
			}
			if ($count == 0) {
				$status = "§c✘§r";
			} else {
				$status = "§b $count";
			}
		}
			return $status;
	
	}

	public function setSword(Player $player, Item $sword)
	{
		if ($player instanceof Player) {
			$team = $this->getTeam($player);
			$enchant = null;
			if (isset($this->utilities[$player->getLevel()->getFolderName()][$team]["sharpness"])) {

				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["sharpness"] == 2) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 1);

				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["sharpness"] == 3) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 2);
				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["sharpness"] == 4) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 3);
				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["sharpness"] == 5) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 4);
				}
			}
			if ($enchant !== null) {
				$sword->addEnchantment($enchant);
			}
			$sword->setUnbreakable(true);
			$player->getInventory()->removeItem($player->getInventory()->getItem(0));
			$player->getInventory()->setItem(0, $sword);
			if (isset($this->shear[$player->getName()])) {
				if (!$player->getInventory()->contains(Item::get(Item::SHEARS))) {
					$sh = Item::get(Item::SHEARS);
					$sh->setUnbreakable(true);
					$player->getInventory()->addItem($sh);
				}
			}
		}
	}

	public function setArmor(Player $player)
	{
		if ($player instanceof Player) {
			$team = $this->getTeam($player);
			$player->getArmorInventory()->clearAll();
			$enchant = null;
			if (isset($this->utilities[$player->getLevel()->getFolderName()][$team]["protection"])) {

				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["protection"] == 2) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 1);
				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["protection"] == 3) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 2);
				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["protection"] == 4) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 3);
				}
				if ($this->utilities[$player->getLevel()->getFolderName()][$team]["protection"] == 5) {
					$enchant = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PROTECTION), 4);
				}
			}
			$color = null;
			if ($team == "red") {
				$color = new Color(255, 0, 0);
			}
			if ($team == "blue") {
				$color = new Color(0, 0, 255);
			}
			if ($team == "yellow") {
				$color = new Color(246, 246, 126);
			}
			if ($team == "green") {
				$color = new Color(72, 253, 72);
			}
			if ($color == null) {
				$color = new Color(0, 0, 0);
			}
			if (isset($this->armor[$player->getName()])) {
				$arm = $player->getArmorInventory();
				$armor = $this->armor[$player->getName()];
				if ($armor == "chainmail") {
					$player->getArmorInventory()->clearAll();
					$helm = Item::get(Item::LEATHER_CAP);
					$helm->setCustomColor($color);
					$helm->setUnbreakable(true);
					if ($enchant !== null) {
						$helm->addEnchantment($enchant);
					}
					$arm->setHelmet($helm);
					$chest = Item::get(Item::LEATHER_TUNIC);
					$chest->setCustomColor($color);
					if ($enchant !== null) {
						$chest->addEnchantment($enchant);
					}
					$chest->setUnbreakable(true);
					$arm->setChestplate($chest);
					$leg = Item::get(Item::CHAINMAIL_LEGGINGS);
					if ($enchant !== null) {
						$leg->addEnchantment($enchant);
					}
					$leg->setUnbreakable(true);
					$leg->setCustomColor($color);
					$arm->setLeggings($leg);
					$boots = Item::get(Item::CHAINMAIL_BOOTS);
					$boots->setUnbreakable(true);
					$boots->setCustomColor($color);
					if ($enchant !== null) {
						$boots->addEnchantment($enchant);
					}
					$arm->setBoots($boots);
				}
				if ($armor == "iron") {
					$helm = Item::get(Item::LEATHER_CAP);
					$helm->setCustomColor($color);
					$helm->setUnbreakable(true);
					if ($enchant !== null) {
						$helm->addEnchantment($enchant);
					}
					$arm->setHelmet($helm);
					$chest = Item::get(Item::LEATHER_TUNIC);
					$chest->setCustomColor($color);
					if ($enchant !== null) {
						$chest->addEnchantment($enchant);
					}
					$chest->setUnbreakable(true);
					$arm->setChestplate($chest);
					$leg = Item::get(Item::IRON_LEGGINGS);
					if ($enchant !== null) {
						$leg->addEnchantment($enchant);
					}
					$leg->setUnbreakable(true);
					$arm->setLeggings($leg);
					$boots = Item::get(Item::IRON_BOOTS);
					if ($enchant !== null) {
						$boots->addEnchantment($enchant);
					}
					$boots->setUnbreakable(true);
					$arm->setBoots($boots);
				}
				if ($armor == "diamond") {
					$helm = Item::get(Item::LEATHER_CAP);
					$helm->setCustomColor($color);
					$helm->setUnbreakable(true);
					if ($enchant !== null) {
						$helm->addEnchantment($enchant);
					}
					$arm->setHelmet($helm);
					$chest = Item::get(Item::LEATHER_TUNIC);
					$chest->setCustomColor($color);
					if ($enchant !== null) {
						$chest->addEnchantment($enchant);
					}
					$chest->setUnbreakable(true);
					$arm->setChestplate($chest);
					$leg = Item::get(Item::DIAMOND_LEGGINGS);
					if ($enchant !== null) {
						$leg->addEnchantment($enchant);
					}
					$leg->setUnbreakable(true);
					$arm->setLeggings($leg);
					$leg->setCustomColor($color);
					$boots = Item::get(Item::DIAMOND_BOOTS);
					if ($enchant !== null) {
						$boots->addEnchantment($enchant);
					}
					$boots->setCustomColor($color);
					$boots->setUnbreakable(true);
					$arm->setBoots($boots);
				}
			} else {
				$arm = $player->getArmorInventory();
				$helm = Item::get(Item::LEATHER_CAP);
				$helm->setCustomColor($color);
				$helm->setUnbreakable(true);
				if ($enchant !== null) {
					$helm->addEnchantment($enchant);
				}
				$arm->setHelmet($helm);
				$chest = Item::get(Item::LEATHER_TUNIC);
				$chest->setCustomColor($color);
				if ($enchant !== null) {
					$chest->addEnchantment($enchant);
				}
				$chest->setUnbreakable(true);
				$arm->setChestplate($chest);
				$leg = Item::get(Item::LEATHER_PANTS);
				$leg->setCustomColor($color);
				if ($enchant !== null) {
					$leg->addEnchantment($enchant);
				}
				$leg->setUnbreakable(true);
				$arm->setLeggings($leg);
				$boots = Item::get(Item::LEATHER_BOOTS);
				$boots->setCustomColor($color);
				if ($enchant !== null) {
					$boots->addEnchantment($enchant);
				}
				$boots->setUnbreakable(true);
				$arm->setBoots($boots);
			}
		}
	}

	public function startRespawn(Player $player)
	{
		if (!($player instanceof Player)) return;
		$this->dropItem($player);
		$player->getInventory()->clearAll();
		$player->removeAllEffects();
		$player->setGamemode($player::SPECTATOR);
		$player->setAllowFlight(true);
		$player->teleport($player->asVector3()->add(0,5));
		$player->sendTitle("§l§cYOU DIED");
		$this->respawnC[$player->getName()] = 6;
		$this->respawn[$player->getName()] = $player;
		$axe = $this->getLessTier($player, true);
		$pickaxe = $this->getLessTier($player, false);
		$this->axe[$player->getId()] = $axe;
		$this->pickaxe[$player->getId()] = $pickaxe;
	}

	public function addexp(Player $player)
	{


		$exp = mt_rand(1, 100);
		$player->sendMessage("§b+$exp EXP");
		$coins = mt_rand(1, 50);
		$exp =  mt_rand(1,100);
		$player->sendMessage("§6+$coins Coins");
		$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender($this), " addcoin {$player->getName()} $coins");
        $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender($this), " addcoin {$player->getName()} $exp");
	}



	public function startGame()
	{

		$players = [];
		$this->initshop();
		$this->initupgrade();
		$this->level->setTime(5000);


		foreach ($this->players as $player) {
			$this->addexp($player);
            $api = $this->plugin->getScore();
			$api->remove($player);
			$this->plugin->mysqldata->addscore($player, "playtime");
			$this->kill[$player->getId()] = 0;
			$this->finalkill[$player->getId()] = 0;
			$this->setColorTag($player);
			$team = $this->getTeam($player);
			$player->setImmobile();
		    $this->teleport($player);

			$player->setScoreTag("§f{$player->getHealth()} §c❤️ §l");
			$player->setNameTagVisible();
			$player->getInventory()->clearAll();
			$this->axe[$player->getId()] = 1;
			$this->tracking[$player->getName()] = $this->getTeam($player);


			$this->pickaxe[$player->getId()] = 1;
			$player->setGamemode($player::SURVIVAL);


			$this->setArmor($player);
			$this->setSword($player, Item::get(Item::WOODEN_SWORD));
			$player->setImmobile(false);
			$player->sendTitle("§l§fFIGHT");

			$players[$player->getName()] = $player;

		}
		$this->phase = self::PHASE_GAME;
		$this->players = $players;
		$this->prepareWorld();
		$this->removeLobby();


	}

	public function teleport($player){
		$team = $this->getTeam($player);
		$vc = Vector3::fromString($this->data["location"][$team]);
		$x = $vc->getX();
		$y = $vc->getY();
		$z = $vc->getZ();
		$player->teleport(new Vector3($x + 0.5, $y + 0.5, $z + 0.5));
		
	}

	public function unsetPlayer(Player $player){

		unset($this->broken[$player->getId()]);

		unset($this->finalkill[$player->getId()]);

		unset($this->kill[$player->getId()]);

		unset($this->plugin->teams[$player->getLevel()->getFolderName()][$this->getTeam($player)][$player->getName()]);

		unset($this->armor[$player->getName()]);

		unset($this->shear[$player->getName()]);

		unset($this->axe[$player->getId()]);

		unset($this->inChest[$player->getId()]);

		unset($this->pickaxe[$player->getId()]);

		unset($this->players[$player->getName()]);

		unset($this->spectators[$player->getName()]);

	}
	


	public function getCountTeam($team)
	{
     foreach($this->players as $player){
		if ($team == "red") {
            return count($this->plugin->teams[$player->getLevel()->getFolderName()]["red"]);

		}
		if ($team == "blue") {

            return count($this->plugin->teams[$player->getLevel()->getFolderName()]["blue"]);

		}
		if ($team == "yellow") {
            return count($this->plugin->teams[$player->getLevel()->getFolderName()]["yellow"]);
		}
		if ($team == "green") {
            return count($this->plugin->teams[$player->getLevel()->getFolderName()]["green"]);
		}
         }
       return "";
	}

	public function calculate(Vector3 $pos1, Vector3 $pos2)
	{
		$pos1 = Vector3::fromString($pos1);
		$pos2 = Vector3::fromString($pos2);
		$max = new Vector3(max($pos1->getX(), $pos2->getX()), max($pos1->getY(), $pos2->getY()), max($pos1->getZ(), $pos2->getZ()));
		$min = new Vector3(min($pos1->getX(), $pos2->getX()), min($pos1->getY(), $pos2->getY()), min($pos1->getZ(), $pos2->getZ()));
		return $min->add($max->subtract($min)->divide(2)->ceil());
	}


	public function prepareWorld()
	{
		foreach (["red", "blue", "yellow", "green"] as $teams) {
			$this->utilities[$this->level->getFolderName()][$teams]["generator"] = 1;
			$this->utilities[$this->level->getFolderName()][$teams]["sharpness"] = 1;
			$this->utilities[$this->level->getFolderName()][$teams]["protection"] = 1;
			$this->utilities[$this->level->getFolderName()][$teams]["haste"] = 1;
			$this->utilities[$this->level->getFolderName()][$teams]["health"] = 1;
			$this->utilities[$this->level->getFolderName()][$teams]["traps"] = 1;
			
		}
		$this->initGenerator();
		$this->checkTeam();
		foreach($this->level->getEntities() as $e){
			if($e instanceof ItemEntity){
				$e->flagForDespawn();
			}
		}
	}

	public function initGenerator()
	{
		foreach ($this->level->getTiles() as $tile) {
			if ($tile instanceof Furnace) {
				$nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 1, $tile->z + 0.5));
				$path = $this->plugin->getDataFolder() . "diamond.png";
				$skin = $this->plugin->getSkinFromFile($path);
				$nbt->setTag(new CompoundTag('Skin', [
					new StringTag('Data', $skin->getSkinData()),
					new StringTag('Name', 'Standard_CustomSlim'),
					new StringTag('GeometryName', 'geometry.player_head'),
					new ByteArrayTag('GeometryData', Generator::GEOMETRY)]));
				$g = new Generator($tile->getLevel(), $nbt);
				$g->type = "gold";
				$g->Glevel = 1;
				$g->setScale(0.000001);
				$g->spawnToAll();
				$tile->getLevel()->setBlock(new Vector3($tile->x, $tile->y, $tile->z), Block::get(Block::STONE));
			}
			if ($tile instanceof EnchantTable) {
				$nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 4, $tile->z + 0.5));
				$path = $this->plugin->getDataFolder() . "diamond.png";
				$skin = $this->plugin->getSkinFromFile($path);
				$nbt->setTag(new CompoundTag('Skin', [
					new StringTag('Data', $skin->getSkinData()),
					new StringTag('Name', 'Standard_CustomSlim'),
					new StringTag('GeometryName', 'geometry.player_head'),
					new ByteArrayTag('GeometryData', Generator::GEOMETRY)]));
				$g = new Generator($tile->getLevel(), $nbt);
				$g->type = "diamond";
				$g->Glevel = 1;
				$g->setScale(1.4);
				$g->yaw = 0;
				$g->spawnToAll();
				$tile->getLevel()->setBlock(new Vector3($tile->x, $tile->y, $tile->z), Block::get(Block::STONE));
			}
			if ($tile instanceof Skull) {
				$nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 4, $tile->z + 0.5));
				$path = $this->plugin->getDataFolder() . "emerald.png";
				$skin = $this->plugin->getSkinFromFile($path);
				$nbt->setTag(new CompoundTag('Skin', [
					new StringTag('Data', $skin->getSkinData()),
					new StringTag('Name', 'Standard_CustomSlim'),
					new StringTag('GeometryName', 'geometry.player_head'),
					new ByteArrayTag('GeometryData', Generator::GEOMETRY)]));
				$g = new Generator($tile->getLevel(), $nbt);
				$g->type = "emerald";
				$g->Glevel = 1;
				$g->yaw = 0;
				$g->setScale(1.4);
				$g->spawnToAll();
				$tile->getLevel()->setBlock(new Vector3($tile->x, $tile->y, $tile->z), Block::get(Block::STONE));
			}
		}
	}

	public function upgradeGeneratorTier(string $type,  $level)
	{
		if ($type == "diamond") {
			foreach ($this->level->getEntities() as $e) {
				if ($e instanceof Generator) {
					if ($e->type == "diamond") {
						$e->Glevel = $level;
					}
				}
			}
		}
		if ($type == "emerald") {
			foreach ($this->level->getEntities() as $e) {
				if ($e instanceof Generator) {
					if ($e->type == "emerald") {
						$e->Glevel = $level;
					}
				}
			}
		}
	}

	public function bedStatus($team){
		$status = null;
        $vc = Vector3::fromString($this->data["bed"][$team]);
        if($this->level->getBlockAt($vc->x, $vc->y, $vc->z) instanceof Bed){
            $status =  true;
        } else {
            $status = false;
        }
        return $status;
	}

	
	public function destroyAllBeds()
	{
		$this->broadcastMessage("§eAll bed was destoyed");
		foreach (["red", "blue", "yellow", "green"] as $t) {
			$pos = Vector3::fromString($this->data["bed"][$t]);
            $bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z);
			if ($bed instanceof Bed) {
				$next = $bed->getOtherHalf();
				$this->level->setBlock($bed, Block::get(0));
				$this->level->setBlock($next, Block::get(0));
				foreach($this->players as $player){
				if ($player instanceof Player) {
					$player->addTitle("§l§CBED DESTORYED", "§r§cyou will no longer respawn");
					$this->addSound($player, 'mob.wither.death');
				}
			}
			}
		}
	}

	public function checkTeam()
	{
		if ($this->getCountTeam("red") <= 0) {
			$pos = Vector3::fromString($this->data["bed"]["red"]);
			if (($bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
				$this->level->setBlock($bed, Block::get(0));
				$this->level->setBlock($bed->getOtherHalf(), Block::get(0));
			}
			foreach ($this->level->getEntities() as $g) {
				if ($g instanceof Generator) {
					if ($g->asVector3()->distance($pos) < 20) {
						$g->close();
					}
				}
			}
		}
		if ($this->getCountTeam("blue") <= 0) {
			$pos = Vector3::fromString($this->data["bed"]["blue"]);
			if (($bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
				$this->level->setBlock($bed, Block::get(0));
				$this->level->setBlock($bed->getOtherHalf(), Block::get(0));
			}
			foreach ($this->level->getEntities() as $g) {
				if ($g instanceof Generator) {
					if ($g->asVector3()->distance($pos) < 20) {
						$g->close();
					}
				}
			}
		}
		if ($this->getCountTeam("yellow") <= 0) {
			$pos = Vector3::fromString($this->data["bed"]["yellow"]);
			if (($bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
				$this->level->setBlock($bed, Block::get(0));
				$this->level->setBlock($bed->getOtherHalf(), Block::get(0));
			}
			foreach ($this->level->getEntities() as $g) {
				if ($g instanceof Generator) {
					if ($g->asVector3()->distance($pos) < 20) {
						$g->close();
					}
				}
			}
		}
		if ($this->getCountTeam("green") <= 0) {
			$pos = Vector3::fromString($this->data["bed"]["green"]);
			if (($bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
				$this->level->setBlock($bed, Block::get(0));
				$this->level->setBlock($bed->getOtherHalf(), Block::get(0));
			}
			foreach ($this->level->getEntities() as $g) {
				if ($g instanceof Generator) {
					if ($g->asVector3()->distance($pos) < 20) {
						$g->close();
					}
				}
			}
		}
	}


	public function breakbed($team, $player = null)
	{
		if (!isset($this->data["bed"][$team])) return;
		$pos = Vector3::fromString($this->data["bed"][$team]);
		$bed = $bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z);
		if ($bed instanceof Bed) {
			$next = $bed->getOtherHalf();
			$this->level->addParticle(new DestroyBlockParticle($bed, $bed));
			$this->level->addParticle(new DestroyBlockParticle($next, $bed));
			$this->level->setBlock($bed, Block::get(0));
			$this->level->setBlock($next, Block::get(0));
		}
		$c = null;
		if ($team == "red") {
			$c = "§c";
		}
		if ($team == "blue") {
			$c = "§9";
		}
		if ($team == "yellow") {
			$c = "§e";
		}
		if ($team == "green") {
			$c = "§a";
		}
		$tn = ucwords($team);
		if ($player instanceof Player) {
			$this->broadcastMessage("§l§fBED DECONTRUCTION > §r§e{$c}{$tn} §ebed was destroyed by §r§f {$player->getDisplayName()}");
			if (isset($this->broken[$player->getId()])) {
				$this->broken[$player->getId()]++;
			}
		}
		foreach ($this->players as $p) {
			if ($p instanceof Player && $this->getTeam($p) == $team) {
				$p->sendTitle("§l§CBED DESTORYED", "§r§cyou will no longer respawn");
				$this->addSound($p, 'mob.wither.death');
			}
		}
	}

	public function getFireworksColor(): string
	{
		$colors = [
			Fireworks::COLOR_BLACK,
			Fireworks::COLOR_RED,
			Fireworks::COLOR_DARK_GREEN,
			Fireworks::COLOR_BROWN,
			Fireworks::COLOR_BLUE,
			Fireworks::COLOR_DARK_PURPLE,
			Fireworks::COLOR_DARK_AQUA,
			Fireworks::COLOR_GRAY,
			Fireworks::COLOR_DARK_GRAY,
			Fireworks::COLOR_PINK,
			Fireworks::COLOR_GREEN,
			Fireworks::COLOR_YELLOW,
			Fireworks::COLOR_LIGHT_AQUA,
			Fireworks::COLOR_DARK_PINK,
			Fireworks::COLOR_GOLD,
			Fireworks::COLOR_WHITE
		];

		return $colors[array_rand($colors)];
	}

	public function addRocket(Player $player)
	{
		$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
		    $fw = ItemFactory::get(Item::FIREWORKS);
			if ($fw instanceof Fireworks) {
				$fw->addExplosion(mt_rand(0, 4), $this->getFireworksColor(), "", true, true);
				$fw->setFlightDuration(3);
				$level = $player->getLevel();
				if ($level instanceof Level) {
					$x = $player->getX();
					$y = $player->getY();
					$z = $player->getZ();
					if (!$player == null) {
						$nbt = FireworksRocket::createBaseNBT(new Vector3($x, $y, $z), new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
						$entity = FireworksRocket::createEntity("FireworksRocket", $level, $nbt, $fw);

						if ($entity instanceof FireworksRocket) {
							$entity->spawnToAll();
						}
					}
				}
			}
		}), 15);
		$player->getLevel()->broadcastLevelSoundEvent(new Vector3($player->getX(), $player->getY(), $player->getZ()), LevelSoundEventPacket::SOUND_LAUNCH);

	}

	public function destroyEntity(){
        foreach ($this->level->getEntities() as $g) {
            if ($g instanceof Generator) {
                $g->close();
            }
            if ($g instanceof EnderDragon) {
                $g->close();
            }
            if ($g instanceof Golem) {
                $g->close();
            }
            if ($g instanceof TNT) {
                $g->close();
            }
            if ($g instanceof EggBridge) {
                $g->close();
            }
            if ($g instanceof Bedbug) {
                $g->close();
            }
            if ($g instanceof Fireball) {
                $g->close();
            }
            if ($g instanceof ItemEntity) {
                $g->close();
            }
            if ($g instanceof ShopVillager) {
                $g->close();
            }
            if ($g instanceof UpgradeVillager) {
                $g->close();
            }
        }
        foreach($this->level->getPlayers() as $p){
            unset($this->tempTeam[$p->getName()]);
            $this->placedBlock = [];
        }
    }

	public function Wins(string $team)
	{
	    $this->destroyEntity();
		foreach ($this->level->getPlayers() as $p) {
			$p->setNametag($p->getDisplayName());
			$p->setScoreTag("");
			if (isset($this->ghost[$p->getName()])) {
				$p->setGamemode($p::SURVIVAL);
				$p->getInventory()->removeItem(item::get(Item::COMPASS));
			}
		}


		foreach ($this->players as $player) {
			$this->TopFinalKills($player);
            $cfg = new Config($this->plugin->getDataFolder(). "finalkills.yml",Config::YAML);
            $cfg->set($player->getName(),0);
            $cfg->save();
			if ($this->getTeam($player) == $team) {
				$player->setHealth(20);
				$player->setFood(20);
				$player->getInventory()->clearAll();
				$this->plugin->mysqldata->addscore($player, "victory");
				$player->getArmorInventory()->clearAll();
				$player->getCursorInventory()->clearAll();
				$this->addRocket($player);
				$this->addRocket($player);
				$this->addRocket($player);
				$this->addRocket($player);
				$this->addRocket($player);

				$player->sendTitle("§l§eVICTORY");
				$this->addSound($player, "random.levelup", 1.25);
                $api = $this->plugin->getScore();
				$api->remove($player);
				$player->getInventory()->clearAll();
				$this->unsetPlayer($player);
				$player->getInventory()->setItem(8, Item::get(Item::BED, 14)->setCustomName("§cBack To Lobby"));
				$player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName("§aPlay Again"));
			}
		}
		$this->placedBlock = [];
		$this->utilities[$this->level->getFolderName()] = [];
		$this->axe = [];
		$this->pickaxe = [];
		$this->milk = [];
		$this->inChest = [];
		$teamName = [
			"red" => "§r§c Red",
			"blue" => "§r§9 Blue",
			"green" => "§r§a Green",
			"yellow" => "§r§e Yellow"
		];
		$this->broadcastMessage("§aTeam $teamName[$team] §eVictory");
		$this->phase = self::PHASE_RESTART;
	}



	public function TopFinalKills(Player $player): void
	{
		if ($player instanceof Player) {
			$player->sendMessage("§l§e===================================");
			$player->sendMessage("         §l§aTOP FINAL KILLS          ");
			$player->sendMessage("                                     ");
			$kconfig = new Config($this->plugin->getDataFolder() . "finalkills.yml", Config::YAML, [$player->getName() => 0]);
			$kills = $kconfig->getAll();
			arsort($kills);
			$i = 0;
			foreach ($kills as $playerName => $killCount) {
				$i++;
				if ($i < 4 && $killCount) {
					switch ($i) {
						case 1:
							$satu = "§a1 st  §f" . $playerName . " - §f" . $killCount . "\n \n \n";
							$player->sendMessage($satu);
							break;
						case 2:
							$dua = "§a2 st §f" . $playerName . " - §f" . $killCount . "\n \n \n";
							$player->sendMessage($dua);
							break;
						case 3:
							$tiga = "§a3 st §f" . $playerName . " - §f" . $killCount . "\n \n \n";
							$player->sendMessage($tiga);
							break;
						default:

							break;
					}
				}
			}
			$player->sendMessage("                                     ");
			$player->sendMessage("§l§e===================================");
		}
	}

	public function draw()
	{
		$this->destroyEntity();
		foreach ($this->level->getPlayers() as $p) {
			$p->setDisplayName($p->getName());
			$p->setScoreTag("");
			$p->setNameTag($p->getName());

			if (isset($this->ghost[$p->getName()])) {
				$p->setGamemode($p::SURVIVAL);
				$p->getInventory()->removeItem(item::get(Item::COMPASS));

			}
		}
		foreach ($this->players as $player) {
			if ($player === null || (!$player instanceof Player) || (!$player->isOnline())) {
				$this->phase = self::PHASE_RESTART;
				return;
			}

			$player->setHealth(20);
			$player->setFood(20);
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
			$api = $this->plugin->getScore();
			$api->remove($player);
			$this->unsetPlayer($player);
			$player->getInventory()->setItem(8, Item::get(Item::BED, 14)->setCustomName("§cBack To Lobby"));
			$player->getInventory()->setItem(0, Item::get(Item::PAPER)->setCustomName("§aPlay Again"));
		}
		$this->placedBlock = [];
		$this->utilities[$this->level->getFolderName()] = [];
		$this->axe = [];
		$this->pickaxe = [];
		$this->milk = [];
		$this->inChest = [];
		$this->broadcastMessage("§l§cGAME OVER", self::MSG_TITLE);
		$this->phase = self::PHASE_RESTART;
	}


	public function inGame(Player $player): bool
	{
		if ($this->phase == self::PHASE_LOBBY) {
			$inGame = false;
			foreach ($this->players as $players) {
				if ($players->getId() == $player->getId()) {
					$inGame = true;
				}
			}
			return $inGame;
		} else {
			return isset($this->players[$player->getName()]);
		}
	}

	/**
	 * @param string $message
	 * @param int $id
	 * @param string $subMessage
	 */
	public function broadcastMessage(string $message, int $id = 0, string $subMessage = "")
	{
		foreach ($this->level->getPlayers() as $player) {
			switch ($id) {
				case self::MSG_MESSAGE:
					$player->sendMessage($message);
					break;
				case self::MSG_TIP:
					$player->sendTip($message);
					break;
				case self::MSG_POPUP:
					$player->sendPopup($message);
					break;
				case self::MSG_TITLE:
					$player->addTitle($message, $subMessage);
					break;
			}
		}
	}

	public function onTrans(InventoryTransactionEvent $event)
	{
		$transaction = $event->getTransaction();
		if ($this->phase !== self::PHASE_GAME) return;
		foreach ($transaction->getActions() as $action) {
			$item = $action->getSourceItem();
			$source = $transaction->getSource();
			if ($source instanceof Player) {
				if ($this->inGame($source)) {
					if ($action instanceof SlotChangeAction) {
						if ($action->getInventory() instanceof PlayerInventory) {
							if ($this->phase == self::PHASE_LOBBY) {
								$event->setCancelled();
							}
							
							if ($this->phase == self::PHASE_RESTART) {
								$event->setCancelled();
							}
						}
						if(isset($this->inChest[$source->getId()]) && $action->getInventory() instanceof PlayerInventory){
			                if($item instanceof Pickaxe || $item instanceof Axe){
			                    $event->setCancelled();
			                }
			            }
						if($action->getInventory() instanceof ArmorInventory){
							if($item instanceof Armor){
								$event->setCancelled();
							} 
						}
					}
				}
			}
		}
	}


	public function hitEntity(ProjectileHitEntityEvent $event)
	{
		$pro = $event->getEntity();
		$hitEntity = $event->getEntityHit();
		$owner = $pro->getOwningEntity();
		if ($pro instanceof Arrow) {
			if ($owner instanceof Player && $hitEntity instanceof Player) {
				if ($this->inGame($owner)) {
					$owner->sendMessage("§b{$hitEntity->getDisplayName()} §fis now {$hitEntity->getHealth()} heart");

				}
			}
		}
	}



	public function itemSpawnEvent(ItemSpawnEvent $event)
	{
		$entity = $event->getEntity();
		if ($entity->level->getFolderName() !== $this->level->getFolderName()) return;
		$entities = $entity->getLevel()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(1, 1, 1));
		if (empty($entities)) {
			return;
		}
		if ($entity instanceof ItemEntity) {
			$originalItem = $entity->getItem();
			foreach ($entities as $e) {
				if ($e instanceof ItemEntity and $entity->getId() !== $e->getId()) {
					$item = $e->getItem();
					if (in_array($originalItem->getId(), [Item::DIAMOND, Item::EMERALD])) {
						if ($item->getId() === $originalItem->getId()) {
							$e->flagForDespawn();
							$entity->getItem()->setCount($originalItem->getCount() + $item->getCount());
						}
					}
				}
			}
		}
	}

	public function onCraftItem(CraftItemEvent $event)
	{
		$player = $event->getPlayer();
		if ($player instanceof Player) {
			if ($this->inGame($player)) {
				$event->setCancelled();
			}
		}
	}

	public function onConsume(PlayerItemConsumeEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if ($this->inGame($player)) {
			if ($item->getId() == 373 && $item->getDamage() == 16) {
				$event->setCancelled();
				$player->getInventory()->setItemInHand(Item::get(0));
				$eff = new EffectInstance(Effect::getEffect(Effect::SPEED), 900, 1);
				$eff->setVisible(true);
				$player->addEffect($eff);
			}
			if ($item->getId() == 373 && $item->getDamage() == 11) {
				$event->setCancelled();
				$player->getInventory()->setItemInHand(Item::get(0));
				$eff = new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 900, 3);
				$eff->setVisible(true);
				$player->addEffect($eff);
			}
			if ($item->getId() == 373 && $item->getDamage() == 7) {
				$event->setCancelled();
				$player->getInventory()->setItemInHand(Item::get(0));
				$eff = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 600, 1);
				$eff->setVisible(true);
				$player->addEffect($eff);
				$this->setInvis($player, true);
			}
			if ($item->getId() == Item::BUCKET && $item->getDamage() == 1) {
				$event->setCancelled();
				$player->getInventory()->setItemInHand(Item::get(0));
				$this->milk[$player->getId()] = 30;
				$player->sendMessage("§eTrap  effected on §a30 §eseconds!");
			}
		}
	}

	public function setInvis($player, $value)
	{
		$arm = $player->getArmorInventory();
		if ($value) {
			$this->invis[$player->getId()] = $player;
			$hide = $this->armorInvis($player);
			foreach ($this->players as $p) {
				if ($player->getId() == $p->getId()) {
					$pk2 = new InventoryContentPacket();
					$pk2->windowId = $player->getWindowId($arm);
					$pk2->items = array_map([ItemStackWrapper::class, 'legacy'], $arm->getContents(true));
					$player->dataPacket($pk2);
				} else {
					if ($this->getTeam($player) !== $this->getTeam($p)) {
						$p->dataPacket($hide);
					}
				}
			}
		} else {
			if (isset($this->invis[$player->getId()])) {
				unset($this->invis[$player->getId()]);
			}
			$player->setInvisible(false);
			$nohide = $this->armorInvis($player, false);
			foreach ($this->players as $p) {
				if ($player->getId() == $p->getId()) {
					
					$pk2 = new InventoryContentPacket();
					$pk2->windowId = $player->getWindowId($arm);
					$pk2->items = array_map([ItemStackWrapper::class, 'legacy'], $arm->getContents(true));
					$player->dataPacket($pk2);
				} else {
					if ($this->getTeam($player) !== $this->getTeam($p)) {
						$p->dataPacket($nohide);
					}
				}
			}
		}
	}

	public function armorInvis($player, bool $hide = true) : MobArmorEquipmentPacket
	{
		if ($hide) {
			$pk = new MobArmorEquipmentPacket();
			$pk->entityRuntimeId = $player->getId();
			$pk->head = ItemStackWrapper::legacy(Item::get(ItemIds::AIR));
			$pk->chest = ItemStackWrapper::legacy(Item::get(ItemIds::AIR));
			$pk->legs = ItemStackWrapper::legacy(Item::get(ItemIds::AIR));
			$pk->feet = ItemStackWrapper::legacy(Item::get(ItemIds::AIR));
			$pk->encode();
			return $pk;
		} else {
			$arm = $player->getArmorInventory();
			$pk = new MobArmorEquipmentPacket();
			$pk->entityRuntimeId = $player->getId();
			$pk->head = $arm->getHelmet();
			$pk->chest = $arm->getChestplate();
			$pk->legs = $arm->getLeggings();
			$pk->feet = $arm->getBoots();
			$pk->encode();
			return $pk;
		}
	}

	public function onExplode(EntityExplodeEvent $event)
	{
		$tnt = $event->getEntity();

		if ($tnt->getLevel()->getFolderName() !== $this->level->getFolderName()) return;

		if ($tnt instanceof PrimedTNT || $tnt instanceof Fireball) {
			$newList = [];
			foreach ($event->getBlockList() as $block) {
				$pos = new Vector3(round($block->x) + 0.5, $block->y, round($block->z) + 0.5);
				if ($block->getId() !== Block::OBSIDIAN && $block->getId() !== 241) {
					if (in_array($pos->__toString(), $this->placedBlock)) {
						$newList[] = $block;
					}
				}
			}
			$event->setBlockList($newList);
		}
	}

	public function blockUpdateEvent(BlockUpdateEvent $event)
	{
		$block = $event->getBlock();
		if($block->getLevel()->getBlockAt($block->x,$block->y,$block->z) instanceof Air){
			$event->setCancelled(false);
		} else {
			$event->setCancelled(true);
		}
	}

	public function leavesDecayEvent(LeavesDecayEvent $event)
	{
		$event->setCancelled();
	}

	/**
	 * @param PlayerMoveEvent $event
	 */
	public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		if (isset($this->ghost[$player->getName()])) {
			if ($player->getY() < -3) {
				$player->teleport($this->level->getSafeSpawn());
			}
		}
		if ($this->inGame($player)) {
			if ($this->phase == self::PHASE_LOBBY) {
				$lv = Vector3::fromString($this->data["lobby"]);
				$p = $lv->getY() - 3;
				if ($player->getLevel()->getFolderName() == $this->level->getFolderName()) {
					if ($player->getY() < $p) {
						$player->teleport(Vector3::fromString($this->data["lobby"]));
					}
				}
			}
	
			if ($this->phase == self::PHASE_GAME) {
				if (isset($this->milk[$player->getId()])) return;
				foreach (["red", "blue", "yellow", "green"] as $teams) {
					$pos = Vector3::fromString($this->data["bed"][$teams]);
					if ($player->distance($pos) < 4) {
						if ($this->getTeam($player) !== $teams) {
							if (isset($this->itstrap[$teams])) {
								$this->utilities[$this->level->getFolderName()][$teams]["traps"]--;
								unset($this->itstrap[$teams]);
								$eff = new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 160, 0);
								$eff->setVisible(true);
								$player->addEffect($eff);
								$eff = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 160, 1);
								$eff->setVisible(true);
								$player->addEffect($eff);
								foreach ($this->players as $p) {
									if ($this->getTeam($p) == $teams) {
										$p->sendTitle("§l§cTRAP TRIGGERED");
									}
								}
							}
							if (isset($this->minertrap[$teams])) {
								$this->utilities[$player->getLevel()->getFolderName()][$teams]["traps"]--;
								unset($this->minertrap[$teams]);
								$eff = new EffectInstance(Effect::getEffect(Effect::FATIGUE), 160, 0);
								$eff->setVisible(true);
								$player->addEffect($eff);
								foreach ($this->players as $p) {
									if ($this->getTeam($p) == $teams) {
									    $p->sendTitle("§l§cTRAP TRIGGERED");
									}
								}
							}
							if (isset($this->alarmtrap[$teams])) {
								$this->utilities[$player->getLevel()->getFolderName()][$teams]["traps"]--;
								unset($this->alarmtrap[$teams]);
								foreach ($this->players as $p) {
									if ($this->getTeam($p) == $teams) {
										$p->sendTitle("§l§cTRAP TRIGGERED");
									}
								}
							}
							if (isset($this->countertrap[$teams])) {
								$this->utilities[$player->getLevel()->getFolderName()][$teams]["traps"]--;
								unset($this->countertrap[$teams]);
								foreach ($this->players as $p) {
									if ($this->getTeam($p) == $teams) {
										$p->sendTitle("§l§cTRAP TRIGGERED");
										$eff = new EffectInstance(Effect::getEffect(Effect::SPEED), 300, 0);
										$eff->setVisible(true);
										$p->addEffect($eff);
										$eff = new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 300, 1);
										$eff->setVisible(true);
										$p->addEffect($eff);
										
									}
								}
							}
						}
					}
				}
			}
		}
	}


	public function projectileLaunchevent(ProjectileLaunchEvent $event)
	{
		$pro = $event->getEntity();
		$player = $pro->getOwningEntity();
		if ($player instanceof Player) {
			if ($this->inGame($player)) {
			    if($pro instanceof EggBridge){
					$team = "red";
					if($this->getTeam($player) !== ""){
						$team = $this->getTeam($player);
					}
					$pro->getLevel()->setBlock($pro->asVector3(),Block::get(Block::WOOL));
			        $pro->team = $team;
					$pro->owner = $player;
                }
				if ($pro instanceof Snowball) {
					$this->spawnBedbug($pro->asVector3(), $player->getLevel(), $player);
				}
			}
		}
	}


	public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		$level = $this->getLevels($player);
		$team = $this->getTeam($player);
		if ($event->isCancelled()) return;
		if ($this->phase == self::PHASE_LOBBY) {
			foreach ($this->players as $players) {

				$players->sendMessage("§7$level §r{$player->getDisplayName()} §7: {$event->getMessage()}");

			}
		}
        if ($this->phase == self::PHASE_RESTART) {
            foreach ($this->players as $players) {

                $players->sendMessage("§7$level §r{$player->getDisplayName()} §7: {$event->getMessage()}");

            }
        }
		if (isset($this->spectators[$player->getName()])) {
			foreach ($this->level->getPlayers() as $pt) {
				$pt->sendMessage("§7[SPECTATOR] §r §7 §r{$player->getDisplayName()}: §7{$msg}");

			}
		}
		if (!$this->inGame($player)) return;
		if ($this->phase == self::PHASE_GAME) {
			$f = $msg[0];
			if ($msg === "!t") {
			    if($player->isOp()){
				$this->reduceTime($player);
				} else {
					foreach ($this->players as $pt) {
						if ($this->getTeam($pt) == $team) {
						    if(!isset($this->spectators[$player->getName()])) {
                                $pt->sendMessage("§aTEAM §r§f> §r §r{$player->getDisplayName()}: §7{$msg}");
                            }
						}
					}
				}
			} elseif ($f == "!") {
                if(!isset($this->spectators[$player->getName()])) {
                    $msg = str_replace("!", "", $msg);
                    if (trim($msg) !== "") {
						$color = ["red" => "§c", "blue" => "§9", "green" => "§a", "yellow" => "§e"];
						$team = $color[$this->getTeam($player)];
                        $this->broadcastMessage("§6SHOUT §r§f> §r §7 §7$team $level §r{$player->getDisplayName()}: §7{$msg}");
                    }
                }
			} else {
				foreach ($this->players as $pt) {
					if ($this->getTeam($pt) == $team) {
                        $pt->sendMessage("§aTEAM §r§f> §r §r{$player->getDisplayName()}: §7{$msg}");
					}
				}
			}

		}
		$event->setCancelled();

	}

	public function reduceTime($player)
    {
        if (in_array($this->scheduler->upgradeNext[$this->data["level"]], [1, 2, 3, 4])) {
            if ($this->scheduler->upgradeTime[$this->data["level"]] > 70) {
                $this->scheduler->upgradeTime[$this->data["level"]] -= 50;
            } else {
                $player->sendMessage("Please wait to reduce time again");
            }
        } else {
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 5) {
                if ($this->scheduler->bedgone[$this->data["level"]] > 70) {
                    $this->scheduler->bedgone[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("Please wait to reduce time again");
                }
            }
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 6) {
                if ($this->scheduler->suddendeath[$this->data["level"]] > 70) {
                    $this->scheduler->suddendeath[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("Please wait to reduce time again");
                }
            }
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 7) {
                if ($this->scheduler->gameover[$this->data["level"]] > 70) {
                    $this->scheduler->gameover[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("Please wait to reduce time again");
                }
            }
        }
    }


	public function onExhaust(PlayerExhaustEvent $event)
	{
		$player = $event->getPlayer();
		if ($player instanceof Generator) {
			$event->setCancelled();
		}
		if ($this->phase == self::PHASE_LOBBY || $this->phase == self::PHASE_RESTART) {
			if ($this->inGame($player)) {
				$event->setCancelled();
			}
		}
	}

	public function onRegen(EntityRegainHealthEvent $event)
	{
		$player = $event->getEntity();
		if ($event->isCancelled()) return;
		if ($player instanceof Player) {
			if ($event->getRegainReason() == $event::CAUSE_SATURATION) {
				$event->setAmount(0.001);
			}
		}
	}

	public function onOpenInventory(InventoryOpenEvent $event)
	{
		$player = $event->getPlayer();
		$inv = $event->getInventory();
		if ($this->inGame($player)) {
			if ($this->phase == self::PHASE_GAME) {
				if ($inv instanceof ChestInventory || $inv instanceof EnderChestInventory) {
					$this->inChest[$player->getId()] = $player;
				}
			}
		}
	}

	public function onCloseInventory(InventoryCloseEvent $event)
	{
		$player = $event->getPlayer();
		$inv = $event->getInventory();
		if ($this->inGame($player)) {
			if ($this->phase == self::PHASE_GAME) {
				if ($inv instanceof ChestInventory || $inv instanceof EnderChestInventory) {
					if (isset($this->inChest[$player->getId()])) {
						unset($this->inChest[$player->getId()]);
					}
				}
			}
		}
	}


	public function onBlockBreak(BlockBreakEvent $event)
	{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$team = null;
		if (isset($this->spectators[$player->getName()])) {
			$event->setCancelled();
		}
		if($this->inGame($player) && $this->phase == 0){
			$event->setCancelled();
		}
		if($this->inGame($player) && $this->phase == 2){
			$event->setCancelled();
		}
		if ($this->inGame($player) && $this->phase == self::PHASE_GAME) {
			$event->setXpDropAmount(0);
			if ($block instanceof Bed) {
				$next = $block->getOtherHalf();
				$red = $this->data["bed"]["red"];
				$blue = $this->data["bed"]["blue"];
				$yellow = $this->data["bed"]["yellow"];
				$green = $this->data["bed"]["green"];

				if (in_array(($pos = (new Vector3($block->x, $block->y, $block->z))->__toString()), [$red, $blue, $yellow, $green])) {
					if ($pos == $red) {
						$team = "red";
					}
					if ($pos == $blue) {
						$team = "blue";
					}
					if ($pos == $yellow) {
						$team = "yellow";
					}
					if ($pos == $green) {
						$team = "green";
					}
					if ($this->getTeam($player) !== $team) {
						$this->breakbed($team, $player);
                        $event->setDrops([]);
						$this->plugin->mysqldata->addscore($player, "bedbroken");
					} else {
						$player->sendMessage("§cyou can't break bed your team"); 
                        $event->setCancelled();
					}
				
				}
				if(in_array(($pos = (new Vector3($next->x, $next->y, $next->z))->__toString()), [$red, $blue, $yellow, $green])){
                    $team = null;
                    if($pos == $red){
                        $team = "red";
                    }
                    if($pos == $blue){
                        $team = "blue";
                    }
                    if($pos == $yellow){
                        $team = "yellow";
                    }
                    if($pos == $green){
                        $team = "green";
                    }
                    if($this->getTeam($player) !== $team && !$player->isSpectator()){
						$this->breakbed($team, $player);
						$event->setDrops([]);
                        $this->plugin->mysqldata->addscore($player, "bedbroken");
					} else {
						$player->sendMessage("§cyou can't break bed your team"); 
                        $event->setCancelled();
					}
                    
                }
			} else {
				$poss = new Vector3(floor($block->x) + 0.5, $block->y, floor($block->z) + 0.5);
				if (!in_array($poss->__toString(), $this->placedBlock)) {
					$event->setCancelled(true);
					if (!$player->isSpectator()) {
						$player->sendMessage("§cYou can't break block in here");
					}
					return;
				
				}
			}
		}
	}

	public function onPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if (isset($this->spectators[$player->getName()])) {
			$event->setCancelled();
		}
		if($this->inGame($player) && $this->phase == 0){
			$event->setCancelled();
		}
		if($this->inGame($player) && $this->phase == 2){
			$event->setCancelled();
		}
		if ($this->inGame($player) && $this->phase == self::PHASE_GAME) {
			if ($block->getY() > 256) {
				$event->setCancelled();
				$player->sendMessage("§cPlaced block is max!");
			}
			$entities = $block->getLevel()->getNearbyEntities($block->getBoundingBox()->expandedCopy(1, 2, 1));
			$i = 0;
			foreach ($entities as $e) {
				if ($e instanceof Generator) {
					$i++;
				}
			}
			if ($i > 0) {
				$event->setCancelled();
			}
		}
		if ($this->inGame($player) && $this->phase == self::PHASE_GAME && !$player->isSpectator()) {
			if ($block->getId() == Block::TNT) {
				$ih = $player->getInventory()->getItemInHand();
				$event->setCancelled();
				$block->ignite();
				$ih->setCount($ih->getCount() - 1);
				$player->getInventory()->setItemInHand($ih);
			}
			if ($event->isCancelled()) return;
			foreach ($this->data["location"] as $spawn) {
				$lv = Vector3::fromString($spawn);
				if ($block->asVector3()->distance($lv) < 6) {
					$event->setCancelled();
					$player->sendMessage("§cyou can't place block in here");
				} else {
					$pos = new Vector3(floor($block->x) + 0.5, $block->y, floor($block->z) + 0.5); 
					$this->placedBlock[] = $pos->__toString();
                    if ($block->getId() == Block::CHEST) {
				         $this->spawntower->SpawnTower($player);
				         $player->sendMessage("Succesfuly spawn tower");
				         $event->setCancelled();
			        }
				}
			}
	        


		}
	}

	public function spawnGolem($pos, $level, $player)
	{
		if ($this->phase !== self::PHASE_GAME) return;
		$nbt = Entity::createBaseNBT($pos);
		$entity = new Golem($level, $nbt);
		$entity->arena = $this;
		$entity->owner = $player;
		$entity->spawnToAll();
	}

	public function spawnBedbug($pos, $level, $player)
	{
		if ($this->phase !== self::PHASE_GAME) return;
		$nbt = Entity::createBaseNBT($pos);
		$entity = new Bedbug($level, $nbt);
		$entity->arena = $this;
		$entity->owner = $player;
		$entity->spawnToAll();
	}

	public function spawnFireball($pos, $level, $player)
	{
		$nbt = Entity::createBaseNBT($pos, $player->getDirectionVector(), ($player->yaw > 180 ? 360 : 0) - $player->yaw, -$player->pitch);
		$entity = new Fireball($level, $nbt, $player);
		$entity->setMotion($player->getDirectionVector()->normalize()->multiply(0.4));
		$entity->spawnToAll();
		$entity->arena = $this;
		$entity->owner = $player;
	}

	public function onItemDrop(PlayerDropItemEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();
		
			if($this->inGame($player) && $this->phase == self::PHASE_LOBBY){
			    $event->setCancelled();
            }
           if($this->inGame($player) && $this->phase == self::PHASE_RESTART){
            $event->setCancelled();
           }
			if(isset($this->spectators[$player->getName()])){
				$event->setCancelled();
			}
			if ($this->phase == self::PHASE_GAME) {
				if ($item instanceof Sword || $item instanceof Armor || $item->getId() == Item::SHEARS || $item instanceof Pickaxe || $item instanceof Axe) {
					$event->setCancelled();
				}

			}
		
	}

	public function playAgain(Player $player)
	{
        QueryQueue::submitQuery(new CheckPartyQuery($player->getName()), function (CheckPartyQuery $query) use ($player) {
            if(!$query->type){
                SkyWars::getInstance()->joinToRandomArena($player);
                return false;
            }
            QueryQueue::submitQuery(new FetchAllParty($query->output), function (FetchAllParty $ingfo) use ($player,$query) {
                QueryQueue::submitQuery(new MemberPartyQuery($query->output), function (MemberPartyQuery $query) use ($player,$ingfo) {
                    if($ingfo->leader !== $player->getName()){
                        $player->sendMessage("§cYou must leader party or leave party to play again!");
                        return false;
                    }
                    $members = array_values(array_filter($query->member));
                    foreach ($members as $member) {
                        $p = $this->plugin->getServer()->getPlayer($member);
                        if(!$p->isOnline() && $p == null){
                            return false;
                        }
                        SkyWars::getInstance()->joinToRandomArena($p);

                    }
                    return true;
                });
            });
            return true;
        });


	}

    
    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        $itemN = $item->getCustomName();
        $action = $event->getAction();
        if(isset($this->spectators[$player->getName()])){
			if($block instanceof Block){
				$event->setCancelled();
			}
		}
		if($action == $event::RIGHT_CLICK_BLOCK || $action == $event::RIGHT_CLICK_AIR){ 
					if ($itemN == "§eSelect Team") {
						$player->getInventory()->setHeldItemIndex(1);

					}
					if ($itemN == "§aPlay Again") {
						$this->playAgain($player);
						$player->getInventory()->setHeldItemIndex(1);
					   }
					
					if ($itemN == "§cBack To Lobby") {
					    $player->getServer()->dispatchCommand($player,"lobby");
					}
					if ($itemN == "§eSpectator") {
						$this->playerlist($player);
					}

				//
			}

            
            if($this->phase == self::PHASE_GAME) {
		
				if($action == $event::RIGHT_CLICK_BLOCK || $action == $event::RIGHT_CLICK_AIR){ 
	

                if($this->inGame($player)){
                $ih = $player->getInventory()->getItemInHand();
                if($item->getId() == Item::FIRE_CHARGE){
                    $this->spawnFireball($player->add(0, $player->getEyeHeight()), $player->level, $player);
                    $this->addSound($player, 'mob.blaze.shoot');
                    $ih->setCount($ih->getCount() - 1);
                    $player->getInventory()->setItemInHand($ih); 
                    $event->setCancelled();
                }
                if($action == $event::RIGHT_CLICK_BLOCK){
                    if($block instanceof Bed){
                        if(!$player->isSneaking()){
                            $event->setCancelled();
                        }
                    }
					if($itemN == "§aPlayer Tracker"){
						$this->trackCompass($player);
					}
                    if($item->getId() == Item::SPAWN_EGG && $item->getDamage() == 14){
                        $this->spawnGolem($block->add(0, 1), $player->level, $player);
                        $ih->setCount($ih->getCount() - 1);
                        $player->getInventory()->setItemInHand($ih); 
                        $event->setCancelled();
                    }
                }
                if($block->getId() == Block::LIT_FURNACE || $block->getId() == Block::CRAFTING_TABLE || $block->getId() == Block::BREWING_STAND_BLOCK || $block->getId() == Block::FURNACE){
                    $event->setCancelled();
                }

             }
                
            }
        }
        if($this->inGame($player) && $event->getBlock()->getId() == Block::CHEST && $this->phase == self::PHASE_LOBBY) {
            $event->setCancelled(true);
            return;
        }


    }

    public function InventoryPickArrow(InventoryPickupArrowEvent $event){
		$player = $event->getInventory()->getHolder();
        if($event->getInventory() instanceof  PlayerInventory) {
			if ($event->isCancelled()) return;
			if (isset($this->spectators[$player->getName()])) {
				$event->setCancelled();
			}
			if ($player instanceof Player && $player->getLevel()->getFolderName() == $this->level->getFolderName()) {
				if ($this->phase == self::PHASE_RESTART) {
					$event->setCancelled();
				}
			}
		}
    }


	public function addKill(Player $damager,$type){
		if($type == "fk"){
			$this->addRocket($damager);
			$this->addRocket($damager);
			$this->addRocket($damager);
			$this->addSound($damager, 'random.levelup');
			$this->plugin->mysqldata->addscore($damager,"fk");
		
			$this->plugin->addFinalKill($damager);
			if(isset($this->finalkill[$damager->getId()])){
			$this->finalkill[$damager->getId()]++;
			}
		}
		if($type == "kill"){
            $this->addRocket($damager);
            $this->addRocket($damager);
            $this->addRocket($damager);
            $this->addSound($damager, 'random.levelup');
            if(isset($this->kill[$damager->getId()])){
            $this->kill[$damager->getId()]++;
            }
            $this->plugin->mysqldata->addscore($damager,"kill");
		}
	}

  
    
    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        $entity = $event->getEntity();
        $isFinal = "";

		if($entity instanceof Generator){
			$event->setCancelled();
		}
        if($player instanceof Player){
            if($this->inGame($player)){
                if($this->phase == self::PHASE_GAME){
                    if($event instanceof EntityDamageByEntityEvent){
						if($event->getDamager() instanceof Player){
							if($this->inGame($event->getDamager())){
								if($this->getTeam($event->getDamager()) == $this->getTeam($player)){
									$event->setCancelled();
								}
	
							}
						}
                 
                   }
                 }
            }
        }
        if(!$entity instanceof Player){
            return;
        }
		if($this->inGame($entity) && $this->phase === 2) {
			$event->setCancelled(true);
		}
		if($this->inGame($entity) && $this->phase === 0) {
			$event->setCancelled(true);
		}


		if(!$this->inGame($entity)) {

			return;
		}

		if($this->phase !== 1) {
			return;
		}
		
		if(!$entity instanceof Player) return;
		if(isset($this->respawnC[$entity->getName()])){
			$event->setCancelled();
		}
		if(isset($this->spectators[$entity->getName()])){
			$event->setCancelled();
		}
        if($entity->getHealth()-$event->getFinalDamage() <= 0) {
            $event->setCancelled(true);
			$team = $this->getTeam($entity);
			if($team !== ""){
				$vc = Vector3::fromString($this->data["bed"][$team]); 
				if(($tr = $this->level->getBlockAt($vc->x, $vc->y, $vc->z)) instanceof Bed){
					$this->startRespawn($entity);
				} else {
				    $this->Spectator($entity);
				}
			}
           if($event instanceof  EntityDamageByEntityEvent){
            	$damager = $event->getDamager();
            	if(!$damager instanceof Player){
					return;
				}
				if(!$entity instanceof Player){
					return;
				}
		
			   
				if(!$this->checkBed($entity,"status")){
					$this->addKill($damager,"fk");
					$isFinal = "§l§bFINAL KILLS";
 
				} else {
					$this->addKill($damager,"kill");
				}
				
            }   
            switch ($event->getCause()) {
                case $event::CAUSE_CONTACT:
                case $event::CAUSE_ENTITY_ATTACK:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                        	if($player instanceof Player) {
								
								$msg = null;
								
								$msg = "§r{$entity->getDisplayName()} §e was  killed By §r{$damager->getDisplayName()} {$isFinal}";
								
								$this->broadcastMessage($msg);

								break;
							}
                        }
                    }
                   break;
                case $event::CAUSE_PROJECTILE:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                        	if($player instanceof Player) {
								$this->broadcastMessage("{$entity->getDisplayName()} §e was killed By {$damager->getDisplayName()} §fwith projectile {$isFinal}");
							}
                           
                            break;
                        }
                    }
                    if($player instanceof Player) {

						$this->broadcastMessage("{$entity->getDisplayName()} §e death with projectile {$isFinal}");
					
					}
                     
                   break;
                case $event::CAUSE_BLOCK_EXPLOSION:
					if($event instanceof EntityDamageByEntityEvent) {
						$damager = $event->getDamager();
						if($damager instanceof Player) {
							if($player instanceof Player) {
								$this->broadcastMessage("{$entity->getDisplayName()} §e death with explosion by {$damager->getDisplayName()} {$isFinal}");
							}

							break;
						}
					}
                	if($player instanceof Player) {

						$this->broadcastMessage("{$entity->getDisplayName()} §e death by explosion {$isFinal}");
					
					}
                    
                    break;
                case $event::CAUSE_FALL:
					if($event instanceof EntityDamageByEntityEvent) {
						$damager = $event->getDamager();
						if($damager instanceof Player) {
							if($player instanceof Player) {

								$this->broadcastMessage("{$entity->getDisplayName()} §e fell from high place by {$damager->getDisplayName()} {$isFinal}");
							}

							break;
						}
					}
                	if($player instanceof Player) {

						$this->broadcastMessage("{$entity->getDisplayName()} §e fell from high place {$isFinal}");
						
					}
                   
                    break;
                case $event::CAUSE_VOID:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player && $this->inGame($damager)) {
							$player->teleport($this->level->getSafeSpawn());
                            $this->broadcastMessage("{$entity->getDisplayName()} §ewas thrown into void by §f{$damager->getDisplayName()} {$isFinal}");
                            
                      
                            break;
                        }
                    }
                    $msg = null;
					$player->teleport($this->level->getSafeSpawn());
                    if($player instanceof  Player) {
              
						if ($entity->getHealth() == 0) {
							$msg = "{$entity->getDisplayName()} §e fell into nothingness {$isFinal}";
						} else {
							$msg = "§r{$entity->getDisplayName()} §e fell into void {$isFinal}";
						}
						$this->broadcastMessage($msg);
				
					}
                 
                    break;
               case $event::CAUSE_ENTITY_EXPLOSION:
                   if($event instanceof EntityDamageByEntityEvent) {
                       $damager = $event->getDamager();
                       if($damager instanceof Player) {
                           if($player instanceof Player) {

                               $this->broadcastMessage(" §b".$entity->getDisplayName(). " §ewas exploded by  §r {$damager->getDisplayName()} {$isFinal}");
                           }

                           break;
                       }
                   }
                   if($player instanceof Player) {
					   $this->broadcastMessage(" §b" . $entity->getDisplayName() . " §edeath by explosion {$isFinal}");
				
				   }
               break;
               case $event::CAUSE_DROWNING:
                    if($player instanceof  Player) {
						$this->broadcastMessage(" §b" . $entity->getDisplayName() . " §e drowned {$isFinal}");
			
					}
               break;
               case $event::CAUSE_STARVATION:
               	if($player instanceof Player) {
					$this->broadcastMessage(" §b" . $entity->getDisplayName() . " §edeath because starvation {$isFinal}");
				
				}
               break;
               case $event::CAUSE_LAVA:
                  if($player instanceof Player) {
					  $this->broadcastMessage(" §b" . $entity->getDisplayName() . " §edeath because of lava {$isFinal}");
				
				  }
               break;
                default:
                	if($player instanceof Player) {
						$this->broadcastMessage("§r{$entity->getDisplayName()} §edeath {$isFinal}");
					
					}
                     
            }
        }
  
    }

    public function onEntityMotion(EntityMotionEvent $event)
	{
		$entity = $event->getEntity();
	
	
		if ($entity instanceof Generator) {
			$event->setCancelled(true);
		}
		if ($entity instanceof Player){
			if (isset($this->spectators[$entity->getName()])) {
				$event->setCancelled();
			}
	    }

        if($entity instanceof ShopVillager){
        	$event->setCancelled(true);

        }
        if($entity instanceof UpgradeVillager){
        	$event->setCancelled(true);
        }
               
           
    }
    
    public function playerlist($player) : bool{
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			foreach($this->level->getPlayers() as $pl){
				if($player->getLevel()->getFolderName() == $this->level->getFolderName()){
						if($pl->getDisplayName() == $target){
							if($this->inGame($pl)){
								$player->teleport($pl->asVector3());
								$player->sendMessage("§eYou spectator {$pl->getName()}");
							}
					}
				}
			}
			return true;

		});
		$form->setTitle("Spectator Player");
		if(empty($this->players)){
			$form->setContent("§cno players!");
		   $form->addButton("CLOSE", 0, "textures/blocks/barrier");
		   return true;
	   }
	   $count = 0;
	   $form->setTitle("§eTeleporter");
	   foreach($this->players as $pl){
	   $count++;
	   $form->addButton($pl->getDisplayName(),-1,"",$pl->getDisplayName());
	   }
	   if($count == count($this->players)){
		   $form->addButton("Cancel", 0, "textures/blocks/barrier");
	   }
	   $form->sendToPlayer($player);
	   return true;

	}

    public function setCompassPosition(Player $player, Position $position): void
    {
        $pk = new SetSpawnPositionPacket();
        $pk->x = $pk->x2 = $position->getFloorX();
        $pk->y = $pk->y2 = $position->getFloorY();
        $pk->z = $pk->z2 = $position->getFloorZ();
        $pk->spawnType = SetSpawnPositionPacket::TYPE_WORLD_SPAWN;
        $pk->dimension = DimensionIds::OVERWORLD;
        $player->sendDataPacket($pk);
    }

    public function findNearestPlayer(Player $player, int $range): ?Player {
        $nearestPlayer = null;
        $nearestPlayerDistance = $range;
        foreach ($this->players as $p) {
            $distance = $player->distance($p);
            if ($distance <= $range && $distance < $nearestPlayerDistance && $player !== $p && $p->isAlive() && !$p->isClosed() && !$p->isFlaggedForDespawn() && $this->getTeam($p) !== $this->getTeam($player)) {
                $nearestPlayer = $p;
                $nearestPlayerDistance = $distance;
            }
        }
        return $nearestPlayer;
    }
    
    public function addGlobalSound($player, string $sound = '', float $pitch = 1){
        $pk = new PlaySoundPacket();
		$pk->x = $player->getX();
		$pk->y = $player->getY();
		$pk->z = $player->getZ();
		$pk->volume = 2;
		$pk->pitch = $pitch;
		$pk->soundName = $sound;
	    Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
    }  
    
    public function addSound($player, string $sound = '', float $pitch = 1){
        $pk = new PlaySoundPacket();
		$pk->x = $player->getX();
		$pk->y = $player->getY();
		$pk->z = $player->getZ();
		$pk->volume = 4;
		$pk->pitch = $pitch;
		$pk->soundName = $sound;
	    $player->dataPacket($pk);
    }
    
    public function stopSound($player, string $sound = '', bool $all = true){
        $pk = new StopSoundPacket();
		$pk->soundName = $sound;
		$pk->stopAll = $all;
	    Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
    }


    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $event->setQuitMessage("");
		if($this->inGame($player)) {
            $this->disconnectPlayer($player,"");
        }

    }
    
    public function upgradeGenerator($team, $player){
        $pos = Vector3::fromString($this->data["bed"][$team]);
		$this->utilities[$this->level->getFolderName()][$team]["generator"]++;
        foreach($this->level->getEntities() as $g){
            if($g instanceof Generator){
                if($g->asVector3()->distance($pos) < 20){
                    $g->Glevel = $g->Glevel + 1;
                }
            }
        }
        foreach($this->players as $t){
            if($this->getTeam($t) == $team){
                $lvl = 	$this->utilities[$this->level->getFolderName()][$team]["generator"] - 1;
                $t->sendMessage("{$player->getDisplayName()} §ehas bought §aForge §eLevel §a" . $lvl);
                
            }
        }
    }
    
    public function upgradeArmor($team, $player){
        $this->utilities[$this->level->getFolderName()][$team]["protection"]++;
        foreach($this->players as $pt){
            if($this->getTeam($pt) == $team){
                
                $lvl = $this->utilities[$this->level->getFolderName()][$team]["protection"] - 1;
                $this->addSound($pt, 'random.levelup');
                $this->setArmor($pt);
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aResistance §eLevel §a" . $lvl);
            }
        }
    }
    
    public function upgradeHaste($team, $player){
        $this->utilities[$this->level->getFolderName()][$team]["haste"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $lvl = $this->utilities[$this->level->getFolderName()][$team]["haste"] - 1;
		        $this->addSound($pt, 'random.levelup');
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aManiac Miner §eLevel §a" . $lvl);
		    }
		}
    }
    
    public function upgradeSword($team, $player){
        $this->utilities[$this->level->getFolderName()][$team]["sharpness"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $this->addSound($pt, 'random.levelup');
		        $lvl = $this->utilities[$this->level->getFolderName()][$team]["sharpness"] - 1;
		        $this->setSword($pt, $pt->getInventory()->getItem(0));
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aSharpNess §eLevel §a ". $lvl);
		    }
		}
    }
    
    public function upgradeHeal($team, $player){
        $this->utilities[$this->level->getFolderName()][$team]["health"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $this->addSound($pt, 'random.levelup');
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aHeal Pool");
		    }
		}
    } 
	
	public function upgradeMenu(Player $player){
	    $team = $this->getTeam($player); 
	    $trapprice = $this->utilities[$this->level->getFolderName()][$team]["traps"];
	    $slevel = $this->utilities[$this->level->getFolderName()][$team]["sharpness"];
	    $Slevel = str_replace(["0"], ["-"], "" . ($slevel - 1) . "");
	    $plevel = $this->utilities[$this->level->getFolderName()][$team]["protection"];
	    $Plevel = str_replace(["0"], ["-"], "" . ($plevel - 1) . ""); 
	    $hlevel = $this->utilities[$this->level->getFolderName()][$team]["haste"];
	    $Hlevel = str_replace(["0"], ["-"], "" . ($hlevel - 1) . ""); 
	    $glevel = $this->utilities[$this->level->getFolderName()][$team]["generator"];
	    $Glevel = str_replace(["0"], ["-"], "" . ($glevel - 1) . "");
	    $htlevel = $this->utilities[$this->level->getFolderName()][$team]["health"];
	    $HTlevel = str_replace(["0"], ["-"], "" . ($htlevel - 1) . "");  
	    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST); 
	    $menu->setName("Team Upgrade");

	    $inv = $menu->getInventory();
	    $this->upgrade = $inv;
	    $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{ 
	    $player = $transaction->getPlayer();
	    $pinv = $player->getInventory();
	    $item = $transaction->getItemClicked();
	    $inv = $transaction->getAction()->getInventory();
        $team = $this->getTeam($player);
        $pt = $player;
	    $slevel = $this->utilities[$this->level->getFolderName()][$team]["sharpness"];
	    $Slevel = str_replace(["0"], ["-"], "" . ($slevel - 1) . "");
	    $plevel = $this->utilities[$this->level->getFolderName()][$team]["protection"];
	    $Plevel = str_replace(["0"], ["-"], "" . ($plevel - 1) . ""); 
	    $hlevel = $this->utilities[$this->level->getFolderName()][$team]["haste"];
	    $Hlevel = str_replace(["0"], ["-"], "" . ($hlevel - 1) . ""); 
	    $glevel = $this->utilities[$this->level->getFolderName()][$team]["generator"];
	    $Glevel = str_replace(["0"], ["-"], "" . ($glevel - 1) . "");
	    $htlevel = $this->utilities[$this->level->getFolderName()][$team]["health"];
	    $HTlevel = str_replace(["0"], ["-"], "" . ($htlevel - 1) . "");  
        if($item instanceof Sword && $item->getId() == Item::IRON_SWORD){
            if(isset($this->utilities[$this->level->getFolderName()][$team]["sharpness"])){
                $g =  $this->utilities[$this->level->getFolderName()][$team]["sharpness"];
		        $cost = 1;
		        if($g == 1){
		            $cost = 2;
                }
		        if($g == 2){
		            $cost = 4;

                }
		        if($g == 3){
		            $cost = 8;
                }
		        if($g == 4){
		            $cost = 16;
                }
                if($g <= 2) {
                    if ($pinv->contains(Item::get(Item::DIAMOND, 0, $cost))) {
                        $pinv->removeItem(Item::get(Item::DIAMOND, 0, $cost));
                        $this->upgradeSword($team, $player);
                        $this->addSound($pt, 'random.levelup');
                    } else {
                        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
                    }
                }
                
            }
        }
        if($item instanceof Armor && $item->getId() == Item::IRON_CHESTPLATE){ 
			if(isset($this->utilities[$this->level->getFolderName()][$team]["protection"])){
                $g =  $this->utilities[$this->level->getFolderName()][$team]["protection"];
		        $cost = 5;
		        if($g === 1){
		            $cost = 5;
                }
		        if($g === 2){
		            $cost = 10;
                }
		        if($g === 3){
		            $cost = 15;
                }
		        if($g === 4){
		            $cost = 20;
                }
             
                if($g <= 4){
                    if ($pinv->contains(Item::get(Item::DIAMOND, 0, $cost))) {
                        $pinv->removeItem(Item::get(Item::DIAMOND, 0, $cost));
                        $this->addSound($pt, 'random.levelup');
                        $this->upgradeArmor($team, $player);
                    } else {
                        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
                    }
                }
                
                
            }
        }
        if($item->getId() == Item::IRON_PICKAXE){
            if(isset($this->utilities[$this->level->getFolderName()][$team]["haste"])){
				$g =  $this->utilities[$this->level->getFolderName()][$team]["haste"];
		        $cost = 4 * $g;
		        if($g == 3){
		            return;
		        }
		        if($pinv->contains(Item::get(Item::DIAMOND, 0, $cost))){
		            $pinv->removeItem(Item::get(Item::DIAMOND, 0, $cost));
		            
    
                 $this->addSound($pt, 'random.levelup');
		            $this->upgradeHaste($team, $player);
		        } else {
		          $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]); 
		        }
            }
        }
        if($item->getId() == Block::FURNACE){
			if(isset($this->utilities[$this->level->getFolderName()][$team]["generator"])){
                $g =  $this->utilities[$this->level->getFolderName()][$team]["generator"];
		        $cost = 4 * $g;
		        if($g == 5){
		            return;
		        }
		        if($pinv->contains(Item::get(Item::DIAMOND, 0, $cost))){
		            $pinv->removeItem(Item::get(Item::DIAMOND, 0, $cost));
		             $this->addSound($pt, 'random.levelup');
		            $this->upgradeGenerator($team, $player);
		        } else {
		         $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]); 
		        }
            }
        }
        if($item->getId() == Block::BEACON){
            if(isset($this->utilities[$this->level->getFolderName()][$team]["health"])){
                $g =  $this->utilities[$this->level->getFolderName()][$team]["health"];
		        $cost = 2 * $g;
		        if($g == 2){
		            return;
		        }
		        if($pinv->contains(Item::get(Item::DIAMOND, 0, $cost))){
		            $pinv->removeItem(Item::get(Item::DIAMOND, 0, $cost));
		           $this->addSound($pt, 'random.levelup');
		            $this->upgradeHeal($team, $player);
		        } else {
		         $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
		        }
            } 
        }
        $trapprice =  $this->utilities[$this->level->getFolderName()][$team]["traps"];
        if($item->getId() == Block::TRIPWIRE_HOOK){
            if(isset($this->itstrap[$team])){
                return; 
            }
            if($pinv->contains(Item::get(Item::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(Item::get(Item::DIAMOND, 0, $trapprice));
		          $this->addSound($pt, 'random.levelup');
		        $this->itstrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ahas bought §bIt's Trap");
		            }
		        }
				$this->utilities[$this->level->getFolderName()][$team]["traps"]++;
            } else {
		     $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == Item::FEATHER){
            if(isset($this->countertrap[$team])){
                return; 
            }
            if($pinv->contains(Item::get(Item::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(Item::get(Item::DIAMOND, 0, $trapprice));
		        $this->addSound($pt, 'random.levelup');
		        $this->countertrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ahas bought §bCounter Offensive Trap");
		            }
		        } 
				$this->utilities[$this->level->getFolderName()][$team]["traps"]++;
            } else {
		        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == Block::LIT_REDSTONE_TORCH){
            if(isset($this->alarmtrap[$team])){
                return; 
            }
            if($pinv->contains(Item::get(Item::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(Item::get(Item::DIAMOND, 0, $trapprice));
		           $this->addSound($pt, 'random.levelup');
		        $this->alarmtrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ahas bought §bAlarm Trap");
		            }
		        } 
				$this->utilities[$this->level->getFolderName()][$team]["traps"]++;
            } else {
		       $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == Item::WOODEN_PICKAXE){
            if(isset($this->minertrap[$team])){
                return; 
            }
            if($pinv->contains(Item::get(Item::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(Item::get(Item::DIAMOND, 0, $trapprice));
		        $this->addSound($pt, 'random.levelup');
		        $this->minertrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ehas bought Miner Fatigue Trap");
		            }
		        } 
				$this->utilities[$this->level->getFolderName()][$team]["traps"]++;
            } else {
		        $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }  
	    $sharp = null;
	    if($slevel > 4){
	        $sharp = "§bSharpness §a(max)";
	    } else {
	        $sharp = "§bSharpness";
	    }
	    $inv->setItem(11, Item::get(Item::IRON_SWORD)
	    ->setCustomName("$sharp")
	    ->setLore([

	        "§bTier 1 - 2 Diamond\n",
	        "§bTier 2 - 4 Diamond\n",
	        "§fAll player in your team will get enchanted Sharpness $Slevel sword"
	        ])
	    );
	    $prot = null;
	    if($plevel > 3){
	        $prot = "§bResistance §a(max)";
	    } else {
	        $prot = "§bResistance";
	    }
	    $inv->setItem(12, Item::get(Item::IRON_CHESTPLATE)
	    ->setCustomName("$prot")
	    ->setLore([
	    	"§aReinforced Armor",
	        "§eLevel 1 - 5 Diamond",
	        "§eLevel 2 - 10 Diamond",
	        "§eLevel 3 - 15 Diamond",
	        "§eLevel 4 - 20 Diamond\n",
	        "§eCurrent Tier: §a{$Plevel}\n",
	        "§fAll player in your team get enchanted protection Armor"
	        ])
	    );
	    $haste = null;
	    if($hlevel > 1){
	        $haste = "§bManiac Miner §a(max)";
	    } else {
	        $haste = "§bManiac Miner";
	    }
	    $inv->setItem(13, Item::get(Item::IRON_PICKAXE)
	    ->setCustomName("$haste")
	    ->setLore([

	        "§eTier 1 - 2 Diamond (Haste 1)",
	        "§eCurrent Tier: §c{$Hlevel}\n",
	        "§fAll player in your team get maniac miner"
	        ])
	    );
	    $gen = null;
	    if($glevel > 4){
	        $gen = "§bForge §a(max)";
	    } else {
	        $gen = "§bForge";
	    }
	    $inv->setItem(14, Item::get(Block::FURNACE)
	    ->setCustomName("$gen")
	    ->setLore([
	        "§aCurrent Forge: §c{$Glevel}\n",
	        "§eIron Forge - 2 Diamond 50% IronIngot",
	        "§eGold Forge Forge -  4 Diamond 50% Gold",
	        "§eEmerald Forge - 6 Diamond (spawn emerald in your team generator)",
	        "§eDouble Forge - 16 Diamond (increase iron & gold generator spawn 100%)\n",
	        "§eIncrease Generator In Your Team"
	        ])
	    );
	    $health = null;
	    if($htlevel > 4){
	        $health = "§cHeal Pool (max)";
	    } else {
	        $health = "§aHeal Pool";
	    }
	    $inv->setItem(15, Item::get(Block::BEACON)
	    ->setCustomName("$health")
	    ->setLore([
	        "§fCurrent Level: §c{$HTlevel}\n",
	        "§e2 Diamond\n",
	        "§fyour team infinite regen nearby your base"
	        ])
	    );
	    $itstrap = null;
	    $itsprice = null;
	    if(isset($this->itstrap[$team])){
	        $itsprice = "";
	        $itstrap = "§aActived";
	    } else {
	        $itsprice = "§e{$trapprice} Diamond\n";
	        $itstrap = "§cDisabled";
	    }
	    $inv->setItem(29, Item::get(Block::TRIPWIRE_HOOK)
	    ->setCustomName("§eIt's Trap")
	    ->setLore([
	        "§eStatus: {$itstrap}\n",
	        "{$itsprice}",
	        "§fGive enemy slowness and blindness effect 8 seconds"
	        ])
	    );
	    $countertrap = null;
	    $counterprice = null;
	    if(isset($this->countertrap[$team])){
	        $countertrap = "§aActived";
	        $counterprice = "";
	    } else {
	        $countertrap = "§cDisabled";
	        $counterprice = "§e{$trapprice} Diamond\n";
	    }
	    $inv->setItem(30, Item::get(Item::FEATHER)
	    ->setCustomName("§eCounter Offensive Trap")
	    ->setLore([
	        "§eStatus: {$countertrap}\n",
	        "{$counterprice}",
	        "§fGive team jump boost II and speed effect 15 seconds"
	        ])
	    );
	    $alarmtrap = null;
	    $alarmprice = null;
	    if(isset($this->alarmtrap[$team])){
	        $alarmtrap = "§aActived";
	        $alarmprice = "";
	    } else {
	        $alarmtrap = "§cDisabled";
	        $alarmprice = "§e{$trapprice} Diamond\n"; 
	    }
	    $inv->setItem(31, Item::get(Block::LIT_REDSTONE_TORCH)
	    ->setCustomName("§eAlarm Trap")
	    ->setLore([
	        "§eStatus: {$alarmtrap}\n",
	        "{$alarmprice}",
	        "§fReveal invisible"
	        ])
	    );
	    $minertrap = null;
	    $minerprice = null;
	    if(isset($this->minertrap[$team])){
	        $minertrap = "§aActived";
	        $minerprice = "";
	    } else {
	        $minertrap = "§cDisabled";
	        $minerprice = "§e{$trapprice} Diamond\n"; 
	    }
	    $inv->setItem(32, Item::get(Item::WOODEN_PICKAXE)
	    ->setCustomName("§eMiner Fatigue Trap")
	    ->setLore([
	        "§eStatus: {$minertrap}\n",
	        "{$minerprice}",
	        "§fGive enemy mining fatigue effect 8 seconds"
	        ])
	    );
	    })); 

	    $menu->send($player);
	} 
	
	public function shopMenu(Player $player){
	    $team = $this->getTeam($player);
	    $meta = [
	        "red" => 14,
	        "blue" => 11,
	        "yellow" => 4,
	        "green" => 5
	    ];
	    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST); 
	    $menu->setName("Item Shop");
	    $inv = $menu->getInventory();

	    $this->shop = $inv;
	    $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{  
	    $player = $transaction->getPlayer();
	    $pinv = $player->getInventory();
	    $item = $transaction->getItemClicked();
	    $inv = $transaction->getAction()->getInventory();
        $in = $item->getCustomName();
        if(in_array($in, ["§fBlocks", "§fMelee", "§fArmor", "§fTools", "§fBow & Arrow", "§fPotions", "§fUtility"])){
            $this->manageShop($player, $inv, $in);
            return;
        }
        if($item instanceof Sword && $in == "§bStone Sword"){
			if(!$pinv->contains(Item::get(Item::STONE_SWORD))){
            if($pinv->contains(Item::get(Item::IRON_INGOT, 0, 10))){
                $pinv->removeItem(Item::get(Item::IRON_INGOT, 0, 10));
                $this->messagebuy($player,"Stone Sword");
                $this->setSword($player, Item::get(Item::STONE_SWORD));
            } else {
				$this->notEnought($player,"Iron ingot");
               $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
         
		    }
			return;
        }
        if($item instanceof Sword && $in == "§bIron Sword"){
			if(!$pinv->contains(Item::get(Item::IRON_SWORD))){
            if($pinv->contains(Item::get(Item::GOLD_INGOT, 0, 7))){
			
                $pinv->removeItem(Item::get(Item::GOLD_INGOT, 0, 7));
                $this->messagebuy($player,"Iron Sword");
                $this->setSword($player,  Item::get(Item::IRON_SWORD));
            } else {
				$this->notEnought($player,"Gold ingot");
               $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
      
		   }
		   return; 
        }
        if($item instanceof Sword && $in == "§bDiamond Sword"){
			if(!$pinv->contains(Item::get(Item::DIAMOND_SWORD))){
            if($pinv->contains(Item::get(Item::EMERALD, 0, 3))){
                $pinv->removeItem(Item::get(Item::EMERALD, 0, 3));
                $this->messagebuy($player,"Diamond Sword");
                $this->setSword($player,Item::get(Item::DIAMOND_SWORD));
            } else {
				$this->notEnought($player,"Emerald");
               $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
       
		}
		return; 
        }
        if($in == "§bShears"){
            if(isset($this->shear[$player->getName()])){
                return;
            } 
            if($pinv->contains(Item::get(Item::IRON_INGOT, 0, 20))){
                $pinv->removeItem(Item::get(Item::IRON_INGOT, 0, 20));

                $this->shear[$player->getName()] = $player;
                $this->messagebuy($player,"Shears");
                $sword = $pinv->getItem(0);
                $this->setSword($player, $sword);
            } else {
				$this->notEnought($player,"Gold ingot");
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
		if($in == "§aPlayer Tracker"){
			if($pinv->contains(Item::get(Item::EMERALD, 0, 3))){
                $pinv->removeItem(Item::get(Item::EMERALD, 0, 3));

                $this->messagebuy($player,"§aPlayer Tracker");
                $pinv->addItem(Item::get(Item::COMPASS)->setCustomName("§aPlayer Tracker"));
            } else {
            	$this->notEnought($player,"Emerald");
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
		}
        if($in == "§bKnockback Stick"){
            if($pinv->contains(Item::get(Item::GOLD_INGOT, 0, 5))){
                $pinv->removeItem(Item::get(Item::GOLD_INGOT, 0, 5));
                $this->messagebuy($player,"KnockBack Stick");
                $stick = Item::get(Item::STICK);
                $stick->setCustomName("§bKnockback Stick");
                $stick->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK), 1));
                $pinv->addItem($stick);
            } else {
            	$this->notEnought($player,"Gold ingot");
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
        if($in == "§bBow (Power I)"){
            if($pinv->contains(Item::get(Item::GOLD_INGOT, 0, 24))){
                $pinv->removeItem(Item::get(Item::GOLD_INGOT, 0, 24));
                $this->messagebuy($player,"Bow (Power I)");
                $bow = Item::get(Item::BOW);
               
                $bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), 1));
                $pinv->addItem($bow);
            } else {
            	$this->notEnought($player,"Gold ingot");
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
        if($in == "§bBow (Power I, Punch I)"){
            if($pinv->contains(Item::get(Item::EMERALD, 0, 2))){
                $pinv->removeItem(Item::get(Item::EMERALD, 0, 2));
                $this->messagebuy($player,"Bow (Power I, Punch I)");

                $bow = Item::get(Item::BOW);
                $bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), 1));
                $bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PUNCH), 1)); 
                $pinv->addItem($bow);
            } else {
            	$this->notEnought($player,"Emerald");
               $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            } 
            return;
        }
        if($item instanceof Armor && $in == "§bChainmail Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["iron", "diamond"])){
                return;
            }
            if(isset($this->armor[$player->getName()]) && $this->armor[$player->getName()] !== "chainmail") {

                if ($pinv->contains(Item::get(Item::IRON_INGOT, 0, 40))) {
                    $pinv->removeItem(Item::get(Item::IRON_INGOT, 0, 40));
                    $this->messagebuy($player, "Chainmail set");
                    $this->armor[$player->getName()] = "chainmail";
                    $this->setArmor($player);
                } else {
                    $this->notEnought($player, "Iron ingot");
                    $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
                }
                return;
            } else {
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item instanceof Armor && $in == "§bIron Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["diamond"])){
                return;
            }
            if($pinv->contains(Item::get(Item::GOLD_INGOT, 0, 12))){
                $pinv->removeItem(Item::get(Item::GOLD_INGOT, 0, 12));
                $this->messagebuy($player,"Iron set");
                 $this->armor[$player->getName()] = "iron";
                $this->setArmor($player);
            } else {
               $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
               $this->notEnought($player,"Gold ingot");
            } 
            return;
        }
        if($item instanceof Armor && $in == "§bDiamond Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["diamond"])){
                return;
            }
            if($pinv->contains(Item::get(Item::EMERALD, 0, 6))){
                $pinv->removeItem(Item::get(Item::EMERALD, 0, 6));
                $this->messagebuy($player,"Diamond set");
                $this->armor[$player->getName()] = "diamond";
                $this->setArmor($player);
            } else {
              $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
              $this->notEnought($player,"Emerald");
            }
            return;
        }
        $this->buyItem($item, $player); 
        if($item instanceof Pickaxe){
            $pickaxe = $this->getPickaxeByTier($player);
            $inv->setItem(20, $pickaxe); 
        }
        if($item instanceof Axe){
            $axe = $this->getAxeByTier($player);
            $inv->setItem(21, $axe); 
        }
	    }));
	    // Main Menu //
	    $inv->setItem(1, Item::get(Block::WOOL, $meta[$team])->setCustomName("§fBlocks"));
	    $inv->setItem(2, Item::get(Item::GOLDEN_SWORD)->setCustomName("§fMelee"));
	    $inv->setItem(3, Item::get(Item::CHAINMAIL_BOOTS)->setCustomName("§fArmor"));
	    $inv->setItem(4, Item::get(Item::STONE_PICKAXE)->setCustomName("§fTools"));
	    $inv->setItem(5, Item::get(Item::BOW)->setCustomName("§fBow & Arrow"));
	    $inv->setItem(6, Item::get(Item::BREWING_STAND)->setCustomName("§fPotions"));
	    $inv->setItem(7, Item::get(Block::TNT)->setCustomName("§fUtility"));
	    // Block Menu //
	    $this->manageShop($player, $inv, "§fBlocks");
	    $menu->send($player);
	}

	public function messagebuy(Player $player, $item){
    	$this->addSound($player,'note.pling',1.53);
         $player->sendMessage("§6You bought §e". $item);

	}

	public function notEnought(Player $player, $item){
		$player->sendMessage("§cYour $item not enought");

	}


    
    public function manageShop($player, $inv, $type){
        $team = $this->getTeam($player);
        $meta = [
            "red" => 14,
            "blue" => 11,
            "yellow" => 4,
            "green" => 5
        ];
        // BLOCKS //
        if($type == "§fBlocks"){
        $inv->setItem(19, Item::get(Block::WOOL, $meta[$team], 16)
        ->setLore(["§f4 Iron"])
        ->setCustomName("§bWool")
        );
	    $inv->setItem(20, Item::get(Block::TERRACOTTA, $meta[$team], 16)
	    ->setLore(["§f12 Iron"])
	    ->setCustomName("§bTerracotta")
	    );
	    $inv->setItem(21, Item::get(241, $meta[$team], 4)
	    ->setLore(["§f12 Iron"])
	    ->setCustomName("§bStained Glass")
	    );
	    $inv->setItem(22, Item::get(Block::END_STONE, 0, 12)
	    ->setLore(["§f24 Iron"])
	    ->setCustomName("§bEnd Stone")
	    );
	    $inv->setItem(23, Item::get(Block::LADDER, 0, 16)
	    ->setLore(["§f4 Iron"])
	    ->setCustomName("§bLadder")
	    );
	    $inv->setItem(24, Item::get(5, 0, 16)
	    ->setLore(["§64 Gold"])
	    ->setCustomName("§bPlank")
	    );
	    $inv->setItem(25, Item::get(Block::OBSIDIAN, 0, 4)
	    ->setLore(["§24 Emerald"])
	    ->setCustomName("§bObsidian")
	    );
	    $inv->setItem(28, Item::get(0));  
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0)); 
        }
        // SWORD //
        if($type == "§fMelee"){
        $inv->setItem(19, Item::get(Item::STONE_SWORD)
        ->setLore(["§f10 Iron"])
        ->setCustomName("§bStone Sword")
        );
	    $inv->setItem(20, Item::get(Item::IRON_SWORD)
	    ->setLore(["§67 Gold"])
	    ->setCustomName("§bIron Sword")
	    );
	    $inv->setItem(21, Item::get(Item::DIAMOND_SWORD)
	    ->setLore(["§23 Emerald"])
	    ->setCustomName("§bDiamond Sword")
	    );
	    $stick = Item::get(Item::STICK);
	    $stick->setLore(["§65 Gold"]);
	    $stick->setCustomName("§bKnockback Stick");
	    $stick->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK), 1)); 
	    $inv->setItem(22, $stick);
	    $inv->setItem(23, Item::get(0));
	    $inv->setItem(24, Item::get(0));
	    $inv->setItem(25, Item::get(0));
	    $inv->setItem(28, Item::get(0)); 
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0)); 
        }
        // ARMOR //
        if($type == "§fArmor"){
        $inv->setItem(19, Item::get(Item::CHAINMAIL_BOOTS)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§bChainmail Set")
        );
	    $inv->setItem(20, Item::get(Item::IRON_BOOTS)
	    ->setLore(["§612 Gold"])
	    ->setCustomName("§bIron Set")
	    );
	    $inv->setItem(21, Item::get(Item::DIAMOND_BOOTS)
	    ->setLore(["§26 Emerald"])
	    ->setCustomName("§bDiamond Set")
	    );
	    $inv->setItem(22, Item::get(0));
	    $inv->setItem(23, Item::get(0)); 
	    $inv->setItem(24, Item::get(0));
	    $inv->setItem(25, Item::get(0));
	    $inv->setItem(28, Item::get(0)); 
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0)); 
        }
        if($type == "§fTools"){
        $inv->setItem(19, Item::get(Item::SHEARS)
        ->setLore(["§f20 Iron"])
        ->setCustomName("§bShears")
        );
        $pickaxe = $this->getPickaxeByTier($player);
        $inv->setItem(20, $pickaxe);  
	    $axe = $this->getAxeByTier($player);
        $inv->setItem(21, $axe);  
	    $inv->setItem(22, Item::get(0));
	    $inv->setItem(23, Item::get(0));
	    $inv->setItem(24, Item::get(0));
	    $inv->setItem(25, Item::get(0));
	    $inv->setItem(28, Item::get(0));  
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0)); 
        }
        if($type == "§fBow & Arrow"){
        $inv->setItem(19, Item::get(Item::ARROW, 0, 8)
        ->setLore(["§62 Gold"])
        ->setCustomName("§bArrow")
        );
	    $inv->setItem(20, Item::get(Item::BOW)
	    ->setLore(["§612 Gold"])
	    ->setCustomName("§bBow")
	    );
	    $bowpower = Item::get(Item::BOW);
	    $bowpower->setLore(["§624 Gold"]);
	    $bowpower->setCustomName("§bBow (Power I)");
	    $bowpower->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), 1));
	    $inv->setItem(21, $bowpower);
	    $bowpunch = Item::get(Item::BOW);
	    $bowpunch->setLore(["§22 Emerald"]);
	    $bowpunch->setCustomName("§bBow (Power I, Punch I)");
	    $bowpunch->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), 1));
        $bowpunch->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PUNCH), 1));  
	    $inv->setItem(22, $bowpunch);
        $inv->setItem(23, Item::get(0));
	    $inv->setItem(24, Item::get(0));
	    $inv->setItem(25, Item::get(0));
	    $inv->setItem(28, Item::get(0));  
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0)); 
        }
        if($type == "§fPotions"){
        $inv->setItem(19, Item::get(373, 16)
        ->setLore(["§21 Emerald"])
        ->setCustomName("§bSpeed Potion II (45 seconds)")
        );
	    $inv->setItem(20, Item::get(373, 11)
	    ->setLore(["§21 Emerald"])
	    ->setCustomName("§bJump Potion IV (45 seconds)")
	    );
	    $inv->setItem(21, Item::get(373, 7)
	    ->setLore(["§22 Emerald"])
	    ->setCustomName("§bInvisibility Potion (30 seconds)")
	    );
	    $inv->setItem(22, Item::get(0));
	    $inv->setItem(23, Item::get(0));
	    $inv->setItem(24, Item::get(0));
	    $inv->setItem(25, Item::get(0));
	    $inv->setItem(28, Item::get(0));  
	    $inv->setItem(29, Item::get(0));
	    $inv->setItem(30, Item::get(0));
        }
        if($type == "§fUtility"){
        $inv->setItem(19, Item::get(Item::GOLDEN_APPLE)
        ->setLore(["§63 Gold"])
        ->setCustomName("§bGolden Apple")
        );
        $inv->setItem(20, Item::get(Item::SNOWBALL)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§bBedbug")
        );
        $inv->setItem(21, Item::get(Item::SPAWN_EGG, 14)
        ->setLore(["§f120 Iron"])
        ->setCustomName("§bDream Defender")
        );
        $inv->setItem(22, Item::get(Item::FIREBALL)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§bFireball")
        ); 
        $inv->setItem(23, Item::get(Block::TNT)
        ->setLore(["§68 Gold"])
        ->setCustomName("§bTNT")
        );
        $inv->setItem(24, Item::get(Item::ENDER_PEARL)
        ->setLore(["§24 Emerald"])
        ->setCustomName("§bEnder Pearl")
        );
        $inv->setItem(25, Item::get(Item::COMPASS)
        ->setLore(["§23 Emerald"])
        ->setCustomName("§aPlayer Tracker")
        ); 
        $inv->setItem(28, Item::get(Item::BUCKET, 1)
        ->setLore(["§64 Gold"])
        ->setCustomName("§bMagic Milk")
        );
        $inv->setItem(29, Item::get(Item::EGG)
        ->setLore(["§23 Emerald"])
        ->setCustomName("§eEgg Bridge")
        );
      
		
      
        }
    }

    public function dragon(){
	foreach($this->players as $player){

    $player->sendTitle("§cSudden Death");
    $this->addSound($player,'mob.enderdragon.growl');
	}
    $this->suddendeath = new DragonTargetManager($this, $this->data["blocks"], $this->calculate($this->data["corner1"], $this->data["corner2"]));

	$this->suddendeath->addDragon("green");
	$this->suddendeath->addDragon("yellow");
	$this->suddendeath->addDragon("blue");
	$this->suddendeath->addDragon("red");


    }
    
    public function getPickaxeByTier($player, bool $forshop = true) : Item {
        if(isset($this->pickaxe[$player->getId()])){
            $tier = $this->pickaxe[$player->getId()];
            $pickaxe = [
                1 => Item::get(Item::WOODEN_PICKAXE),
                2 => Item::get(Item::WOODEN_PICKAXE),
                3 => Item::get(Item::IRON_PICKAXE),
                4 => Item::get(Item::GOLDEN_PICKAXE),
                5 => Item::get(Item::DIAMOND_PICKAXE),
                6 => Item::get(Item::DIAMOND_PICKAXE)
            ];
            $enchant = [
                1 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1),
                2 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1), 
                3 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2),
                4 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2),
                5 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 3),
                6 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 3) 
            ];
            $name = [
                1 => "§aWooden Pickaxe (Efficiency I)",
                2 => "§aWooden Pickaxe (Efficiency I)", 
                3 => "§aIron Pickaxe (Efficiency II)",
                4 => "§aGolden Pickaxe (Efficiency II)",
                5 => "§aDiamond Pickaxe (Efficiency III)",
                6 => "§aDiamond Pickaxe (Efficiency III)"
            ];
            $lore = [
                1 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ],
                2 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ], 
                3 => [
                    "§f10 Iron",
                    "§eTier: §cII", 
                    ""
                ], 
                4 => [
                    "§63 Gold",
                    "§eTier: §cIII", 
                    ""
                ],
                5 => [
                    "§66 Gold",
                    "§eTier: §cIV", 
                    ""
                ],
                6 => [
                    "§66 Gold",
                    "§eTier: §cV", 
                    "§aMax",
                    ""
                ] 
            ];
            $pickaxe[$tier]->addEnchantment($enchant[$tier]);
            if($forshop){
                $pickaxe[$tier]->setLore($lore[$tier]);
                $pickaxe[$tier]->setCustomName($name[$tier]);
            }
            return $pickaxe[$tier];
        }
        return Item::get(0);
    }
    
    public function getAxeByTier($player, bool $forshop = true) : Item{
        if(isset($this->axe[$player->getId()])){
            $tier = $this->axe[$player->getId()];
            $axe = [
                1 => Item::get(Item::WOODEN_AXE),
                2 => Item::get(Item::WOODEN_AXE),
                3 => Item::get(Item::STONE_AXE),
                4 => Item::get(Item::IRON_AXE),
                5 => Item::get(Item::DIAMOND_AXE),
                6 => Item::get(Item::DIAMOND_AXE)
            ];
            $enchant = [
                1 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1),
                2 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1), 
                3 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1),
                4 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2),
                5 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 3),
                6 => new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 3) 
            ];
            $name = [
                1 => "§aWooden Axe (Efficiency I)",
                2 => "§aWooden Axe (Efficiency I)", 
                3 => "§aStone Axe (Efficiency I)",
                4 => "§aIron Axe (Efficiency II)",
                5 => "§aDiamond Axe (Efficiency III)",
                6 => "§aDiamond Axe (Efficiency III)" 
            ];
            $lore = [
                1 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ],
                2 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ], 
                3 => [
                    "§f10 Iron",
                    "§eTier: §cII", 
                    "",
                    "§7This is an upgradable item.",
                    "§7will lose 1 tier upon",
                    "§7death!",
                    ""
                ], 
                4 => [
                    "§63 Gold",
                    "§eTier: §cIII", 
                    ""
                ],
                5 => [
                    "§66 Gold",
                    "§eTier: §cIV", 
                    ""
                ],
                6 => [
                    "§66 Gold",
                    "§eTier: §cV", 
                    "§aMax",
                    ""
                ] 
            ];
            $axe[$tier]->addEnchantment($enchant[$tier]);
            if($forshop){
                $axe[$tier]->setLore($lore[$tier]);
                $axe[$tier]->setCustomName($name[$tier]);
            }
            return $axe[$tier];
        }
        return Item::get(0);
    } 

    
    public function buyItem(Item $item, Player $player){
        if(!isset($item->getLore()[0])) return;
        $lore = TextFormat::clean($item->getLore()[0], true);
        $desc = explode(" ", $lore);
        $value = $desc[0];
        $valueType = $desc[1];
        $value = intval($value);
        $id = null;
        if ($value < 1) return;
        if(!$item instanceof Pickaxe && !$item instanceof Axe){
            $item = $item->setLore([]);
        }
        switch ($valueType) {
            case "Iron":
                $id = Item::IRON_INGOT;
                break;
            case "Gold":
                $id = Item::GOLD_INGOT;
                break;
            case "Emerald":
                $id = Item::EMERALD;
                break;
            default:
                break;
        }

        if($item instanceof Pickaxe){
            if(isset($this->pickaxe[$player->getId()])){
                if($this->pickaxe[$player->getId()] >= 6){
                    return;
                }
            }
            $item = $item->setLore([]);
            $item->setUnbreakable(true); 
            $c = 0;
            $i = 0;
            foreach($player->getInventory()->getContents() as $slot => $isi){
                if($isi instanceof Pickaxe){
                    $c++;
                    $i = $slot;
                }
            }

            $payment = Item::get($id, 0, $value);
            if ($player->getInventory()->contains($payment)) { 
                $this->pickaxe[$player->getId()] = $this->getNextTier($player, false); 
                $player->getInventory()->removeItem($payment);
				$this->messagebuy($player,"$value x {$item->getName()}");
                if($c > 0){
                    $player->getInventory()->setItem($i, $item); 
                } else {
                    $player->getInventory()->addItem($item); 
                }
            } else {
                $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
                $this->notEnought($player,$payment->getName());
            }
            return;
        }
        if($item instanceof Axe){
            if(isset($this->axe[$player->getId()])){
                if($this->axe[$player->getId()] >= 6){
                    return;
                }
            } 
            $item = $item->setLore([]);
            $item->setUnbreakable(true);
            $c = 0;
            $i = 0;
            foreach($player->getInventory()->getContents() as $slot => $isi){
                if($isi instanceof Axe){
                    $c++;
                    $i = $slot;
                }
            }
            $payment = Item::get($id, 0, $value);
            if ($player->getInventory()->contains($payment)) { 
                $this->axe[$player->getId()] = $this->getNextTier($player, true); 
                $player->getInventory()->removeItem($payment);
				$this->messagebuy($player,"$value x {$item->getName()}");

                if($c > 0){
                    $player->getInventory()->setItem($i, $item); 
                } else {
                    $player->getInventory()->addItem($item); 
                }
            } else {
				$this->notEnought($player,$payment->getName());
              $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]); 
            }
            return; 
        }
        $payment = Item::get($id, 0, $value);
        if ($player->getInventory()->contains($payment)) {
            $player->getInventory()->removeItem($payment);
            $it = Item::get($item->getId(), $item->getDamage(), $item->getCount());
            if(in_array($item->getCustomName(), ["§bMagic Milk", "§bBedbug", "§bDream Defender", "§bFireball", "§bInvisibility Potion (30 seconds)", "§bSpeed Potion II (45 seconds)", "§bJump Potion IV (45 seconds)"])){
                $it->setCustomName("{$item->getCustomName()}");
            }
            if($player->getInventory()->canAddItem($it)){
                $player->getInventory()->addItem($it); 
            } else {
                $player->getLevel()->dropItem($player, $it);
            }
            $this->messagebuy($player,"$value x {$item->getName()}");
        } else {
            $this->notEnought($player,$payment->getName());
         $player->getLevel()->addSound(new EndermanTeleportSound($player), [$player]);
        }
    }

    
    public function getLessTier($player, bool $type){
        if($type){
            if(isset($this->axe[$player->getId()])){
                $tier = $this->axe[$player->getId()];
                $less = [
                    6 => 4,
                    5 => 4,
                    4 => 3,
                    3 => 2,
                    2 => 1,
                    1 => 1
                ];
                return $less[$tier];
            }
        } else {
            if(isset($this->pickaxe[$player->getId()])){
                $tier = $this->pickaxe[$player->getId()];
                $less = [
                    6 => 4,
                    5 => 4,
                    4 => 3,
                    3 => 2,
                    2 => 1,
                    1 => 1
                ];
                return $less[$tier];
            } 
        }
        return "";
    }


    
    public function getNextTier($player, bool $type){
        if($type){
            if(isset($this->axe[$player->getId()])){
                $tier = $this->axe[$player->getId()];
                $less = [
                    1 => 3,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                    5 => 6,
                    6 => 6
                ];
                return $less[$tier];
            }
        } else {
            if(isset($this->pickaxe[$player->getId()])){
                $tier = $this->pickaxe[$player->getId()];
                $less = [
                    1 => 3,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                    5 => 6,
                    6 => 6
                ];
                return $less[$tier];
            } 
        }
        return "";
    } 

    /**
     * @param bool $restart
     */
    public function loadArena(bool $restart = false) {
        if(!$this->data["enabled"]) {
            $this->plugin->getLogger()->error("Can not load arena: Arena is not enabled!");
            return;
        }

        if(!$this->mapReset instanceof MapReset) {
            $this->mapReset = new MapReset($this);
        }

        if(!$restart) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        }



        else {
            $this->scheduler->reloadTimer();
            $this->level = $this->mapReset->loadMap($this->data["level"]);
        }

        if(!$this->level instanceof Level) {
            $level = $this->mapReset->loadMap($this->data["level"]);
            if(!$level instanceof Level) {
                $this->plugin->getLogger()->error("Arena level wasn't found. Try save level in setup mode.");
                $this->setup = true;
                return;
            }
            $this->level = $level;
        }
        $this->level->setAutoSave(false);


        $this->phase = static::PHASE_LOBBY;
        $this->players = [];

    }

    /**
     * @param bool $loadArena
     * @return bool $isEnabled
     */
    public function enable(bool $loadArena = true): bool {
        if(empty($this->data)) {
            return false;
        }
        if($this->data["level"] == null) {
            return false;
        }
        if(!$this->plugin->getServer()->isLevelGenerated($this->data["level"])) {
            return false;
        }
        if(!is_int($this->data["slots"])) {
            return false;
        }
        if(!is_array($this->data["location"])) {
            return false;
        }
        if(!is_array($this->data["joinsign"])) {
            return false;
        }
        if(count($this->data["joinsign"]) !== 2) {
            return false;
        }
        $this->data["enabled"] = true;
        $this->setup = false;
        if($loadArena) $this->loadArena();
        return true;
    }

    private function createBasicData() {
        $this->data = [
            "level" => null,
            "slots" => 16,
            "lobby" => null,
			"mode" => "4",
            "bed" => [],
            "y" => null,
            "shop" => [],
			"teams" => [],
            "upgrade" => [],
            "location" => [],
            "enabled" => false,
            "corner1" => [],
            "corner2" => [],
            "corner3" => [],
            "corner4" => [],
            "blocks" => [],
            "joinsign" => []
        ];
    }

    public function __destruct() {
        unset($this->scheduler);
    }
}