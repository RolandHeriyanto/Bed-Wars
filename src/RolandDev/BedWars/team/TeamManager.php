<?php

namespace RolandDev\BedWars\team;

use RolandDev\BedWars\Game;
class TeamManager {

    public $redteam = [];
    public $blueteam = [];
    public $yellowteam = [];
    public $greenteam = [];
    public $teamsharpness = [];
    public $teamprotection = [];

    public function __construct(Game $plugin)
    {
        $this->plugin = $plugin;
        
    }

	/**
	 * @return array
	 */
	public function getBlueteam(): array
	{
		return $this->blueteam;
	}

	/**
	 * @return array
	 */
	public function getRedteam(): array
	{
		return $this->redteam;
	}

	/**
	 * @return array
	 */
	public function getYellowteam(): array
	{
		return $this->yellowteam;
	}

	/**
	 * @return array
	 */
	public function getGreenteam(): array
	{
		return $this->greenteam;
	}
}