<?php

declare(strict_types=1);

namespace deathrun\player;

use deathrun\arena\Arena as CustomArena;
use gameapi\arena\Arena;
use gameapi\player\Player as mainPlayer;

class Player extends mainPlayer {

    /** @var bool */
    protected $runner = true;

    /**
     * @return CustomArena
     */
    public function getArena(): Arena {
        /** @var CustomArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    /**
     * @return bool
     */
    public function isRunner(): bool {
        return $this->runner;
    }

    /**
     * @param bool $running
     */
    public function setRunning(bool $running = false): void {
        $this->runner = $running;
    }

    /**
     * @param bool $teleport
     */
    public function setDefaultPlayerAttributes(bool $teleport = false): void {
        if ($this->getArena()->getTrapper() == null) {
            $this->setRunning();
        }

        parent::setDefaultPlayerAttributes($teleport);
    }
}