<?php

declare(strict_types = 1);

namespace BlackAngels56\AngelicEnvoy;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\math\Vector3;


class Fireworks extends Entity {

	public const NETWORK_ID = Entity::FIREWORKS_ROCKET;

	public const DATA_FIREWORK_ITEM = 16; 

	public $width = 0.25;
	public $height = 0.25;

	/** @var int */
	protected $lifeTime = 30;

	public function __construct($level ,$nbt){
	    parent::__construct($level, $nbt);
       	$level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_LAUNCH);
	}

	protected function tryChangeMovement(): void {
		$this->motion->x *= 1.15;
		$this->motion->y += 0.04;
		$this->motion->z *= 1.15;
	}

	public function entityBaseTick(int $tickDiff = 1): bool {
		if($this->closed) {
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);
		if($this->doLifeTimeTick()) {
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

	public function setLifeTime(int $life): void {
		$this->lifeTime = $life;
	}

	protected function doLifeTimeTick(): bool {
		if(!$this->isFlaggedForDespawn() and --$this->lifeTime < 0) {
			$this->doExplosionAnimation();
			$this->flagForDespawn();
			return true;
		}

		return false;
	}

	protected function doExplosionAnimation(): void {
		$this->broadcastEntityEvent(ActorEventPacket::FIREWORK_PARTICLES);
		 $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_EXPLODE);

	}
}
