<?php

namespace RolandDev\BedWars\math;


use pocketmine\block\BlockIds;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\item\ItemIds;
use RolandDev\BedWars\math\Vector3;
use RolandDev\BedWars\Game;

class Tower {


    public function __construct(Game $plugin)
    {
        $this->plugin = $plugin;
        
    }


    public function SpawnTower(Player $player){
        $direction = $player->getDirection();
        $team = $this->plugin->getTeam($player);
        $meta = [
	        "red" => 14,
	        "blue" => 11,
	        "yellow" => 4,
	        "green" => 5
	    ];
        $m = [
            0 => 4,
            1 => 2,
            2 => 5,
            3 => 3
        ];
		foreach(["red", "blue", "yellow", "green"] as $teams) {
			$pos = $this->plugin->data["bed"][$teams];
			$iyah = (new Vector3((int)$player->getX(), (int)$player->getY(), (int)$player->getZ()))->__toString();


				$pos1 = $player->getPosition()->add(0, 2, 0);
				$this->plugin->level->setBlock($pos1, Block::get(BlockIds::WOOL, $meta[$team]));
				$pos2 = $player->getPosition()->add(4, 0, 1);
				$player->getLevel()->setBlock($pos2, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos3 = $player->getPosition()->add(4, 0, -1);
				$player->getLevel()->setBlock($pos3, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos4 = $player->getPosition()->add(0, 0, 1);
				$player->getLevel()->setBlock($pos4, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos5 = $player->getPosition()->add(0, 0, -1);
				$player->getLevel()->setBlock($pos5, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos6 = $player->getPosition()->add(4, 0, 0);
				$player->getLevel()->setBlock($pos6, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos7 = $player->getPosition()->add(4, 1, 0);
				$player->getLevel()->setBlock($pos7, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos8 = $player->getPosition()->add(0, 2, 1);
				$player->getLevel()->setBlock($pos8, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos9 = $player->getPosition()->add(0, 2, -1);
				$player->getLevel()->setBlock($pos9, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos10 = $player->getPosition()->add(0, 3, 0);
				$player->getLevel()->setBlock($pos10, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos11 = $player->getPosition()->add(0, 1, 1);
				$player->getLevel()->setBlock($pos11, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos12 = $player->getPosition()->add(0, 1, -1);
				$player->getLevel()->setBlock($pos12, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos13 = $player->getPosition()->add(0, 3, 1);
				$player->getLevel()->setBlock($pos13, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos14 = $player->getPosition()->add(0, 3, -1);
				$player->getLevel()->setBlock($pos14, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos15 = $player->getPosition()->add(0, 4, 1);
				$player->getLevel()->setBlock($pos15, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos16 = $player->getPosition()->add(0, 4, -1);
				$player->getLevel()->setBlock($pos16, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos17 = $player->getPosition()->add(-1, 5, 1);
				$player->getLevel()->setBlock($pos17, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos18 = $player->getPosition()->add(-1, 4, 0);
				$player->getLevel()->setBlock($pos18, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos19 = $player->getPosition()->add(-1, 6, 0);
				$player->getLevel()->setBlock($pos19, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos20 = $player->getPosition()->add(-1, 5, 0);
				$player->getLevel()->setBlock($pos20, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos21 = $player->getPosition()->add(-1, 5, -1);
				$player->getLevel()->setBlock($pos21, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos22 = $player->getPosition()->add(0, 5, -2);
				$player->getLevel()->setBlock($pos22, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos23 = $player->getPosition()->add(0, 4, -2);
				$player->getLevel()->setBlock($pos23, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos24 = $player->getPosition()->add(0, 4, 2);
				$player->getLevel()->setBlock($pos24, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos25 = $player->getPosition()->add(0, 5, 2);
				$player->getLevel()->setBlock($pos25, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos26 = $player->getPosition()->add(1, 2, 2);
				$player->getLevel()->setBlock($pos26, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos27 = $player->getPosition()->add(1, 3, 2);
				$player->getLevel()->setBlock($pos27, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos28 = $player->getPosition()->add(1, 4, 2);
				$player->getLevel()->setBlock($pos28, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos29 = $player->getPosition()->add(1, 5, 3);
				$player->getLevel()->setBlock($pos29, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos30 = $player->getPosition()->add(0, 4, 3);
				$player->getLevel()->setBlock($pos30, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos31 = $player->getPosition()->add(4, 1, 1);
				$player->getLevel()->setBlock($pos31, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos32 = $player->getPosition()->add(4, 2, 0);
				$player->getLevel()->setBlock($pos32, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos33 = $player->getPosition()->add(4, 2, 1);
				$player->getLevel()->setBlock($pos33, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos34 = $player->getPosition()->add(4, 1, -1);
				$player->getLevel()->setBlock($pos34, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos35 = $player->getPosition()->add(4, 3, 1);
				$player->getLevel()->setBlock($pos35, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos36 = $player->getPosition()->add(4, 3, 0);
				$player->getLevel()->setBlock($pos36, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos37 = $player->getPosition()->add(4, 3, -1);
				$player->getLevel()->setBlock($pos37, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos38 = $player->getPosition()->add(4, 4, -1);
				$player->getLevel()->setBlock($pos38, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos39 = $player->getPosition()->add(4, 4, 1);
				$player->getLevel()->setBlock($pos39, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos40 = $player->getPosition()->add(4, 4, 0);
				$player->getLevel()->setBlock($pos40, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos41 = $player->getPosition()->add(4, 2, -1);
				$player->getLevel()->setBlock($pos41, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos42 = $player->getPosition()->add(5, 5, 0);
				$player->getLevel()->setBlock($pos42, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos43 = $player->getPosition()->add(-1, 5, 2);
				$player->getLevel()->setBlock($pos43, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos44 = $player->getPosition()->add(-1, 4, 2);
				$player->getLevel()->setBlock($pos44, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos45 = $player->getPosition()->add(-1, 6, 2);
				$player->getLevel()->setBlock($pos45, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos46 = $player->getPosition()->add(-1, 4, -2);
				$player->getLevel()->setBlock($pos46, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos47 = $player->getPosition()->add(-1, 5, -2);
				$player->getLevel()->setBlock($pos47, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos48 = $player->getPosition()->add(-1, 6, -2);
				$player->getLevel()->setBlock($pos48, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos49 = $player->getPosition()->add(0, 4, 0);
				$player->getLevel()->setBlock($pos49, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos50 = $player->getPosition()->add(5, 4, 0);
				$player->getLevel()->setBlock($pos50, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos51 = $player->getPosition()->add(5, 6, 0);
				$player->getLevel()->setBlock($pos51, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos52 = $player->getPosition()->add(5, 5, 1);
				$player->getLevel()->setBlock($pos52, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos53 = $player->getPosition()->add(5, 5, -1);
				$player->getLevel()->setBlock($pos53, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos54 = $player->getPosition()->add(5, 5, 2);
				$player->getLevel()->setBlock($pos54, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos55 = $player->getPosition()->add(5, 5, -2);
				$player->getLevel()->setBlock($pos55, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos56 = $player->getPosition()->add(5, 6, 2);
				$player->getLevel()->setBlock($pos56, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos57 = $player->getPosition()->add(5, 6, -2);
				$player->getLevel()->setBlock($pos57, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos58 = $player->getPosition()->add(5, 4, 2);
				$player->getLevel()->setBlock($pos58, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos59 = $player->getPosition()->add(5, 4, -2);
				$player->getLevel()->setBlock($pos59, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos60 = $player->getPosition()->add(4, 5, 2);
				$player->getLevel()->setBlock($pos60, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos61 = $player->getPosition()->add(4, 5, -2);
				$player->getLevel()->setBlock($pos61, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos62 = $player->getPosition()->add(4, 4, 2);
				$player->getLevel()->setBlock($pos62, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos63 = $player->getPosition()->add(4, 4, -2);
				$player->getLevel()->setBlock($pos63, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos64 = $player->getPosition()->add(1, 1, 2);
				$player->getLevel()->setBlock($pos64, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos65 = $player->getPosition()->add(2, 0, 2);
				$player->getLevel()->setBlock($pos65, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos66 = $player->getPosition()->add(2, 1, 2);
				$player->getLevel()->setBlock($pos66, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos67 = $player->getPosition()->add(2, 2, 2);
				$player->getLevel()->setBlock($pos67, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos68 = $player->getPosition()->add(2, 3, 2);
				$player->getLevel()->setBlock($pos68, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos69 = $player->getPosition()->add(2, 4, 2);
				$player->getLevel()->setBlock($pos69, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos70 = $player->getPosition()->add(0, 5, 3);
				$player->getLevel()->setBlock($pos70, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos71 = $player->getPosition()->add(2, 5, 3);
				$player->getLevel()->setBlock($pos71, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos72 = $player->getPosition()->add(0, 6, 3);
				$player->getLevel()->setBlock($pos72, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos73 = $player->getPosition()->add(2, 4, 3);
				$player->getLevel()->setBlock($pos73, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos74 = $player->getPosition()->add(2, 6, 3);
				$player->getLevel()->setBlock($pos74, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos75 = $player->getPosition()->add(3, 0, 2);
				$player->getLevel()->setBlock($pos75, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos76 = $player->getPosition()->add(3, 1, 2);
				$player->getLevel()->setBlock($pos76, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos77 = $player->getPosition()->add(3, 2, 2);
				$player->getLevel()->setBlock($pos77, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos78 = $player->getPosition()->add(3, 3, 2);
				$player->getLevel()->setBlock($pos78, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos79 = $player->getPosition()->add(3, 4, 2);
				$player->getLevel()->setBlock($pos79, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos80 = $player->getPosition()->add(4, 4, 3);
				$player->getLevel()->setBlock($pos80, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos81 = $player->getPosition()->add(3, 5, 3);
				$player->getLevel()->setBlock($pos81, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos82 = $player->getPosition()->add(4, 6, 3);
				$player->getLevel()->setBlock($pos82, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos83 = $player->getPosition()->add(1, 0, -2);
				$player->getLevel()->setBlock($pos83, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos84 = $player->getPosition()->add(1, 1, -2);
				$player->getLevel()->setBlock($pos84, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos85 = $player->getPosition()->add(1, 2, -2);
				$player->getLevel()->setBlock($pos85, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos86 = $player->getPosition()->add(1, 3, -2);
				$player->getLevel()->setBlock($pos86, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos87 = $player->getPosition()->add(1, 4, -2);
				$player->getLevel()->setBlock($pos87, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos88 = $player->getPosition()->add(0, 4, -3);
				$player->getLevel()->setBlock($pos88, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos89 = $player->getPosition()->add(0, 5, -3);
				$player->getLevel()->setBlock($pos89, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos90 = $player->getPosition()->add(0, 6, -3);
				$player->getLevel()->setBlock($pos90, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos91 = $player->getPosition()->add(2, 0, -2);
				$player->getLevel()->setBlock($pos91, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos92 = $player->getPosition()->add(2, 1, -2);
				$player->getLevel()->setBlock($pos92, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos93 = $player->getPosition()->add(2, 2, -2);
				$player->getLevel()->setBlock($pos93, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos94 = $player->getPosition()->add(2, 3, -2);
				$player->getLevel()->setBlock($pos94, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos95 = $player->getPosition()->add(2, 4, -2);
				$player->getLevel()->setBlock($pos95, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos96 = $player->getPosition()->add(0, 4, -3);
				$player->getLevel()->setBlock($pos96, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos97 = $player->getPosition()->add(1, 5, -3);
				$player->getLevel()->setBlock($pos97, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos98 = $player->getPosition()->add(0, 6, -3);
				$player->getLevel()->setBlock($pos98, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos99 = $player->getPosition()->add(3, 0, -2);
				$player->getLevel()->setBlock($pos99, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos100 = $player->getPosition()->add(3, 1, -2);
				$player->getLevel()->setBlock($pos100, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos101 = $player->getPosition()->add(3, 2, -2);
				$player->getLevel()->setBlock($pos101, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos102 = $player->getPosition()->add(3, 3, -2);
				$player->getLevel()->setBlock($pos102, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos103 = $player->getPosition()->add(3, 4, -2);
				$player->getLevel()->setBlock($pos103, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos104 = $player->getPosition()->add(2, 4, -3);
				$player->getLevel()->setBlock($pos104, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos105 = $player->getPosition()->add(2, 5, -3);
				$player->getLevel()->setBlock($pos105, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos106 = $player->getPosition()->add(2, 6, -3);
				$player->getLevel()->setBlock($pos106, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos107 = $player->getPosition()->add(3, 5, -3);
				$player->getLevel()->setBlock($pos107, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos108 = $player->getPosition()->add(4, 6, -3);
				$player->getLevel()->setBlock($pos108, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos109 = $player->getPosition()->add(4, 4, -3);
				$player->getLevel()->setBlock($pos109, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos110 = $player->getPosition()->add(4, 4, -3);
				$player->getLevel()->setBlock($pos110, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos111 = $player->getPosition()->add(1, 4, -1);
				$player->getLevel()->setBlock($pos111, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos112 = $player->getPosition()->add(1, 4, 0);
				$player->getLevel()->setBlock($pos112, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos113 = $player->getPosition()->add(1, 4, 1);
				$player->getLevel()->setBlock($pos113, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos114 = $player->getPosition()->add(2, 4, -1);
				$player->getLevel()->setBlock($pos114, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos115 = $player->getPosition()->add(2, 4, 0);
				$player->getLevel()->setBlock($pos115, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos116 = $player->getPosition()->add(2, 4, 1);
				$player->getLevel()->setBlock($pos116, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos117 = $player->getPosition()->add(3, 4, -1);
				$player->getLevel()->setBlock($pos117, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos118 = $player->getPosition()->add(3, 4, 1);
				$player->getLevel()->setBlock($pos118, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos123 = $player->getPosition()->add(1, 0, 2);
				$player->getLevel()->setBlock($pos123, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$pos124 = $player->getPosition()->add(4, 5, 3);
				$player->getLevel()->setBlock($pos124, Block::get(BlockIds::WOOL, $meta[$team]),false,true);
				$player->getInventory()->removeItem(Item::get(ItemIds::CHEST));



				$pos119 = $player->getPosition()->add(3, 3, 0);
				$player->getLevel()->setBlock($pos119, Block::get(BlockIds::WOOL),false,true);
				$pos120 = $player->getPosition()->add(3, 2, 0);
				$player->getLevel()->setBlock($pos120, Block::get(BlockIds::WOOL),false,true);
				$pos121 = $player->getPosition()->add(3, 1, 0);
				$player->getLevel()->setBlock($pos121, Block::get(BlockIds::WOOL),false,true);
				$pos122 = $player->getPosition()->add(3, 0, 0);
				$player->getLevel()->setBlock($pos122, Block::get(BlockIds::WOOL),false,true);





		}
    }
}