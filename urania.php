<?php 
/*
Simple CMS for image galleries

Author: Mathias Beke
Url: http://denbeke.be
Date: September 2013
*/


require_once(dirname(__FILE__).'/album.php');
require_once(dirname(__FILE__).'/image.php');
require_once(dirname(__FILE__).'/database.php');


class Urania {
    
    private $debug = true;
    private $database;
    private $db_table_albums;
    private $db_table_images;
    
    /**
    Constructor
    
    @param path to config file
    */
    public function __construct($config = "./config.php") {
        require($config);
        $this->db_table_albums = $db_table_albums;
        $this->db_table_images = $db_table_images;
        $this->database = new Database($db_host, $db_user, $db_password, $db_database);
    }
    
    
    
    /**
    Add a new photo album to the database with the given name
    @param name
    */
    public function addAlbum($albumName) {
    
        //Create query
        $albums = $this->database->escape($this->db_table_albums);
        $albumName = $this->database->escape($albumName);
        $date = time();
        
        $query = 
        "
        INSERT INTO  `$albums` (
        `id` ,
        `name` ,
        `date`
        )
        VALUES (
        NULL ,  '$albumName',  '$date'
        );
        ";
        
        $affectedRows = $this->database->doQuery($query);
        
        //Debug
        if($this->debug) {
            echo "$affectedRows affected rows with query<br>$query";
        }
        
    }
    
    
    /**
    Add the given image to the database
    
    @param image
    */
    public function addImage($image) {
    
        //Escape strings
        $fileName = $this->database->escape($image->getFileName());
        $name = $this->database->escape($image->getName());
        $date = $this->database->escape($image->getDate());
        $albumId = $this->database->escape($image->getAlbumId());
    
        $query = 
        "
        INSERT INTO  `Images` (
        `id` ,
        `fileName` ,
        `name` ,
        `date` ,
        `albumId`
        )
        VALUES (
        NULL ,  '$fileName',  '$name',  '$date',  '$albumId'
        );
        ";
        
        $affectedRows = $this->database->doQuery($query);
        
        if($this->debug) {
            echo "$affectedRows affected rows with query<br>$query";
        }
    
    }
    
    
    /**
    Get a list of all albums
    
    @return albums (without images included)
    */
    public function getAllAlbums() {
        
        //Create query
        $albums = $this->database->escape($this->db_table_albums);
    
        $query = 
        "
        SELECT * 
        FROM  `$albums` 
        ORDER BY  `Albums`.`date` DESC 
        ";
        
        $result = $this->database->getQuery($query);
        
        //Debug           
        if($this->debug) {
            echo $query;
        }
        
        $albums = array();
        
        foreach ($result as $row => $album) {
            $albums[] = new Album($album['id'], $album['name'], $album['date']);
        }
        
        return $albums;
        
    }
    
    
    
    /**
    Get the album with the given id
    
    @param id
    @return album
    
    @pre id exists
    */
    public function getAlbum($id) {
        if(!$this->albumExists($id)) {
            throw new Exception("There is no album with the id $id");
        }
        else {
            
            //Get all images from the album
            $images = $this->getImagesFromAlbum($id);
        
        
            //Get album information
            //Create query
            $albums = $this->database->escape($this->db_table_albums);
            $id = $this->database->escape($id);
            
            $query = 
            "
            SELECT * 
            FROM  `$albums` 
            WHERE id = $id
            ";
            
            //Debug
            if($this->debug) {
                echo $query;
            }
            
            //Fetch query
            $result = $this->database->getQuery($query);
            $album = new Album($result[0]['id'], $result[0]['name'], $result[0]['date']);
            
            foreach ($images as $image) {
                $album->addImage($image);
            }
            
            return $album;
            
        }
    }
    
    
    /**
    Get the image with the given id
    
    @param id
    @return image
    
    @pre image exists
    */
    public function getImage($id) {
        if(!$this->imageExists($id)) {
            throw new Exception("There is no image with the id $id");
        }
        else {
            
            //Create query
            $images = $this->database->escape($this->db_table_images);
            $id = $this->database->escape($id);
            
            $query = 
            "
            SELECT * 
            FROM  `$images` 
            WHERE id = $id
            ";
            
            //Debug
            if($this->debug) {
                echo $query;
            }
            
            //Fetch query
            $result = $this->database->getQuery($query);
            $image = new Image($result[0]['id'], $result[0]['fileName'], $result[0]['name'], $result[0]['date'], $result[0]['albumId']);
            
            return $image;
        }
    }
    
    
    
