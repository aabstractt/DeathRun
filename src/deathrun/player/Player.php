<?php

declare(strict_types=1);

namespace deathrun\player;

use deathrun\arena\Arena as CustomArena;
use Exception;
use gameapi\arena\Arena;
use gameapi\player\Player as mainPlayer;
use pocketmine\utils\TextFormat;

class Player extends mainPlayer {

    /** @var bool */
    protected $runner = true;
    /** @var int */
    private $currentTrap = 1;
    /** @var int */
    private $currentCheckpoint = 0;
    /** @var bool */
    private $leapCountDown = false;
    /** @var bool */
    private $trapCountDown = false;

    /**
     * @return CustomArena
     */
    public function getArena(): Arena {
        /** @var CustomArena $arena */
        $arena = parent::getArena();

        return $arena;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->isRunner() ? 1 : 2;
    }

    /**
     * @param string $message
     */
    public function sendTip(string $message): void {
        $instance = $this->getGeneralPlayer();

        if ($instance == null) return;

        $instance->sendTip(TextFormat::colorize($message));
    }

    public function increase(): void {
        if ($this->isRunner()) {
            $this->currentCheckpoint++;

            if ($this->getArena()->getLevel()->isCheckpoint($this->currentCheckpoint)) return;

            $this->finishPlayer();

            return;
        }

        if (!$this->getArena()->getLevel()->isTrap($this->getStep() + 1)) return;

        $this->currentTrap++;
    }

    public function decrease(): void {
        if ($this->isRunner()) return;

        if (!$this->getArena()->getLevel()->isTrap($this->getStep() - 1)) return;

        $this->currentTrap--;
    }

    /**
     * @return int
     */
    public function getStep(): int {
        return $this->isRunner() ? $this->currentCheckpoint + 1 : $this->currentTrap;
    }

    public function hasLeapCountDown(): bool {
        return $this->leapCountDown;
    }

    /**
     * @param bool $value
     */
    public function setLeapCountDown(bool $value = false): void {
        $this->leapCountDown = $value;
    }

    /**
     * @return bool
     */
    public function hasTrapCountDown(): bool {
        return $this->trapCountDown;
    }

    /**
     * @param bool $value
     */
    public function setTrapCountDown(bool $value = false): void {
        $this->trapCountDown = $value;
    }

    /**
     * Execute the teleport when you are in a game started
     */
    public function executeTeleport(): void {
        $level = $this->getArena()->getLevel();

        try {
            if ($this->isRunner()) {
                $pos = $this->currentCheckpoint > 0 ? $level->getCheckpointPosition($this->currentCheckpoint) : $level->getSlotPosition(1, $this->getArena()->getWorldNonNull());
            } else {
                if (!$level->isTrap($this->currentTrap)) return;

                $pos = $level->getTrapPosition($this->currentTrap, $this->getArena()->getWorldNonNull());
            }

            if ($pos == null) {
                $this->finishPlayer();

                return;
            }

            $this->teleport($pos);
        } catch (Exception $e) {
            $this->getGeneralPlayer()->kick($e->getMessage());
        }
    }

    public function finishPlayer(): void {
        $this->remove();

        $arena = $this->getArena();

        $arena->increasePlayersFinished();

        $this->getGeneralPlayer()->sendTitle('&a¡Felicidades!', '&aHas quedado en el puesto numero ' . $arena->getPlayersFinished());

        $arena->broadcastMessage('&a' . $this->getName() . ' a quedado en el ' . $arena->getPlayersFinished() . ' lugar');

        $this->getGeneralPlayer()->setGamemode(3);
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
     * @param bool $findSlot
     */
    public function setDefaultPlayerAttributes(bool $teleport = false, bool $findSlot = false): void {
        parent::setDefaultPlayerAttributes($teleport, $findSlot);
    }

    public function setMatchPlayerAttributes(): void {
        parent::setMatchPlayerAttributes();

        $this->setDefaultPlayerAttributes();
    }
}