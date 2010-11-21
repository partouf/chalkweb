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

include_once( "chalkweb/classes/sqlquery.inc.php" );

class CSQLQuick extends CSQLQuery {
	public function __construct( &$db ) {
		parent::__construct($db);
	}
	
	protected function genFieldsStr( $arr, $fieldIsValue = true ) {
		$s = "*";
		
		if ( is_array($arr) ) {
			if ( !empty($arr) ) {
				$s = "";
				if ( $fieldIsValue ) {
					foreach ( $arr as $f => $v ) {
						if ( $s != "" ) {
							$s .= ", " . "`" . $v . "`";
						} else {
							$s = "`" . $v . "`";
						}
					}
				} else {
					foreach ( $arr as $f => $v ) {
						if ( $s != "" ) {
							$s .= ", " . "`" . $f . "`";
						} else {
							$s = "`" . $f . "`";
						}
					}
				}
			}
		}
		
		return $s;
	}
	
	protected function genValuesStr( $arr ) {
		$s = "";
		
		if ( is_array($arr) ) {
			if ( !empty($arr) ) {
				foreach ( $arr as $f => $v ) {
					if ( $s != "" ) {
						$s .= ", ";
					}
					$s .= ":" . $f;
				}
			}
		}
		
		return $s;
	}
	
	protected function genCommaKeyValueStr( $arr ) {
		$s = "";
		
		if ( is_array($arr) ) {
			if ( !empty($arr) ) {
				foreach ( $arr as $f => $v ) {
					if ( $s != "" ) {
						$s .= ", ";
					}
					$s .= $f . "=:" . $f;
				}
			}
		}
		
		return $s;
	}
	
	protected function genAndWhereStr( $arr ) {
		$s = "";
		
		if ( is_array($arr) ) {
			if ( !empty($arr) ) {
				foreach ( $arr as $f => $v ) {
					if ( $s != "" ) {
						$s .= "and ";
					} else {
						$s = " where ";
					}
					$s .= "`" . $f . "`=:" . $f . " ";
				}
			}
		}
		
		return $s;
	}
	
	protected function genOrderByStr( $arr ) {
		$s = "";
		
		if ( is_array($arr) ) {
			if ( !empty($arr) ) {
				foreach ( $arr as $f => $dir ) {
					if ( $s != "" ) {
						$s .= ",";
					} else {
						$s = " order by";
					}
					$s .= " `" . $f . "` " . $dir . " ";
				}
			}
		}
		
		return $s;
	}
	
	protected function getAutoType( $v ) {
		if ( is_numeric($v) ) {
			return dtInteger;
		}
		
		return dtString;
	}
	
	public function SimpleSelect( $table, $fields = false, $where = false, $order = false ) {
		$f = $this->genFieldsStr( $fields );
		$w = $this->genAndWhereStr( $where );
		$o = $this->genOrderByStr( $order );

		$qry = "select " . $f . " from " . $table . $w . $o;
		$this->SetQuery( $qry );
		if ( is_array($where) ) {
			if ( !empty($where) ) {
				foreach ( $where as $k => $v ) {
					$this->AddParam( $this->getAutoType($v), $k, $v );
				}
			}
		}
	}
	
	public function SimpleUpdate( $table, $keyvals = false, $where = false ) {
		$f = $this->genCommaKeyValueStr( $keyvals );
		$w = $this->genAndWhereStr( $where );

		$qry = "update " . $table . " set " . $f . $w;
		$this->SetQuery( $qry );
		if ( is_array($keyvals) ) {
			if ( !empty($keyvals) ) {
				foreach ( $keyvals as $k => $v ) {
					$this->AddParam( $this->getAutoType($v), $k, $v );
				}
			}
		}
		if ( is_array($where) ) {
			if ( !empty($where) ) {
				foreach ( $where as $k => $v ) {
					$this->AddParam( $this->getAutoType($v), $k, $v );
				}
			}
		}
	}
	
	public function SimpleDelete( $table, $where = false ) {
		$w = $this->genAndWhereStr( $where );

		$qry = "delete from " . $table . $w;
		$this->SetQuery( $qry );
		if ( is_array($where) ) {
			if ( !empty($where) ) {
				foreach ( $where as $k => $v ) {
					$this->AddParam( $this->getAutoType($v), $k, $v );
				}
			}
		}
	}
	
	public function SimpleInsert( $table, $keyvals = false ) {
		$f = $this->genFieldsStr( $keyvals, false );
		$v = $this->genValuesStr( $keyvals );

		$qry = "insert into " . $table . " (" . $f .  ") values (" . $v . ")";
		
		$this->SetQuery( $qry );
		if ( is_array($keyvals) ) {
			if ( !empty($keyvals) ) {
				foreach ( $keyvals as $k => $v ) {
					$this->AddParam( $this->getAutoType($v), $k, $v );
				}
			}
		}
	}
	
	public function ExecAndGetAllRecords() {
		$arr = array();
		
		$this->Open();
		while ( $this->Next() ) {
			$arr[] = $this->GetArray();			
		}
		$this->Close();
		
		return $arr;
	}
}

function QuickSQL_Select( &$db, $table, $fields = false, $where = false, $order = false ) {
	$qry = new CSQLQuick( $db );
	$qry->SimpleSelect( $table, $fields, $where, $order );
	
	return $qry->ExecAndGetAllRecords();
}

function QuickSQL_Update( &$db, $table, $keyvals = false, $where = false ) {
	$qry = new CSQLQuick( $db );
	$qry->SimpleUpdate( $table, $keyvals, $where );
	
	$qry->Open();
	$qry->Close();
}

function QuickSQL_Delete( &$db, $table, $where = false ) {
	$qry = new CSQLQuick( $db );
	$qry->SimpleDelete( $table, $where );
	
	$qry->Open();
	$qry->Close();
}

function QuickSQL_Insert( &$db, $table, $keyvals = false ) {
	$qry = new CSQLQuick( $db );
	$qry->SimpleInsert( $table, $keyvals );
	
	$qry->Open();
	$qry->Close();
	
	return $db->getInsertId();
}

// --------------------------------------------------------------------------

class CTableObject {
	protected $db;
	
	protected $table;
	protected $fields;
	protected $pks;
	
	protected $records;
	
	public function __construct( &$db ) {
		$this->db = $db;
		$this->table = "";
		$this->fields = array ();
		$this->pks = array ();
		
		$this->records = array();
	}
	
	protected function PopulateFieldsFromDB( $table ) {
		$this->fields = array ();
		$this->pks = array ();
		
		$qry = new CSQLQuery( $db );
		$qry->SetQuery( "show columns from :table" );
		$qry->AddParam( dtIdentifier, "table", $table, true );
		$qry->Open();
		while ( $qry->Next() ) {
			$row = $qry->GetArray();
			
			$t = (strpos($row['Type'],'int') !== false) ? dtInteger : dtString;
			$t = (strpos($row['Type'],'timestamp') !== false) ? dtTimestamp : $t;
			
			$this->Fields[$row['Field']] = $t;
			if ( $row['Key'] == "PRI" ) {
				$this->pks[] = $row['Field'];
			} 
		}
	}
	
	public function Select( $where = false, $order = false ) {
		$this->records = QuickSQL_Select( $this->db, $this->table, array(), $where, $order );
	}

	public function CreateEmptyRecord() {
		$row = array();
		
		foreach ( $fields as $f => $dt ) {
			$row[$f] = null;
		}
		
		return $row;
	}
	
	public function CurrentRecord() {
		
	}
}




?>