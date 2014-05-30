<?php
/*
Controller for the Admin Themes page

Author: Mathias Beke
Url: http://denbeke.be
Date: March 2014
*/


namespace Controller\Admin {

	
	require_once(dirname(__FILE__) . '/../controller.php');


	class Themes extends \Controller\Controller {

	
		public $pageName = 'themes';
		public $albums = array();
		public $notification = NULL;
		public $themes = [];
		
		
		public function __construct() {
			parent::__construct();
			$this->themeDir = dirname(__FILE__) . '/../../../admin/theme/';
			$this->theme = 'themes.php';
			$this->pageTitle = 'Themes - ' . SITE_TITLE;
					
			
			$themes = dirname(__FILE__) . '/../../../theme/*';			
			foreach (glob( $themes ) as $dir) {
				$this->themes[] = json_decode( file_get_contents($dir . '/theme.json') );
			}
			
		}
			
	
	}

}

?>
