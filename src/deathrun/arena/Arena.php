<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\arena\task\GameMatchUpdateTask;
use deathrun\player\Player;
use gameapi\arena\Level;
use gameapi\arena\task\GameCountDownUpdateTask;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Arena extends \gameapi\arena\Arena {

    public function bootGame(): void {
        $this->scheduleRepeatingTask(new GameCountDownUpdateTask('game_count_down_update', $this, 5, 10, 15));
    }

    /**
     * @return \deathrun\arena\Level
     */
    public function getLevel(): Level {
        /** @var \deathrun\arena\Level $level */
        $level = parent::getLevel();

        return $level;
    }


    /**
     * @return Player[]
     */
    public function getPlayers(): array {
        /** @var Player[] $players */
        $players = parent::getPlayers();

        return $players;
    }

    /**
     * @return int
     */
    public function getFreeSlot(): int {
        return 1;
    }

    /**
     * @param bool $started
     */
    public function start(bool $started = true): void {
        if (!$started) {
            /** @var Player|null $player */
            $player = $this->getPlayer(array_rand($this->getPlayers()));

            if ($player != null) {
                $player->setRunning();

                $player->setImmobile();
            }

            foreach ($this->getPlayers() as $player) {
                if (!$player->isRunner()) continue;

                $instance = $player->getGeneralPlayer();

                if ($instance == null) continue;

                $instance->getInventory()->clearAll();

                $instance->getInventory()->setItem(0, (Item::get(Item::FEATHER))->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Leap'));
            }
        }

        parent::start($started);
    }

    public function startGame(): void {
        $this->getTrapper()->setImmobile(false);

        $this->scheduleRepeatingTask(new GameMatchUpdateTask('game_match_update', $this));
    }

    /**
     * @return Player|null
     */
    public function getTrapper(): ?Player {
        foreach ($this->getPlayers() as $player) {
            if ($player->isRunner()) continue;

            return $player;
        }

        return null;
    }
}