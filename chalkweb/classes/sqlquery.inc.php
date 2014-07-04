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

define( "dtBoolean", 6 );

define( "dtIdentifier", 10 );
define( "dtRawEscaped", 11 );

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
    protected $resolvedPDOQuery = "";
	protected $parameters = array();
	protected $db = null;

	protected $currentResource = null;
	protected $currentRecord = null;

	protected function resolveParams() {
		$this->resolvedQuery = "" . $this->currentQuery;
        $this->resolvedPDOQuery = "" . $this->currentQuery;

		uksort( $this->parameters, "paramlengthsort" );
		foreach ( $this->parameters as $param => $value ) {
			$iType = $value[0];
			$aValue = $value[1];
			$sParam = ":" . $param;

            // if :param occurs more than once, emit error
            $c = substr_count($this->resolvedPDOQuery, $sParam);
            if ($c > 1) {
                throw new Exception("Can't use the same parameter more than once ($sParam in query \"" . $this->currentQuery . "\")");
            }

            if ( $iType == dtRaw ) {
				$this->resolvedQuery = str_replace( $sParam, $aValue, $this->resolvedQuery );

                $this->resolvedPDOQuery = str_replace( $sParam, $aValue, $this->resolvedPDOQuery );
			} else if ( $iType == dtRawEscaped ) {
				$this->resolvedQuery = str_replace( $sParam, $this->db->Escape( $aValue ), $this->resolvedQuery );

                $this->resolvedPDOQuery = str_replace( $sParam, $this->db->Escape( $aValue ), $this->resolvedPDOQuery );
			} else if ( $iType == dtString ) {
				$this->resolvedQuery = str_replace( $sParam, "'" . $this->db->Escape( $aValue ) . "'", $this->resolvedQuery );
			} else if ( $iType == dtInteger ) {
				$this->resolvedQuery = str_replace( $sParam, $aValue, $this->resolvedQuery );
			} else if ( $iType == dtTimestamp ) {
				// mysql specific
				$this->resolvedQuery = str_replace( $sParam, "'" . date( "Y-m-d H:i:s", $aValue ) . "'", $this->resolvedQuery );
            } else if ( $iType == dtBoolean ) {
                $this->resolvedQuery = str_replace( $sParam, ($aValue ? 1 : 0), $this->resolvedQuery );
			} else if ( $iType == dtIdentifier ) {
				// mysql specific
				$this->resolvedQuery = str_replace( $sParam, "`" . $this->db->Escape( $aValue ) . "`", $this->resolvedQuery );

                $this->resolvedPDOQuery = str_replace( $sParam, "`" . $this->db->Escape( $aValue ) . "`", $this->resolvedPDOQuery );
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

    /**
     * @param integer $iType
     * @param string $sName
     * @param * $aValue
     * @param bool $bTypeMismatchIsFatal
     * @return bool
     */
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
		} else if ( $iType == dtString ) {
            if ( !is_string($aValue) ) {
                if ( $bTypeMismatchIsFatal ) {
                    $globalErrorHandler->Fatal( "CSQLQuery(" . $this->currentQuery . ") -> Value for " . $sName . " is not a valid string." );
                }
                return false;
            }
        }

		$this->parameters[$sName] = array( $iType, $aValue );

		return true;
	}

	public function Open() {
		$this->resolveParams();

		$this->currentResource = $this->db->Query( $this->resolvedPDOQuery, $this->parameters );

		if ( $this->currentResource ) {
			return true;
		}
		return false;
	}

	public function Next( $type = MYSQL_ASSOC ) {
		if ( $this->currentResource ) {
			$this->currentRecord = $this->db->Fetch($this->currentResource, $type);
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
            $this->db->FreeResult($this->currentResource);
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
	
	public function GetRowCount() {
		if ( ($this->currentResource != null) && ($this->currentResource !== true) ) {
			return $this->db->RowCount($this->currentResource);
        }
		return 0;
	}

	public function Iterate( $f ) {
		if ( is_array($f) ) {
			while ( $this->Next() ) {
				$f[0]->$f[1]( $this->GetArray() );
			}
		} else if ( is_string($f) ) {
			while ( $this->Next() ) {
				$f( $this->GetArray() );
			}
		}
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