<?php
namespace Edu\Cnm\DataDesign\Test;

use Edu\Cnm\DataDesign\Profile;

// grab the class under scrutiny
require_once(dirname(__DIR__) . "/autoload.php");


/**
 * Full PHPUnit test for the Profile class
 *
 * This is a complete PHPUnit test of the Profile class. It is complete because *ALL* mySQL/PDO enabled methods
 * are tested for both invalid and valid inputs.
 *
 * @see Profile
 * @author Dylan McDonald <dmcdonald21@cnm.edu>
 **/
class ProfileTest extends DataDesignTest {
	/**
	 * placeholder until account activation is created
	 * @var string $VALID_ACTIVATION
	 */
	protected $VALID_ACTIVATION;

	/**
	 * valid at handle to use
	 * @var string $VALID_ATHANDLE
	 **/
	protected $VALID_ATHANDLE = "@phpunit";

	/**
	 * second valid at handle to use
	 * @var string $VALID_ATHANDLE2
	 **/
	protected $VALID_ATHANDLE2 = "@passingtests";

	/**
	 * valid email to use
	 * @var string $VALID_EMAIL
	 **/
	protected $VALID_EMAIL = "test@phpunit.de";

	/**
	 * valid hash to use
	 * @var $VALID_HASH
	 */
	protected $VALID_HASH;

	/**
	 * valid phone number to use
	 * @var string $VALID_PHONE
	 **/
	protected $VALID_PHONE = "+12125551212";

	/**
	 * valid salt to use to create the profile object to own the test
	 * @var string $VALID_SALT
	 */
	protected $VALID_SALT;



	/**
	 * run the default setup operation to create salt and hash.
	 */
	public final function setUp() : void {
		parent::setUp();

		//
		$password = "abc123";
		$this->VALID_SALT = bin2hex(random_bytes(32));
		$this->VALID_HASH = hash_pbkdf2("sha512", $password, $this->VALID_SALT, 262144);
		$this->VALID_ACTIVATION = bin2hex(random_bytes(16));
	}

	/**
	 * test inserting a valid Profile and verify that the actual mySQL data matches
	 **/
	public function testInsertValidProfile() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);

		//var_dump($profile);

		$profile->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 * test inserting a Profile that already exists
	 *
	 * @expectedException \PDOException
	 **/
	public function testInsertInvalidProfile() : void {
		// create a profile with a non null profileId and watch it fail
		$profile = new Profile(DataDesignTest::INVALID_KEY, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());
	}

	/**
	 * test inserting a Profile, editing it, and then updating it
	 **/
	public function testUpdateValidProfile() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		// edit the Profile and update it in mySQL
		$profile->setProfileAtHandle($this->VALID_ATHANDLE2);
		$profile->update($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE2);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 *
	 * /**
	 * test updating a Profile that does not exist
	 *
	 * @expectedException \PDOException
	 **/
	public function testUpdateInvalidProfile() {
		// create a Profile and try to update it without actually inserting it
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->update($this->getPDO());
	}

	/**
	 * test creating a Profile and then deleting it
	 **/
	public function testDeleteValidProfile() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		// delete the Profile from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$profile->delete($this->getPDO());

		// grab the data from mySQL and enforce the Profile does not exist
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertNull($pdoProfile);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("profile"));
	}

	/**
	 * test deleting a Profile that does not exist
	 *
	 * @expectedException \PDOException
	 **/
	public function testDeleteInvalidProfile() : void {
		// create a Profile and try to delete it without actually inserting it
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->delete($this->getPDO());
	}

	/**
	 * test inserting a Profile and regrabbing it from mySQL
	 **/
	public function testGetValidProfileByProfileId() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 * test grabbing a Profile that does not exist
	 **/
	public function testGetInvalidProfileByProfileId() : void {
		// grab a profile id that exceeds the maximum allowable profile id
		$profile = Profile::getProfileByProfileId($this->getPDO(), DataDesignTest::INVALID_KEY);
		$this->assertNull($profile);
	}

	public function testGetValidProfileByAtHandle() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		//grab the data from MySQL
		$results = Profile::getProfileByProfileAtHandle($this->getPDO(), $this->VALID_ATHANDLE);
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("profile"));

		//enforce no other objects are bleeding into profile
		$this->assertContainsOnlyInstancesOf("Edu\\CNM\\DataDesign\\Profile", $results);

		//enforce the results meet expectations
		$pdoProfile = $results[0];
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 * test grabbing a Profile by at handle that does not exist
	 **/
	public function testGetInvalidProfileByAtHandle() : void {
		// grab an at handle that does not exist
		$profile = Profile::getProfileByProfileAtHandle($this->getPDO(), "@doesnotexist");
		$this->assertCount(0, $profile);
	}

	/**
	 * test grabbing a Profile by email
	 **/
	public function testGetValidProfileByEmail() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileEmail($this->getPDO(), $profile->getProfileEmail());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 * test grabbing a Profile by an email that does not exists
	 **/
	public function testGetInvalidProfileByEmail() : void {
		// grab an email that does not exist
		$profile = Profile::getProfileByProfileEmail($this->getPDO(), "does@not.exist");
		$this->assertNull($profile);
	}

	/**
	 * test grabbing a profile by its activation
	 */
	public function testGetValidProfileByActivationToken() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileActivationToken($this->getPDO(), $profile->getProfileActivationToken());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	public function testGetAllProfiles() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");

		// create a new Profile and insert to into mySQL
		$profile = new Profile(null, $this->VALID_ACTIVATION, $this->VALID_ATHANDLE, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_PHONE, $this->VALID_SALT);
		$profile->insert($this->getPDO());

		//grab the data from MySQL
		$results = Profile::getAllProfiles($this->getPDO());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("profile"));

		//enforce no other objects are bleeding into profile
		$this->assertContainsOnlyInstancesOf("Edu\\CNM\\DataDesign\\Profile", $results);

		//enforce the results meet expectations
		$pdoProfile = $results[0];
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileAtHandle(), $this->VALID_ATHANDLE);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfilePhone(), $this->VALID_PHONE);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
	}

	/**
	 * test grabbing a Profile by an email that does not exists
	 **/
	public function testGetInvalidProfileActivation() : void {
		// grab an email that does not exist
		$profile = Profile::getProfileByProfileActivationToken($this->getPDO(), "5ebc7867885cb8dd25af05b991dd5609");
		$this->assertNull($profile);
	}
}