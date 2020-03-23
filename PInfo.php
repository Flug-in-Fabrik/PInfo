<?php
/**
 * @name PInfo
 * @main PInfo\PInfo
 * @author Puki
 * @version 1.0.0
 * @api 4.0.0
 */
namespace PInfo;

    use pocketmine\event\Listener;
    use pocketmine\Player;
    use pocketmine\entity\Entity;
    use pocketmine\scheduler\Task;
    use pocketmine\plugin\PluginBase;
    use pocketmine\math\Vector3;
    use pocketmine\network\mcpe\protocol\AddActorPacket;
    use pocketmine\network\mcpe\protocol\AddPlayerPacket;
    use pocketmine\network\mcpe\protocol\RemoveActorPacket;
    use pocketmine\Server;
    use onebone\economyapi\EconomyAPI;
    use pocketmine\utils\UUID;
    use pocketmine\item\Item;
    use pocketmine\event\player\{
      PlayerQuitEvent, PlayerJoinEvent
    };

    class PInfo extends PluginBase implements Listener {

        public function onEnable() {
          $this->getServer()->getPluginManager()->registerEvents($this, $this);
          $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            public function __construct(Info $owner) {
              $this->owner = $owner;
            }
            public function onRun(int $currentTick) {
              foreach($this->owner->getServer()->getOnlinePlayers() as $player){
                if(isset($this->owner->entitydata[$player->getName()])){
                  $this->owner->ColseInfoMob($player);
                  $this->owner->SpawnInfoMob($player);
                }
              }
            }
          }, 20 );
        }

        public $entitydata = [];
        public $tagdata = [];

        public function SpawnInfoMob($player){
          $player_yaw = $player->yaw + 20;
          $x = $player->x + -sin($player_yaw / 180 * M_PI) * 3;
          $z = $player->z + cos($player_yaw / 180 * M_PI) * 3;
          /*$yawx =  $player-> x - $x;
          $yawz = $player-> z = $z;
          $abs_yaw = abs($yawx) + abs($yawz);*/
          $pk = new AddActorPacket();
          $pk->type = 82;
          $pk->position = new Vector3($x, $player-> y +1, $z);
          $pk->entityRuntimeId = $this->entitydata[$player->getName()] = Entity::$entityCount++;
          //$pk->yaw = -atan2($yawx / $abs_yaw, $yawz / $abs_yaw) * 180 / M_PI;
        /*  $pk->headYaw = 0;
          $pk->pitch = 0;*/
          $player->dataPacket($pk);

          $money = EconomyAPI::getInstance()->myMoney($player);
          $online_player =  count(Server::getInstance()->getOnlinePlayers());
          $pk1 = new AddPlayerPacket();
          $pk1->entityRuntimeId = $this->tagdata[$player->getName()] = Entity::$entityCount++;
          $pk1->uuid = UUID::fromRandom();
          $pk1->username = "돈 : {$money}\n접속수 : {$online_player}";
          $pk1->position = new Vector3($x, $player-> y +1, $z);
          $pk1->item = Item::get(0);
          $pk1->metadata = [Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0.001]];
          $player->dataPacket($pk1);
        }

        public function ColseInfoMob($player){
          $pk = new RemoveActorPacket ();
          $pk->entityUniqueId = $this->entitydata[$player->getName()];
          $player->DataPacket($pk);

          $pk1 = new RemoveActorPacket ();
          $pk1->entityUniqueId = $this->tagdata[$player->getName()];
          $player->DataPacket($pk1);
        }

        public function onQuit(PlayerQuitEvent $ev){
          $player = $ev->getPlayer();
          if(isset($this->entitydata[$player->getName()])) {
            $this->ColseInfoMob($player);
          }
        }

        public function onJoin(PlayerJoinEvent $ev){
          $player = $ev->getPlayer();
          if(!isset($this->entitydata[$player->getName()])) {
            $this->SpawnInfoMob($player);
          }
        }
}
