<?php
/**
 * Copyright (C) 2011 by iRail vzw/asbl
 *
 * @author Jens Segers
 * @author  Pieter Colpaert <pieter aŧ iRail.be>
 * @license AGPLv3
 *
 */

include_once ("custom/packages/NMBS/Stations.class.php");
include_once ("custom/packages/iRailVehicle.class.php");
include_once ("custom/packages/NMBS/simple_html_dom.php");

class NMBSVehicle extends iRailVehicle {
    
    private $html;
    
    public function call() {
        $o = new stdClass();
        $o->vehicle = $this->getVehicle($this->id);
        $o->stops = $this->getStops($this->id);
        return $o;
    }
    
    public function getVehicle($id) {
        if ($this->html) {
            $html = $this->html;
        } else {
            $url = "http://www.railtime.be/mobile/HTML/TrainDetail.aspx";
            $id = preg_replace("/.*?(\d.*)/smi", "\\1", $id);
            $url .= "?l=" . $this->lang . "&tid=" . $id . "&dt=" . urlencode(date('d/m/Y'));
            
            $request = TDT::HttpRequest($url);
            if (isset($request->error)) {
                throw new HttpOutTDTException($url);
            }
            $html = $request->data;
        }
        
        $html = str_get_html($html);
        $nodes = $html->find("td[class*=TrainReperage]");
        if ($nodes) {
            $station = $nodes[0]->parent()->children(1)->first_child()->plaintext;
        }
        
        $vehicle = new stdClass();
        $vehicle->name = "BE.NMBS." . $id;
        
        if (isset($station)) {
            $now = NMBSStations::getStationFromName($station, $this->lang);
            $vehicle->longitude = $now->longitude;
            $vehicle->latitude = $now->latitude;
        } else {
            $vehicle->longitude = 0;
            $vehicle->latitude = 0;
        }
        return $vehicle;
    }
    
    public function getStops($id) {
        if ($this->html) {
            $html = $this->html;
        } else {
            $url = "http://www.railtime.be/mobile/HTML/TrainDetail.aspx";
            $id = preg_replace("/.*?(\d.*)/smi", "\\1", $id);
            $url .= "?l=" . $this->lang . "&tid=" . $id . "&dt=" . urlencode($this->day . "/" . $this->month . "/" . $this->year);
            
            $request = TDT::HttpRequest($url);
            if (isset($request->error)) {
                throw new HttpOutTDTException($url);
            }
            $html = $request->data;
        }
        
        $stops = array();
        
        $html = str_get_html($html);
        $nodes = $html->find("tr.rowHeightTraject");
        $i = 0;
        foreach ($nodes as $node) {
            $row_delay = str_replace("'", '', str_replace('+', '', trim($node->children(3)->first_child()->plaintext)));
            if (isset($row_delay)) {
                $arr = array();
                $arr = explode(":", $row_delay);
                if (isset($arr[1])) {
                    $delay = (60 * $arr[0] + $arr[1]) * 60;
                } else {
                    $delay = $row_delay * 60;
                }
            } else {
                $delay = 0;
            }
            
            $stop = new stdClass();
            $station = new stdClass();
            $station = NMBSStations::getStationFromName($node->children(1)->first_child()->plaintext, $this->lang);
            $stop->station = $station;
            $stop->delay = $delay;
            $stop->time = iRailTools::transformTime("00d" . $node->children(2)->first_child()->plaintext . ":00", date("Ymd"));
            $stop->iso8601 = date(DateTime::ISO8601, $stop->time);
            
            $stops[$i] = $stop;
            
            $i++;
        }
        
        return $stops;
    }

}

?>
