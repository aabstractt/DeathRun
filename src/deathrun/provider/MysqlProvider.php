<?php

declare(strict_types=1);

namespace deathrun\provider;

use Exception;
use gameapi\Game;
use gameapi\provider\TargetOffline as GameTargetOffline;
use mysqli_result;

class MysqlProvider extends \gameapi\provider\MysqlProvider {

    /**
     * MysqlProvider constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data) {
        parent::__construct($data);

        $connect = $this->connect();

        if ($connect == null) {
            throw new Exception('Mysql null');
        }

        try {
            if (!mysqli_query($connect, 'CREATE TABLE IF NOT EXISTS sumo_stats (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(16), gamesPlayed INT DEFAULT 0, killStreak INT DEFAULT 0, bestKillStreak INT DEFAULT 0, wins INT DEFAULT 0, losses INT DEFAULT 0)')) {
                throw new Exception(mysqli_error($connect));
            }
        } catch (Exception $e) {
            Game::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param GameTargetOffline $targetOffline
     * @throws Exception
     */
    public function setTargetOffline(GameTargetOffline $targetOffline): void {
        if (!$targetOffline instanceof TargetOffline) return;

        $connect = $this->connect();

        if ($connect == null) return;

        try {
            if ($this->getTargetOffline($targetOffline->getName()) !== null) {
                $query = "UPDATE sumo_stats SET gamesPlayed = '{$targetOffline->getGamesPlayed()}', killStreak = '{$targetOffline->getKillStreak()}', bestKillStreak = '{$targetOffline->getBestKillStreak()}', wins = '{$targetOffline->getWins()}', losses = '{$targetOffline->getLosses()}' WHERE username = '{$targetOffline->getName()}'";
            } else {
                $query = "INSERT INTO sumo_stats(username, gamesPlayed, killStreak, bestKillStreak, wins, losses) VALUES ('{$targetOffline->getName()}', '{$targetOffline->getGamesPlayed()}', '{$targetOffline->getKillStreak()}', '{$targetOffline->getBestKillStreak()}', '{$targetOffline->getWins()}', '{$targetOffline->getLosses()}')";
            }

            if (!mysqli_query($connect, $query)) {
                throw new Exception($connect->error);
            }
        } catch (Exception $e) {
            Game::getInstance()->getLogger()->logException($e);
        }
    }

    /**
     * @param string $name
     * @return GameTargetOffline|null
     * @throws Exception
     */
    public function getTargetOffline(string $name): ?GameTargetOffline {
        $connect = $this->connect();

        if ($connect == null) return null;

        try {
            if (!($query = mysqli_query($connect, "SELECT * FROM sumo_stats WHERE username = '{$name}'")) instanceof mysqli_result) {
                throw new Exception($connect->error);
            }

            if (mysqli_num_rows($query) <= 0) return null;

            $data = mysqli_fetch_assoc($query);

            if ($data == null) return null;

            return Game::getInstance()->generateNewTargetOffline($data);
        } catch (Exception $e) {
            Game::getInstance()->getLogger()->logException($e);
        }

        return null;
    }
}