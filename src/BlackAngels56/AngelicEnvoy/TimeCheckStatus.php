<?php

declare(strict_types=1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;

class TimeCheckStatus extends Task {
    private $plugin;
    public function __construct($plugin){
    $this->plugin = $plugin;
  }
    public function onRun($tick) {
        $Hours = date("H");
    //    var_dump($Hours);
        $minutes = date("i");
     //   var_dump($minutes);
        $day = date("D");
     //   var_dump($day);
        $getAllMinutes = ($Hours * 60 )+ $minutes;
        foreach($this->plugin->areas as $areas => $data)  {
        if(isset($this->plugin->areaStatus[$areas])){
         continue;
         }
      //   var_dump(isset($this->plugin->areaStatus[$areas]));

          $time = $data["StartTime"];
         $array = explode("-" , $time);
         if($array[0] == "E" ){
                if($getAllMinutes % $array[1] == 0){
                    $this->plugin->startEnv($areas);
         }
         }
         elseif($array[1] == "E"){
             if($array[0] == $day and $getAllMinutes % $array[2] == 0){
          $this->plugin->startEnv($areas);
        
             }
             else{
                 if($day == $array[0] and $Hours == $array[1] and $minutes == $array[2]){
                     
                     $this->plugin->startEnv($areas);

                 }
             }
         }
        }
    }

}
