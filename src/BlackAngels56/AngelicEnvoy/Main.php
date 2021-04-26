<?php

declare(strict_types=1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\tile\Tile;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\entity\Entity;

class Main extends PluginBase{
    
    public $areas = [];
    public $areaStatus = [];
    public $positions = [];
    public $countdown ;
    public $fireworktick;
    public $fireworks;

	public function onEnable() : void{
		$this->getLogger()->info("Loading plugin");
            @mkdir($this->getDataFolder());
        $this->saveResource("Config.yml");
	    $conf = new Config($this->getDataFolder() . "Config.yml", Config::YAML);
	    $this->fireworktick = $conf->get("fireworktick");
        $this->areas = $conf->get("Areas");
	    $this->countdown = $conf->get("countdown");
	    $this->fireworks = $conf->get("summonFireworks");
	    if($conf->get("registerFireworks")){
	   Entity::registerEntity(Fireworks::class, true);
	    }
        Tile::registerTile(EnvoyTile::class, ["Envoy"]);
        
	      $this->getScheduler()->scheduleRepeatingTask(new TimeCheckStatus($this), 20 ) ;
	      $this->getScheduler()->scheduleRepeatingTask(new RunTask(), 20 ) ;
	      $this->getScheduler()->scheduleRepeatingTask(new NameTask(), 20 ) ;



	    
	}
    public function getRandomSavePosition( $areaName ,$times){
        $data = $this->areas;
                $X = [$data[$areaName]["Pos1"]["X"] ,$data[$areaName]["Pos2"]["X"]];
                $Z = [$data[$areaName]["Pos1"]["Z"] ,$data[$areaName]["Pos2"]["Z"]];
            
        
        $minX = min($X);
        $minZ = min($Z);
        $maxX = max($X);
        $maxZ = max($Z);
        $level = $this->getServer()->getLevelByName($data[$areaName]["Level"]);
        $positions =[];
        for($a = 0; $a < $times ;++$a ){
        $x = random_int($minX,$maxX);
        $z = random_int($minZ ,$maxZ);
        $y= $level->getHighestBlockAt($x ,$z) + 1;
         $positions[] = new Position($x,$y,$z,$level);
            
        }
        return $positions;
    }
public function startEnv($areaName){
        $this->areaStatus[$areaName] = false;
    	      $this->getScheduler()->scheduleRepeatingTask(new CountTask($areaName ,$this), 20 ) ;
}
public function start($areaName){
    $this->areaStatus[$areaName] = false;
    $data = $this->areas;
foreach ($data[$areaName]["Envoys"] as $rarity => $d){
    $positions =$this->getRandomSavePosition( $areaName ,$d["NumberChestSpawned"]);
    $this->positions[$areaName][$rarity]= $positions;
    var_dump($positions);
    foreach ($positions as $pos){
        if($pos instanceof Position){
        		$pos->getLevel()->timings->setBlock->startTiming();
        	$pos->getLevel()->getChunkAtPosition($pos, true)->setBlock($pos->x & 0x0f , $pos->y, $pos->z & 0x0f ,54 ,0 );
        	$pos->getLevel()->timings->setBlock->stopTiming();
        	  
			$items = $d["Items"];
			        $nbt = new CompoundTag("BlockEntityTag", [
			new StringTag("id", "Envoy"),
			new StringTag("CustomName" , "§6Chest " . $rarity),
			new ListTag ("Items" , []),
			new IntTag("x", (int) $pos->x),
			new IntTag("y", (int) $pos->y),
			new IntTag("z", (int) $pos->z)]);

			$tile = Tile::createTile("Envoy", $pos->getLevel() ,$nbt);
			$tile->setData($d["NumberOfItemsExtraged"],$this->countdown ,$this->fireworks, $this->fireworktick , $items ,$rarity);
    }
        
    }
}
foreach ($this->positions[$areaName] as $a => $b){
   foreach ($b as $pos){
    $this->updateBlocks($pos);
   }
}
}
  public function updateBlocks($pos){
	$block = $pos->getLevel()->getBlock($pos);
	$block->onScheduledUpdate();
	$block->clearCaches(); 
	$chunk = $pos->getLevel()->getChunkAtPosition($pos, true);
$pl = $pos->getLevel()->getChunkPlayers($chunk->getX(), $chunk->getZ());
	$pos->getLevel()->sendBlocks($pl, [$block], UpdateBlockPacket::FLAG_ALL);	
      
  }
						
	public function onDisable() : void{
		$this->getLogger()->info("Dissabling Plugin");
	}
}
