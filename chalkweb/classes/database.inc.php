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

    /***
     * @var PDO $pdolink
     */
    protected $pdolink;

    /***
     * @var PDOStatement $pdolaststmt
     */
    protected $pdolaststmt;

    protected $affectedrows = 0;

    // ----------------------------------------------------
    protected function connectToServer() {
        try {
            if ( $this->port != 3306 ) {
                $this->pdolink = new PDO(
                    "mysql:host=" . $this->server .
                    ";port=" . $this->port .
                    ";dbname=" . $this->dbname .
                    ";charset=utf8",
                    $this->username,
                    $this->password,
                    array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
                return false;
            } else {
                $this->pdolink = new PDO(
                    "mysql:host=" . $this->server .
                    ";dbname=" . $this->dbname .
                    ";charset=utf8",
                    $this->username,
                    $this->password,
                    array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
            }
        } catch(PDOException $ex) {
            $this->lastError = $ex->getMessage();
        }

        if ( !$this->pdolink ) {
            return false;
        }

        return true;
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
        }
    }

    public function GetInsertId() {
        return $this->pdolink->lastInsertId();
    }

    public function AffectedRows() {
        $this->affectedrows = $this->pdolaststmt->rowCount();
        return $this->affectedrows;
    }

    public function RowCount(&$res) {
        throw new Exception("RowCount deprecated");
    }

    public function Query( $sql, $arrParameters = false ) {
        parent::Query( $sql );

        $res = false;
        try {
            /***
             * @var PDOStatement $res
             */
            $res = $this->pdolink->prepare($sql);

            if ($arrParameters) {
                // example $arrParameters = array( "abc" => array(dtInteger, 123), "def" => array(dtString, "test"))

                foreach ( $arrParameters as $param => $value ) {
                    $iType = $value[0];
                    $aValue = $value[1];
                    $sParam = ":" . $param;

                    $oType = PDO::PARAM_NULL;
                    if ($iType == dtInteger) {
                        $oType = PDO::PARAM_INT;
                    } else if ($iType == dtString) {
                        $oType = PDO::PARAM_STR;
                    } else if ($iType == dtBoolean) {
                        $oType = PDO::PARAM_INT;
                        $aValue = !empty($aValue) ? 1 : 0;
                    } else if ($iType == dtTimestamp) {
                        $oType = PDO::PARAM_STR;
                        $aValue = date( "Y-m-d H:i:s", $aValue );
                    }

                    // note: dtRaw dtRawEscaped dtIdentifier should be handled before calling this function
                    //  also if you have these types, make sure they have no overlapping name with the regular parameters

                    // note: params should have unique names, even if you want to assign the same values multiple times

                    if ($oType != PDO::PARAM_NULL) {
                        $res->bindValue($sParam, $aValue, $oType);
                    }
                }
            }

            $res->execute();
            $this->pdolaststmt = $res;
        } catch(PDOException $ex) {
            $this->lastError = $ex->getMessage();
            $this->pdolaststmt = false;
        }

        return $res;
    }

    /***
     * @param PDOStatement $res
     */
    public function FreeResult( &$res ) {
        $res->closeCursor();
    }

    /***
     * @param PDOStatement $res
     * @param mixed $type
     * @return mixed
     */
    public function Fetch( &$res, $type = false ) {
        try {
            if ($type == MYSQL_NUM) {
                return $res->fetch(PDO::FETCH_NUM);
            } else {
                return $res->fetch(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $ex) {
            $this->lastError = $ex->getMessage();
        }

        return false;
    }

    /***
     * @param string $s
     * @return string
     */
    public function Escape( $s ) {
        $es = $this->pdolink->quote($s);

        return substr($es, 1, -1);
    }

    public function GetLastError() {
        return $this->lastError;
    }

    public function startTransaction() {
        return $this->pdolink->beginTransaction();
    }

    public function rollbackTransaction() {
        return $this->pdolink->rollBack();
    }

    public function commitTransaction() {
        return $this->pdolink->commit();
    }
}

