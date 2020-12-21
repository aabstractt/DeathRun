<?php

declare(strict_types=1);

namespace deathrun\listener;

use deathrun\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $ev
     *
     * @priority MONITOR
     */
    public function onBlockBreakEvent(BlockBreakEvent $ev): void {
        $player = $ev->getPlayer();

        Utils::handleTrapStep($player, $ev->getBlock()->asPosition());
    }
}