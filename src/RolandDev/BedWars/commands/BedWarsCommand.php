<?php



declare(strict_types=1);

namespace RolandDev\BedWars\commands;

use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use RolandDev\BedWars\Game;
use RolandDev\BedWars\BedWars;

/**
 *
 */
class BedWarsCommand extends PluginCommand {
    /**
     * @var BedWars
     */
    private $plugin;

    /**
     * BedWarsCommand constructor.
     * @param BedWars $plugin
     */
    public function __construct(BedWars $plugin) {
        $this->plugin = $plugin;
        parent::__construct("bw", $plugin);
        $this->setDescription("BedWars Command");
        $this->setPermission("bw.cmd");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if(!$sender->hasPermission("bw.cmd")){
            $sender->sendMessage("§c{$this->plugin->getServer()->getLanguage()->translateString("%commands.generic.notFound")}");
            return;
        }
        $helpM = "
                §l§b===================================\n" .
            "§aBedWars 4vs4vs4vs4\n".
            "§7/bw help\n".
            "§7/bw create\n".
            "§7/bw remove\n".
            "§7/bw set\n".
            "§7/bw list\n§l§b===================================
                    ";
        if(!isset($args[0])) {
            $sender->sendMessage($helpM);


            
            return;
        }
        switch ($args[0]) {
            case "help":

                $sender->sendMessage($helpM);

                break;

            case "create":
            
                if(!$sender->hasPermission("bw.cmd.create")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bw create <arenaName>");
                    break;
                }
                if(isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] already exists!");
                    break;
                }
                $this->plugin->arenas[$args[1]] = new Game($this->plugin, []);
                $sender->sendMessage("§a> Arena $args[1] created!");
                break;
            case "remove":
                if(!$sender->hasPermission("bw.cmd.remove")) {
                    $sender->sendMessage("§cYou have not permissions to use this command!");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bwremove <arenaName>");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§c> Arena $args[1] was not found!");
                    break;
                }

                /** @var Game $arena */
                $arena = $this->plugin->arenas[$args[1]];

                foreach ($arena->players as $player) {
                    $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
                }

                if(is_file($file = $this->plugin->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $args[1] . ".yml")) unlink($file);
                unset($this->plugin->arenas[$args[1]]);

                $sender->sendMessage("§cArena removed!");
                break;
            case "set":
                if(!$sender->hasPermission("bw.cmd.set")) {
                  
                    break;
                }
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§cyou can't execute this command in console");
                    break;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage("§cUsage: §7/bw set <arenaName>");
                    break;
                }
                if(isset($this->plugin->setters[$sender->getName()])) {
                    $sender->sendMessage("§bYou are already in setup mode!");
                    break;
                }
                if(!isset($this->plugin->arenas[$args[1]])) {
                    $sender->sendMessage("§bArena $args[1] does not found!");
                    break;
                }
                $sender->sendMessage("§bYou've joined setup mode");
                if(!$sender->getServer()->isLevelLoaded($args[1])) {
                    $sender->getServer()->loadLevel($args[1]);
                }
                
                $sender->teleport($sender->getServer()->getLevelByName($args[1])->getSafeSpawn());
                $this->plugin->setters[$sender->getName()] = $this->plugin->arenas[$args[1]];
                break;
            case "random":
                if(!$sender instanceof Player) {
                    $sender->sendMessage("§cyou can't execute this command in console");
                    break;
                }
                $sender->sendMessage("§bYou've join to arena");
                $this->plugin->joinToRandomArena($sender);
                break;
            case "test":
                if(!$sender->hasPermission("bw.test")){
                    break;
                }
                for($i = 0; $i < 10; ++$i) $sender->getServer()->dispatchCommand($sender,"s add Vipz$i");
                $sender->sendMessage("§aSucessfuly Spawn 10 Bot");
            break;
            case "list":
                if(count($this->plugin->arenas) === 0) {
                    $sender->sendMessage("§a0 Arena");
                    break;
                }
                $list = "§eArenas\n";
                foreach ($this->plugin->arenas as $name => $arena) {
                    if($arena->setup) {
                        $list .= "§b$name  §cnot active\n";
                    }
                    else {
                        $list .= "§b$name : §aactived\n";
                    }
                }
                $sender->sendMessage($list);
                break;
        }



    }

    /**
     * @return BedWars|Plugin $plugin
     */
    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}
