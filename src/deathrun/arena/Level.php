<?php

declare(strict_types=1);

namespace deathrun\arena;

use pocketmine\math\Vector3;

class Level extends \gameapi\arena\Level {

    public function addCheckpointPosition(int $slot, Vector3 $vector3): void {
        $this->data['checkpoints'][$slot] = [
            'x' => $vector3->getFloorX(),
            'y' => $vector3->getFloorY(),
            'z' => $vector3->getFloorZ()
        ];
    }

    /**
     * @param int $slot
     * @return Vector3|null
     */
    public function getCheckpointPosition(int $slot): ?Vector3 {
        $data = $this->data['checkpoints'][$slot] ?? null;

        if ($data == null) {
            return null;
        }

        return new Vector3($data['x'], $data['y'], $data['z']);
    }
}