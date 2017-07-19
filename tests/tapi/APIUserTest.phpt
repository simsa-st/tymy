<?php
/**
 * TEST: Test Discussion on TYMY api
 * 
 */

namespace Test;

use Nette;
use Tester;
use Tester\Assert;

$container = require __DIR__ . '/../bootstrap.php';

if (in_array(basename(__FILE__, '.phpt') , $GLOBALS["testedTeam"]["skips"])) {
    Tester\Environment::skip('Test skipped as set in config file.');
}

class APIUserTest extends ITapiTest {

    /** @var \Tymy\User */
    private $tapi_user;

    function __construct(Nette\DI\Container $container) {
        $this->container = $container;
    }
    
    public function getTestedObject() {
        return $this->tapi_user;
    }
    
    protected function setUp() {
        $this->tapi_user = $this->container->getByType('Tymy\User');
        parent::setUp();
    }
    
    /* TEST GETTERS AND SETTERS */ 
    
    function testLogin(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setLogin($field);
        Assert::equal($field, $this->tapi_user->getLogin());
    }
    
    function testPassword(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setPassword($field);
        Assert::equal($field, $this->tapi_user->getPassword());
    }
    
    function testEmail(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setEmail($field);
        Assert::equal($field, $this->tapi_user->getEmail());
    }
    
    function testFirstname(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setFirstName($field);
        Assert::equal($field, $this->tapi_user->getFirstName());
    }
    
    function testLastname(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setLastName($field);
        Assert::equal($field, $this->tapi_user->getLastName());
    }
    
    function testAdminNote(){
        $field = "test" . md5(rand(0,100));
        $this->tapi_user->setAdminNote($field);
        Assert::equal($field, $this->tapi_user->getAdminNote());
    }
    
    /* TEST TAPI FUNCTIONS */ 
    
    /* TAPI : SELECT */

    function testSelectFailsNoRecId(){
        $this->userTestAuthenticate("TESTLOGIN", "TESTPASS");
        Assert::exception(function(){$this->tapi_user->reset()->getResult(TRUE);} , "\Tymy\Exception\APIException", "User ID not set!");
    }
    
    function testSelectNotLoggedInFails404() {
        $this->userTestAuthenticate("TESTLOGIN", "TESTPASS");
        Assert::exception(function(){$this->tapi_user->reset()->recId(1)->getResult(TRUE);} , "Nette\Security\AuthenticationException", "Login failed.");
    }
        
    function testSelectSuccess() {
        $this->userTapiAuthenticate($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);
        $userId = 1;
        $this->tapi_user->reset()->recId($userId)->getResult(TRUE);
        
        Assert::true(is_object($this->tapi_user));
        Assert::true(is_object($this->tapi_user->result));
        Assert::type("string",$this->tapi_user->result->status);
        Assert::same("OK",$this->tapi_user->result->status);
        
        Assert::type("int",$this->tapi_user->result->data->id);
        Assert::true($this->tapi_user->result->data->id > 0);
        Assert::type("string",$this->tapi_user->result->data->login);
        Assert::type("bool",$this->tapi_user->result->data->canLogin);
        Assert::type("string",$this->tapi_user->result->data->lastLogin);
        Assert::same(1, preg_match_all($GLOBALS["dateRegex"], $this->tapi_user->result->data->lastLogin)); //timezone correction check
        Assert::type("string",$this->tapi_user->result->data->status);
        Assert::true(in_array($this->tapi_user->result->data->status, ["PLAYER", "MEMBER", "SICK"]));
        Assert::type("array",$this->tapi_user->result->data->roles);
        foreach ($this->tapi_user->result->data->roles as $role) {
            Assert::type("string",$role);
        }
        Assert::type("string",$this->tapi_user->result->data->firstName);
        Assert::type("string",$this->tapi_user->result->data->lastName);
        Assert::type("string",$this->tapi_user->result->data->callName);
        Assert::type("string",$this->tapi_user->result->data->language);
        Assert::type("string",$this->tapi_user->result->data->email);
        Assert::type("string",$this->tapi_user->result->data->jerseyNumber);
        if(property_exists($this->tapi_user->result->data, "gender"))
            Assert::type("string",$this->tapi_user->result->data->gender);
        Assert::type("string",$this->tapi_user->result->data->street);
        Assert::type("string",$this->tapi_user->result->data->city);
        Assert::type("string",$this->tapi_user->result->data->zipCode);
        Assert::type("string",$this->tapi_user->result->data->phone);
        Assert::type("string",$this->tapi_user->result->data->phone2);
        Assert::type("string",$this->tapi_user->result->data->birthDate);
        Assert::type("int",$this->tapi_user->result->data->nameDayMonth);
        Assert::type("int",$this->tapi_user->result->data->nameDayDay);
        Assert::type("string",$this->tapi_user->result->data->pictureUrl);
        Assert::type("string",$this->tapi_user->result->data->fullName);
        Assert::type("string",$this->tapi_user->result->data->displayName);
        Assert::type("string",$this->tapi_user->result->data->webName);
        Assert::type("int",$this->tapi_user->result->data->errCnt);
        Assert::true($this->tapi_user->result->data->errCnt>= 0);
        Assert::type("array",$this->tapi_user->result->data->errFls);
        foreach ($this->tapi_user->result->data->errFls as $errF) {
            Assert::type("string",$errF);
        }
        
    }

    /* TAPI : REGISTER */
    
    
    function testRegisterFailsNoLogin(){
        Assert::exception(function(){$this->tapi_user->reset()->register();} , "\Tymy\Exception\APIException", "Login not set!");
    }
    
    function testRegisterFailsNoPassword(){
        Assert::exception(function(){$this->tapi_user->reset()->setLogin("test")->register();} , "\Tymy\Exception\APIException", "Password not set!");
    }
    
    function testRegisterFailsNoEmail(){
        Assert::exception(function(){$this->tapi_user->reset()->setLogin("test")->setPassword("test")->register();} , "\Tymy\Exception\APIException", "Email not set!");
    }
    
    function testRegisterSuccess(){
        if(!$GLOBALS["testedTeam"]["invasive"])
            return null;
        
        $this->userTapiAuthenticate($GLOBALS["testedTeam"]["user"], $GLOBALS["testedTeam"]["pass"]);
        $this->tapi_user->reset()->setLogin("test" . rand(0,10000))->setPassword("test")->setEmail("test@test-matej.com")->register();
        
        Assert::true(is_object($this->tapi_user));
        Assert::true(is_object($this->tapi_user->result));
        Assert::type("string",$this->tapi_user->result->status);
        Assert::same("OK",$this->tapi_user->result->status);
    }
}

$test = new APIUserTest($container);
$test->run();
