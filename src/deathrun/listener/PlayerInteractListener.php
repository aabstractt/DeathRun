<?php

namespace deathrun\listener;

use deathrun\Utils;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $ev
     *
     * @priority MONITOR
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();

        Utils::handleTrapStep($player, $ev->getTouchVector());
    }
}