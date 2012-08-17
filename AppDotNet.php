<?php
/*
|
|  AppDotNetPHP: App.net PHP library
|    created by Josh Dolitsky, August 2012
|    https://github.com/jdolitsky/AppDotNetPHP
|
|  Contributions by Robin Beckett on 17th August 2012
|  Brings library up to Spec for PHP V5.3
|
|  For more info on App.net, please visit:
|    https://join.app.net/
|    https://github.com/appdotnet/api-spec
|
*/

// comment these two lines out in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

class AppDotNet {

	// 1.) Enter your Client ID
	private $_clientId = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

	// 2.) Enter your Client Secret
	private $_clientSecret = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';

	// 3.) Enter your Callback URL
	private $_redirectUri = 'http://your-website.com/callback.php';
	
	// 4.) Add or remove scopes
	private $_scope = array(
		'stream','email','write_post','follow','messages','export'
	);
	
	// Set to FALSE if you dont want sessions to be started right now
	private $_session = TRUE;

	private $_baseUrl = 'https://alpha-api.app.net/stream/0/';
	private $_authUrl = 'https://alpha.app.net/oauth/';

	private $_authSignInUrl;
	private $_authPostParams=array();

	// constructor
	public function __construct() {
		$this->_scope = implode('+',$this->_scope);

		$this->_authSignInUrl = $this->_authUrl.'authenticate?client_id='.$this->_clientId
				        .'&response_type=code&redirect_uri='.$this->_redirectUri
				        .'&scope='.$this->_scope;

		$this->_authPostParams = array('client_id'=>$this->_clientId,
						'client_secret'=>$this->_clientSecret,
		      			'grant_type'=>'authorization_code',
						'redirect_uri'=>$this->_redirectUri);
		
		if ($this->_session === TRUE) session_start();
	}

	// returns the authentication URL constructed above
	public function getAuthUrl() {
		return $this->_authSignInUrl;
	}

	// user login
	public function setSession() {
		if (isset($_GET['code'])) {
			$code = $_GET['code'];
			$this->_authPostParams['code'] = $code;
			$res = $this->httpPost($this->_authUrl.'access_token', $this->_authPostParams);
			$access_token = $res['access_token'];
			$_SESSION['AppDotNetPHPAccessToken'] = $access_token;
			return $access_token;
		}
		return false;
	}

	// check if user is logged in
	public function getSession() {
		if (isset($_SESSION['AppDotNetPHPAccessToken'])) {
			return $_SESSION['AppDotNetPHPAccessToken'];
		} else {
			return false;
		}
	}

	// log the user out
	public function deleteSession() {
		session_unset();
		return false;
	}

	// Return the Filters for the current user.
	public function getAllFilters() {
		return $this->httpGet($this->_baseUrl.'filters');
	}

	// Create a Filter for the current user.
	function createFilter($name='New filter', $user_ids=array(), $hashtags=array(), 
                                     $link_domains=array(), $mention_user_ids= array()) {

		$params = array('name'=>$name, 'user_ids'=>$user_ids, 'hashtags'=>$hashtags,
				'link_domains'=>$link_domains, 'mention_user_ids'=>$mention_user_ids);
		
		return $this->httpPost($this->_baseUrl.'filters',$params);

	}

	// Returns a specific Filter object.
	public function getFilter($filter_id=null) {
		return $this->httpGet($this->_baseUrl.'filters/'.$filter_id);
	}

	// Delete a Filter. The Filter must belong to the current User. 
	// It returns the deleted Filter on success.
	public function deleteFilter($filter_id=null) {
		return $this->httpDelete($this->_baseUrl.'filters');
	}

	// Create a new Post object. Mentions and hashtags will be parsed out of the 
	// post text, as will bare URLs. To create a link in a post without using a 
	// bare URL, include the anchor text in the post's text and include a link 
	// entity in the post creation call.
	public function createPost($text=null, $reply_to=null, $annotations=null, $links=null) {
		$params = array('text'=>$text, 'reply_to'=>$reply_to, 
                                'annotations'=>$annotations, 'links'=>$links);

		return $this->httpPost($this->_baseUrl.'posts',$params);
	}

	// Returns a specific Post.
	public function getPost($post_id=null) {
		return $this->httpGet($this->_baseUrl.'posts/'.$post_id);
	}

	// Delete a Post. The current user must be the same user who created the Post. 
	// It returns the deleted Post on success.
	public function deletePost($post_id=null) {
		return $this->httpDelete($this->_baseUrl.'posts/'.$post_id);
	}

	// Retrieve the Posts that are 'in reply to' a specific Post.
	public function getPostReplies($post_id=null) {
		return $this->httpGet($this->_baseUrl.'posts/'.$post_id.'/replies');
	}

	// Get the most recent Posts created by a specific User in reverse 
	// chronological order.
	public function getUserPosts($user_id='me') {
		return $this->httpGet($this->_baseUrl.'users/'.$user_id.'/posts');
	}

	// Get the most recent Posts mentioning by a specific User in reverse 
	// chronological order.
	public function getUserMentions($user_id='me') {
		return $this->httpGet($this->_baseUrl.'users/'.$user_id.'/mentions');
	}

	// Return the 20 most recent Posts from the current User and 
	// the Users they follow.
	public function getUserStream($user_id='me') {
		return $this->httpGet($this->_baseUrl.'posts/stream/global');
		//return $this->httpGet($this->_baseUrl.'users/'.$user_id.'/stream');
	}

