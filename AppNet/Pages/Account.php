<?php

    /**
     * AppNet pages
     */

    namespace IdnoPlugins\AppNet\Pages {

        /**
         * Default class to serve AppNet-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($appnet = \Idno\Core\site()->plugins()->get('AppNet')) {
                    if (!$appnet->hasAppNet()) {
                        if ($appnetAPI = $appnet->connect()) {
                            $login_url = $appnetAPI->getAuthenticationUrl(
				\IdnoPlugins\AppNet\Main::$AUTHORIZATION_ENDPOINT,
				\IdnoPlugins\AppNet\Main::getRedirectUrl(),
				['response_type' => 'code', 'state' => \IdnoPlugins\AppNet\Main::getState(), 'scope' => 'basic,write_post'] 
                            );
			    
                        }
                    } else {
                        $login_url = '';
                    }
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(['login_url' => $login_url])->draw('account/appnet');
                $t->__(['title' => 'AppNet', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->appnet = [];
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your AppNet settings have been removed from your account.');
                }
                $this->forward('/account/appnet/');
            }

        }

    }