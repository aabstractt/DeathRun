<?php

declare(strict_types=1);

namespace deathrun;

use deathrun\arena\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Utils {

    /** @var int[] */
    private static $trapSteps = [];
    /** @var int[] */
    private static $playerSlot = [];

    /**
     * @param string $name
     * @param int $slot
     */
    public static function addPlayer(string $name, int $slot): void {
        self::$trapSteps[strtolower($name)] = 2;

        self::$playerSlot[strtolower($name)] = $slot;
    }

    /**
     * @param string $name
     */
    public static function remove(string $name): void {
        if (!self::inTrapStep($name)) return;

        $name = strtolower($name);

        unset(self::$playerSlot[$name], self::$trapSteps[$name]);
    }

    /**
     * @param string $name
     */
    public static function updateStep(string $name): void {
        if (!self::inTrapStep($name)) return;

        self::$trapSteps[strtolower($name)]++;
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getTrapStep(string $name): int {
        return self::$trapSteps[strtolower($name)] ?? 1;
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getPlayerSlot(string $name): int {
        return self::$playerSlot[strtolower($name)] ?? 0;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function inTrapStep(string $name): bool {
        return isset(self::$trapSteps[strtolower($name)]);
    }

    /**
     * @param Player $player
     * @param Vector3 $loc
     */
    public static function handleTrapStep(Player $player, Vector3 $loc): void {
        $name = $player->getName();

        if (!self::inTrapStep($name)) return;

        /** @var Level $level */
        $level = DeathRun::getLevelFactory()->getLevel($player->getLevelNonNull()->getFolderName());

        if ($level == null) {
            $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

            return;
        }

        $level->addTrapPosition(self::getPlayerSlot($name), self::getTrapStep($name), $loc);

        $level->handleUpdate();

        $player->sendMessage(TextFormat::BLUE . 'Trap ' . self::getPlayerSlot($name) . ' (' . self::getTrapStep($name) . ') set to §6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ());

        self::updateStep($player->getName());

        if (self::getTrapStep($name) > 3) {
            self::remove($name);
        }
    }
}