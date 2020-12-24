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
use pocketmine\math\Vector3;
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

        $pos1 = $traps[1]->asVector3();
        $pos2 = $traps[2]->asVector3();

        if ($type == 'BREAK') {
            $level->handleUpdateBlocks($world, $pos1, $pos2);

            return;
        }

        if ($type == 'PLACE') {
            $level->handleUpdateBlocks($world, $pos1, $pos2, Block::STAINED_GLASS, 14, true);

            return;
        }

        if ($type == 'UPDATE') {
            $level->handleUpdateTile($world, $this->getPlayersRadius($pos1, $pos2), $pos1, $pos2);
        }
    }

    /**
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @return Player[]
     */
    private function getPlayersRadius(Vector3 $pos1, Vector3 $pos2): array {
        /** @var array<string, Player> $players */
        $players = [];

        for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); ++$x) {
            for ($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); ++$y) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); ++$z) {
                    foreach ($this->getPlayers() as $player) {
                        if (!$player->isRunner()) continue;

                        $pos = $player->getGeneralPlayer()->asVector3();

                        if ((int) floor($x) == $pos->getFloorX() && (int) floor($y) == $pos->getFloorY() && (int) floor($z) == $pos->getFloorZ()) {
                            $players[$player->getName()] = $player;
                        }
                    }
                }
            }
        }

        return $players;
    }
}