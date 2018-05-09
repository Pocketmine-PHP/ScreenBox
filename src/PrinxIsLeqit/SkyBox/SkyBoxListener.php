<?php

namespace PrinxIsLeqit\SkyBox;

use PrinxIsLeqit\SkyBox\SkyBox;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\tile\Sign;
use pocketmine\level\Position;
use pocketmine\event\player\PlayerQuitEvent;

class SkyBoxListener implements Listener{
    private $plugin;
    
    public function __construct(SkyBox $pl) {
        $this->plugin = $pl;
    }
    
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $block->getLevel()->getTile($block);
        if($this->plugin->player === $player->getName()){
            if($block->getId() == 0){
                return;
            }
            $file = $this->plugin->file;
            if($file instanceof Config){
                if($this->plugin->mode == 1){
                    $x = $block->x;
                    $y = $block->y;
                    $z = $block->z;
                    $world = $block->level->getName();
                
                    $file->set('x', $x);
                    $file->set('y', $y + 1);
                    $file->set('z', $z);
                    $file->set('world', $world);
                    $file->save();
                    
                    $this->plugin->mode = 2;
                    $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Bitte berühre ein Schild!!');
                    return;
                }elseif($this->plugin->mode == 2){
                    if($tile instanceof Sign){
                        $tile->setText(
                                $this->plugin->prefix,
                                TextFormat::GOLD.$file->get('name'),
                                TextFormat::YELLOW.'0 seconds',
                                TextFormat::RED.'CLOSED'
                                );
                        $file->set('youtuber', NULL);
                        $file->set('current', NULL);
                        $file->set('timer', 0);
                        $file->set('queue', array());
                        $file->save();
                        
                        $this->plugin->mode = 0;
                        $this->plugin->player = 0;
                        $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Screenbox erstellt!');
                        return;
                    }else{
                        return;
                    }
                }
            }
        }
        if($tile instanceof Sign){
            $text = $tile->getText();
            if($text['0'] === $this->plugin->prefix){
                $boxname = TextFormat::clean($text['1']);
                $boxfile = new Config($this->plugin->getDataFolder().'/rooms/'.$boxname.'.yml');
                if(empty($boxfile->get('youtuber'))){
                    if($player->hasPermission('skybox.youtuber')){
                        $x = $boxfile->get('x');
                        $y = $boxfile->get('y');
                        $z = $boxfile->get('z');
                        $world = $boxfile->get('world');
                        
                        $boxfile->set('youtuber', $player->getName());
                        $boxfile->save();
                        
                        $player->teleport(new Position($x, $y, $z, $this->plugin->getServer()->getLevelByName($world)));
                        
                        $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Du kannst die ScreenBox jederzeit verlassen mit /skybox leave');
                        
                        $tile->setLine(3, TextFormat::GREEN.$player->getName());
                    }else{
                        $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Diese Boy ist leer!');
                    }
                }else{
                    if(empty($boxfile->get('current'))){
                        $x = $boxfile->get('x');
                        $y = $boxfile->get('y');
                        $z = $boxfile->get('z');
                        $world = $boxfile->get('world');
                        
                        $player->teleport(new Position($x, $y, $z, $this->plugin->getServer()->getLevelByName($world)));
                        
                        $boxfile->set('current', $player->getName());
                        $boxfile->set('timer', 0);
                        $boxfile->save();
                        
                        $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Du hast 15 Sekunden zeit!');
                    }else{
                        $array = $boxfile->get('queue');
                        
                        if(in_array($player->getName(), $array)){
                            $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Du bist schon in der Warteschlange!');
                            return;
                        }
                        
                        array_push($array, $player->getName());
                        $boxfile->set('queue', $array);
                        $boxfile->save();
                        
                        $count = count($array);
                        $player->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Deine Nummer ist' .$count.' in der Warteschlange,warte bis '.$count * 15 . ' Sekunden,dann wirst du teleportiert.');
                    }
                }
            }
        }
    }
    
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        
        $dir = $this->plugin->getDataFolder() . "rooms/";
        $rooms = array_slice(scandir($dir), 2);
        $this->cfg = new Config($this->plugin->getDataFolder().'config.yml', Config::YAML);
        foreach ($rooms as $g) {
            $boxname = pathinfo($g, PATHINFO_FILENAME);
            $boxfile = new Config($this->plugin->getDataFolder().'/rooms/'.$boxname.'.yml', Config::YAML);
            $youtuber = $boxfile->get('youtuber');
            $queue = $boxfile->get('queue');
            if(in_array($player->getName(), $queue)){
                $new = array_diff($queue, [$player->getName()]);
                $boxfile->set('queue', $new);
                $boxfile->save();
                foreach ($queue as $pname){
                    $p = $this->plugin->getServer()->getPlayer($pname);
                    $c = count($queue);
                    $p->sendMessage($this->plugin->prefix.TextFormat::WHITE.' Jetzige Zeit: '. $c * 15 . ' sekunden');
                    
                    $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                    foreach ($tiles as $tile){
                        if($tile instanceof Sign){
                            $text = $tile->getText();
                            if($text['0'] === $this->plugin->prefix){
                                if(TextFormat::clean($text['1']) === $boxname){
                                        $tile->setText(
                                        $this->plugin->prefix,
                                        TextFormat::GOLD.$boxname,
                                        TextFormat::YELLOW.$c * 15 .' seconds',
                                        TextFormat::GREEN.$boxfile->get('youtuber')
                                    );
                                }
                            }
                        }
                    }
                }
            }
            if($boxfile->get('current') === $player->getName()){
                $queue = $boxfile->get('queue');
                if(!empty($queue[0])){
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
                                        TextFormat::YELLOW.$c * 15 . ' seconds',
                                        TextFormat::GREEN.$boxfile->get('youtuber')
                                    );
                                }
                            }
                        }
                    }
                }else{
                    $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                    foreach ($tiles as $tile){
                        if($tile instanceof Sign){
                            $text = $tile->getText();
                            if($text['0'] === $this->plugin->prefix){
                                if(TextFormat::clean($text['1']) === $boxname){
                                    $tile->setText(
                                        $this->plugin->prefix,
                                        TextFormat::GOLD.$boxname,
                                        TextFormat::YELLOW.'0 seconds',
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
            }
            if(!empty($boxfile->get('youtuber'))){
                if($boxfile->get('youtuber') == $player->getName()){
                    if(!empty($boxfile->get('current'))){
                        $p = $this->plugin->getServer()->getPlayer($boxfile->get('current'));
                        $p->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                        $p->sendMessage($this->plugin->prefix.TextFormat::WHITE.' '.$boxfile->get('youtuber').' hat verlassen');
                    }
                    $queue = $boxfile->get('queue');
                    foreach ($queue as $pname){
                        $p = $this->plugin->getServer()->getPlayer($pname);
                        $p->sendMessage($this->plugin->prefix.' '.$boxfile->get('youtuber').' hat verlassen!');
                    }
                    $boxfile->set('youtuber', NULL);
                    $boxfile->set('queue', array());
                    $boxfile->set('current', NULL);
                    $boxfile->set('timer', 0);
                    $boxfile->save();
                    
                    $tiles = $this->plugin->getServer()->getDefaultLevel()->getTiles();
                    foreach ($tiles as $tile){
                        if($tile instanceof Sign){
                            $text = $tile->getText();
                            if($text['0'] == $this->plugin->prefix){
                                if(TextFormat::clean($text['1']) == $boxname){
                                    $tile->setText(
                                        $this->plugin->prefix,
                                        TextFormat::GOLD.$boxname,
                                        TextFormat::YELLOW.'0 seconds',
                                        TextFormat::RED.'CLOSED'
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}