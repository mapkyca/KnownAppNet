<?php

/**
 * AppNet pages
 */

namespace IdnoPlugins\AppNet\Pages {

    /**
     * Default class to serve the AppNet callback
     */
    class Callback extends \Idno\Common\Page {

	function getContent() {
	    $this->gatekeeper(); // Logged-in users only

	    try {
		if ($appnet = \Idno\Core\site()->plugins()->get('AppNet')) {
		    if ($appnetAPI = $appnet->connect()) {

			if ($response = $appnetAPI->getAccessToken(\IdnoPlugins\AppNet\Main::$TOKEN_ENDPOINT, 'authorization_code', [
			    'code' => $this->getInput('code'), 
			    'redirect_uri' => \IdnoPlugins\AppNet\Main::getRedirectUrl(), 
			    'state' => \IdnoPlugins\AppNet\Main::getState()])) {

			    $response = json_decode($response['content']);
			    
			    $user = \Idno\Core\site()->session()->currentUser();
			    if ($response->access_token) {
				$user->appnet = ['access_token' => $response->access_token];
			    
				$user->save();
				\Idno\Core\site()->session()->addMessage('Your App.net account was connected.');
			    } else {
				\Idno\Core\site()->session()->addErrorMessage('There was a problem connecting your App.net account.');
			    }
			}
		    }
		}
	    } catch (\Exception $e) {
		\Idno\Core\site()->session()->addErrorMessage($e->getMessage());
	    }
	    
	    $this->forward('/account/appnet/');
	}

    }

}