<?php

include_once("chalkweb/classes/sqlquery.inc.php");
include_once("chalkweb/classes/usersessions.inc.php");

include_once("Auth/OpenID.php");
include_once("Auth/OpenID/Consumer.php");
include_once("Auth/OpenID/FileStore.php");
include_once("Auth/OpenID/SReg.php");
include_once("Auth/OpenID/AX.php");
include_once("Auth/OpenID/PAPE.php");


/*
CREATE TABLE `openiduser` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `nickname` varchar(100) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `url` varchar(1024) NOT NULL,
  `email` varchar(255) NOT NULL,
  `permissions` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
*/


define("OPENIDSTOREDIR","/tmp/openidstore");

abstract class COpenIDLogin {
	protected $requestFullname;
	protected $requestEmail;
	
	protected $returnUrl;
	protected $openidstoreDirectory;

	public function __construct() {
		$this->openidstoreDirectory = OPENIDSTOREDIR;
		
		// although on by default, don't ask for it if you don't need it
		$this->requestEmail = true;
		
		// defaults to false, you usually don't have use for these
		$this->requestFullname = false;
	}

	public function setRelativeReturnUrl( $url ) {
		$this->returnUrl = $this->getTrustRoot() . $url;
	}

	protected function getReturnTo() {
		$s = session_id() . "";
	    return sprintf( $this->returnUrl . "&" . session_name() . "=" . $s);
	}

	protected function getScheme() {
	    $scheme = 'http';
	    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
	        $scheme .= 's';
	    }
	    return $scheme;
	}

	protected function getTrustRoot() {
	    return sprintf( "%s://%s:%s", $this->getScheme(), $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], "" );
	}

	protected function DoOpenIdAuth( $url ) {
		@mkdir( $this->openidstoreDirectory );

		$store =& new Auth_OpenID_FileStore( $this->openidstoreDirectory );
	    $consumer =& new Auth_OpenID_Consumer( $store );

	    $auth_request = $consumer->begin( $url );
	    if (!$auth_request) {
	        return "Authentication error; not a valid OpenID.";
	    }

	    // SRegRequest for simple openid providers
	    $sreg_names = array();
		if ( $this->requestEmail ) {
	    	$sreg_names[] = "email";
		}
	    if ( $this->requestFullname ) {
	    	$sreg_names[] = "fullname";
	    }
	    $sreg_request = Auth_OpenID_SRegRequest::build( array(), $sreg_names );
        $auth_request->addExtension($sreg_request);

	    // AX Attributes in case SReg isn't supported
		$ax_attribute = array ();
		if ( $this->requestEmail ) {
			$ax_attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/email',2,1, 'email');			
		}
	    if ( $this->requestFullname ) {
			$ax_attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/first',1,1, 'firstname');
			$ax_attribute[] = Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/last',1,1, 'lastname');
	    }

		$ax = new Auth_OpenID_AX_FetchRequest;
		foreach($ax_attribute as $attr){
		        $ax->add($attr);
		}
		$auth_request->addExtension($ax);

		// PAPE, unused atm
	    $policy_uris = null;

	    $pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
	    if ($pape_request) {
	        $auth_request->addExtension($pape_request);
	    }

	    // connect and find out what to do first
	    // some openid providers send you an url to redirect to, and some give you some html to display
	    if ($auth_request->shouldSendRedirect()) {
	        $redirect_url = $auth_request->redirectURL( $this->getTrustRoot() . "/", $this->getReturnTo() );
	        if (Auth_OpenID::isFailure($redirect_url)) {
	            return "Could not redirect to server: " . $redirect_url->message;
	        } else {
	            header("Location: ".$redirect_url);
	        }
	    } else {
	        $form_id = 'openid_message';
	        $form_html = $auth_request->htmlMarkup($this->getTrustRoot(), $this->getReturnTo(), false, array('id' => $form_id));

	        if (Auth_OpenID::isFailure($form_html)) {
	            return "Could not redirect to server: " . $form_html->message;
	        } else {
	            return $form_html;
	        }
	    }

	    return "";
	}

	protected function FinishOpenIdAuth() {
		global $globalErrorHandler;

	    $store =& new Auth_OpenID_FileStore( $this->openidstoreDirectory );
	    $consumer =& new Auth_OpenID_Consumer( $store );

	    $return_to = $this->getReturnTo();
	    $response = $consumer->complete($return_to);
	    $msg = "";
	    $error = "";
	    $sreg = array();
	    $b = false;
	    $ax_obj = false;
	    $url = "";

	    // Check the response status.
	    if ($response->status == Auth_OpenID_CANCEL) {
	    	$msg = 'Verification cancelled.';

	    	$globalErrorHandler->Warning( $msg );
	    } else if ($response->status == Auth_OpenID_FAILURE) {
	        $msg = "OpenID authentication failed: " . $response->message;

	        $globalErrorHandler->Warning( $msg );
	    } else if ($response->status == Auth_OpenID_SUCCESS) {
	        $url = $response->getDisplayIdentifier();

	        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
	        $sreg = $sreg_resp->contents();

	        $ax = new Auth_OpenID_AX_FetchResponse();
	        $ax_obj = $ax->fromSuccessResponse($response);

			$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

			$b = true;
	    }

	    return array ($b, $msg, $error, $sreg, $ax_obj, $url);
	}
	
	public abstract function AddUser( $url, $email, $nickname = "", $fullname = "" );
	public abstract function GetUserRegistrationInfo( $url );
	
	public function LoginAuthorizationCheck() {
		list($b, $msg, $error, $sreg, $ax_obj, $url) = $this->FinishOpenIdAuth();

		if ( $b ) {
			$user = $this->GetUserRegistrationInfo( $url );
			if ( !$user ) {
				$email = "";
				$nickname= "";
				$fullname = "";

				if ( !empty($ax_obj) && !empty($ax_obj->data) ) {
					$fullname = $ax_obj->data['http://axschema.org/namePerson/first'][0] . " ";
					$fullname .= $ax_obj->data['http://axschema.org/namePerson/last'][0];
					$fullname = trim($fullname);
					$email = $ax_obj->data['http://axschema.org/contact/email'][0];
				}
				if ( empty($email) ) {
					$email = @$sreg['email'];
					$nickname = @$sreg['nickname'];
					$fullname = @$sreg['fullname'];
				}
				$id = $this->AddUser( $url, $email, $nickname, $fullname );

				$user = $this->GetUserRegistrationInfo( $url );
			}

			$u = new CUser( $user['id'], 0, $user['nickname'], $user['permissions'], $user['email'] );
			$u->SaveToSessionUser();

			return array ($b, $msg, $error, $sreg, $ax_obj, $url);
		}

		return array ($b, $msg, $error, $sreg, $ax_obj, $url);
	}
}