    /**
    Change update the information of the given image in the database
    
    @param image
    @pre image exists
    */
    public function changeImage($image) {
        if(!$this->imageExists($image->getId())) {
            throw new Exception("There is no image with the id $id");
        }
        else {
            //Create query from image
            $id = $this->database->escape($image->getId());
            $fileName = $this->database->escape($image->getfileName());
            $name = $this->database->escape($image->getName());
            $date = $this->database->escape($image->getDate());
            $albumId = $this->database->escape($image->getAlbumId());
            $images = $this->database->escape($this->db_table_images);
            
            $query = 
            "
            UPDATE  `$images` SET  `fileName` = '$fileName',
            `name` =  '$name',
            `date` =  '$date',
            `albumId` =  '$albumId' WHERE  `$images`.`id` = $id;
            ";
            
            $affectedRows = $this->database->doQuery($query);
            
            //Debug
            if($this->debug) {
                echo "$affectedRows affected rows with query<br>$query";
            }
            
        }
    }
    
    
    /**
    Change update the information of the given album in the database
    
    @param album
    @pre album exists
    */
    public function changeAlbum($album) {
        if(!$this->albumExists($album->getId())) {
            throw new Exception("There is no album with the id $id");
        }
        else {
            //Create query from image
            $id = $this->database->escape($album->getId());
            $name = $this->database->escape($album->getName());
            $date = $this->database->escape($album->getDate());
            $albums = $this->database->escape($this->db_table_albums);
            
            $query = 
            "
            UPDATE  `$albums` SET `name` =  '$name',
            `date` =  '$date' WHERE  `$albums`.`id` = $id;
            ";
            
            $affectedRows = $this->database->doQuery($query);
            
            //Debug
            if($this->debug) {
                echo "$affectedRows affected rows with query<br>$query";
            }
            //TODO not yet tested
        }
    }
    
    
    
    /**
    Delete the image with the given id
    
    @param id   
    @pre image exists
    */
    public function deleteImage($id) {
        if(!$this->imageExists($id)) {
            throw new Exception("There is no image with the id $id");
        }
        else {
            //Find image
            //Create query
            $images = $this->database->escape($this->db_table_images);
            $id = $this->database->escape($id);
            
            $query = 
            "
            SELECT * 
            FROM  `$images` 
            WHERE  `id` = $id
            LIMIT 0 , 30
            ";
            
            //DEBUG
            if($this->debug) {
                echo $query;
            }
            
            //Fetch query
            $result = $this->database->getQuery($query);
            $image = new Image($result[0]['id'], $result[0]['fileName'], $result[0]['name'], $result[0]['date'], $result[0]['albumId']);
            
            
            //Delete image file
            //TODO!!!
            
            //Delete image in the database
            $query = "DELETE FROM `$images` WHERE `$images`.`id` = $id";
            $this->database->doQuery($query);
            
           
            //Debug           
            if($this->debug) {
                echo $query;
            }
        }
    }
    
    
    
    /**
    Delete the album with the given id
    
    @param id   
    @pre album exists
    */
    public function deleteAlbum($id) {
        if(!$this->albumExists($id)) {
            throw new Exception("There is no album with the id $id");
        }
        else {
            //TODO
            //Find all images from the album
            //Delete image files
            //Delete images in the database
            //Delete album in the database
        }
    }
    
    
    
    private function albumExists($id) {
        
        //Create query
        $albums = $this->database->escape($this->db_table_albums);
        $id = $this->database->escape($id);
        
        $query = 
        "
        SELECT * 
        FROM  `$albums` 
        WHERE  `id` = $id
        LIMIT 0 , 30
        ";
    
        //DEBUG
        if($this->debug) {
            echo $query;
        }
        
        //Fetch query
        $result = $this->database->getQuery($query);
        
        //Check if there is a result (album)
        if(sizeof($result) > 0) {
            return true;
        }
        else {
            return false;
        }
        
    }
    
    
    private function imageExists($id) {
        
        //Create query
        $images = $this->database->escape($this->db_table_images);
        $id = $this->database->escape($id);
        
        $query = 
        "
        SELECT * 
        FROM  `$images` 
        WHERE  `id` = $id
        LIMIT 0 , 30
        ";
        
        //DEBUG
        if($this->debug) {
            echo $query;
        }
        
        //Fetch query
        $result = $this->database->getQuery($query);
        
        //Check if there is a result (album)
        if(sizeof($result) > 0) {
            return true;
        }
        else {
            return false;
        }
        
    }
    
    
    private function getImagesFromAlbum($albumId) {
        
        //Create query
        $images = $this->database->escape($this->db_table_images);
        $albumId = $this->database->escape($albumId);
        
        $query = 
        "
        SELECT * 
        FROM  `$images` 
        WHERE  `albumId` = $albumId
        LIMIT 0 , 30
        ";
        
        //DEBUG
        if($this->debug) {
            echo $query;
        }
        
        //Fetch query
        $result = $this->database->getQuery($query);
        
        //Create images from result
        $images = array();
        
        foreach ($result as $row => $image) {
            $images[] = new Image($image['id'], $image['fileName'], $image['name'], $image['date'], $image['albumId']);
        }
        
        return $images;
    }
}



?>