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

$globalErrorHandler = null;


class CErrorHandler {
	protected $directOutputWarning;
	protected $directOutputFatal;
	protected $dieOnFatal;
	protected $lstWarnings = array ();
	protected $lstFatals = array ();

	public function __construct( $directOutWarning = false, $directOutFatal = true, $dieOnFatal = true ) {
		$this->directOutputWarning = $directOutWarning;
		$this->directOutputFatal = $directOutFatal;
		$this->dieOnFatal = $dieOnFatal;
	}

	public function Warning( $msg ) {
		if ( $this->directOutputWarning ) {
			echo "Warning: " . $msg . "<br />\n";
		} else {
			$this->lstWarnings[] = array ( "msg" => $msg );
		}
	}

	public function Fatal( $msg ) {
		if ( $this->directOutputFatal ) {
			echo "Fatal: " . $msg . "<br />\r\n";

			if ( $this->dieOnFatal ) {
				die();
			}
		} else {
			$this->lstFatals[] = array ( "msg" => $msg );
		}
	}

	public function ListWarnings() {
		return $this->lstWarnings;
	}

	public function WarningsAsString() {
		$s = "";
		foreach ( $this->lstWarnings as $warning ) {
			$s .= $warning['msg'] . "<br />\n";
		}
		return $s;
	}

	public function FatalsAsString() {
		$s = "";
		foreach ( $this->lstFatals as $fatal ) {
			$s .= $fatal['msg'] . "<br />\n";
		}
		return $s;
	}

	public function ListFatals() {
		return $this->lstFatals;
	}

	public function ClearWarnings() {
		$this->lstWarnings = array ();
	}

	public function ClearFatals() {
		$this->lstFatals = array ();
	}
}


?>