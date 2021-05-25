<?php



namespace RolandDev\BedWars;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use RolandDev\BedWars\libs\scoreboard\ScoreAPI;
use RolandDev\BedWars\math\MapReset;
use RolandDev\BedWars\commands\BedWarsCommand;
use RolandDev\BedWars\provider\MySQLDataProvider;
use RolandDev\BedWars\math\Vector3;
use RolandDev\BedWars\math\TNT;
use RolandDev\BedWars\math\Bedbug;
use RolandDev\BedWars\math\Golem;
use RolandDev\BedWars\math\Fireball;
use RolandDev\BedWars\math\Generator;
use RolandDev\BedWars\math\ShopVillager;
use RolandDev\BedWars\math\UpgradeVillager;
use RolandDev\BedWars\provider\YamlDataProvider;
use pocketmine\entity\Skin;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use VipzCore\query\CheckPartyQuery;
use VipzCore\query\FetchAllParty;
use VipzCore\query\MemberPartyQuery;
use VipzCore\query\QueryQueue;
use pocketmine\Player;
use RolandDev\BedWars\math\EnderDragon;
use RolandDev\BedWars\math\ThrownBlock;
use RolandDev\BedWars\math\Egg;
use RolandDev\BedWars\libs\muqsit\invmenu\InvMenuHandler;

/**
 * Class BedWars
 * @package RolandDev\BedWars
 */
