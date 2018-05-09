<?php

namespace PrinxIsLeqit\SkyBox\Tasks;

use PrinxIsLeqit\SkyBox\SkyBox;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;

class SkyBoxTask extends PluginTask{
    private $plugin;
    
    public function __construct(\pocketmine\plugin\Plugin $owner) {
        $this->plugin = $owner;
        parent::__construct($owner);
    }
    
    public function onRun(int $currentTick) {
        $dir = $this->plugin->getDataFolder() . "rooms/";
        $rooms = array_slice(scandir($dir), 2);
        $this->cfg = new Config($this->plugin->getDataFolder().'config.yml', Config::YAML);
        foreach ($rooms as $g) {
            $boxname = pathinfo($g, PATHINFO_FILENAME);
            $boxfile = new Config($this->plugin->getDataFolder().'/rooms/'.$boxname.'.yml', Config::YAML);
            if(!empty($boxfile->get('youtuber'))){
                $countsign = 0;
                if(!empty($boxfile->get('current'))){
                    $countsign = $countsign + 15 - $boxfile->get('timer');
                }
                $queue = $boxfile->get('queue');
                $countsign = $countsign + count($queue);
                
                $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                foreach ($tiles as $tile){
                    if($tile instanceof Sign){
                        $text = $tile->getText();
                        if($text['0'] === $this->plugin->prefix){
                            if(TextFormat::clean($text['1']) === $boxname){
                                $tile->setLine(2, TextFormat::YELLOW.$countsign.' Sekunden');
                            }
                        }
                    }
                }
                
                
                if(!empty($boxfile->get('current'))){
                    $timer = $boxfile->get('timer');
                    if($timer == 15){
                        
                        $queue = $boxfile->get('queue');
                        if(!empty($queue[0])){
                            $player = $this->plugin->getServer()->getPlayer($boxfile->get('current'));
                            $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                            
                            $pname = $queue[0];
                            $boxfile->set('current', $pname);
                            $new = array_diff($queue, [$queue[0]]);
                            $boxfile->set('timer', 0);
                            $boxfile->set('queue', $new);
                            $boxfile->save();
                                
                            $p = $this->plugin->getServer()->getPlayer($pname);
                    
                            $x = $boxfile->get('x');
                            $y = $boxfile->get('y');
                            $z = $boxfile->get('z');
                            $world = $boxfile->get('world');
                    
                            $p->teleport(new Position($x, $y, $z, $this->plugin->getServer()->getLevelByName($world)));
                            $p->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Du hast 15 Sekunden zeit!');
                            
                            $c = count($queue);
                    
                            $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                            foreach ($tiles as $tile){
                                if($tile instanceof Sign){
                                    $text = $tile->getText();
                                    if($text['0'] === $this->plugin->prefix){
                                        if(TextFormat::clean($text['1']) === $boxname){
                                            $tile->setText(
                                                $this->plugin->prefix,
                                                TextFormat::GOLD.$boxname,
                                                TextFormat::YELLOW.$c * 15 . ' Sekunden',
                                                TextFormat::GREEN.$boxfile->get('youtuber')
                                            );
                                        }
                                    }
                                }
                            }
                        }else{
                            $player = $this->plugin->getServer()->getPlayer($boxfile->get('current'));
                            $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                            
                            $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                            foreach ($tiles as $tile){
                                if($tile instanceof Sign){
                                    $text = $tile->getText();
                                    if($text['0'] === $this->plugin->prefix){
                                        if(TextFormat::clean($text['1']) === $boxname){
                                            $tile->setText(
                                                $this->plugin->prefix,
                                                TextFormat::GOLD.$boxname,
                                                TextFormat::YELLOW.'0 Sekunden',
                                                TextFormat::GREEN.$boxfile->get('youtuber')
                                            );
                                        }
                                    }
                                }
                            }
                            $boxfile->set('current', NULL);
                            $boxfile->set('timer', 0);
                            $boxfile->set('queue', array());
                            $boxfile->save();
                        }
                        
                    }else{
                        $timer = $timer + 1;
                        $boxfile->set('timer', $timer);
                        $boxfile->save();
                    }
                }
            }
        }
    }
}
