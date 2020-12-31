<?php

declare(strict_types=1);

namespace deathrun;

use deathrun\arena\Arena as CustomArena;
use deathrun\arena\Level as CustomLevel;
use deathrun\listener\EntityListener;
use deathrun\listener\PlayerListener;
use deathrun\player\Player as CustomPlayer;
use gameapi\arena\Arena;
use gameapi\arena\Level;
use gameapi\Game;
use gameapi\player\Player;
use gameapi\provider\TargetOffline;

class DeathRun extends Game {

    public function registerClasses(): void {
        self::$isInDevelopmentMode = true;

        $this->registerListener(
            new PlayerListener(),
            new EntityListener());

        $this->getServer()->getCommandMap()->register(DeathRunCommand::class, new DeathRunCommand());
    }

    /**
     * @param int $id
     * @param Level $level
     * @return Arena
     */
    public function generateNewArena(int $id, Level $level): Arena {
        return new CustomArena($id, $level);
    }

    /**
     * @param array $data
     * @return CustomLevel
     */
    public function generateNewLevel(array $data): Level {
        return new CustomLevel($data);
    }

    /**
     * @param string $name
     * @param Arena $arena
     * @return Player
     */
    public function generateNewPlayer(string $name, Arena $arena): Player {
        return new CustomPlayer($name, $arena);
    }

    /**
     * @param array $data
     * @return TargetOffline
     */
    public function generateNewTargetOffline(array $data): TargetOffline {
        return new TargetOffline($data);
    }
}