	// Returns a specific User object.
	public function getUser($user_id='me') {
		return $this->httpGet($this->_baseUrl.'users/'.$user_id);
	}

	// Returns the User object of the user being followed.
	public function followUser($user_id=null) {
		return $this->httpPost($this->_baseUrl.'users/'.$user_id.'/follow');
	}

	// Returns the User object of the user being unfollowed.
	public function unfollowUser($user_id=null) {
		return $this->httpDelete($this->_baseUrl.'users/'.$user_id.'/follow');
	}

	// Returns an array of User objects the specified user is following.
	public function getFollowing($user_id='me') {
		return $this->httpGet($this->_baseUrl.'users/'.$user_id.'/following');
	}

	// Returns an array of User objects for users following the specified user.
	public function getFollowers($user_id='me') {
		return $this->httpGet($this->_baseUrl.'users/'.$user_id.'/followers');
	}

	// Return the 20 most recent Posts for a specific hashtag.
	public function searchHashtags($hashtag=null) {
		return $this->httpGet($this->_baseUrl.'posts/tag/'.$hashtag);
	}

	// Retrieve a personalized Stream for the current authorized User. This endpoint 
	// is similar to the 'Retrieve a User's personalized stream' endpoint.
	public function getUserRealTimeStream() {
		return $this->httpGet($this->_baseUrl.'posts/stream/global');
		//return $this->httpGet($this->_baseUrl.'streams/user');

	}

	// Retrieve a personalized Stream for the specified users. This endpoint is similar 
	// to the 'Retrieve a User's personalized stream' endpoint.
	public function getUsersRealTimeStream($user_ids=null) {
		$str = json_encode($user_ids);
		return $this->httpGet($this->_baseUrl.'streams/app?user_ids='.$str);
	}

	// Retrieve a Stream of all public Posts on App.net.
	public function getPublicPosts() {
		return $this->httpGet($this->_baseUrl.'posts/stream/global');
		//return $this->httpGet($this->_baseUrl.'streams/public');
	}

	// Retrieve the current status for a specific Stream
	public function getStreamStatus($stream_id=null) {
		return $this->httpGet($this->_baseUrl.'streams/'.$stream_id);
	}

	// Change the Posts returned in the specified Stream.
	public function controlStream($stream_id=null, $data=array()) {
		return $this->httpPost($this->_baseUrl.'streams/'.$stream_id, $data);
	}

	// List all the Subscriptions this app is currently subscribed to. 
	// This resource must be accessed with an App access token.
	public function listSubscriptions() {
		return $this->httpGet($this->_baseUrl.'subscriptions');
	}

	// Create a new subscription. Returns either 201 CREATED or an error 
	// status code. Please read the general subscription information to 
	// understand the entire subscription process. This resource must be 
	// accessed with an App access token.
	public function createSubscription($object='user', $aspect=null, 
                                    $callback_url=null, $verify_token=null) {

		$params = array('object'=>$object, 'aspect'=>$aspect, 
                           'callback_url'=>$callback_url, 'verify_token'=>$verify_token);

		return $this->httpPost($this->_baseUrl.'subscriptions', $params);
	}

	// Delete a single subscription. Returns the deleted subscription. 
	// This resource must be accessed with an App access token.
	public function deleteSubscription($subscription_id=null) {
		return $this->httpDelete($this->_baseUrl.'subscriptions/'.$subscription_id);
	}

	// Delete all subscriptions for the authorized App. Returns a list 
	// of the deleted subscriptions. This resource must be accessed with 
	// an App access token.
	public function deleteAllSubscriptions() {
		return $this->httpDelete($this->_baseUrl.'subscriptions');
	}

	// workaround function to return userID by username
	public function getIdByUsername($username=null) {
		$ch = curl_init('https://alpha.app.net/'.$username); 
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_USERAGENT,
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
		$response = curl_exec($ch); 
		curl_close($ch);
		$temp = explode('title="User Id ',$response);
		$temp2 = explode('"',$temp[1]);
		$user_id = $temp2[0];
		return $user_id;
	}

	// function to handle all POST requests
	private function httpPost($req, $params=array()) {
		$ch = curl_init($req); 
		curl_setopt($ch, CURLOPT_POST, true);
		$access_token = $this->getSession();
		if ($access_token) {
			curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: Bearer '.$access_token));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$qs = http_build_query($params);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
		$response = curl_exec($ch); 
		curl_close($ch);
		$response = json_decode($response,true);
		if (isset($response['error'])) {
			exit('AppDotNetPHP<br>Error accessing: <br>'.$req.'<br>Error code: '.$response['error']['code']);
		} else {
			return $response;
		}
	}

	// function to handle all GET requests
	private function httpGet($req) {
		$ch = curl_init($req); 
		curl_setopt($ch, CURLOPT_POST, false);
		$access_token = $this->getSession();
		if ($access_token) {
			curl_setopt($ch,CURLOPT_HTTPHEADER,array('Authorization: Bearer '.$access_token));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch); 
		curl_close($ch);
		$response = json_decode($response,true);
		if (isset($response['error'])) {
			exit('AppDotNetPHP<br>Error accessing: <br>'.$req.'<br>Error code: '.$response['error']['code']);
		} else {
			return $response;
		}
		
	}

	// function to handle all DELETE requests
	private function httpDelete($req) {
		// Deletions absolutely require an access token
		$access_token = $this->getSession();
		if ($access_token) {
			$ch = curl_init($req); 
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$access_token));
			curl_exec($ch); 
			curl_close($ch);
			return true;
		} else {
			return false;
		}
	}
}
