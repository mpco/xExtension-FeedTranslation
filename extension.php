<?php

class FeedTranslationExtension extends Minz_Extension
{
	public function init()
	{
		$this->registerTranslates();
		$this->registerHook('entry_before_insert', array($this, 'handle_entry'));
	}

	public function handleConfigureAction()
	{
		$this->registerTranslates();
		if (Minz_Request::isPost())
		{
			FreshRSS_Context::$user_conf->ft_engine = Minz_Request::param('ft_engine', 'YouDao');
			FreshRSS_Context::$user_conf->ft_match_feed_urls = Minz_Request::param('ft_match_feed_urls', '');
			
			FreshRSS_Context::$user_conf->save();
		}
	}

	public function handle_entry($entry)
	{
		// 获取原标题
        $entry_orig_title = $entry->title();
		$entry_link = $entry->link();

		// Minz_Log::debug('##############');
		$feed = $entry->feed();
		$feed_url = $feed->url();
		$feedId = $entry->feedId();
        
		// 不知道不同系统使用会不会导致换行符不一样
		$ft_match_feed_urls_array = explode("\r\n", FreshRSS_Context::$user_conf->ft_match_feed_urls);

		// Minz_Log::debug('############## here');
		
		if ( !in_array($feed_url, $ft_match_feed_urls_array) ) {
			return $entry;
		}

		// make request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://fanyi.youdao.com/translate?&doctype=json&type=AUTO&i='.$entry_orig_title); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$response = curl_exec($ch);   

		// handle error; error output
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
			curl_close($ch);
			return $entry;
		}

		// convert response
		$output = json_decode($response, true);
		$title_translation = $output['translateResult'][0][0]['tgt'];
		// Minz_Log::debug($title_translation);

		$entry->_title($title_translation ."  ". $entry_orig_title);
		return $entry;
	}
}

?>