class COpenIDUsers extends COpenIDLogin {
	protected $db;
	protected $users;


	public function __construct( &$database ) {
		parent::__construct();

		$this->db = $database;
		$this->users = null;
	}

	protected function LoadUsers() {
		$this->users = array();
		$serverid = 0;

		$query = new CSQLQuery( $this->db );
		$query->SetQuery("select * from openiduser");
		if ( $query->Open() ) {
			while ( $query->Next() ) {
				$user = $query->GetArray();
				$id = $user['id'] . "_" . $serverid;
				$this->users[$id] = $user;
			}

			$query->Close();
		} else {
			$globalErrorHandler->Fatal( $this->database->GetLastError() );
		}
	}
	
	public function GetAll() {
		if ( !$this->users ) {
			$this->LoadUsers();
		}

		return $this->users;
	}
	
	public function GetUserById( $userid, $serverid = 0 ) {
		if ( !$this->users ) {
			$this->LoadUsers();
		}

		return $this->users[$userid . '_' . $serverid];
	}

	public function AddUser( $url, $email, $nickname = "", $fullname = "" ) {
		if ( $nickname == "" ) {
			$p = strpos( $email, "@" );
			if ( $p !== false ) {
				$nickname = substr( $email, 0, $p );
			}
		}

		$query = new CSQLQuery( $this->db );
		$query->SetQuery("insert into openiduser ( url, email, nickname, fullname) values (:url,:email,:nickname,:fullname)");
		$query->AddParam( dtString, "url", $url );
		$query->AddParam( dtString, "email", $email );
		$query->AddParam( dtString, "nickname", $nickname );
		$query->AddParam( dtString, "fullname", $fullname );
		if ( $query->Open() ) {
			$query->Close();
		} else {
			$globalErrorHandler->Fatal( $this->database->GetLastError() );
		}

		return $this->db->GetInsertId();
	}

	public function GetUserRegistrationInfo( $url ) {
		global $globalErrorHandler;
		$row = null;

		$qrySelect = new CSQLQuery( $this->db );
		$qrySelect->SetQuery( "select * from openiduser where url=:url" );
		$qrySelect->AddParam( dtString, "url", $url );

		if ( $qrySelect->Open() ) {
			if ( $qrySelect->Next() ) {
				$row = $qrySelect->GetArray();
			}
			$qrySelect->Close();
		} else {
			$globalErrorHandler->Fatal( $this->database->GetLastError() );
		}

		return $row;
	}

	//--------------------------------------------------------
	public function AttemptLogin( $url ) {
		global $globalCurrentUser;

		$this->Logout();
		session_start();

		$_SESSION['openid_identifier'] = $url;
		$b = $this->DoOpenIdAuth( $url );

		return $b;
	}

	public function Logout() {
		global $globalCurrentUser;

		session_unset();
		session_destroy();

		$globalCurrentUser = null;
	}
}

?>