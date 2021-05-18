<?php

declare(strict_types=1);

namespace RolandDev\BedWars\task;

use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use RolandDev\BedWars\Game as Arena;
use pocketmine\tile\Sign;
use pocketmine\item\Item;
use RolandDev\BedWars\math\Time;
use RolandDev\BedWars\math\Vector3;
use RolandDev\BedWars\Game;


class TaskTick extends Task {


    protected $plugin;
    
    /** @var int $waitTime */
    public $waitTime = [];
    public $kedip2 = 0;
    
    public $upgradeNext = [];
    public $upgradeTime = [];
    
    public $bedgone = [];
    public $suddendeath = [];
    public $gameover = [];
    public $mboh = 500;

    /** @var int $restartTime */
    public $kedip1 = 0;
    public $restartTime = [];
    public $kedip = 0;
    public $kedip3 = 0;

    /** @var array $restartData */
    public $restartData = [];

    public $dragon;

    /**
     * ArenaScheduler constructor.
     * @param Arena $plugin
     */
    public function __construct(Game $plugin) {
        $this->plugin = $plugin;
    }

    public function counter(Player $player,$type){
    	if($type == "fk"){
    		if(isset($this->plugin->finalkill[$player->getId()])){
    			return $this->plugin->finalkill[$player->getId()];
			}
		}
    	if($type == "kill"){
    		if(isset($this->plugin->kill[$player->getId()])){
               return $this->plugin->kill[$player->getId()];

			}
		}
    	if($type == "broken"){
    		if(isset($this->plugin->broken[$player->getId()])){
    			return $this->plugin->broken[$player->getId()];

			}

		}
    	return "";
	}


