<?php

namespace PrinxIsLeqit\SkyBox;

use PrinxIsLeqit\SkyBox\SkyBoxListener;
use PrinxIsLeqit\SkyBox\Tasks\SkyBoxTask;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\tile\Sign;

class SkyBox extends PluginBase{
    public $cfg;
    public $prefix;
    public $player;
    public $mode;
    public $file;
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder().'/rooms');
        if(!file_exists($this->getDataFolder().'config.yml')){
            $this->initConfig();
        }
        $this->cfg = new Config($this->getDataFolder().'config.yml');
        
        $this->prefix = $this->cfg->get('prefix');
        
        $this->getServer()->getPluginManager()->registerEvents(new SkyBoxListener($this), $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new SkyBoxTask($this), 20);
    }
    
    public function onDisable() {
        $dir = $this->getDataFolder() . "rooms/";
        $rooms = array_slice(scandir($dir), 2);
        $this->cfg = new Config($this->getDataFolder().'config.yml', Config::YAML);
        foreach ($rooms as $g) {
            $boxname = pathinfo($g, PATHINFO_FILENAME);
            $boxfile = new Config($this->getDataFolder().'/rooms/'.$boxname.'.yml', Config::YAML);

            $tiles = $this->getServer()->getDefaultLevel()->getTiles();
            
            foreach ($tiles as $t){
                if($t instanceof Sign){
                    $text = $t->getText();
                    if($text['0'] == $this->prefix){
                        if(TextFormat::clean($text['1']) == $boxname){
                            $t->setLine(2, TextFormat::YELLOW.'0 seconds');
                            $t->setLine(3, TextFormat::RED.'CLOSED');
                        }
                    }
                }
            }
            
            $boxfile->set('youtuber', NULL);
            $boxfile->set('current', NULL);
            $boxfile->set('timer', 0);
            $boxfile->set('queue', array());
            $boxfile->save();
            
            
        }
    }
    
    public function initConfig(){
        $this->cfg = new Config($this->getDataFolder().'config.yml');
        $this->cfg->set('prefix', '§7[§bVaronPE §5Box§7]');
        $this->cfg->save();
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command == 'skybox'){
            if($sender instanceof Player){
                if(empty($args['0'])){
                    $sender->sendMessage($this->prefix);
                    $sender->sendMessage(TextFormat::GREEN.'/skybox create <name>');
                    $sender->sendMessage(TextFormat::GREEN.'/skybox leave');
                    $sender->sendMessage(TextFormat::GOLD.'Plugin by '.TextFormat::AQUA.'@PrinxIsLeqit&georgianYT');
                    return FALSE;
                }
                if($args['0'] == 'create'){
                    if($sender->hasPermission('skybox.admin')){
                        if(empty($args['1'])){
                            $sender->sendMessage(TextFormat::GREEN.'/skybox create <name>');
                            return FALSE;
                        }else{
                            if(file_exists($this->getDataFolder().'/rooms/'. strtolower($args['1']) .'.yml')){
                                $sender->sendMessage($this->prefix.TextFormat::WHITE.' That name is already in use!');
                                return FALSE;
                            }else{
                                $sender->sendMessage($this->prefix.TextFormat::WHITE.' Bitte setzt den Spawn für die Box!');
                                $this->player = $sender->getName();
                                $this->mode = 1;
                                
                                $this->file = new Config($this->getDataFolder().'/rooms/'.strtolower($args['1']).'.yml', Config::YAML);
                                $this->file->set('name', strtolower($args['1']));
                                
                                return TRUE;
                            }
                        }
                    }else{
                        $sender->sendMessage($this->prefix.TextFormat::WHITE.' No permission skybox.admin');
                    }
                }elseif($args['0'] == 'leave'){
                    if($sender->hasPermission('skybox.youtuber')){
                        $dir = $this->getDataFolder() . "rooms/";
                        $rooms = array_slice(scandir($dir), 2);
                        $this->cfg = new Config($this->getDataFolder().'config.yml', Config::YAML);
                        foreach ($rooms as $g) {
                            $boxname = pathinfo($g, PATHINFO_FILENAME);
                            $boxfile = new Config($this->getDataFolder().'/rooms/'.$boxname.'.yml', Config::YAML);
                            
                            if(!empty($boxfile->get('youtuber'))){
                                if($boxfile->get('youtuber') == $sender->getName()){
                                    if(!empty($boxfile->get('current'))){
                                        $p = $this->getServer()->getPlayer($boxfile->get('current'));
                                        $p->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                                        $p->sendMessage($this->prefix.TextFormat::WHITE.' '.$boxfile->get('youtuber').' hat verlassen');
                                    }
                                    
                                    $queue = $boxfile->get('queue');
                                    foreach ($queue as $pname){
                                        $p = $this->getServer()->getPlayer($pname);
                                        $p->sendMessage($this->prefix.' '.$boxfile->get('youtuber').' hat verlassen');
                                    }
                                    $boxfile->set('youtuber', NULL);
                                    $boxfile->set('queue', array());
                                    $boxfile->set('current', NULL);
                                    $boxfile->set('timer', 0);
                                    $boxfile->save();
                                    
                                    $tiles = $this->getServer()->getDefaultLevel()->getTiles();
                                    foreach ($tiles as $tile){
                                        if($tile instanceof Sign){
                                            $text = $tile->getText();
                                            if($text['0'] == $this->prefix){
                                                if(TextFormat::clean($text['1']) == $boxname){
                                                    $tile->setText(
                                                        $this->prefix,
                                                        TextFormat::GOLD.$boxname,
                                                        TextFormat::YELLOW.'0 seconds',
                                                        TextFormat::RED.'CLOSED'
                                                    );
                                                }
                                            }
                                        }
                                    }
                                    
                                    $sender->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                                    return TRUE;
                                }
                            }
                        }
                        
                        $sender->sendMessage($this->prefix.TextFormat::WHITE.' Du bist nicht in der ScreenBox!');
                    }else{
                        $sender->sendMessage($this->prefix.TextFormat::WHITE.' Du bist kein Youtuber!');
                    }
                }
            }else{
                $sender->sendMessage($this->prefix.TextFormat::WHITE.' Du bist kein Spieler!');
                return FALSE;
            }
            return FALSE;
        }
    }
}
