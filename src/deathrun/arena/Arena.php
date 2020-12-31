<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\arena\task\GameMatchUpdateTask;
use deathrun\listener\ArenaListener;
use deathrun\player\Player;
use deathrun\utils\Trap;
use Exception;
use gameapi\arena\Level;
use gameapi\arena\task\GameCountDownUpdateTask;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class Arena extends ArenaListener {

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
        }

        parent::start($started);
    }

    public function startGame(): void {
        /** @var Vector3[] $positions */
        $positions = [];

        $world = $this->getWorld();

        if ($world == null) return;

        foreach ($world->getTiles() as $tile) {
            if (!$tile instanceof Sign) continue;

            if ($tile->getLine(0) != 'START' && $tile->getLine(0) != 'STOP') continue;

            $positions[] = $tile->asVector3();
        }

        $pos1 = $positions[0];
        $pos2 = $positions[1];

        for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); ++$x) {
            for ($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); ++$y) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); ++$z) {
                    $block = $world->getBlockAt((int)$x, (int)$y, (int)$z);

                    if ($block->getId() != Block::STAINED_HARDENED_CLAY && $block->getId() != Block::STAINED_GLASS) continue;

                    $world->setBlockIdAt((int)$x, (int)$y, (int)$z, 0);
                }
            }
        }

        foreach ($this->getPlayers() as $player) {
            $player->setImmobile(false);

            $instance = $player->getGeneralPlayer();

            if ($player->isRunner()) {
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

                for ($i = 2; $i < 9; $i++) $instance->getInventory()->setItem($i, $item);
            }
        }

        if ($world != null) $this->traps = $this->getLevel()->loadTraps($world);

        $this->scheduleRepeatingTask(new GameMatchUpdateTask('game_match_update', $this));
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