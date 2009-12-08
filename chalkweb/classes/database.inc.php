<?
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

include_once( "chalkweb/classes/errorhandling.inc.php" );


class CDBConnection {
	protected $lastError;
	protected $queries;

	public function __construct() {
		$this->queries = array();
	}

	public function Query( $sql ) {
		$this->queries[] = array ( "query" => $sql );
	}

	public function GetAllQueries() {
		return $this->queries;
	}
}


class CMySQLDB extends CDBConnection {
	protected $server;
	protected $port;

	protected $dbname;
	protected $username;
	protected $password;

	protected $link;

	// ----------------------------------------------------
	protected function connectToServer() {
		if ( $this->port != 3306 ) {
			$this->link = mysql_connect( $this->server . ":" . $this->port, $this->username, $this->password, true );
		} else {
			$this->link = mysql_connect( $this->server, $this->username, $this->password, true );
		}

		if ( !$this->link ) {
			$this->lastError = $this->GetLastError();

			return false;
		}

		if ( !mysql_set_charset( "utf8", $this->link ) ) {
			$this->lastError = $this->GetLastError();

			return false;
		}

		return true;
	}

	protected function selectDatabase() {
		$ok = mysql_select_db( $this->dbname, $this->link );
		if ( !$ok ) {
			$this->lastError = $this->GetLastError();
		}

		return $ok;
	}

	// ----------------------------------------------------
	public function __construct( $dbname, $server = "localhost", $port = 3306, $user = "root", $pass = "" ) {
		global $globalErrorHandler;

		parent::__construct();

		$this->server = $server;
		$this->port = $port;

		$this->dbname = $dbname;

		$this->username = $user;
		$this->password = $pass;

		if ( !$this->connectToServer() ) {
			$globalErrorHandler->Fatal( "Can't connect to database server (" . $this->lastError . ")" );
		} else {
			if ( !$this->selectDatabase() ) {
				$globalErrorHandler->Fatal( "Unable to select database (" . $this->lastError . ")" );
			}
		}
	}

	public function GetInsertId() {
		return mysql_insert_id( $this->link );
	}

	public function AffectedRows() {
		return mysql_affected_rows( $this->link );
	}

	public function Query( $sql ) {
		parent::Query( $sql );

		$res = mysql_query( $sql, $this->link );
		if ( !$res ) {
			$this->lastError = $this->GetLastError();
		}

		return $res;
	}

	public function Escape( $s ) {
		return mysql_real_escape_string( $s, $this->link );
	}

	public function GetLastError() {
		if ( !$this->link ) {
			return mysql_error();
		} else {
			return mysql_error( $this->link );
		}
	}
}

?>