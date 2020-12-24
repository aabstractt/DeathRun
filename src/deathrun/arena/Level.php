<?php

declare(strict_types=1);

namespace deathrun\arena;

use deathrun\DeathRun;
use deathrun\utils\Trap;
use Exception;
use gameapi\math\GameLocation;
use gameapi\math\GamePosition;
use gameapi\math\GameVector3;
use pocketmine\block\Block;
use pocketmine\level\Level as pocketLevel;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Sign;

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

        return $this->getTrapperSpawn(1, $level);
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
     * @param int $type
     * @param Vector3 $loc
     */
    public function addTrapPosition(int $slot, int $type, Vector3 $loc): void {
        $data = GameVector3::toArray($loc);

        if (empty($data['yaw'])) $data = array_merge($data, ['yaw' => 0, 'pitch' => 0]);

        $this->data['traps'][$slot][$type] = $data;
    }

    /**
     * @param int $slot
     * @param int $type
     * @param pocketLevel $level
     * @return GameLocation
     * @throws Exception
     */
    public function getTrapPosition(int $slot, int $type, pocketLevel $level): GameLocation {
        $data = $this->data['traps'][$slot] ?? null;

        if ($data == null) {
            throw new Exception('Trap slot ' . $slot . ' not found');
        }

        $data = $data[$type] ?? null;

        if ($data == null) {
            throw new Exception('Trap type ' . $type . ' not found');
        }

        return GameLocation::fromArray($data, $level);
    }

    /**
     * @param int $slot
     * @param pocketLevel $level
     * @return GameLocation
     * @throws Exception
     */
    public function getTrapperSpawn(int $slot, pocketLevel $level): GameLocation {
        return $this->getTrapPosition($slot, 1, $level);
    }

    /**
     * @param pocketLevel $world
     * @return Trap[]
     */
    public function loadTraps(pocketLevel $world): array {
        /** @var Trap[] $traps */
        $traps = [];

        foreach ($world->getTiles() as $tile) {
            if (!$tile instanceof Sign) continue;

            $text = explode(':', $tile->getLine(0));

            if (empty($text)) continue;

            $traps[] = new Trap((int) $text[0], (int) $traps[1], (int) $tile->getLine(1), $tile->getLine(2), $tile->asVector3());
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
    public function removeBlocks(pocketLevel $world, Vector3 $pos1, Vector3 $pos2, int $id = 0, int $damage = 0, bool $replaceAll = false): void {
        /** @var array<string, Block> $data */
        $data = [];

        for ($x = min($pos1->getX(), $pos2->getX()); $x < max($pos1->getX(), $pos2->getX()); ++$x) {
            for ($y = min($pos1->getY(), $pos2->getY()); $y < max($pos1->getY(), $pos2->getY()); ++$y) {
                for ($z = min($pos1->getZ(), $pos2->getZ()); $z < max($pos1->getZ(), $pos2->getZ()); ++$z) {
                    $block = $world->getBlockAt((int) $x, (int) $y, (int) $z);

                    if (!$replaceAll) {
                        if ($block->getId() != Block::STAINED_HARDENED_CLAY || $block->getId() != Block::STAINED_GLASS) continue;

                        if ($block->getDamage() == 5) continue;
                    }

                    $data[$x . ':' . $y . ':' . $z] = clone $block;

                    $world->setBlock(new Vector3($x, $y, $z), Block::get($id, $damage));
                }
            }
        }

        DeathRun::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use($data) : void {
            foreach ($data as $k => $v) {
                list($x, $y, $z) = explode(':', $k);

                $v->getLevelNonNull()->setBlockIdAt((int) $x, (int) $y, (int) $z, $v->getId());
            }
        }), 8 * 10);
    }
}