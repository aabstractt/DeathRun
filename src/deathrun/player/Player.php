<?php

declare(strict_types=1);

namespace deathrun\player;

use deathrun\arena\Arena as CustomArena;
use Exception;
use gameapi\arena\Arena;
use gameapi\player\Player as mainPlayer;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Player extends mainPlayer {

    /** @var bool */
    protected $runner = true;
    /** @var int */
    private $currentTrap = 1;
    /** @var int */
    private $currentCheckpoint = 0;
    /** @var bool */
    private $cooldownTrap = false;

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
    public function sendPopup(string $message): void {
        $instance = $this->getGeneralPlayer();

        if ($instance == null) return;

        $instance->sendPopup(TextFormat::colorize($message));
    }

    public function increase(): void {
        if ($this->isRunner()) {
            $this->currentCheckpoint++;

            return;
        }

        if (!$this->getArena()->getLevel()->isTrap($this->getStep())) return;

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
        return $this->isRunner() ? $this->currentCheckpoint : $this->currentTrap;
    }

    /**
     * @return bool
     */
    public function hasCoolDownTrap(): bool {
        return $this->cooldownTrap;
    }

    /**
     * @param bool $value
     */
    public function setCoolDownTrap(bool $value = false): void {
        $this->cooldownTrap = $value;
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

    private function finishPlayer(): void {
        $this->remove();

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

        $instance = $this->getGeneralPlayer();

        if ($this->isRunner()) {
            $instance->getInventory()->setItem(0, (Item::get(Item::FEATHER))->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Leap'));
        } else {
            $item = Item::get(Item::STICK);

            $item->setCustomName(TextFormat::colorize('&r&cLast Trap'));
            $item->setCustomBlockData(new CompoundTag("", [new StringTag('Name', 'Last')]));

            $instance->getInventory()->setItem(0, $item);

            $item = Item::get(Item::FEATHER);

            $item->setCustomName(TextFormat::colorize('&r&aNext Trap'));
            $item->setCustomBlockData(new CompoundTag('', [new StringTag('Name', 'Next')]));

            $instance->getInventory()->setItem(1, $item);

            $item = Item::get(Item::SLIME_BALL);

            $item->setCustomName(TextFormat::colorize('&r&aActivate Trap'));
            $item->setCustomBlockData(new CompoundTag('', [new StringTag('Name', 'Activate')]));

            for ($i = 2; $i < 7; $i++) $instance->getInventory()->setItem($i, $item);
        }
    }
}