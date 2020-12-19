<?php

declare(strict_types=1);

namespace deathrun\arena;

class Arena extends \gameapi\arena\Arena {

    /**
     * @return int
     */
    public function getFreeSlot(): int {
        return 1;
    }

    public function startGame(): void {
        // TODO: Implement startGame() method.
    }
}