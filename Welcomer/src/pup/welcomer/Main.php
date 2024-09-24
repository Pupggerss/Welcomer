<?php
    
    
    namespace pup\welcomer;
    
    
    use pocketmine\event\Listener;
    use pocketmine\event\player\PlayerChatEvent;
    use pocketmine\event\player\PlayerJoinEvent;
    use pocketmine\plugin\PluginBase;
    use pocketmine\scheduler\ClosureTask;
    use pocketmine\utils\TextFormat;
    use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
    
    class Main extends PluginBase implements Listener
    {
        private static Main $instance;
        private float $money;
        private int $timer;
        private int $timeRemaining;
        private bool $eco = FALSE;
        
        private array $cooldown = [];
        private int $cooldownTime;
        
        public static function getInstance(): self
        {
            return self::$instance;
        }
        
        public function onLoad(): void
        {
            self::$instance = $this;
        }
        
        public function onEnable(): void
        {
            $this->saveDefaultConfig ();
            
            $this->money = $this->getConfig ()->get ('money');
            $this->timer = $this->getConfig ()->get ('timer');
            $this->cooldownTime = $this->getConfig ()->get ('cooldown');
            
            if ($this->getServer ()->getPluginManager ()->getPlugin ('BedrockEconomy') !== NULL) {
                $this->eco = TRUE;
            } else {
                $this->getServer ()->getLogger ()->error ('BedrockEconomy not found');
            }
            
            if ($this->cooldownTime < $this->timer) {
                $this->getLogger ()->warning ('Cooldown time MUST be greater than chat timer! Defaulting to Timer + 10.');
                $this->cooldownTime = $this->timer + 10;
            }
            
            $this->getServer ()->getPluginManager ()->registerEvents ($this , $this);
        }
        
        public function onJoin(PlayerJoinEvent $event): void
        {
            $player = $event->getPlayer ();
            if (!$player->hasPlayedBefore ()) {
                $this->startCountdown ();
            }
        }
        
        private function startCountdown(): void
        {
            $this->timeRemaining = $this->timer;
            
            $this->getScheduler ()->scheduleRepeatingTask (new ClosureTask(function (): void {
                if ($this->timeRemaining > 0) {
                    $this->timeRemaining--;
                }
            }) , 20);
        }
        
        public function onChat(PlayerChatEvent $event): void
        {
            $player = $event->getPlayer ();
            $playerName = $player->getName ();
            $message = strtolower (trim ($event->getMessage ()));
            
            if (isset($this->cooldown[$playerName]) && (time () - $this->cooldown[$playerName]) < $this->cooldownTime) {
                $player->sendMessage (TextFormat::DARK_RED . 'You are on cooldown!');
                return;
            }
            
            if ($message === 'welcome' && $this->timeRemaining > 0 && $this->eco) {
                BedrockEconomyAPI::legacy ()->addToPlayerBalance ($playerName , $this->money);
                $player->sendMessage (TextFormat::GREEN . 'You welcomed a player and received ' . $this->money . ' coins!');
                $this->cooldown[$playerName] = time ();
            }
        }
    }