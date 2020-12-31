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

        if ($player->isRunner()) return;

        $item = $ev->getItem();

        $nbt = $item->getCustomBlockData();

        if ($nbt == null) return;

        if (!$nbt->hasTag('Name', StringTag::class)) return;

        $name = $nbt->getString('Name');

        if ($name == null) return;

        if ($name == 'Last' || $name == 'Next') {
            $name == 'Last' ? $player->decrease() : $player->increase();

            $player->executeTeleport();

            return;
        }

        if ($player->hasCoolDownTrap()) return;

        $arena->handleActivateTrap($player->getStep());

        $player->setCoolDownTrap(true);

        DeathRun::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use($player): void {
            $player->setCoolDownTrap();
        }), 20 * 5);
    }
}