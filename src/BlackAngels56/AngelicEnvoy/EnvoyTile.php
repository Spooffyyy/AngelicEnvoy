<?php

declare(strict_types=1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\tile\Chest;
use pocketmine\item\ItemFactory;
use pocketmine\block\Chest as C;
use pocketmine\world\Position;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use ReflectionProperty;
use ReflectionClass;
use pocketmine\world\World;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\utils\UUID;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class EnvoyTile extends Chest{
    private $lifetime;
    private $tick;
    private $fireworks;
    private $summonFireworks;
    private $count;
    private $rarity;
    private $items;
    private $ticks = 1;
    private $showname;
    private $chunchL;
    private $nameId = null;
    private $Fid;
    private $players = [];
    private $packets = [];
    public function __construct( $level ,$nbt){
        parent::__construct($level , $nbt);
        
    }
    public function setData($count = 1 , $lifetime = 10 ,$summonFireworks = false ,$fireworks = 0 ,$items = [],$rarity = ""){
        $this->lifetime = $lifetime;
        $this->fireworks = $fireworks;;
        $this->summonFireworks = $summonFireworks;
        $this->count = $count;
        $this->rarity = $rarity;
        $this->items = $items;
        $this->setName("$rarity Envoy");
        $this->setRandomItems();
        $this->startEvent();
    }
    public function getRarity(){
        return $this->rarity;
    }
    
    public function setRandomItems(){
         $count = $this->count;
         $array = $this->items;
         if(count($array) < $count){
             $count = count($array);
         }
         $index = array_rand($array,$count);
         $items = [];
         foreach ($index as $ind){
             $items[] = $array[$ind];
         }
         $itm = [];
         foreach ($items as $it){
             $idMetaCounEnch = explode(":" ,(string)$it);
             $id = $idMetaCounEnch[0];
             $meta =isset($idMetaCounEnch[1]) ? $idMetaCounEnch[1] : 0 ;
             $count = isset($idMetaCounEnch[2]) ? $idMetaCounEnch[2] : 1;
             $i = ItemFactory::get((int)$id , (int)$meta ,(int) $count);
             if(isset($idMetaCounEnch[3])){
$enchantment = Enchantment::getEnchantment((int)$idMetaCounEnch[3] );
$level = isset($idMetaCounEnch[4]) ? $idMetaCounEnch[4] : 1 ;
$enchInstance = new EnchantmentInstance($enchantment,(int)$level);
$i->addEnchantment($enchInstance);
}
             $itm[] = $i;
             
         }
             $this->getInventory()->setContents($itm);


    }
    public function dropItems(){
			$this->getInventory()->dropContents($this->level, $this->asVector3()->add(0.5, 0.5, 0.5));
			$this->getInventory()->clearAll();
    }
    public function showName(){
        $pa = $this->packets;
        $pl = $this->players;
        $packets = array_diff_key($pa ,$pl);
       foreach ($packets as $k => $data){
              $player = Server::getInstance()->getPlayerExact($k);
              if(!$player){
                unset($this->packets[$k]);
                continue;
              }
              if((int)$this->distance($player->getPosition()) > 5){
               $this->removeText($this ,$player);
               unset($this->packets[$k]);
              }
       }
       $players = array_diff_key($pl , $pa);
       foreach ($players as $n => $p){
               $this->createText($this , $p);
           }
       }
       
    
    public static function createText($t ,$player){
        $rarity = $t->getRarity();
        $eid = Entity::$entityCount++;
        $pk = new AddPlayerPacket();
        $pk->username = "$rarity Envoy";
        $pk->uuid = UUID::fromRandom();
        $pk->entityRuntimeId = $eid;
        $pk->entityUniqueId = $eid;
        $pk->position = $t->asVector3()->add(0.5, 0.4, 0.5);
        $pk->item = ItemStackWrapper::legacy(Item::get(0));
        $pk->metadata = [
            Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_IMMOBILE],
            Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0]
        ];

        $player->dataPacket($pk);
        $t->packets[$player->getName()] = $pk;
        
    }
    public static function removeText($t ,Player $player) {
        $eid = $t->packets[$player->getName()]->entityRuntimeId;
        $pk = new RemoveActorPacket();
        $pk->entityUniqueId = $eid;
        $player->dataPacket($pk);
    }
    public function startEvent(){
        $this->Fid = uniqid("Envoy");
        RunTask::$functions[$this->Fid] = array($this, 'run');
                $this->nameId = uniqid("Name");
        NameTask::$functions[$this->nameId] = array($this, 'showName'); 
    }
    
    public function stopEvent(){
        $this->stopTask();
        $this->inventory->removeAllViewers(true);
        $this->dropItems();
        $this->getWorld()->getChunkAtPosition($this, true)->setBlock($this->x & 0x0f , $this->y, $this->z & 0x0f  , 0, 0);
        $this->updateBlocks($this);
        parent::close();

    }
    public function stopTask(){
        unset(RunTask::$functions[$this->Fid]);
        unset(NameTask::$functions[$this->nameId]);
        foreach ($this->players as $p){
            $this->removeText($this , $p);
        }
    }
    public function close() : void{
        $this->stopTask();
        parent::close();

    }
    
  public function updateBlocks($pos){
	$block = $this->getBlock($pos);
	$block->onScheduledUpdate();
	$block->clearCaches(); 
	$chunk = $this->getWorld()->getChunkAtPosition($pos, true);
$pl = $pos->getWorld()->getChunkPlayers($chunk->getX(), $chunk->getZ());
	$pos->getWorld()->sendBlocks($pl, [$block], UpdateBlockPacket::FLAG_ALL);	
      
  }

    public function run(){
        $this->showname = false;
$world = $this->getWorld();
if($world == null){
    $this->close();
    return;
}
$chunk =$this->getWorld()->getChunkAtPosition($this, true);;
$players = $this->getWorld()->getChunkPlayers($chunk->getX(), $chunk->getZ());
if($players != null){
    foreach ($players as $p){
        if((int)$this->distance($p->getPosition()) <= 5){
            $this->players[$p->getName()] = $p;
        }else{
            unset($this->players[$p->getName()]);

        }
        //$p->sendMessage("Run " . (string)count(RunTask::$functions));
      //  $p->sendMessage("name " . (string)count(NameTask::$functions));

}
}
        if((($this->ticks % $this->fireworks) == 0 ) and $this->summonFireworks ){
            
        $this->spawnFireworks($this);

        }
            if($this->ticks >= $this->lifetime){
            $this->stopEvent();
        }
        $this->ticks++;

}
public function spawnFireworks($pos){
        $motion = new Vector3(0.001, 0.05, 0.001);
	    $yaw = lcg_value() * 360;
	    $pitch = 90;
	    $world = $pos->getWorld();
    	    $nbt =new CompoundTag("", [
			new ListTag("Pos", [
				new DoubleTag("", $pos->x),
				new DoubleTag("", $pos->y),
				new DoubleTag("", $pos->z)
			]),
			new ListTag("Motion", [
				new DoubleTag("", $motion->x),
				new DoubleTag("", $motion->y),
				new DoubleTag("", $motion->z)
			]),
			new ListTag("Rotation", [
				new FloatTag("", $yaw),
				new FloatTag("", $pitch)
			])
		]);

    $entity = Entity::createEntity(Entity::FIREWORKS_ROCKET ,$pos->getLevel(),$nbt);
	    $c = ["\x00","\x01","\x02" ,"\x03" ,"\x04" ,"\x05" ,"\x06" , "\x07" ,"\x08" ,"\x09" ,"\x0a" , "\x0b" , "\x0c" ,"\x0d" ,"\x0e" ,"\x0f" ];
	    $color = $c[array_rand($c)];

	$prop = new CompoundTag("" ,[
	       "Fireworks"=>
	           new CompoundTag("Fireworks",[
	           "Explosions" => new ListTag(
	               "Explosions",[
	                   new CompoundTag("",[
	                   new ByteTag("FireworkType", random_int(0 ,4)),
	                   new ByteArrayTag("FireworkColor", (string)$color),
	                   new ByteArrayTag("FireworkFade", (string)$color),
	                   new ByteTag("FireworkFlicker", true ? 1 : 0),
	                   new ByteTag("FireworkTrail", true ? 1 : 0)
	                   ])
	                   ])
	                   ,
	                   new ByteTag("Flight", 3)
	                   ])
	               
	        ]);
	        $entity->getDataPropertyManager()->setCompoundTag(16, $prop);

    $entity->spawnToAll();
}
}
