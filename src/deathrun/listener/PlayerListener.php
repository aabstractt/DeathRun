<?php

declare(strict_types=1);

namespace deathrun\listener;

use deathrun\arena\Arena;
use deathrun\DeathRun;
use deathrun\player\Player;
use Exception;
use gameapi\Game;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\nbt\tag\StringTag;
use pocketmine\scheduler\ClosureTask;

class PlayerListener implements Listener {

    /**
     * @param PlayerInteractEvent $ev
     *
     * @priority MONITOR
     * @throws Exception
     * @noinspection PhpUnusedParameterInspection
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();

        /** @var Arena $arena */
        $arena = Game::getArenaFactory()->getArena($player);

        if ($arena == null) return;

        /** @var Player|null $player */
        $player = $arena->getPlayer($player->getName());

        if ($player == null) return;

        $item = $ev->getItem();

        $nbt = $item->getCustomBlockData();

        if ($nbt == null) return;

        if (!$nbt->hasTag('Name', StringTag::class)) return;

        $name = $nbt->getString('Name');

        if ($name == null) return;

        if ($name == 'Leap') {
            if ($player->hasLeapCountDown()) return;

            $direction = $player->getGeneralPlayer()->getDirectionVector();

            $player->getGeneralPlayer()->knockBack($player->getGeneralPlayer(), 0, $direction->getFloorX(), $direction->getFloorZ(), 1);

            $player->setLeapCountDown(true);

            DeathRun::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use($player): void {
                $player->setLeapCountDown();
            }), 20 * 30);
            return;
        }

        if ($player->isRunner()) return;

        if ($name == 'Last' || $name == 'Next') {
            $name == 'Last' ? $player->decrease() : $player->increase();

            $player->executeTeleport();

            return;
        }

        $step = $player->getStep();

        if ($player->hasTrapCountDown($step)) return;

        $arena->handleActivateTrap($step);

        $player->setTrapCountDown($step);

        DeathRun::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use($player, $step): void {
            $player->removeTrapCountDown($step);
        }), 20 * 30);
    }
}