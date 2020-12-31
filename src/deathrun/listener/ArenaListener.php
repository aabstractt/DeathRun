<?php

declare(strict_types=1);

namespace deathrun\listener;

use deathrun\arena\Level;
use deathrun\player\Player;
use pocketmine\block\Block;
use pocketmine\block\Water;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\Player as pocketPlayer;
use pocketmine\plugin\PluginException;

abstract class ArenaListener extends \gameapi\listener\ArenaListener {

    /**
     * @param pocketPlayer $player
     * @param Location $from
     * @param Location $to
     * @throws PluginException
     */
    public function handlePlayerMove(pocketPlayer $player, Location $from, Location $to): void {
        if (!$this->isStarted()) return;

        /** @var Player|null $player */
        $player = $this->getPlayer($player->getName());

        if ($player == null) return;

        $instance = $player->getGeneralPlayer();

        if (!$player->isRunner()) return;

        $x = $instance->getFloorX();

        $z = $instance->getFloorZ();

        $block = $instance->getLevelNonNull()->getBlock($instance->asPosition());

        if ($block instanceof Water) $player->sendMessage($block->getId() . ' > ' . $block->getName());

        if ($block instanceof Water) {
            $player->executeTeleport();

            return;
        }

        if ($block->getId() == Block::REDSTONE_BLOCK) {
            $instance->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 20*5, 4, false));

            return;
        }

        if ($block->getId() == Block::EMERALD_BLOCK) {
            $instance->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 20*5, 3));

            return;
        }

        if ($player->getStep() == 0) return;

        /** @var Level $level */
        $level = $this->getLevel();

        if (!$level->isCheckpoint($player->getStep())) {
            $player->finishPlayer();

            return;
        }

        $pos1 = $level->getCheckpointPosition($player->getStep(), 1)->get();
        $pos2 = $level->getCheckpointPosition($player->getStep(), 2)->get();

        if (((min($pos1->getFloorX(), $pos2->getFloorX()) <= $x) && (max($pos1->getFloorX(), $pos2->getFloorX()) >= $x)) && ((min($pos1->getFloorZ(), $pos2->getFloorZ()) <= $z) && (max($pos1->getFloorZ(), $pos2->getFloorZ()) >= $z))) {
            $player->increase();
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

    /**
     * @param pocketPlayer $player
     * @param int $cause
     * @param float $finalDamage
     * @return bool
     */
    public function handleEntityDamage(pocketPlayer $player, int $cause, float $finalDamage): bool {
        return true;
    }

    /**
     * @param pocketPlayer $player
     * @param pocketPlayer|null $target
     * @param float $finalDamage
     * @return bool
     */
    public function handleEntityDamageByPlayer(pocketPlayer $player, ?pocketPlayer $target, float $finalDamage): bool {
        return true;
    }
}