    public function onRun(int $currentTick) {

        $r = $this->plugin->teamstatus("red");
        $b = $this->plugin->teamstatus("blue");
        $y = $this->plugin->teamstatus("yellow");
        $g = $this->plugin->teamstatus("green");

        $redteam = [
            "red" => "§7YOU",
            "blue" => " ",
            "yellow" => "  ",
            "green" => " ",
        ];
        $blueteam = [
            "red" => " ",
            "blue" => "§7YOU",
            "yellow" => " ",
            "green" => " ",
        ];
        $yellowteam = [
            "red" => " ",
            "blue" => "§ ",
            "yellow" => "§7YOU",
            "green" => " ",
        ];
        $greenteam = [
            "red" => " ",
            "blue" => " ",
            "yellow" => " ",
            "green" => "§7YOU",
        ];
        $text = "§l§eBEDWARS";
        if($this->plugin->setup) return;
        $api = $this->plugin->plugin->getScore();
        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= 8) {
                    $time = $this->waitTime[$this->plugin->data["level"]];
                    if($time > 0){
                          $this->waitTime[$this->plugin->data["level"]] -= 1;
                          foreach($this->plugin->players as $player){
                              $api->new($player, $player->getName(),  $text);
                              $api->setLine($player, 1, "§f");
                              $api->setLine($player,2, "§fMap: §a".$this->plugin->level->getFolderName());
                              $api->setLine($player, 3, "§fPlayers§7: §a" .  count($this->plugin->players) . "/{$this->plugin->data["slots"]}");
                              $api->setLine($player, 4, "           ");
                              $api->setLine($player, 5, "§fStarting in ".$this->waitTime[$this->plugin->data["level"]]. "§a s");
                              $api->setLine($player, 6, "   ");
                              $api->setLine($player, 7, "§fMode: §a4vs4vs4vs4");
                              $api->setLine($player, 8, "§fTeam: §a". ucfirst($this->plugin->getTeam($player)));
                              $api->setLine($player, 9, "                 ");
                              $api->setLine($player, 10, $this->plugin->plugin->config["scoreboard-title"]);

                    }
                    }
                    if($this->waitTime[$this->plugin->data["level"]] == 1){
                    $this->plugin->startGame();
                    $this->plugin->broadcastMessage("§cCross teaming is not allowed");
                    $this->plugin->broadcastMessage("§7This map is the property of the Hypixel Network. VipzMc Network is not affliated with or endorsed by Hypixel in any way");
                    }
                } else {

                    foreach($this->plugin->players as $player){
                     $api->new($player, $player->getName(),  $text);
                     $api->setLine($player, 1, "§f");
                     $api->setLine($player,2, "§fMap: §a".$this->plugin->level->getFolderName());
                     $api->setLine($player, 3, "§fPlayers§7: §a" .  count($this->plugin->players) . "/{$this->plugin->data["slots"]}");
                     $api->setLine($player, 4, "           ");
                     $api->setLine($player, 5, "§fWaiting...");
                     $api->setLine($player, 6, "   ");
                     $api->setLine($player, 7, "§fMode: §a4vs4vs4vs4");
                     $api->setLine($player, 8, "§fTeam: §a". ucfirst($this->plugin->getTeam($player)));
                     $api->setLine($player, 9, "                 ");
                     $api->setLine($player, 10, $this->plugin->plugin->config["scoreboard-title"]);
                    $this->waitTime[$this->plugin->data["level"]] = 30;
                }
                }
                break;
            case Arena::PHASE_GAME:
                $this->plugin->level->setTime(5000);
                $events = "";
                if($this->upgradeNext[$this->plugin->data["level"]] <= 4){
                    $this->upgradeTime[$this->plugin->data["level"]] -=  1;
                    if($this->upgradeNext[$this->plugin->data["level"]] == 1){
                        $events = "§fDiamond II in: §a" . Time::calculateTime($this->upgradeTime[$this->plugin->data["level"]]) . "";
                    }
                    if($this->upgradeNext[$this->plugin->data["level"]] == 2){
                        $events = "§fEmerald II in: §a" . Time::calculateTime($this->upgradeTime[$this->plugin->data["level"]]) . "";
                    }
                    if($this->upgradeNext[$this->plugin->data["level"]] == 3){
                        $events = "§fDiamond III in: §a" . Time::calculateTime($this->upgradeTime[$this->plugin->data["level"]]) . "";
                    }
                    if($this->upgradeNext[$this->plugin->data["level"]] == 4){
                        $events = "§fEmerald III in: §a" . Time::calculateTime($this->upgradeTime[$this->plugin->data["level"]]) . "";
                    }
                    if($this->upgradeTime[$this->plugin->data["level"]] == (0.0 * 60)){
                        $this->upgradeTime[$this->plugin->data["level"]] = 5 * 60;
                        if($this->upgradeNext[$this->plugin->data["level"]] == 1){
                            $this->plugin->broadcastMessage("§bDiamond Generators §ahas been upgraded to Tier §eII");
                            $this->plugin->upgradeGeneratorTier("diamond", 2);
                            foreach($this->plugin->players as $player){
                                $this->plugin->addexp($player);
                            }

                        }
                        if($this->upgradeNext[$this->plugin->data["level"]] == 2){
                            $this->plugin->broadcastMessage("§2Emerald Generators §ahas been upgraded to Tier §eII");
                            $this->plugin->upgradeGeneratorTier("emerald", 2);

                        }
                        if($this->upgradeNext[$this->plugin->data["level"]] == 3){
                            $this->plugin->broadcastMessage("§bDiamond Generators §ahas been upgraded to Tier §eIII");
                            $this->plugin->upgradeGeneratorTier("diamond", 3);
                            foreach($this->plugin->players as $player){
                                $this->plugin->addexp($player);
                            }

                        }
                        if($this->upgradeNext[$this->plugin->data["level"]] == 4){
                            $this->plugin->broadcastMessage("§2Emerald Generators §ahas been upgraded to Tier §eIII");
                            $this->plugin->upgradeGeneratorTier("emerald", 3);

                        }
                        $this->upgradeNext[$this->plugin->data["level"]]++;
                    }
                } else {
                    if($this->bedgone[$this->plugin->data["level"]] > (-1.0 * 60)){
                        $this->bedgone[$this->plugin->data["level"]] -=  1;
                        $events = "§fBedgone in: §a" . Time::calculateTime($this->bedgone[$this->plugin->data["level"]]) . "";
                    }
                    if($this->upgradeNext[$this->plugin->data["level"]] == 6){
                        $this->suddendeath[$this->plugin->data["level"]] -=  1;

                        $events = "§fSudden Death in: §a" . Time::calculateTime($this->suddendeath[$this->plugin->data["level"]]) . "";
                    }
                    if($this->bedgone[$this->plugin->data["level"]] == (0.0 * 60)){
                        if($this->upgradeNext[$this->plugin->data["level"]] == 5){
                            $this->plugin->destroyAllBeds();
                            $this->upgradeNext[$this->plugin->data["level"]] = 6;
                            $this->suddendeath[$this->plugin->data["level"]] -=  1;
                        }
                        $this->plugin->level->setTime(5000);
                        foreach($this->plugin->players as $player){
                            $this->plugin->addexp($player);
                        }
                    }
                    if($this->suddendeath[$this->plugin->data["level"]] == (0.1 * 60)){

                        if($this->upgradeNext[$this->plugin->data["level"]] == 6){
                            $this->upgradeNext[$this->plugin->data["level"]] = 7;

                            $this->plugin->dragon();
                        }
                    }
                    if($this->upgradeNext[$this->plugin->data["level"]] == 7){
                        $this->gameover[$this->plugin->data["level"]] -=  1;
                        $this->mboh--;
                        $events = "§fGame end in: §a" . Time::calculateTime($this->gameover[$this->plugin->data["level"]]) . "";
                    }
                    if($this->gameover[$this->plugin->data["level"]] == (0.1 * 60)){
                        $this->upgradeNext[$this->plugin->data["level"]] = 8;
                        $this->plugin->draw();
                        foreach($this->plugin->players as $player){
                            $this->plugin->addexp($player);
                        }
                    }
                }
                foreach($this->plugin->respawn as $r) {
                    if($this->plugin->respawnC[$r->getName()] <= 1) {
                        unset($this->plugin->respawn[$r->getName()]);
                        unset($this->plugin->respawnC[$r->getName()]);
                        $this->plugin->respawn($r);
                    } else {
                        $this->plugin->respawnC[$r->getName()]--;

                        $r->sendSubtitle("§eYou will respawn in §c{$this->plugin->respawnC[$r->getName()]} §eseconds!");
                        $r->sendMessage("§eYou will respawn in §c{$this->plugin->respawnC[$r->getName()]} §eseconds!");

                    }
                }
                foreach($this->plugin->players as $milk){
                    if(isset($this->plugin->milk[$milk->getId()])){
                        if($this->plugin->milk[$milk->getId()] <= 0) {
                            unset($this->plugin->milk[$milk->getId()]);
                        } else {
                            $this->plugin->milk[$milk->getId()]--;
                        }
                    }
                }

