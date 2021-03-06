<?php

namespace Tapi;

use App\Model\Supplier;
use App\Model\TapiAuthenticator;
use Nette\Security\User;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tapi\Exception\APIAuthenticationException;
use Tapi\Exception\APIException;
use Tapi\Exception\APINotFoundException;
use Tapi\TracyTapiPanel;
use Tracy\Debugger;

/**
 * Project: tymy_v2
 * Description of TapiService
 *
 * @author kminekmatej created on 7.1.2018, 22:24:46
 */
class TapiService {
    
    /** @var User */
    private $user;
    
    /** @var TapiAuthenticator */
    private $authenticator;
    
    /** @var Supplier */
    private $supplier;
    
    /** @var TracyTapiPanel */
    private $tapiPanel;
    
    /** @var TapiObject */
    private $tapiObject;
    
    /** @var string */
    private $url;
    
    private $curl;
    
    public function __construct(User $user, TapiAuthenticator $authenticator, Supplier $supplier) {
        $this->user = $user;
        $this->authenticator = $authenticator;
        $this->supplier = $supplier;
        $this->tapiObject = NULL;
        $this->initTapiDebugPanel();
        $this->initCURL();
    }
    
    private function initTapiDebugPanel(){
        $panelId = "TAPI";
        if(is_null(Debugger::getBar()->getPanel($panelId))){
            $this->tapiPanel = new TracyTapiPanel;
            Debugger::getBar()->addPanel($this->tapiPanel, $panelId);
        } else {
            $this->tapiPanel = Debugger::getBar()->getPanel($panelId);
        }
    }
    
    private function initCURL(){
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curl, CURLOPT_ENCODING, '');
        curl_setopt($this->curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );

