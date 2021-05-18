<?php

/**
 * Copyright 2018-2020 GamakCZ
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace RolandDev\BedWars\provider;

use pocketmine\level\Level;
use pocketmine\utils\Config;
use RolandDev\BedWars\Game;
use RolandDev\BedWars\BedWars;

/**
 * Class YamlDataProvider
 * @package skywars\provider
 */
class YamlDataProvider {

    /** @var SkyWars $plugin */
    private $plugin;

    /**
     * YamlDataProvider constructor.
     * @param SkyWars $plugin
     */
    public function __construct(BedWars $plugin) {
        $this->plugin = $plugin;
        $this->init();
        $this->loadArenas();
    }

    public function init() {
        if(!is_dir($this->getDataFolder())) {
            @mkdir($this->getDataFolder());
        }
        if(!is_dir($this->getDataFolder() . "arenas")) {
            @mkdir($this->getDataFolder() . "arenas");
        }
        if(!is_dir($this->getDataFolder() . "saves")) {
            @mkdir($this->getDataFolder() . "saves");
        }
    }

    public function loadArenas() {
        foreach (glob($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . "*.yml") as $arenaFile) {
            $config = new Config($arenaFile, Config::YAML);
            $iyah = $config->getAll();  
            foreach ($iyah as $key => $value) {
                if(is_string($value) && substr($value, 0, $length = strlen("serialized=")) == "serialized=") {
                    $iyah[$key] = unserialize(substr($value, $length));
                }
            }
            $this->plugin->arenas[basename($arenaFile, ".yml")] = new Game($this->plugin, $iyah);
        }
    }

    public function saveArenas() {
        foreach ($this->plugin->arenas as $fileName => $arena) {
            if($arena->level instanceof Level) {
                foreach ($arena->players as $player) {
                    $player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
                }
                $arena->draw();
                // must be reseted
                $arena->mapReset->loadMap($arena->level->getFolderName(), true);
            }
            $config = new Config($this->getDataFolder() . "arenas" . DIRECTORY_SEPARATOR . $fileName . ".yml", Config::YAML);
            $config->setAll($arena->data);
            $config->save(false);
        }
    }

    /**
     * @return string $dataFolder
     */
    private function getDataFolder(): string {
        return $this->plugin->getDataFolder();
    }
}
