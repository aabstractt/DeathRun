<?php

declare(strict_types=1);

namespace deathrun\arena;

use Exception;
use gameapi\math\GameLocation;
use gameapi\math\GameVector3;
use pocketmine\level\Level as pocketLevel;
use pocketmine\math\Vector3;

class Level extends \gameapi\arena\Level {

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
}