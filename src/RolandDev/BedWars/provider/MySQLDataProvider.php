<?php

namespace RolandDev\BedWars\provider;


use pocketmine\Player;
use RolandDev\BedWars\BedWars;

class MySQLDataProvider{
	public $query;

	public function __construct(BedWars $plugin)
	{
		$this->plugin = $plugin;
		$config = $this->plugin->getConfig()->get("mysql");
		$this->query = new \mysqli($config["ip"],$config["user"],$config["password"],$config["database"]);
		$this->query->query("CREATE TABLE IF NOT EXISTS bedwars_4 (
    name VARCHAR(255) PRIMARY KEY, bedbroken TEXT NOT NULL, playtime TEXT NOT NULL, killplayer TEXT NOT NULL, finalkill TEXT NOT NULL, victory TEXT NOT NULL, quickbuy TEXT NOT NULL
    );");

	}

	public function registerAccount(Player  $player){
	    $name = $player->getName();
		$this->query->query("INSERT INTO bedwars_4 (name, bedbroken,playtime,killplayer,finalkill,victory,quickbuy )VALUES('$name', '0','0','0','0','0','')");
	}

	public function addscore(Player $player,$type){
	    $name = $player->getName();
		if($type == "kill"){
			$this->query->query("UPDATE bedwars_4 SET killplayer = killplayer + 1 WHERE name = '$name'");
		}
		if($type == "fk"){
			$this->query->query("UPDATE bedwars_4 SET finalkill = finalkill + 1 WHERE name = '$name'");
		}
		if($type == "playtime"){
			$this->query->query("UPDATE bedwars_4 SET playtime = playtime + 1 WHERE name = '$name'");
		}
		if($type == "victory"){
		   $this->query->query("UPDATE bedwars_4 SET victory = victory + 1 WHERE name = '$name'");
		}
		if($type == "bedbroken"){
			$this->query->query("UPDATE bedwars_4 SET bedbroken = bedbroken + 1 WHERE name = '$name'");
		}
	}

	public function getAccount(Player $player){
	    $name = $player->getName();
	   $result = $this->query->query("SELECT * FROM bedwars_4 WHERE name = '$name'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
           if(isset($data["name"])){
            unset($data["name"]);
            return $data;
           }
       }
       return null;
	}


}
