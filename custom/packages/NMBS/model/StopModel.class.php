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

class StopModel extends StopModelDriver{
    
    protected $defaultchild = 'cassandra';
    
    /**
     * All names should be lowercase AND the classnames should be 
     * this name capitalized in the front concated with StopModel
     * eg: cassandra -> CassandraStopModel
     */
    private $childs = array('cassandra' => 'CassandraStopModel',
                            'dummy'     => 'DummyStopModel' // Possilbe dummy model: Saves nothing, scrapes everything.
                           );
    
    /**
     * Yes, I'm copying the CI driver pattern
     */
    public function __get( $child ){
        if( !isset($this->childs[$child]) ){
            throw new ResourceTDTException('Invalid StopModelDriver '. $child .'.');
        }
        $classname =  $this->childs[$child];
        $filename = __DIR__ . "/ModelDrivers/" . $classname . ".class.php" ;
        
        require_once($filename);
        $driver = new $classname();
        if( !is_subclass_of($driver, 'StopModelDriver')){
            throw new ResourceTDTException('Invalid StopModelDriver. '. $classname .' is not correctly inherited.');
        }
        return $driver ;
    }
    
    public function save( $country, $company, $stops ){
        return $this->{$this->defaultchild}->save( $country, $company, $stops );
    }
    
    public function exists( $country, $company, $sid ){
        return $this->{$this->defaultchild}->exists( $country, $company, $sid );
    }
    
    public function get( $country, $company, $sid = NULL ){
        return $this->{$this->defaultchild}->get( $country, $company, $sid );
    }
}

abstract class StopModelDriver{
    abstract public function save( $country, $company, $stops );
    abstract public function exists( $country, $company, $sid );
    abstract public function get( $country, $company, $sid = NULL );
}
 
?>
