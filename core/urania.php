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
    private $uploadDir;
    
    /**
    Constructor
    
    @param path to config file
    */
    public function __construct($config = "./config.php") {
        require($config);
        $this->db_table_albums = $db_table_albums;
        $this->db_table_images = $db_table_images;
        $this->database = new Database($db_host, $db_user, $db_password, $db_database);
        $this->uploadDir = $uploadDir;
    }
    
    
    
    /**
    Add a new photo album to the database with the given name
    @param name
    @pre there is no album with the given name
    */
    public function addAlbum($albumName) {
    
    	if($this->albumNameExists($albumName)) {
    		throw new Exception("There is already an album with the name '$albumName'");
    	}
    	else {
    
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
	        
	        //Add new directory to the upload folder
	        mkdir($this->uploadDir . '/' . $this->simplifyFileName($albumName));
	        
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
    
    
    
    
    /**
    Upload image to the server and add the info to the database
    
    @param uploaded file
    @param album id
    
    @pre Album with given albumId exists
    */
    public function uploadImage($imageFile, $albumId) {    	
    	//move_uploaded_file($image['tmp_name'], $this->uploadDir . $image['name']);
    	
    	/* 
    	Store
    	- image name (without extension)
    	- date
    	- album name
    	*/
    	$imageName = $this->removeExtension($imageFile['name']);
    	$imageDate = time();
    	$albumName = $this->simplifyFileName($this->getAlbumName($albumId));
    	
    	//Get the filename of the image
    	$fileName = $albumName . '/' . $this->simplifyFileName($imageFile['name']);

		//Check if this file name is unique
		//If it exists, we add a suffix to it and check again if it's unique    	
    	if($this->fileNameExists($fileName)) {
    		$suffix = 2;
    		while ($this->fileNameExists($this->addSuffix($fileName, "-" . $suffix))) {
    			$suffix++;
    		}
    		$fileName = $this->addSuffix($fileName, "-" . $suffix);
    	}
    	
    	//Upload the file
    	move_uploaded_file($imageFile['tmp_name'], $this->uploadDir . $fileName);
    	
    	
    	//Insert the image in the database
    	$image = new Image(0, $fileName, $imageName, $imageDate, $albumId);
    	$this->addImage($image);
    	
    	
    }
    
    
    
    
    /**
    Get the name of the album with the given id
    
    @param id
    @return album  name
    @pre albume exists
    */
    private function getAlbumName($id) {
    	if(!$this->albumExists($id)) {
    	    throw new Exception("There is no album with the id $id");
    	}
    	else {
    	
 
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
    	    
    	    return $album->getName();
    	    
    	}
    	
    }
    
    
    
    /**
    Remove the extention from a file name
    
    @param file name
    @return string
    */
    private function removeExtension($fileName) {
		$dotIndex = 0;
		for ($i = strlen($fileName)-1; $i > 0; $i--) {
			if($fileName[$i] == '.'){
				$dotIndex = $i;
				break;
			}
		}
		return substr($fileName, 0, $dotIndex);
    }
    
    
    
    /**
    Simplify a file name to store the file on the disk
    
    @param file name
    @return string
    */
    private function simplifyFileName($fileName) {
    	$table = array(
    	    'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
    	    'ă' => 'a', 'ā' => 'a', 'ą' => 'a', 'æ' => 'a', 'ǽ' => 'a', 'þ' => 'b',
    	    'ç' => 'c', 'č' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ż' => 'z',
    	    'đ' => 'd', 'ď' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
    	    'ĕ' => 'e', 'ē' => 'e', 'ę' => 'e', 'ė' => 'e', 'ĝ' => 'g', 'ğ' => 'g',
    	    'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i',
    	    'î' => 'i', 'ï' => 'i', 'į' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i',
    	    'ı' => 'i', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ĺ' => 'l', 'ļ' => 'l',
    	    'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n',
    	    'ņ' => 'n', 'ŋ' => 'n', 'ŉ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o',
    	    'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o',
    	    'œ' => 'o', 'ð' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's',
    	    'ŝ' => 's', 'ś' => 's', 'ş' => 's', 'ŧ' => 't', 'ţ' => 't', 'ť' => 't',
    	    'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u',
    	    'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ẁ' => 'w',
    	    'ẃ' => 'w', 'ẅ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z',
    	    'ź' => 'z',
    	);
    	
    	// We don't deal with uppercase characters
    	$fileName = strtolower($fileName);
    	
    	// Strip accents
    	$fileName = strtr($fileName, $table);
    	
    	// Non-alphanumericals characters become spaces
    	$fileName = preg_replace('/[^a-z0-9.]/', ' ', $fileName);
    	
    	// Remove trailing and ending spaces
    	$fileName = trim($fileName);
    	
    	// Spaces become -
    	$fileName = preg_replace('#\s+#', '-', $fileName);
    	
    	return $fileName;
    }
    
    
    
    /**
    Check if the image with the given file name exists in the upload folder
    
    @param file name
    @return true/false
    */
    private function fileNameExists($fileName) {
    	return file_exists($this->uploadDir . $fileName);
    }
    
    
    /**
    Adds a suffix after the file name, but before the extension
    
    @param file name
    @param suffix
    @return string
    */
    private function addSuffix($fileName, $suffix) {
    	$dotIndex = 0;
    	for ($i = strlen($fileName)-1; $i > 0; $i--) {
    		if($fileName[$i] == '.'){
    			$dotIndex = $i;
    			break;
    		}
    	}
    	$baseName = substr($fileName, 0, $dotIndex);
    	$extension = substr($fileName, $dotIndex);
    	return $baseName . $suffix . $extension;
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
    
    
    private function albumNameExists($albumName) {
    	//Create query
    	$albums = $this->database->escape($this->db_table_albums);
    	$albumName = $this->database->escape($albumName);
    	
    	$query = 
    	"
    	SELECT * 
    	FROM  `$albums` 
    	WHERE  `name` = '$albumName'
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