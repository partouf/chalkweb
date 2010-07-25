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
	public function PreHandleAjax( $ajhandler );
	public function PostHandleAjax( $ajhandler, $funcreturn );
}


class CAjaxHandler {
	protected $pages = array();
	public $allowedfuncs = array();

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

	protected function HandleFunctionCall( $obj, $func, $args ) {
		$kvpairs = array();
		$callarr = array();
		foreach ( $args as $name => $type ) {
			$value = null;
			switch ( $type ) {
				case dtTimestamp:
				case dtInteger:
					$value = GetPostVarAsIntVar( $name, 0 );
					break;
				default:
					$value = GetRawPostVar( $name, "" );
					break;
			}
			$kvpairs[$name] = $value;
			$callarr[] = $value;
		}
		
		return call_user_func_array( array( $obj, $func ), $callarr );
	}
	
	protected function HandleFunctions( $obj, $allowedfuncs ) {
		$func = $_POST['func'];
		
		$ret = false;
		if ( isset($allowedfuncs[$func]) ) {
			$ret = $this->HandleFunctionCall( $obj, $func, $allowedfuncs[$func] );
		}
		
		return $ret;
	}
	
	public function Show() {
		$page = $this->checkAndLoadPage();
		if ( !$page ) {
			return;
		}

		if( $this->handlerObject instanceof CAjax ) {
			$this->handlerObject->PreHandleAjax( $this );
			$ret = $this->HandleFunctions( $this->handlerObject, $this->allowedfuncs );
			echo $this->handlerObject->PostHandleAjax( $this, $ret );
		} else {
			echo "Fatal error: class doesn't implement CAjax";
		}
	}
}

?>