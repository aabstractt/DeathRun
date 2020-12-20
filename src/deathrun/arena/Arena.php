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