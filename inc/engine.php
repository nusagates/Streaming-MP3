<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of engine
 *
 * @author nusagates<nusagates@gmail.com>
 * @version  1.0
 * @category Core engine
 * @http://opensource.org/licenses/gpl-license.php GNU Public License
 */
class Engine {

    private $database;
    private $id;
    private $track_query;
    private $track_count;
    private $track_item;

    public function __construct($db) {
        $this->database = $db;
        $this->id = isset($_GET['id']) ? $_GET['id'] : "0";
        $track_query = $this->database->prepare("select * from track where track_id=?");
        $track_query->execute(array($this->id));
        $this->track_count = $track_query->rowCount();
        $this->track_item = $track_query->fetchObject();
    }

    function getTrackTitle() {
        if ($this->track_count > 0) {
            return $this->track_item->track_name;
        }else{
            return "Streaming MP3";
        }
    }
    function getTrackSource(){
        if ($this->track_count > 0) {
            return $this->track_item->track_source;
        }else{
            return "No Source Found";
        }
    }
    function setAudio(){
        if ($this->track_count > 0) {
        echo '<amp-audio autoplay width="auto" height="100" src="'.$this->getTrackSource().'" title="'.$this->getTrackTitle().'">
            <div fallback>Browser Anda tidak mendukung untuk memainkan MP3 di halaman ini!</div>
            <source type="audio/mpeg" src="'.$this->getTrackSource().'">
            </amp-audio>';
        }else{
            echo "No audio found with given id";
        }
    }

}
