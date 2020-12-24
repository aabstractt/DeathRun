<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\arena\task\GameMatchUpdateTask;
use deathrun\player\Player;
use deathrun\utils\Trap;
use Exception;
use gameapi\arena\Level;
use gameapi\arena\task\GameCountDownUpdateTask;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Arena extends \gameapi\arena\Arena {

    /** @var array<int, Trap> */
    private $traps = [];

    public function bootGame(): void {
        $this->scheduleRepeatingTask(new GameCountDownUpdateTask('game_count_down_update', $this, 5, 10, 15));
    }

    /**
     * @return \deathrun\arena\Level
     */
    public function getLevel(): Level {
        /** @var \deathrun\arena\Level $level */
        $level = parent::getLevel();

        return $level;
    }


    /**
     * @return array<string, Player>
     */
    public function getPlayers(): array {
        /** @var array<string, Player> $players */
        $players = parent::getPlayers();

        return $players;
    }

    /**
     * @return int
     */
    public function getFreeSlot(): int {
        return 1;
    }

    /**
     * @param bool $started
     */
    public function start(bool $started = true): void {
        if (!$started) {
            /** @var Player|null $player */
            $player = $this->getPlayer((string) array_rand($this->getPlayers()));

            if ($player !== null) {
                $player->setRunning();

                $player->setImmobile();
            }

            foreach ($this->getPlayers() as $player) {
                if (!$player->isRunner()) continue;

                $instance = $player->getGeneralPlayer();

                if ($instance == null) continue;

                $instance->getInventory()->clearAll();

                $instance->getInventory()->setItem(0, (Item::get(Item::FEATHER))->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Leap'));
            }
        }

        parent::start($started);
    }

    public function startGame(): void {
        foreach ($this->getAllPlayers() as $player) $player->setImmobile(false);

        $world = $this->getWorld();

        if ($world != null) $this->traps = $this->getLevel()->loadTraps($world);

        $this->scheduleRepeatingTask(new GameMatchUpdateTask('game_match_update', $this));
    }

    /**
     * @return Player|null
     */
    public function getTrapper(): ?Player {
        foreach ($this->getPlayers() as $player) {
            if ($player->isRunner()) continue;

            return $player;
        }

        return null;
    }

    /**
     * @param int $trapSlot
     * @throws Exception
     */
    public function handleActivateTrap(int $trapSlot): void {
        /** @var array<int, Trap> $traps */
        $traps = [];

        $intents = 0;

        while(count($traps) < 2 && $intents < 3) {
            foreach ($this->traps as $trap) {
                if ($trap->getSlot() != $trapSlot) continue;

                if (isset($traps[$trap->getStep()])) continue;

                $traps[$trap->getStep()] = $trap;
            }

            $intents++;
        }

        if (count($traps) < 2) {
            throw new Exception('Trap ' . $trapSlot . ' is not valid');
        }

        $type = null;

        foreach ($traps as $trap) {
            $type = $trap->selectType($traps);
        }

        if ($type == null) {
            throw new Exception('Trap ' . $trapSlot . ' is not valid');
        }

        $level = $this->getLevel();

        $world = $this->getWorld();

        if ($world == null) return;

        if ($type == 'BREAK') {
            $level->handleUpdateBlocks($world, $traps[1]->asVector3(), $traps[2]->asVector3());

            return;
        }

        if ($type == 'PLACE') {
            $level->handleUpdateBlocks($world, $traps[1]->asVector3(), $traps[2]->asVector3(), Block::STAINED_GLASS, 14, true);

            return;
        }
    }
}