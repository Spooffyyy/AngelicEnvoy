<?php

declare(strict_types=1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;

class CountTask extends Task {
    private $plugin;
    private $area ;
    private $sec; 
    private $cs = 11 ;
    
    public function __construct($area,$plugin){
    $this->plugin = $plugin;
    $this->area = $area;
    $this->sec = $plugin->countdown;
  }
    public function onRun($tick) {
        --$this->cs;
        $s = $this->cs ;
        if($s <= 10 and $s > 0){
            $this->plugin->getServer()->broadcastMessage("§6Envoys will be spawned in §3$s §6 seconds in §3" . $this->area);
        }
        if($s == 0){
            $this->plugin->start($this->area);
        }
        if($s <= 0){
            --$this->sec;
            $secunde = $this->sec;
            if($secunde <= 0){
                            $this->plugin->getServer()->broadcastMessage("§4Envoy items will be dropped");
                           unset($this->plugin->areaStatus[$this->area]);
                    $this->plugin->getScheduler()->cancelTask($this->getTaskId());
            }
        }
        
    }
}
