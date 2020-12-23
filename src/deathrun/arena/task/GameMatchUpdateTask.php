<?php

declare(strict_types=1);

namespace deathrun\arena\task;

use deathrun\arena\Arena;
use gameapi\arena\task\GameUpdateTask;

class GameMatchUpdateTask extends GameUpdateTask {

    /** @var int */
    protected $timePassed = 0;

    /**
     * Action executed when the task run
     */
    public function run(): void {
        $arena = $this->arena;

        if ($arena == null) {
            return;
        }

        if (!$arena->isStarted()) {
            $this->cancel();

            return;
        }

        $players = $arena->getPlayers();

        /*if (count($arena->getAllPlayers()) <= 1) {
            $this->cancel();

            $arena->finish($players);

            return;
        }*/

        $this->handleUpdateScoreboard();

        $this->timePassed++;
    }

    public function handleUpdateScoreboard(): void {
        /** @var Arena $arena */
        $arena = $this->arena;

        if ($arena == null) return;

        foreach ($arena->getPlayers() as $player) {
            if (!$player->isConnected()) continue;

            $player->sendPopup('&bRunner &7&l> &r&60 Deaths &7> &a0/&2' . count($arena->getLevel()->getCheckpoints()) . ' &aCheckpoints');
        }
    }

    /**
     * @return bool
     */
    public function beforeRun(): bool {
        $parent = parent::beforeRun();

        if (!$parent) return false;

        if (!$this->arena->isStarted()) {
            return false;
        }

        return true;
    }
}