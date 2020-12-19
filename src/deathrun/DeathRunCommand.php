<?php

declare(strict_types=1);

namespace deathrun;

use gameapi\Command;
use pocketmine\Player as pocketPlayer;

class DeathRunCommand extends Command {

    /**
     * @param pocketPlayer $player
     * @param array $args
     */
    protected function run(pocketPlayer $player, array $args): void {
    }
}