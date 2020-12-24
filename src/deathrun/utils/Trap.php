<?php

namespace deathrun\utils;

use pocketmine\math\Vector3;

class Trap {

    /** @var int */
    private $slot;
    /** @var int */
    private $step;
    /** @var int */
    private $startAt;
    /** @var string */
    private $type;
    /** @var Vector3 */
    private $vector3;

    /**
     * Trap constructor.
     * @param int $slot
     * @param int $step
     * @param int $startAt
     * @param string $type
     * @param Vector3 $vector3
     */
    public function __construct(int $slot, int $step, int $startAt, string $type, Vector3 $vector3) {
        $this->slot = $slot;

        $this->step = $step;

        $this->startAt = $startAt;

        $this->type = $type;

        $this->vector3 = $vector3;
    }

    /**
     * @return int
     */
    public function getSlot(): int {
        return $this->slot;
    }

    /**
     * @return int
     */
    public function getStep(): int {
        return $this->step;
    }

    /**
     * @return Vector3
     */
    public function asVector3(): Vector3 {
        $vector = $this->vector3;

        return $vector->add(0, $this->startAt);
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function canBreak(): bool {
        return $this->type == 'Break';
    }

    /**
     * @return bool
     */
    public function canReplace(): bool {
        return $this->type == 'Replace';
    }

    /**
     * @return bool
     */
    public function canUpdate(): bool {
        return $this->type == 'Update';
    }

    /**
     * @param array<int, Trap> $traps
     * @return string|null
     */
    public function selectType(array $traps): ?string {
        foreach ($traps as $trap) {
            if ($trap->getStep() != $this->step) continue;
            if ($trap->getType() != $this->type) continue;

            return $this->type;
        }

        return null;
    }
}