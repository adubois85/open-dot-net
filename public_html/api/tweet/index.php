<?php

require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 3) . "/php/classes/autoload.php";
require_once dirname(__DIR__, 3) . "/php/lib/xsrf.php";
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");

use Edu\Cnm\DataDesign\{
	Tweet,
	// we only use the profile class for testing purposes
	Profile,
	JsonObjectStorage
};


/**
 * api for the Tweet class
 *
 * @author Valente Meza <valebmeza@gmail.com>
 **/

//verify the session, start if not active
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {
	//grab the mySQL connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/ddctwitter.ini");


	  // mock a logged in user by forcing the session. This is only for testing purposes and should not be in the live code.

	  // profileId of profile to use for testing,
	  $person = 95;

	  // grab a profile by its profileId and add it to the session
	  $_SESSION["profile"] = Profile::getProfileByProfileId($pdo, $person);



	//determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];

	//sanitize input
	$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
	$tweetProfileId = filter_input(INPUT_GET, "tweetProfileId", FILTER_VALIDATE_INT);
	$tweetContent = filter_input(INPUT_GET, "tweetContent", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

	//make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true || $id < 0)) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}


	// handle GET request - if id is present, that tweet is returned, otherwise all tweets are returned
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();

		//get a specific tweet or all tweets and update reply
		if(empty($id) === false) {
			$tweet = Tweet::getTweetByTweetId($pdo, $id);
			if($tweet !== null) {
				$reply->data = $tweet;
			}
		} else if(empty($tweetProfileId) === false) {
			$tweet = Tweet::getTweetByTweetProfileId($pdo, $tweetProfileId)->toArray();
			if($tweet !== null) {
				$reply->data = $tweet;
			}
		} else if(empty($tweetContent) === false) {
			$tweets = Tweet::getTweetByTweetContent($pdo, $tweetContent)->toArray();
			if($tweets !== null) {
				$reply->data = $tweets;
			}
		} else {
			$tweets = Profile::getAllProfiles($pdo);
			if($tweets !== null) {

				//initialize the json storage object
				$storage = new JsonObjectStorage();

				for ($i=0; $i <count($tweets); $i++){

					//grab the profile atHandle by the tweetProfileId
					$profile = Profile::getProfileByProfileId($pdo, $tweets[$i]->getTweetProfileId());
					$profileAtHandle = $profile->getProfileAtHandle();

					//create the combined tweetProfile Class to clean up front end logic
					$tweetProfileObject = new stdClass();
					 $tweetProfileObject->tweetId = $tweets[$i]->getTweetId();
					$tweetProfileObject->tweetatHandle = $profileAtHandle;
					 $tweetProfileObject->tweetContent = $tweets[$i]->getTweetContent();

					//clean up the date to be compatible with the front end
					 $tempTweetDate = $tweets[$i]->getTweetDate();
					 $formatTweetDate = round(floatval($tempTweetDate->format("U.u")) * 1000);
					 $tweetProfileObject->tweetDate = $formatTweetDate;

					//prepare the tweetProfileObject to be sent to the front end
					$storage->attach($tweetProfileObject);

				}
				$reply->data = $storage;
			}
		}
	} else if($method === "PUT" || $method === "POST") {

		verifyXsrf();
		$requestContent = file_get_contents("php://input");
		// Retrieves the JSON package that the front end sent, and stores it in $requestContent. Here we are using file_get_contents("php://input") to get the request from the front end. file_get_contents() is a PHP function that reads a file into a string. The argument for the function, here, is "php://input". This is a read only stream that allows raw data to be read from the front end request which is, in this case, a JSON package.
		$requestObject = json_decode($requestContent);
		// This Line Then decodes the JSON package and stores that result in $requestObject


		//make sure tweet content is available (required field)
		if(empty($requestObject->tweetContent) === true) {
			throw(new \InvalidArgumentException ("No content for Tweet.", 405));
		}

		// make sure tweet date is accurate (optional field)
		if(empty($requestObject->tweetDate) === true) {
			$requestObject->tweetDate = date("y-m-d H:i:s");
		}

		//perform the actual put or post
		if($method === "PUT") {

			// retrieve the tweet to update
			$tweet = Tweet::getTweetByTweetId($pdo, $id);
			if($tweet === null) {
				throw(new RuntimeException("Tweet does not exist", 404));
			}

			//enforce the user is signed in and only trying to edit their own tweet
			if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $tweet->getTweetProfileId()) {
				throw(new \InvalidArgumentException("You are not allowed to edit this tweet", 403));
			}

			// update all attributes
			$tweet->setTweetDate($requestObject->tweetDate);
			$tweet->setTweetContent($requestObject->tweetContent);
			$tweet->update($pdo);

			// update reply
			$reply->message = "Tweet updated OK";

		} else if($method === "POST") {

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to post tweets", 403));
			}

			// create new tweet and insert into the database
			$tweet = new Tweet(null, $_SESSION["profile"]->getProfileId(), $requestObject->tweetContent, null);
			$tweet->insert($pdo);

			// update reply
			$reply->message = "Tweet created OK";
		}

	} else if($method === "DELETE") {

		//enforce that the end user has a XSRF token.
		verifyXsrf();

		// retrieve the Tweet to be deleted
		$tweet = Tweet::getTweetByTweetId($pdo, $id);
		if($tweet === null) {
			throw(new RuntimeException("Tweet does not exist", 404));
		}

		//enforce the user is signed in and only trying to edit their own tweet
		if(empty($_SESSION["profile"]) === true || $_SESSION["profile"]->getProfileId() !== $tweet->getTweetProfileId()) {
			throw(new \InvalidArgumentException("You are not allowed to delete this tweet", 403));
		}

		// delete tweet
		$tweet->delete($pdo);
		// update reply
		$reply->message = "Tweet deleted OK";
	} else {
		throw (new InvalidArgumentException("Invalid HTTP method request"));
	}
// update the $reply->status $reply->message
} catch(\Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}

header("Content-type: application/json");
if($reply->data === null) {
	unset($reply->data);
}

// encode and return reply to front end caller
echo json_encode($reply);