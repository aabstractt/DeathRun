<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\player\Player;

class Arena extends \gameapi\arena\Arena {

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
        }

        parent::start($started);
    }

    public function startGame(): void {
        // TODO: Implement startGame() method.
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