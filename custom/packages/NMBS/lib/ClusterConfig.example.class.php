<?php
/**
 * @copyright (C) 2012 by iRail vzw/asbl
 * @license AGPLv3
 * @author Hannes Van De Vreken <hannes aŧ irail.be>
 */
class ClusterConfig {
    /**
     * All ip addresses in your cluster. Connection is made on a random basis by the phpcassa ConnectionPool class
     */
    const SERVERS  = array( "192.168.0.2", "192.168.0.3");
    
    /**
     * more info about authentication:
     * http://www.datastax.com/docs/1.1/configuration/authentication
     */
    const USERNAME = "";
    const PASSWD   = ""; // not md5 hashed
    
    /**
     * choose a name (like a tablespace in a traditional RDBMS)
     */
    const KEYSPACE = "";
    
    /**
     * more info:
     * http://www.datastax.com/docs/1.1/cluster_architecture/replication
     * This variable is only used one, at schema definition
     */
    const REPLICATION_FACTOR = 1 ;
    
}
?>
