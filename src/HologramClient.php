<?php
declare(strict_types = 1);

/*_   _       _                                  ____ _ _            _   
 | | | | ___ | | ___   __ _ _ __ __ _ _ __ ___  / ___| (_) ___ _ __ | |_ 
 | |_| |/ _ \| |/ _ \ / _` | '__/ _` | '_ ` _ \| |   | | |/ _ \ '_ \| __|
 |  _  | (_) | | (_) | (_| | | | (_| | | | | | | |___| | |  __/ | | | |_ 
 |_| |_|\___/|_|\___/ \__, |_|  \__,_|_| |_| |_|\____|_|_|\___|_| |_|\__|
                      |___/
    written by @yeondu1062.
*/

namespace HologramClient;

use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\Config;

final class HologramClient extends PluginBase{
    public function onEnable(): void {
		$this->saveResource('hologramClient.yml');

		$hologramConfig = new Config($this->getDataFolder() . 'hologramClient.yml', Config::YAML);
		$world = Server::getInstance()->getWorldManager()->getDefaultWorld();

		foreach ($hologramConfig->get('hologramClient', []) as $entry) {
			foreach ($entry as $pos => $text) {
				[$x, $y, $z] = array_map('intval', explode('.', $pos));
				(new HologramEntity(new Location($x, $y, $z, $world, 0, 0), $text))->spawnToAll();
			}
		}
    }
}

final class HologramEntity extends Entity{
	public function __construct(Location $location, string $text) {
		parent::__construct($location);
		$this->setNameTag($text);
		$this->setCanSaveWithChunk(false);
		$this->setNoClientPredictions(true);
		$this->setNameTagAlwaysVisible(true);
	}

	public static function getNetworkTypeId(): string {
		return EntityIds::FALLING_BLOCK;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(1, 1);
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void {
		parent::syncNetworkData($properties);
		$properties->setInt(EntityMetadataProperties::VARIANT, 12032); //AIR (12032)
	}

	public function spawnTo(Player $player): void {
		parent::spawnTo($player);		
		$packet = new SetActorDataPacket();
		$packet->actorRuntimeId = $this->getId();
		$packet->syncedProperties = new PropertySyncData([], []);
		$packet->metadata = [EntityMetadataProperties::NAMETAG => new StringMetadataProperty(str_replace(
			['{name}', '{name_tag}'], [$player->getName(), $player->getNameTag()], $this->getNameTag()))];
		$player->getNetworkSession()->sendDataPacket($packet);
	}

	public function attack(EntityDamageEvent $evd): void { $evd->cancel(); }

	public function isFireProof(): bool { return true; }
	public function canBeMovedByCurrents(): bool { return false; }

	protected function getInitialDragMultiplier(): float { return 0.0; }
	protected function getInitialGravity(): float { return 0.0; }
}
