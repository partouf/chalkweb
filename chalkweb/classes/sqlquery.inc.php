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

include_once( "chalkweb/classes/database.inc.php" );

define( "dtRaw", 0 );
define( "dtString", 1 );
define( "dtInteger", 2 );
define( "dtTimestamp", 3 );
define( "dtIdentifier", 10 );

function paramlengthsort( $a, $b ) {
	$k = strlen($a);
	$l = strlen($b);
	if ( $k == $l ) {
		return 0;
	}
	return ($k > $l) ? -1 : 1;
}

class CSQLQuery {
	protected $currentQuery = "";
	protected $resolvedQuery = "";
	protected $parameters = array();
	protected $db = null;

	protected $currentResource = null;
	protected $currentRecord = null;

	protected function resolveParams() {
		$this->resolvedQuery = "" . $this->currentQuery;

		uksort( $this->parameters, "paramlengthsort" );

		foreach ( $this->parameters as $param => $value ) {
			$iType = $value[0];
			$aValue = $value[1];
			$sParam = ":" . $param;

			if ( $iType == dtRaw ) {
				$this->resolvedQuery = str_replace( $sParam, $aValue, $this->resolvedQuery );
			} else if ( $iType == dtString ) {
				$this->resolvedQuery = str_replace( $sParam, "'" . $this->db->Escape( $aValue ) . "'", $this->resolvedQuery );
			} else if ( $iType == dtInteger ) {
				$this->resolvedQuery = str_replace( $sParam, $aValue, $this->resolvedQuery );
			} else if ( $iType == dtTimestamp ) {
				// mysql specific
				$this->resolvedQuery = str_replace( $sParam, "'" . date( "Y-m-d H:i:s", $aValue ) . "'", $this->resolvedQuery );
			} else if ( $iType == dtIdentifier ) {
				// mysql specific
				$this->resolvedQuery = str_replace( $sParam, "`" . $this->db->Escape( $aValue ) . "`", $this->resolvedQuery );
			}
		}
	}

	// ---------------------------------------------------------

	public function __construct( &$database ) {
		$this->db = $database;
	}

	public function __destruct() {
		$this->Close();
	}

	public function SetQuery( $sql ) {
		$this->currentQuery = $sql;
	}

	public function GetResolvedQuery() {
		$this->resolveParams();

		return $this->resolvedQuery;
	}

	public function AddParam( $iType, $sName, $aValue, $bTypeMismatchIsFatal = true ) {
		global $globalErrorHandler;

		if ( $iType == dtInteger ) {
			if ( !is_numeric( $aValue ) ) {
				if ( $bTypeMismatchIsFatal ) {
					$globalErrorHandler->Fatal( "CSQLQuery(" . $this->currentQuery . ") -> Value for " . $sName . " is not a valid integer." );
				}
				return false;
			}
		} else if ( $iType == dtTimestamp ) {
			if ( !is_numeric( $aValue ) ) {
					if ( $bTypeMismatchIsFatal ) {
						$globalErrorHandler->Fatal( "CSQLQuery(" . $this->currentQuery . ") -> Value for " . $sName . " is not a valid timestamp." );
					}
					return false;
			}
		}

		$this->parameters[$sName] = array( $iType, $aValue );

		return true;
	}

	public function Open() {
		$this->resolveParams();

		$this->currentResource = $this->db->Query( $this->resolvedQuery );

		if ( $this->currentResource ) {
			return true;
		}
		return false;
	}

	public function Next( $type = MYSQL_ASSOC ) {
		if ( $this->currentResource ) {
			$this->currentRecord = mysql_fetch_array( $this->currentResource, $type );
		} else {
			return false;
		}

		if ( $this->currentRecord ) {
			return true;
		}
		return false;
	}

	public function Close() {
		if ( ($this->currentResource != null) && ($this->currentResource !== true) ) {
			mysql_free_result( $this->currentResource );
		}

		$this->currentResource = null;
		$this->currentRecord = null;
	}

	public function GetResource() {
		return $this->currentResource;
	}

	public function GetFieldValue( $field ) {
		return $this->currentRecord[$field];
	}

	public function GetArray() {
		return $this->currentRecord;
	}
}

// ------------------------------------------------------------------

function SelectMax( $db, $table, $field, $where = "" ) {
	global $globalErrorHandler;
	$basequery = "select max(:field) from :table";

	$qrySelect = new CSQLQuery( $db );
	if ( $where != "" ) {
		$qrySelect->SetQuery( $basequery . " where " . $where );
	} else {
		$qrySelect->SetQuery( $basequery );
	}

	$qrySelect->AddParam( dtIdentifier, "field", $field );
	$qrySelect->AddParam( dtIdentifier, "table", $table );

	$c = 0;
	if ( $qrySelect->Open() ) {
		if ( $qrySelect->Next() ) {
			$c = $qrySelect->GetFieldValue(0);
		}
		$qrySelect->Close();
	} else {
		$globalErrorHandler->Fatal( $db->GetLastError() );
	}

	return $c;
}

?>