<?php

namespace IdnoPlugins\AppNet {

    class Main extends \Idno\Common\Plugin {

	public static $AUTHORIZATION_ENDPOINT = 'https://account.app.net/oauth/authenticate';
	public static $TOKEN_ENDPOINT = 'https://account.app.net/oauth/access_token';

	public static function getRedirectUrl() {
	    return \Idno\Core\site()->config()->url . 'appnet/callback';
	}

	public static function getState() {
	    return md5(\Idno\Core\site()->config()->site_secret . \Idno\Core\site()->config()->url . dirname(__FILE__));
	}
	
	/**
	 * Parse entities in message body.
	 * Returns activated links, hashtags and users.
	 * @param type $text
	 */
	protected function getEntities($text) {
	    
	    $entities = new \stdClass();
	    
	    // Parse links
	    if (preg_match_all('#\bhttps?://[^\s]+#s', $text, $links, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		
		$entities->links = [];
		
		foreach ($links[0] as $link) {
		    
		    $tmp = new \stdClass();
		    $tmp->len = strlen($link[0]);
		    $tmp->pos = $link[1];
		    $tmp->text = $link[0];
		    $tmp->url = $link[0];
		    
		    $entities->links[] = $tmp;
		}
	    }
	    
	    return $entities;
	}

	function registerPages() {
	    // Register the callback URL
	    \Idno\Core\site()->addPageHandler('appnet/callback', '\IdnoPlugins\AppNet\Pages\Callback');
	    // Register admin settings
	    \Idno\Core\site()->addPageHandler('admin/appnet', '\IdnoPlugins\AppNet\Pages\Admin');
	    // Register settings page
	    \Idno\Core\site()->addPageHandler('account/appnet', '\IdnoPlugins\AppNet\Pages\Account');

	    /** Template extensions */
	    // Add menu items to account & administration screens
	    \Idno\Core\site()->template()->extendTemplate('admin/menu/items', 'admin/appnet/menu');
	    \Idno\Core\site()->template()->extendTemplate('account/menu/items', 'account/appnet/menu');
	}

	function registerEventHooks() {

	    // Register syndication services
	    \Idno\Core\site()->syndication()->registerService('appnet', function() {
		return $this->hasAppNet();
	    }, ['note', 'article']);


	    // Push "notes" to AppNet
	    \Idno\Core\site()->addEventHook('post/note/appnet', function(\Idno\Core\Event $event) {

		$object = $event->data()['object'];
		if ($this->hasAppNet()) {
		    if ($appnetAPI = $this->connect()) {
			$appnetAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->appnet['access_token']);
			$message = strip_tags($object->getDescription());

			if (!empty($message) && substr($message, 0, 1) != '@') {

			    try {

				$entity = new \stdClass();
				$entity->text = $message;
				$entity->entities = $this->getEntities($message);
				$entity->parse_links = true;
				
				$result = \Idno\Core\Webservice::post('https://api.app.net/posts?access_token=' . $appnetAPI->access_token, json_encode($entity /*[
					    'text' => $message,
					    'entities' => $this->getEntities($message)
				]*/), ['Content-Type: application/json']);
				$content = json_decode($result['content']);

				if ($result['response'] < 400) {
				    // Success
				    $link = $content->data->canonical_url; // We don't have a full posse link here, so we have to link to appnet account

				    $object->setPosseLink('appnet', $link);
				    $object->save();
				} else {
				    \Idno\Core\site()->logging->log("AppNet Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				    throw new \Exception($content->meta->error_message);
				}
			    } catch (\Exception $e) {
				\Idno\Core\site()->session()->addMessage($e->getMessage());
			    }
			}
		    }
		}
	    });

	    // Push "articles" to AppNet
	    \Idno\Core\site()->addEventHook('post/article/appnet', function(\Idno\Core\Event $event) {
		$object = $event->data()['object'];
		if ($this->hasAppNet()) {
		    if ($appnetAPI = $this->connect()) {
			$appnetAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->appnet['access_token']);

			try {
			    $status = $object->getTitle();
			    if (strlen($status) > 110) { // Trim status down if required
				$status = substr($status, 0, 106) . ' ...';
			    }
			    $status .= ': ' . $object->getURL();

			    $attachment_list = [];
			    $cross = new \stdClass();
			    $cross->type = 'net.app.core.crosspost';
			    $cross->value = new \stdClass();
			    $cross->value->canonical_url = $object->getUrl();
			    $attachment_list[] = $cross;
			    
			    $entity = new \stdClass();
			    $entity->text = $status;
			    $entity->entities = $this->getEntities($status);
			    $entity->annotations = $attachment_list;
			    $entity->parse_links = true;
			    
			    $result = \Idno\Core\Webservice::post('https://api.app.net/posts?access_token=' . $appnetAPI->access_token, json_encode($entity /*[
					'text' => $status,
					'entities' => $this->getEntities($status),
					'attachments' => $attachment_list // Well, I'm sending this as an attachment, but it doesn't seem to do anything...
			    ]*/), ['Content-Type: application/json']);
			    $content = json_decode($result['content']);

			    if ($result['response'] < 400) {
				// Success
				$link = $content->data->canonical_url; // We don't have a full posse link here, so we have to link to appnet account

				$object->setPosseLink('appnet', $link);
				$object->save();
			    } else {
				\Idno\Core\site()->logging->log("AppNet Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				throw new \Exception($content->meta->error_message);
			    }
			} catch (\Exception $e) {
			    \Idno\Core\site()->session()->addMessage('There was a problem posting to AppNet: ' . $e->getMessage());
			}
		    }
		}
	    });

	    // Push "images" to AppNet (NOT IMPLEMENTED YET)
	    \Idno\Core\site()->addEventHook('post/image/appnet', function(\Idno\Core\Event $event) {
		$object = $event->data()['object'];
		if ($attachments = $object->getAttachments()) {

		    $attachment_list = [];

		    foreach ($attachments as $attachment) {

			$tmp = new \stdClass();

			$tmp->type = 'net.app.core.oembed';
			$tmp->value = new \stdClass();

			$tmp->value->type = 'photo';
			$tmp->value->version = '1.0';
			$tmp->value->title = '1.0';
			$tmp->value->width = $object->width;
			$tmp->value->height = $object->height;
			$tmp->value->url = $attachment['url'];
			

			if (!empty($object->thumbnail_large)) {
			    $src = $object->thumbnail_large;			    
			} else if (!empty($object->small)) { 
			    $src = $object->thumbnail_small;
			} else if (!empty($object->thumbnail)) { // Backwards compatibility
			    $src = $object->thumbnail;
			} else {
			    $src = $attachment['url'];
			}

			$tmp->value->thumbnail_url = $src;
			$tmp->value->thumbnail_width = $width;
			$tmp->value->thumbnail_height = $height;
			
			$attachment_list[] = $tmp;
		    }
		    
		    if ($this->hasAppNet()) {
			if ($appnetAPI = $this->connect()) {
			    $appnetAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->appnet['access_token']);


			    try {

				$status = $object->getTitle();
				$status .= ': ' . $object->getURL();
				
				$entity = new \stdClass();
				$entity->text = $status;
				$entity->entities = $this->getEntities($status);
				$entity->annotations = $attachment_list;
				
				$result = \Idno\Core\Webservice::post('https://api.app.net/posts?include_annotations=1&access_token=' . $appnetAPI->access_token, json_encode($entity), ['Content-Type: application/json']);
				$content = json_decode($result['content']);

				if ($result['response'] < 400) {
				    // Success
				    $link = $content->data->canonical_url; // We don't have a full posse link here, so we have to link to appnet account

				    $object->setPosseLink('appnet', $link);
				    $object->save();
				} else {
				    \Idno\Core\site()->logging->log("AppNet Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				    throw new \Exception($content->meta->error_message);
				}
			    } catch (\Exception $e) {
				\Idno\Core\site()->session()->addMessage('There was a problem posting to AppNet: ' . $e->getMessage());
			    }
			}
		    }
		}
	    });
	}

	/**
	 * Connect to AppNet
	 * @return bool|\IdnoPlugins\AppNet\Client
	 */
	function connect() {
	    if (!empty(\Idno\Core\site()->config()->appnet)) {
		$api = new Client(
			\Idno\Core\site()->config()->appnet['appId'], \Idno\Core\site()->config()->appnet['secret']
		);
		return $api;
	    }
	    return false;
	}

	/**
	 * Can the current user use AppNet?
	 * @return bool
	 */
	function hasAppNet() {
	    if (\Idno\Core\site()->session()->currentUser()->appnet) {
		return true;
	    }
	    return false;
	}

    }

}