                foreach($this->plugin->players as $pt){ 
                    $team = $this->plugin->getTeam($pt);
                    $pos = Vector3::fromString($this->plugin->data["bed"][$team]);
                    if(isset($this->plugin->utilities[$this->plugin->level->getFolderName()][$team]["haste"])){
                        if($this->plugin->getTeam($pt) == $team){
                            if($this->plugin->utilities[$this->plugin->level->getFolderName()][$team]["haste"] > 1){
                                $eff = new EffectInstance(Effect::getEffect(Effect::HASTE), 60, ($this->plugin->utilities[$this->plugin->level->getFolderName()][$team]["haste"]  - 2));
                                $eff->setVisible(false);
                                $pt->addEffect($eff);
                            }
                        }
                    }
                    if(isset($this->plugin->utilities[$this->plugin->level->getFolderName()][$team]["health"])){
                        if($this->plugin->getTeam($pt) == $team){
                            if($this->plugin->utilities[$this->plugin->level->getFolderName()][$team]["health"] > 1){
                                if($pt->distance($pos) < 10){
                                    $eff = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 60, 0);
                                    $eff->setVisible(false);
                                    $pt->addEffect($eff);
                                }
                            }
                        }
                    }
                }
         
                

                     $this->kedip2++;
                foreach (array_merge($this->plugin->players, $this->plugin->spectators) as $player) {
                    	$player->setScoreTag("§f{$player->getHealth()} §c❤️ §l");

                    if ($player->getInventory()->contains(Item::get(Item::COMPASS))) {
                        if(!$player->isSpectator()){
                        if(isset($this->plugin->tracking[$player->getName()])){
                        $trackIndex = $this->plugin->tracking[$player->getName()];
                        $team = $trackIndex;
                        $status = $this->plugin->bedStatus($team);
                        $destroyed = null;
                        if($status){
                            $destroyed = "";
                        } else {
                            $destroyed = "§cELEMINATED";
                        }
                        $player->sendTip("§eTracking: §f$team  §f- §aDistance: §f"  . round(Vector3::fromString($this->plugin->data["location"][$team])->distance($player)) . "§f§am". " ". "$destroyed");
                        }
                        }
                    }
                    $team = $this->plugin->getTeam($player);
                    if(!$player->hasEffect(14)){
                        if(isset($this->invis[$player->getId()])){
                            $this->plugin->setInvis($player, false);
                        }
                    }
                    $player->setFood(20);
    
                    $kills = $this->counter($player,"kill");
                    $fkills = $this->counter($player,"fk");
                    $broken = $this->counter($player,"broken");
                    $api->new($player, $player->getName(), $text);
                    $api->setLine($player, 1, "        ");
                    $api->setLine($player, 2,  $events);
                    $api->setLine($player, 3, "§b§b§b ");
                    $api->setLine($player, 4, "§l§cR§r §fRed $r  {$redteam[$team]}");
                    $api->setLine($player, 5,  "§l§9B§r §fBlue $b {$blueteam[$team]}");
                    $api->setLine($player, 6, "§l§eY§r §fYellow $y {$yellowteam[$team]}");
                    $api->setLine($player, 7, "§l§aG§r §fGreen  $g {$greenteam[$team]}");
                    $api->setLine($player, 8, "§b§b§b  ");
                    $api->setLine($player, 9, "§fKills: §a{$kills}");
                    $api->setLine($player, 10, "§fFinal Kills: §a{$fkills}");
                    $api->setLine($player, 11, "§fBed Broken: §a{$broken}");
                    $api->setLine($player, 12, "  ");
                    $api->setLine($player, 13, $this->plugin->plugin->config["scoreboard-title"]);
                    

                }
       
               $this->plugin->checkWinner();

                break;
            case Arena::PHASE_RESTART:
                $this->restartTime[$this->plugin->data["level"]] -=  1;
                foreach ($this->plugin->level->getPlayers() as $player) {
                        $api->new($player, $player->getName(), $text);
						$api->setLine($player, 1, "§f");
						$api->setLine($player, 2, "§fMap: §a" . $this->plugin->level->getFolderName());
						$api->setLine($player, 3, "§fPlayers§7: §a" . count($this->plugin->players) . "/{$this->plugin->data["slots"]}");
						$api->setLine($player, 4, "           ");
						$api->setLine($player, 5, "§fRestarting §f" . $this->restartTime[$this->plugin->data["level"]]. "§a s");
						$api->setLine($player, 6, "   ");
						$api->setLine($player, 7, "§fMode: §a4vs4vs4vs4");

						$api->setLine($player, 8, "    ");
						$api->setLine($player, 9, $this->plugin->plugin->config["scoreboard-title"]);
                }

                switch ($this->restartTime[$this->plugin->data["level"]]) {
                    case 0:
                        foreach ($this->plugin->level->getPlayers() as $player){
                            $name = $player->getName();
                            $kills = new Config($this->plugin->plugin->getDataFolder() . "finalkills.yml", Config::YAML);
                            $kills->set($name, 0);
                            $kills->save();
                            $this->plugin->plugin->joinToRandomArena($player);
                            $api->remove($player);
                        }

                        break;
                    case -1:
                        $this->plugin->loadArena(true);
                        $this->reloadTimer();
                        $this->plugin->destroyEntity();
                     break;
                }
                break;
        }
    }



    public function reloadSign() {
        if(!is_array($this->plugin->data["joinsign"]) || empty($this->plugin->data["joinsign"])) return;

        $signPos = Position::fromObject(Vector3::fromString($this->plugin->data["joinsign"][0]), $this->plugin->plugin->getServer()->getLevelByName($this->plugin->data["joinsign"][1]));

        if(!$signPos->getLevel() instanceof Level || is_null($this->plugin->level)) return;

        $signText = [
            "§bBEDWARS",
            "§7[ §c? / ? §7]",
            "§cdisable",
            "§c"
        ];

        if($signPos->getLevel()->getTile($signPos) === null) return;

        if($this->plugin->setup || $this->plugin->level === null) {
            /** @var Sign $sign */
            $sign = $signPos->getLevel()->getTile($signPos);
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
            return;
        }

        $signText[1] = "§7[ §c" . count($this->plugin->players) . " / " . $this->plugin->data["slots"] . " §7]";

        switch ($this->plugin->phase) {
            case Arena::PHASE_LOBBY:
                if(count($this->plugin->players) >= $this->plugin->data["slots"]) {
                    $signText[2] = "§6Full";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                }
                else {
                    $signText[2] = "§aJoin";
                    $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                }
                break;
            case Arena::PHASE_GAME:
                $signText[2] = "§5InGame";
                $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                break;
            case Arena::PHASE_RESTART:
                $signText[2] = "§cRestarting...";
                $signText[3] = "§8Map: §7{$this->plugin->level->getFolderName()}";
                break;
        }

        /** @var Sign $sign */
        $sign = $signPos->getLevel()->getTile($signPos);
        if($sign instanceof Sign)    // Chest->setText() doesn't work :D
            $sign->setText($signText[0], $signText[1], $signText[2], $signText[3]);
    }

    public function reloadTimer() {

         if(!empty($this->plugin->data["level"])){

        $this->waitTime[$this->plugin->data["level"]] = 30;
        $this->upgradeNext[$this->plugin->data["level"]] = 1;
        $this->upgradeTime[$this->plugin->data["level"]] = 5 * 60;
        $this->bedgone[$this->plugin->data["level"]] = 10 * 60;
        $this->suddendeath[$this->plugin->data["level"]] = 10 * 60;
        $this->gameover[$this->plugin->data["level"]] = 10 * 60; 
        $this->restartTime[$this->plugin->data["level"]] = 10;
      }

    }
}
