<?php

declare(strict_types=1);

namespace deathrun\provider;

class TargetOffline extends \gameapi\provider\TargetOffline {

    /**
     * TargetOffline constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        parent::__construct($data);

        if (!isset($this->data['losses'])) {
            $this->data['losses'] = 0;
        }
    }

    /**
     * @return int
     */
    public function getLosses(): int {
        return (int) $this->data['losses'];
    }

    public function increaseLosses(): void {
        $this->data['losses']++;
    }
}