<?php

declare(strict_types=1);

namespace deathrun\listener;

use deathrun\arena\Level;
use deathrun\player\Player;
use pocketmine\block\Block;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\Player as pocketPlayer;

abstract class ArenaListener extends \gameapi\listener\ArenaListener {

    /**
     * @param pocketPlayer $player
     * @param Location $from
     * @param Location $to
     * @throws \Exception
     */
    public function handlePlayerMove(pocketPlayer $player, Location $from, Location $to): void {
        if (!$this->isStarted()) return;

        /** @var Player|null $player */
        $player = $this->getPlayer($player->getName());

        if ($player == null) return;

        if (!$player->isRunner()) return;

        $instance = $player->getGeneralPlayer();

        if ($player->getStep() == 0) return;

        /** @var Level $level */
        $level = $this->getLevel();

        $pos1 = $level->getCheckpointPosition($player->getStep(), 1)->get();
        $pos2 = $level->getCheckpointPosition($player->getStep(), 2)->get();

        $x = $instance->getFloorX();

        $z = $instance->getFloorZ();

        if (((min($pos1->getFloorX(), $pos2->getFloorX()) <= $x) && (max($pos1->getFloorX(), $pos2->getFloorX()) >= $x)) && ((min($pos1->getFloorZ(), $pos2->getFloorZ()) <= $z) && (max($pos1->getFloorZ(), $pos2->getFloorZ()) >= $z))) {
            $player->increase();

            $player->executeTeleport();
        }
    }

    /**
     * @param pocketPlayer $player
     * @param Item $item
     * @return bool
     */
    public function handlePlayerDropItem(pocketPlayer $player, Item $item): bool {
        return true;
    }

    /**
     * @param pocketPlayer $player
     * @param InventoryTransaction $transaction
     * @return bool
     */
    public function handleInventoryTransaction(pocketPlayer $player, InventoryTransaction $transaction): bool {
        return true;
    }

    /**
     * @param pocketPlayer $player
     * @param Block $block
     * @return bool
     */
    public function handleBlockPlace(pocketPlayer $player, Block $block): bool {
        return true;
    }

    /**
     * @param pocketPlayer $player
     * @param Block $block
     * @return bool
     */
    public function handleBlockBreak(pocketPlayer $player, Block $block): bool {
        return true;
    }
}