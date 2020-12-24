<?php

declare(strict_types=1);

namespace deathrun;

use deathrun\arena\Arena;
use deathrun\arena\Level;
use Exception;
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
     * @throws Exception
     */
    protected function run(pocketPlayer $player, array $args): void {
        if (!$player->hasPermission('deathrun.admin')) {
            $player->sendMessage(TextFormat::RED . 'You don\'t have permissions to use this command');

            return;
        }

        if ($args[0] == 'create') {
            $level = $player->getLevelNonNull();

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
                'minSlots' => 1,
                'maxSlots' => 8,
                'spawns' => [],
                'trapspawn' => [],
                'checkpoints' => [],
            ];

            Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(Server::getInstance()->getDataPath() . '/worlds/' . $data['folderName'], DeathRun::getInstance()->getDataFolder() . '/arenas/' . $data['folderName'], function () use ($player, $data) {
                DeathRun::getLevelFactory()->loadLevel($data)->handleUpdate();

                $player->sendMessage(TextFormat::GREEN . 'Successfully created ' . $data['folderName']);
            }));

            return;
        }

        if ($args[0] == 'spawns') {
            if ($player->getLevelNonNull() === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            /** @var Level $level */
            $level = DeathRun::getLevelFactory()->getLevel($player->getLevelNonNull()->getFolderName());

            if ($level == null) {
                $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

                return;
            }

            $level->handleSpawns($player->getLevelNonNull());

            $level->handleUpdate();

            $player->sendMessage(TextFormat::BLUE . 'Spawns (' . TextFormat::GOLD . count($level->getData()['spawns']) . TextFormat::BLUE . ') and Traps (' . TextFormat::GOLD . count($level->getData()['trapspawn']) . TextFormat::BLUE . ') loaded!');

            return;
        }

        if ($args[0] == 'checkpoint') {
            if (!isset($args[1])) {
                $player->sendMessage(TextFormat::RED . '/' . $this->getName() . ' checkpoint <slot>');

                return;
            }

            if ($player->getLevelNonNull() === Server::getInstance()->getDefaultLevel()) {
                $player->sendMessage(TextFormat::RED . 'You can\'t setup maps in the lobby.');

                return;
            }

            /** @var Level $level */
            $level = DeathRun::getLevelFactory()->getLevel($player->getLevelNonNull()->getFolderName());

            if ($level == null) {
                $player->sendMessage(TextFormat::RED . 'This arena doesn\'t exist.');

                return;
            }

            $level->addCheckpointPosition((int) $args[1], ($loc = $player->getLocation()));

            $level->handleUpdate();

            $player->sendMessage(TextFormat::BLUE . 'Checkpoint ' . $args[1] . ' set to §6X:§b ' . $loc->getX() . ' §6Y:§b ' . $loc->getY() . ' §6Z:§b ' . $loc->getZ() . ' §6Yaw:§b ' . $loc->getYaw() . ' §6Pitch:§b ' . $loc->getPitch());

            return;
        }

        if ($args[0] == 't') {
            /** @var Arena $arena */
            $arena = DeathRun::getArenaFactory()->getArena($player);

            if ($arena == null) return;

            $player->sendMessage(TextFormat::RED . 'Removing blocks...');

            $arena->handleActivateTrap((int) $args[1]);

            return;
        }
    }
}