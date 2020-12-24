<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\DeathRun;
use deathrun\player\Player;
use deathrun\utils\Trap;
use Exception;
use gameapi\math\GamePosition;
use gameapi\math\GameVector3;
use pocketmine\block\Block;
use pocketmine\level\Explosion;
use pocketmine\level\Level as pocketLevel;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Sign;
use redstone\blockEntities\BlockEntityDispenser;

class Level extends \gameapi\arena\Level {

    /**
     * @param int $slot
     * @param pocketLevel $level
     * @return GamePosition
     * @throws Exception
     */
    public function getSlotPosition(int $slot, pocketLevel $level): GamePosition {
        if ($slot == 1) {
            return parent::getSlotPosition($slot, $level);
        }

        return $this->getTrapPosition(1, $level);
    }

    /**
     * @param int $slot
     * @param Vector3 $vector3
     */
    public function addCheckpointPosition(int $slot, Vector3 $vector3): void {
        $this->data['checkpoints'][$slot] = GameVector3::toArray($vector3);
    }

    /**
     * @param int $slot
     * @return GameVector3
     * @throws Exception
     */
    public function getCheckpointPosition(int $slot): GameVector3 {
        $data = $this->data['checkpoints'][$slot] ?? null;

        if ($data == null) {
            throw new Exception('Checkpoint not found');
        }

        return GameVector3::fromArray($data);
    }

    /**
     * @return array
     */
    public function getCheckpoints(): array {
        return $this->data['checkpoints'];
    }

    /**
     * @param int $slot
     * @param Vector3 $loc
     */
    public function addTrapPosition(int $slot, Vector3 $loc): void {
        $this->data['trapspawn'][$slot] = GameVector3::toArray($loc);
    }

    /**
     * @param int $slot
     * @param pocketLevel $level
     * @return GamePosition
     * @throws Exception
     */
    public function getTrapPosition(int $slot, pocketLevel $level): GamePosition {
        $data = $this->data['trapspawn'][$slot] ?? null;

        if ($data == null) {
            throw new Exception('Trap slot ' . $slot . ' not found');
        }

        /** @var GamePosition $pos */
        $pos = GamePosition::fromArray($data, $level);

        return $pos;
    }

    /**
     * @param pocketLevel $world
     */
    public function handleSpawns(pocketLevel $world): void {
        foreach ($world->getTiles() as $tile) {
            if (!$tile instanceof Sign) continue;

            $text = $tile->getLine(0);

            if ($text != 'TRAP' && $text != 'SPAWN') continue;

            if ($text == 'TRAP') {
                $this->addTrapPosition((int)$tile->getLine(1), $tile->asVector3());
            } else {
                $this->addSlotPosition(1, Location::fromObject($tile->asVector3(), $world));
            }
        }
    }

    /**
     * @param pocketLevel $world
     * @return array<int, Trap>
     */
    public function loadTraps(pocketLevel $world): array {
        /** @var array<int, Trap> $traps */
        $traps = [];

        foreach ($world->getTiles() as $tile) {
            if (!$tile instanceof Sign) continue;

            $lines = $tile->getText();

            if (empty($lines[0])) continue;

            $text = explode(':', $lines[0]);

            if (empty($text[1])) continue;

            if (empty($lines[1])) continue;

            $traps[] = new Trap((int)$text[0], (int)$text[1], $lines[1], $tile->asVector3());

            $world->setBlock($tile->asVector3(), Block::get(0));
        }

        return $traps;
    }

    /**
     * @param pocketLevel $world
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     * @param int $id
     * @param int $damage
     * @param bool $replaceAll
     */
    public function handleUpdateBlocks(pocketLevel $world, Vector3 $pos1, Vector3 $pos2, int $id = 0, int $damage = 0, bool $replaceAll = false): void {
        /** @var array<string, Block> $data */
        $data = [];

        for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); ++$x) {
            for ($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); ++$y) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); ++$z) {
                    $block = $world->getBlockAt((int)$x, (int)$y, (int)$z);

                    if (!$replaceAll) {
                        if ($block->getId() != Block::STAINED_HARDENED_CLAY && $block->getId() != Block::STAINED_GLASS) continue;

                        if ($block->getDamage() == 5) continue;
                    }

                    $data[$x . ':' . $y . ':' . $z] = clone $block;

                    $world->setBlock(new Vector3($x, $y, $z), Block::get($id, $damage));
                }
            }
        }

        DeathRun::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($data): void {
            foreach ($data as $k => $v) {
                list($x, $y, $z) = explode(':', $k);

                $v->getLevelNonNull()->setBlock(new Vector3((int)$x, (int)$y, (int)$z), $v);
            }
        }), 8 * 10);
    }

    /**
     * @param pocketLevel $world
     * @param array<string, Player> $players
     * @param Vector3 $pos1
     * @param Vector3 $pos2
     */
    public function handleUpdateTile(pocketLevel $world, array $players, Vector3 $pos1, Vector3 $pos2): void {
        for ($x = min($pos1->getX(), $pos2->getX()); $x <= max($pos1->getX(), $pos2->getX()); ++$x) {
            for ($y = min($pos1->getY(), $pos2->getY()); $y <= max($pos1->getY(), $pos2->getY()); ++$y) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z <= max($pos1->getZ(), $pos2->getZ()); ++$z) {
                    $block = $world->getBlockAt((int)$x, (int)$y, (int)$z);

                    if ($block->getId() != Block::TNT && $block->getId() != Block::DISPENSER) continue;

                    if ($block->getId() == Block::TNT) {
                        (new Explosion(Position::fromObject($block->add(0, 0.98 / 2), $block->getLevelNonNull()), 4, $block))->explodeB();

                        foreach ($players as $player) {
                            $player->remove(false);
                        }

                        return;
                    }

                    $tile = $world->getTileAt($x, $y, $z);

                    if (!$tile instanceof BlockEntityDispenser) continue;

                    $tile->dropItem();
                }
            }
        }
    }
}