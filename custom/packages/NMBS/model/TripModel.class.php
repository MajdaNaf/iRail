<?php
/**
 * Copyright (C) 2012 by iRail vzw/asbl
 *
 * @author Hannes Van De Vreken (hannes aŧ irail.be) 
 * @license AGPLv3
 * 
 * Yes, if you didn't knew this already, I got my secret sauce for this from the CI framework
 * this is a driver class for saving stop data.
 * Possible drivers are: mysql driver
 *                       cassandra driver
 *                       .*db driver (eg: couchDB)
 *                       gtfs driver (save and load from gtfs files)
 *
 */

class TripModel extends TripModelDriver{
    
    protected $defaultchild = 'cassandra';
    
    /**
     * All names should be lowercase AND the classnames should be 
     * this name capitalized in the front concated with StopModel
     * eg: cassandra -> CassandraTripModel
     */
    private $childs = array('cassandra' => 'CassandraTripModel',
                            'dummy'     => 'DummyTripModel' // Possilbe dummy model: Saves nothing, scrapes everything.
                           );
    
    /**
     * Yes, I'm copying the CI driver pattern
     */
    public function __get( $child ){
        if( !isset($this->childs[$child]) ){
            throw new ResourceTDTException('Invalid TripModelDriver '. $child .'.');
        }
        $classname =  $this->childs[$child];
        $filename = __DIR__ . "/ModelDrivers/" . $classname . ".class.php" ;
        
        require_once($filename);
        $driver = new $classname();
        if( !is_subclass_of($driver, 'TripModelDriver')){
            throw new ResourceTDTException('Invalid TripModelDriver. '. $classname .' is not correctly inherited.');
        }
        return $driver ;
    }
    
    public function save( $country, $company, $trip ){
        return $this->{$this->defaultchild}->save( $country, $company, $trip );
    }
    
    public function exists( $country, $company, $tid, $iteration ){
        return $this->{$this->defaultchild}->exists( $country, $company, $tid, $iteration );
    }
    
    /**
     * @param $date 'yyyymmdd' formatted string
     */
    public function get( $country, $company, $date, $tid, $iteration = NULL ){
        return $this->{$this->defaultchild}->get( $country, $company, $date, $tid, $iteration );
    }
}

abstract class TripModelDriver{
    abstract public function save( $country, $company, $trip );
    abstract public function exists( $country, $company, $trip, $iteration );
    abstract public function get( $country, $company, $date, $tid, $iteration = NULL );
}
 
?>
