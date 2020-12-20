<?php

declare(strict_types=1);

namespace deathrun\listener;

use deathrun\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class PlayerQuitListener implements Listener {

    /**
     * @param PlayerQuitEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        Utils::remove($player->getName());
    }
}