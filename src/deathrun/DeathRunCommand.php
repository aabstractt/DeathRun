<?php

declare(strict_types=1);

namespace deathrun;

use deathrun\arena\Level;
use gameapi\asyncio\FileCopyAsyncTask;
use gameapi\Command;
use pocketmine\Player as pocketPlayer;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class DeathRunCommand extends Command {

    /**
     * DeathRunCommand constructor.
     */
    public function __construct() {
        parent::__construct('deathrun', 'DeathRun Command', '/deathrun leave', ['dr']);
    }

    /**
     * @param pocketPlayer $player
     * @param array $args
     */
    protected function run(pocketPlayer $player, array $args): void {
        if (!$player->hasPermission('deathrun.admin')) {
            $player->sendMessage(TextFormat::RED . 'You don\'t have permissions to use this command');

            return;
        }

        if ($args[0] == 'create') {
            $level = $player->getLevel();

            if ($level == null) return;

            if ($level === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            if (isset(DeathRun::getLevelFactory()->getAllLevels()[strtolower($level->getFolderName())])) {
                $player->sendMessage(TextFormat::RED . 'This arena already exists.');

                return;
            }

            $level->save(true);

            $data = [
                'folderName' => $level->getFolderName(),
                'minSlots' => 3,
                'maxSlots' => 8,
                'spawns' => [],
                'checkpoints' => [],
                'traps' => []
            ];

            Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Server::getInstance()->getDataPath() . '/worlds/' . $data['folderName'], DeathRun::getInstance()->getDataFolder() . '/arenas/' . $data['folderName'], function () use ($player, $data) {
                DeathRun::getLevelFactory()->saveLevel(DeathRun::getLevelFactory()->loadLevel($data));

                $player->sendMessage(TextFormat::GREEN . 'Successfully created ' . $data['folderName']);
            }));

            return;
        }

        if ($args[0] == 'spawn') {
            $worldLevel = $player->getLevel();

            if ($worldLevel == null) return;

            if ($worldLevel === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            $level = DeathRun::getLevelFactory()->getLevel($worldLevel->getFolderName());

            if ($level == null) {
                $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

                return;
            }

            $level->addSlotPosition(1, ($loc = $player->getLocation()));

            $player->sendMessage(TextFormat::BLUE . 'Spawn set to §6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ() . ' §6Yaw:§b ' . $loc->getYaw() . ' §6Pitch:§b ' . $loc->getPitch());

            DeathRun::getLevelFactory()->saveLevel($level);

            return;
        }

        if ($args[0] == 'checkpoint') {
            if (!isset($args[1])) {
                $player->sendMessage(TextFormat::RED . '/' . $this->getName() . ' checkpoint <slot>');

                return;
            }

            $worldLevel = $player->getLevel();

            if ($worldLevel == null) return;

            if ($worldLevel === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            /** @var Level $level */
            $level = DeathRun::getLevelFactory()->getLevel($worldLevel->getFolderName());

            if ($level == null) {
                $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

                return;
            }

            $level->addCheckpointPosition((int) $args[1], ($loc = $player->getLocation()));

            $player->sendMessage(TextFormat::BLUE . 'Checkpoint ' . $args[1] . ' set to §6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ() . ' §6Yaw:§b ' . $loc->getYaw() . ' §6Pitch:§b ' . $loc->getPitch());

            DeathRun::getLevelFactory()->saveLevel($level);

            return;
        }

        if ($args[0] == 'trap') {
            if (!isset($args[1])) {
                $player->sendMessage(TextFormat::RED . '/' . $this->getName() . ' checkpoint <slot>');

                return;
            }

            $worldLevel = $player->getLevel();

            if ($worldLevel == null) return;

            if ($worldLevel === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            if (DeathRun::getLevelFactory()->getLevel($worldLevel->getFolderName()) == null) {
                $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

                return;
            }
        }
    }
}