        if($this->supplier->isHttps()){
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, TRUE);
        }
    }
    
    public function __destruct(){
        curl_close($this->curl);
    }
    
    /**
     * @param TapiObject $tapiObject Object to perform request on
     * @throws APIException
     * @throws APIAuthenticationException
     * @return ResultStatus or NULL on failure;
     */
    public function request(TapiObject $tapiObject){
        $this->tapiObject = $tapiObject;
        $this->composeRequestUrl();
        return $this->performRequest(TRUE);
    }
    
    /**
     * @param TapiObject $tapiObject Object to perform request on
     * @throws APIException
     * @throws APIAuthenticationException
     * @return ResultStatus or NULL on failure;
     */
    public function requestNoRelogin(TapiObject $tapiObject){
        $this->tapiObject = $tapiObject;
        $this->composeRequestUrl();
        return $this->performRequest(FALSE);
    }
    
    /**
     * @param bool $relogin
     * @return ResultStatus
     * @throws APIException
     */
    private function performRequest($relogin = TRUE) {
        if (is_null($this->supplier->getApiRoot()) || is_null($this->tapiObject) || is_null($this->url))
            throw new APIException("Failure: request input data not set correctly.");

        $curl_response = $this->executeRequest();
        
        if ($curl_response->data === FALSE)
            throw new APIException("Unknown error while procesing tapi request" . (property_exists ($curl_response, "error") ? ". CURL error " . $curl_response->error : ""));
        return $this->respond($curl_response->data, $curl_response->info, $relogin);
    }

    private function composeRequestUrl() {
        $fullUrl = $this->getFullUrl();

        $paramArray = $this->tapiObject->getRequestParameters();
        if ($this->tapiObject->getTsidRequired())
            $paramArray["TSID"] = $this->user->getIdentity()->sessionKey;

        //add parameters to url
        if (count($paramArray)) {
            $fullUrl = preg_replace('/\\?.*/', '', $fullUrl); // firstly try to remove all url params before adding them - important for relogins
            $fullUrl .= "?" . http_build_query($paramArray);
        }
        $this->url = $fullUrl;
        return TRUE;
    }
    
    private function getFullUrl(){
        return $this->supplier->getApiRoot() . DIRECTORY_SEPARATOR . $this->tapiObject->getUrl();
    }
    
    private function executeRequest() {
        $objectHash = spl_object_hash($this->tapiObject);
        Debugger::timer("tapi-request $objectHash");
        
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->tapiObject->getMethod());
        
        $formattedData = NULL;
        
        if ($this->tapiObject->getMethod() != RequestMethod::GET && $this->tapiObject->getRequestData()) {
            switch ($this->tapiObject->getEncoding()) {
                case TapiObject::ENCODING_JSON:
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: ' . TapiObject::ENCODING_JSON));
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->tapiObject->getRequestData()));
                    break;
                case TapiObject::ENCODING_URLENCODED:
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: ' . TapiObject::ENCODING_URLENCODED));
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->tapiObject->getRequestData()));
                    break;
                default:
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->tapiObject->getRequestData());
                    break;
            }
        }
        curl_setopt($this->curl, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($this->curl, CURLOPT_STDERR, $verbose);
        $result = ["data" => curl_exec($this->curl), "info" => curl_getinfo($this->curl)];

        if(curl_error($this->curl)) $result["error"] = curl_errno($this->curl) . ": " . curl_error($this->curl);
        
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        //echo $verboseLog;
        
        $this->tapiPanel->logAPI($this->url, $this->tapiObject->getMethod(), $formattedData, Debugger::timer("tapi-request $objectHash"), $result["info"]["http_code"]);
        return (object) $result;
    }

    /**
     * @return ResultStatus
     * @throws APIException
     * @throws APINotFoundException
     */
    private function respond($curl_data, $curl_info, $relogin) {
        try {
            $resultStatus = new ResultStatus(Json::decode($curl_data));
        } catch (JsonException $exc) {
            if (!Debugger::$productionMode) {
                Debugger::barDump($curl_data, "Response");
                Debugger::barDump($curl_info, "CURL_info");
                Debugger::barDump($this->url, "Url");
                Debugger::barDump($this->tapiObject->getMethod(), "Method");
                Debugger::barDump($this->tapiObject->getRequestData(), "Request data");
            }
            throw new APINotFoundException("Request " . $this->url . " [" . $this->tapiObject->getMethod() . "] failed with error " . $curl_info["http_code"] . ". JSON parsing error: " . $exc->getMessage());
        }
        
        switch ($curl_info["http_code"]) {
            case 200: //everything ok
                return $this->success($resultStatus, $relogin);
            case 400: 
                throw new APIException(($resultStatus->getMessage() ? $resultStatus->getMessage() : "Chyba 400: Neznámý dotaz"), $curl_info["http_code"]);
            case 401: // unauthorized, try to refresh
                return $this->loginFailure($relogin);
            case 403: 
                throw new APIException(($resultStatus->getMessage() ? $resultStatus->getMessage() : "Chyba 403: Nedostatečná práva)"), $curl_info["http_code"]);
            case 404: 
                self::throwNotFound();
            case 500: 
                if($resultStatus->getMessage() == "Not found") self::throwNotFound ();
            default:
                Debugger::barDump($curl_info);
                Debugger::barDump($this->url);
                Debugger::barDump($this->tapiObject->getMethod());
                Debugger::barDump($this->tapiObject->getRequestData());
                self::throwRequestError ($this->url, $this->tapiObject->getMethod(), $curl_info["http_code"], $resultStatus->getMessage());
        }
    }

    private function loginFailure($relogin) {
        if ($relogin && !is_null($this->authenticator)) { // relogin only if specified, is authenticator and is class needed logins
            $savedTapiObject = $this->tapiObject;
            $newLogin = $this->authenticator->setTapiService($this)->reAuthenticate([$this->user->getIdentity()->data["login"], $this->user->getIdentity()->data["hash"]]);
            $this->user->getIdentity()->sessionKey = $newLogin->sessionKey;
            return $this->requestNoRelogin($savedTapiObject);
        } else {
            throw new APIAuthenticationException("Login failed. Wrong username or password.");
        }
    }
    
    private function success(ResultStatus $resultStatus, $relogin) {
        $data = $resultStatus->getData();
        switch ($resultStatus->getStatus()) {
            case "ERROR":
                switch ($data->statusMessage) { //TODO add some another reasons when they appear
                    case "Not loggged in":
                        return $this->loginFailure($relogin);
                }
                break;
            case "OK":
                return $resultStatus;
            default:
                throw new APIException("API request returned abnormal status " . $data->status . " : " . $data->statusMessage, 501);
        }
    }
    
    public static function throwNotFound($msg = NULL) {
        throw new APINotFoundException(is_null($msg) ? "Záznam nenalezen" : $msg, 404);
    }
    
    public static function throwRequestError($url, $method, $http_code, $message) {
        throw new APIException("Request $url [$method] failed with error $http_code: $message", $http_code);
    }
}
