<?php
/*
   Copyright 2009 P.B. Quist

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

include_once( "chalkweb/classes/template.inc.php" );

class CSubPage extends CTemplate {
	protected $itemCount = 0;
	protected $itemsPerPage = 20;
	protected $currentPage = 1;
	protected $uploaddir = "/tmp/";

	protected $subtitle = "";
	
	protected function getPageCount() {
		return floor($this->itemCount / $this->itemsPerPage);
	}

	protected function getItemPos() {
		return (($this->currentPage - 1) * $this->itemsPerPage);
	}

	protected function hasNextPage() {
		$c = $this->getPageCount();

		return (($c >= 1) && ($this->currentPage <= $c));
	}

	protected function hasPreviousPage() {
		return (($this->getPageCount() > 1) && ($this->currentPage > 1));
	}

	protected function filterArray( $arr ) {
	    $filteredArr = array ();
        $this->itemCount = count($arr);

	    $offset = ($this->currentPage - 1) * $this->itemsPerPage;
	    $i = 0;
        $c = $offset + $this->itemsPerPage;
	    foreach ( $arr as $a => $b ) {
	        if ( $i >= $offset ) {
	            if ( $i == $c ) {
	                break;
	            }
                $filteredArr[$a] = $b;
	        }
	        $i++;
	    }

	    return $filteredArr;
	}

	public function __construct( $file ) {
		parent::__construct( $file );

		$this->currentPage = GetGetVarAsInt("i", 1);
	}

	protected function includeTrailingSlash( $haystack ) {
	   	if ($haystack[strlen($haystack) - 1] === '/') {
			return $haystack;	   		
	   	}
	   	
	   	return $haystack . "/";
	}
		
	public function AllowUpload( $fieldname, $prefunc = false, $postfunc = false ) {
		global $globalErrorHandler;

		if ( !empty($_FILES[$fieldname]) ) {
			if ( $_FILES[$fieldname]['error'] == UPLOAD_ERR_INI_SIZE ) {
				$globalErrorHandler->Warning( "Filesize of '" . $filename . "' exceeds the maximum allowed filesize." );
				return false;
			}
		}

		$filename = $_FILES[$fieldname]['name'];
		$tempname = $_FILES[$fieldname]['tmp_name'];
		$targetfile = $this->includeTrailingSlash($this->uploaddir) . $filename;

		if ( $prefunc ) {
			if ( !HandleCallback1Arg( $prefunc, $_FILES[$fieldname] ) ) {
				return false;
			}
		}

		if ( !file_exists($targetfile) ) {
			if ( !move_uploaded_file( $tempname, $targetfile ) ) {
				$globalErrorHandler->Warning( "Something prevented us from saving your upload '" . $filename . "'." );
			} else {
				if ( $postfunc ) {
					if ( !HandleCallback1Arg( $postfunc, $targetfile ) ) {
						return false;
					}
				}

				return $targetfile;
			}
		} else {
			$globalErrorHandler->Warning( "File '" . $filename . "' already exists, please rename it before uploading." );
		}

		return false;
	}
	
	public function Prepare() {
		// moved assigns to Process so you're not stuck to a mandatory order of calling parent::Prepare()
	}

	public function Process() {
		global $globalCurrentUser;

		$this->AssignCondition( "loggedin", ($globalCurrentUser != null) );

		$this->AssignValue( "page_index", $this->currentPage );
		$this->AssignValue( "page_count", $this->getPageCount() );

		$this->AssignCondition( "page_hasnext", $this->hasNextPage() );
		$this->AssignCondition( "page_hasprevious", $this->hasPreviousPage() );
		$this->AssignValue( "page_previous", $this->currentPage - 1 );
		$this->AssignValue( "page_next", $this->currentPage + 1 );
		
		$this->AssignValue( "subtitle", $this->subtitle );

		return parent::Process();
	}
}

?>