<?php

declare(strict_types=1);

namespace deathrun\arena\task;

use deathrun\arena\Arena;
use gameapi\arena\task\GameUpdateTask;

class GameMatchUpdateTask extends GameUpdateTask {

    /** @var int */
    protected $timePassed = 0;
    /** @var int */
    protected $cooldown = 60;

    /**
     * Action executed when the task run
     */
    public function run(): void {
        /** @var Arena $arena */
        $arena = $this->arena;

        if ($arena == null) {
            return;
        }

        if (!$arena->isStarted()) {
            $this->cancel();

            return;
        }

        if (count($arena->getPlayers()) <= 1) {
            $this->cancel();

            $arena->finish($arena->getSpectators());

            return;
        }

        if ($arena->getPlayersFinished() >= 3) {
            if (in_array($this->cooldown, [30, 15]) || $this->cooldown <= 5) {
                $arena->broadcastMessage('&cQuedan ' . $this->cooldown . ' para que acabe la partida');
            }

            if ($this->cooldown == 0) {
                $this->cancel();

                $arena->finish($arena->getSpectators());

                return;
            }

            $this->cooldown--;
        }

        $this->handleUpdateScoreboard();

        $this->timePassed++;
    }

    public function handleUpdateScoreboard(): void {
        /** @var Arena $arena */
        $arena = $this->arena;

        if ($arena == null) return;

        foreach ($arena->getPlayers() as $player) {
            if (!$player->isConnected()) continue;

            if (!$player->isRunner()) continue;

            $player->sendTip('&bRunner &7&l> &r&60 Deaths &7> &a' . ($player->getStep() - 1) . '/&2' . count($arena->getLevel()->getCheckpoints()) . ' &aCheckpoints');
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