class BedWars extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;
    /**
     * @var
     */
    public $config;
    /**
     * @var array
     */
    public $placedBlock = [];
    /**
     * @var array
     */
    public $arenas = [];

    /**
     * @var array
     */
    public $setters = [];
    /**
     * @var array
     */
    public $setupData = [];
    /**
     * @var
     */
    public $mysqldata;
    /**
     * @var array
     */
    public $arenaPlayer = [];
    /**
     * @var array
     */
    public $teams = [];
    /**
     * @var
     */
    public static $score;

    /**
     * @var
     */
    public static $instance;

    /**
     * @var
     */
    public $shop;
    /**
     * @var
     */
    public $upgrade;

    public function onEnable() {
        self::$instance = $this;
        self::$score = new ScoreAPI($this);
        $this->saveResource("config.yml");
        $this->saveResource("diamond.png");
        $this->saveResource("emerald.png");
        $this->registerEntity();
        parent::onEnable();
        $this->mysqldata = new MySQLDataProvider($this);
        $this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->dataProvider = new YamlDataProvider($this);
        $this->getServer()->getCommandMap()->register("bw", new BedWarsCommand($this));
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        if(is_null($this->getConfig()->get("join-arena"))){
            $this->getConfig()->set("join-arena","false");
            $this->getConfig()->save();
            $this->getConfig()->reload();
        }
        $this->getServer()->getLogger()->info("§l§eBedWars actived");

    }

    
    public static function getInstance(){
        return self::$instance;
    }

    public function isInGame(Player $player): bool
    {
        if(isset($this->arenaPlayer[$player->getName()])){
            return true;
        } else {
            return false;
        }
    }

    public function getArenaByPlayer(Player $player){
        return $this->arenaPlayer[$player->getName()];
    }

    public function registerEntity(){
        Entity::registerEntity(EnderDragon::class, true);
        Entity::registerEntity(ShopVillager::class, true);
        Entity::registerEntity(UpgradeVillager::class, true);
        Entity::registerEntity(Generator::class, true);
        Entity::registerEntity(Bedbug::class, true);
        Entity::registerEntity(Egg::class,true);
        Entity::registerEntity(Golem::class, true);
        Entity::registerEntity(Fireball::class, true);
    
    }

    public function onPlayerJoin(PlayerJoinEvent $event){
        $event->setJoinMessage("");
    }

    public function join(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
	
		if ($this->mysqldata->getAccount($player) == null) {
			$this->mysqldata->registerAccount($player);
		}  
        if($this->getConfig()->get("join-arena") == true){
            $this->joinToRandomArena($player);
        }
	}

    /**
     * @param $path
     * @return Skin
     */
    
    public function getSkinFromFile($path) : Skin{
        $img = imagecreatefrompng($path);
        $bytes = '';
        $l = (int) getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $r = ($rgba >> 16) & 0xff;
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($img);
        return new Skin("Standard_CustomSlim", $bytes);
    }

    public function onDisable() {
        $this->dataProvider->saveArenas();
        if(file_exists($this->getDataFolder()."finalkills.yml")){
            unlink($this->getDataFolder()."finalkills.yml");
        }
        

    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();

        if(!isset($this->setters[$player->getName()])) {
            return;
        }

        $event->setCancelled();
        $args = explode(" ", $event->getMessage());
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage(
                "§bhelp : §aDisplays list of available setup commands\n" .
                "§bslots : §aUpdates arena slots\n".
                "§blevel : §aSets arena level\n".
                "§blobby : §aSets Lobby Spawn\n".
                "§blocation: §aSets arena location\n".
                "§bcorner1: §aSets arena dragon 1\n".
                "§bcorner2: §aSets arena dragon 2\n".
                "§bjoinsign : §aSets arena join sign\n".
                "§bsavelevel : §aSaves the arena level\n".
                "§bsetbed : §aset bed position \n".
                "§benable : §aEnables the arena");
                break;
            case "corner1":
                $arena->data["corner1"] =  Position::fromObject($player->ceil(), $player->getLevel());
                $player->sendMessage("Sucessfuly set ender dragon position 1");
                break;
            case "corner2":
                $firstPos = $arena->data["corner1"];

                $level = $player->getLevel();
               

                $player->sendMessage("§6> Importing blocks...");
                $secondPos = $player->ceil();
                $blocks = [];

                for($x = min($firstPos->getX(), $secondPos->getX()); $x <= max($firstPos->getX(), $secondPos->getX()); $x++) {
                    for($y = min($firstPos->getY(), $secondPos->getY()); $y <= max($firstPos->getY(), $secondPos->getY()); $y++) {
                        for($z = min($firstPos->getZ(), $secondPos->getZ()); $z <= max($firstPos->getZ(), $secondPos->getZ()); $z++) {
                            if($level->getBlockIdAt($x, $y, $z) !== Block::AIR) {
                                $blocks["$x:$y:$z"] = new Vector3($x,$y,$z);
                            }
                        }
                    }
                }

                $player->sendMessage("§aDragon position 2 set to {$player->asVector3()->__toString()} in level {$level->getName()}");
                $arena->data["corner1"] =  (new Vector3((int)$firstPos->getX(), (int)$firstPos->getY(), (int)$firstPos->getZ()))->__toString();
                $arena->data["corner2"] = (new Vector3((int)$player->getX(), (int)$player->getY(), (int)$player->getZ()))->__toString();
                $arena->data["blocks"] = $blocks;
                $player->sendMessage("Sucessfuly set ender dragon position 2");
                break;
            case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7slots <int: slots>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§bSlots updated to $args[1]!");
                break;
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("§bUsage: §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->isLevelGenerated($args[1])) {
                    $player->sendMessage("§bLevel $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§bArena level updated to $args[1]!");
                $arena->data["level"] = $args[1];
                break;
            case "addupgrade":
                if(isset($this->upgrade[$player->getName()])){
                    $this->upgrade[$player->getName()] = 0;
                }
                if(!isset($args[1])) {
                      $player->sendMessage("§cUsage: shop <1/2/3/4>");
                    break;
                }
                if(!is_numeric($args[1])) {
                    $player->sendMessage("§cType number!");
                    break;
                }
                if(!in_array($args[1],[1,2,3,4])){
                    $player->sendMessage("§cUsage: shop <1/2/3/4>");
                    break;
                }

                $upgrade = $this->upgrade[$player->getName()];
                $arena->data["upgrade"]["$upgrade"] = (new Vector3(floor($player->getX()), floor($player->getY()), floor($player->getZ())))->__toString();
                $player->sendMessage("§bSpawn upgrade $upgrade setted " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                $this->upgrade[$player->getName()]++;
                break;
            case "addshop":
                if(isset($this->shop[$player->getName()])){
                    $this->shop[$player->getName()] = 1;
                }
                $shop = $this->shop[$player->getName()];
                $arena->data["shop"]["$shop"] = (new Vector3(floor($player->getX()), floor($player->getY()), floor($player->getZ())))->__toString();
               
                $player->sendMessage("§bSpawn Shop  $shop setted " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                $this->shop[$player->getName()]++;
            break;
            case "setdistance":
              $arena->data["distance"] = Vector3::fromString($arena->data["location"]["red"])->distance($player->asVector3());
            break;
            case "location":
                if(!in_array($args[1], ["red", "blue", "yellow", "green"])){
                    $player->sendMessage("§cUsage: §7location blue/red/yellow/green");
                    break;
                }
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7location blue/red/yellow/green");
                    break;
                }
 

               $arena->data["location"]["{$args[1]}"] = (new Vector3(floor($player->getX()), floor($player->getY()), floor($player->getZ())))->__toString();
                $player->sendMessage("§bLocation Team $args[1] set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));

            break;   
            case "setbed":
                
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7setspawn blue/red/yellow/green");
                    break;
                }
                if(!in_array($args[1], ["red", "blue", "yellow", "green"])){
                    break;
                }
                $arena->data["bed"]["{$args[1]}"] = (new Vector3(floor($player->getX()), floor($player->getY()), floor($player->getZ())))->__toString();
                $player->sendMessage("§a Bed position $args[1] set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                break; 
            case "lobby":
                $arena->data["lobby"] = (new Vector3(floor($player->getX()) + 0.0, floor($player->getY()), floor($player->getZ()) + 0.0))->__toString();
                $player->sendMessage("§bLobby set to X: " . (string)floor($player->getX()) . " Y: " . (string)floor($player->getY()) . " Z: " . (string)floor($player->getZ()));
                break;
            case "joinsign":
                $player->sendMessage("§a> Break block to set join sign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "savelevel":
                if(!$arena->level instanceof Level) {
                    $player->sendMessage("§c> Error when saving level: world not found.");
                    if($arena->setup) {
                        $player->sendMessage("§bEror arena not enabled");
                    }
                    break;
                }
                $arena->mapReset->saveMap($arena->level);
                $player->sendMessage("Level Saved");
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }

                if(!$arena->enable(false)) {
                    $player->sendMessage("§cCould not load arena, there are missing information!");
                    break;
                }

                if($this->getServer()->isLevelGenerated($arena->data["level"])) {
                    if(!$this->getServer()->isLevelLoaded($arena->data["level"]))
                        $this->getServer()->loadLevel($arena->data["level"]);
                    if(!$arena->mapReset instanceof MapReset)
                        $arena->mapReset = new MapReset($arena);
                    $arena->mapReset->saveMap($this->getServer()->getLevelByName($arena->data["level"]));
                }

                $arena->loadArena(false);
                $player->sendMessage("§aArena enabled!");
                break;
            case "mode":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7mode 1/2/3/4");
                    break;
                }
                if(in_array($args[1],[1,2,3,4])){
                    $player->sendMessage("§bSuccesfuly"); 
                    $arena->data["mode"] = $args[1];
                }
              
            break;    
            case "done":
         
                $player->sendMessage("§eArena saved to database");
                unset($this->setters[$player->getName()]);
                unset($this->upgrade[$player->getName()]);
                unset($this->shop[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§etype 'help' for list commands ");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getX(), $block->getY(), $block->getZ()))->__toString(), $block->getLevel()->getFolderName()];
                    $player->sendMessage("§aJoin sign seted");
                    unset($this->setupData[$player->getName()]);
                    $event->setCancelled();
                    break;
            }
        }
    }

    public function getRandomArena(){

        $availableArenas = [];
        foreach ($this->arenas as $index => $arena) {
            $availableArenas[$index] = $arena;
        }

        //2.
        foreach ($availableArenas as $index => $arena) {
            if($arena->phase !== 0 || $arena->setup) {
                unset($availableArenas[$index]);
            }
        }

        //3.
        $arenasByPlayers = [];
        foreach ($availableArenas as $index => $arena) {
            $arenasByPlayers[$index] = count($arena->players);
        }

        arsort($arenasByPlayers);
        $top = -1;
        $availableArenas = [];

        foreach ($arenasByPlayers as $index => $players) {
            if($top == -1) {
                $top = $players;
                $availableArenas[] = $index;
            }
            else {
                if($top == $players) {
                    $availableArenas[] = $index;
                }
            }
        }

        if(empty($availableArenas)) {
            return null;
        }

        return $this->arenas[$availableArenas[array_rand($availableArenas, 1)]];
    }
    

    
    
    public function joinToRandomArena(Player $player) {
        $arena = $this->getRandomArena();
        if(!is_null($arena)) {
            $arena->joinToArena($player);
            return;
        }
       
        $player->sendMessage("§cArena full sending you to lobby-1");
        $player->getInventory()->clearAll();
        $player->getServer()->dispatchCommand($player,"lobby");
        $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
        
    }

    public function getScore(){
        return self::$score;
    }


    public function addFinalKill(Player $player){
        $name = $player->getName();
        $kills = new Config($this->getDataFolder() . "finalkills.yml", Config::YAML);
        $k = $kills->get($name);
        $kills->set($name, $k + 1);
        $kills->save();
    }
    
}
