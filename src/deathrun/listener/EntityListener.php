<?php

declare(strict_types=1);

namespace deathrun\listener;

use gameapi\Game;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\Player;

class EntityListener implements Listener {

    /**
     * @param EntityDamageEvent $ev
     *
     * @priority NORMAL
     */
    public function onEntityDamageEvent(EntityDamageEvent $ev): void {
        $entity = $ev->getEntity();

        if (!$entity instanceof Player) return;

        $arena = Game::getArenaFactory()->getArena($entity);

        if ($arena == null) return;

        $player = $arena->getPlayerOrSpectator($entity->getName());

        if ($player == null) return;

        if (!$arena instanceof ArenaListener) return;

        if ($ev->getCause() != EntityDamageEvent::CAUSE_PROJECTILE) return;

        if (!$ev instanceof EntityDamageByEntityEvent) return;

        $target = $ev->getDamager();

        if (!$target instanceof Projectile) return;

        $arena->handleEntityDamageByProjectile($entity, $target);

        $ev->setCancelled();
    }
}