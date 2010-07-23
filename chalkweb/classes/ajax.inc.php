<?php
/*
   Copyright 2010 P.B. Quist

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

include_once( "chalkweb/classes/errorhandling.inc.php" );


interface CAjax {
	public function HandleAjax( $getvars, $postvars );
}


class CAjaxHandler {
	protected $pages = array();

	//-------------------------------------------------
	protected function loadPage( $page, $classname = "" ) {
		include_once( "pages/" . $page . ".php" );

		if ( $classname == "" ) { 
			$classname = "C" . $page;
		}
		$this->handlerObject = new $classname();
	}

	protected function checkAndLoadPage() {
		if ( isset( $_REQUEST['action'] ) ) {
			$selectedPage = $_REQUEST['action'];
		} else {
			$selectedPage = "main";
		}

		foreach ( $this->pages as $page ) {
			if ( $page[0] == $selectedPage ) {
				$classname = $page[1];
				$this->loadPage( $page[0], $classname );

				return $page[0];
			}
		}

		return false;
	}


	//-------------------------------------------------

	public function __construct() {
	}

	public function AddPage( $name, $classname = "" ) {
		$this->pages[] = array ( $name, $classname );
	}

	public function Show() {
		$page = $this->checkAndLoadPage();
		if ( !$page ) {
			return;
		}

		if( $this->handlerObject instanceof CAjax ) {
			echo $this->handlerObject->HandleAjax( $_GET, $_POST );
		}
	}
}

?>