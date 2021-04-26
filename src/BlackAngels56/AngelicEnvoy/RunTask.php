<?php

declare(strict_types=1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;

class RunTask extends Task {
    public static $functions = [];
    
    public function onRun($tick) {
        foreach (self::$functions as $f){
            call_user_func($f);
        }
    }
}
