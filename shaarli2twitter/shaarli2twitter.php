<?php

/**
 * Shaarli2Twitter plugin
 *
 * This plugin uses the Twitter API to automatically tweet public links published on Shaarli.
 * Note: this requires a valid API authentication using OAuth.
 *
 *
 * Compatibility: Shaarli v0.8.1 and higher.
 */

use Shaarli\Config\ConfigManager;
use Shaarli\Plugin\PluginManager;
use Shaarli\Render\TemplatePage;

/**
 * Maximum tweet length.
 */
const TWEET_LENGTH = 280;

/**
 * Length of t.co transformed URL.
 * Available at https://dev.twitter.com/rest/reference/get/help/configuration,
 * but manually updated here.
 */
const TWEET_URL_LENGTH = 23;

/**
 * Default tweet format if none is provided.
 */
const TWEET_DEFAULT_FORMAT = '#Shaarli: ${title} ${url} ${tags}';

/**
 * Authorized placeholders.
 */
const TWEET_ALLOWED_PLACEHOLDERS = ['url', 'permalink', 'title', 'tags', 'description'];

/**
 * Hide url when sharing a note
 * Values can be 'yes' or 'no'.
 */
const TWEET_HIDE_URL = 'no';


/**
 * Init function: check settings, and set default format.
 *
 * @param ConfigManager $conf instance.
 *
 * @return array|void Error if config is not valid.
 */
function shaarli2twitter_init($conf)
{
    $format = $conf->get('plugins.TWITTER_TWEET_FORMAT');
    if (empty($format)) {
        $conf->set('plugins.TWITTER_TWEET_FORMAT', TWEET_DEFAULT_FORMAT);
    }

    $hide = $conf->get('plugins.TWITTER_HIDE_URL');
    if (empty($hide)) {
        $conf->set('plugins.TWITTER_HIDE_URL', TWEET_HIDE_URL);
    }

    if (!s2t_is_config_valid($conf)) {
        return ['Please set up your Twitter API and token keys in plugin administration page.'];
    }
}

/**
 * Add the CSS file for editlink page
 *
 * @param array $data - header data.
 *
 * @return mixed - header data with s2t CSS file added.
 */
function hook_shaarli2twitter_render_includes($data)
{
    if ($data['_PAGE_'] == TemplatePage::EDIT_LINK) {
        $data['css_files'][] = PluginManager::$PLUGINS_PATH . '/shaarli2twitter/shaarli2twitter.css';
    }

    return $data;
}

/**
 * Add the JS file: disable the tweet button if the link is set to private.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data with the JS file.
 */
function hook_shaarli2twitter_render_footer($data, $conf)
{
    if ($data['_PAGE_'] == TemplatePage::EDIT_LINK) {
        $data['js_files'][] = PluginManager::$PLUGINS_PATH . '/shaarli2twitter/shaarli2twitter.js';
    }

    return $data;
}

/**
 * Hook save link: will automatically publish a tweet when a new public link is shaared.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data not altered.
 */
function hook_shaarli2twitter_save_link($data, $conf)
{
    // No tweet without config, for private links, or on edit.
    if (!s2t_is_config_valid($conf)
        || (isset($data['updated']) && $data['updated'] != false)
        || $data['private']
        || !isset($_POST['tweet'])
    ) {
        return $data;
    }

    // We make sure not to alter data
    $link = $data;

    // We will use an array to generate hashtags, then restore original shaare tags.
    $data['tags'] = array_values(array_filter(explode(' ', $data['tags'])));
    for ($i = 0; $i < count($data['tags']); $i++) {
        // Keep tags strictly alphanumerical because Twitter only allows that.
        $data['tags'][$i] = s2t_get_tagify($data['tags'][$i]);
    }


    $data['permalink'] = index_url($_SERVER) . '?' . $data['shorturl'];

    // In case of note, we use permalink
    if (s2t_is_link_note($data)) {
        $data['url'] = $data['permalink'];
    }

    $hide = $conf->get('plugins.TWITTER_HIDE_URL', TWEET_HIDE_URL);
    if (! empty($_POST['s2t-content'])) {
        $format = escape($_POST['s2t-content']);
    } else {
        $format = $conf->get('plugins.TWITTER_TWEET_FORMAT', TWEET_DEFAULT_FORMAT);
    }
    $tweet = s2t_format_tweet($data, $format, $hide);
    $response = s2t_tweet($conf, $tweet);
    $response = json_decode($response, true);
    // If an error has occurred, not blocking: just log it.
    if (isset($response['errors'])) {
        foreach ($response['errors'] as $error) {
            error_log('Twitter error ' . $error['code'] . ': ' . $error['message']);
            error_log('Tweet: "' . $tweet . '"');
        }
    }

    return $link;
}

/**
 * Hook render_editlink: add a checkbox to tweet the new link or not.
 *
 * @param array         $data New link values.
 * @param ConfigManager $conf instance.
 *
 * @return array $data with `edit_link_plugin` placeholder filled.
 */
function hook_shaarli2twitter_render_editlink($data, $conf)
{
    if (!$data['link_is_new'] || !s2t_is_config_valid($conf)) {
        return $data;
    }

    $private = $conf->get('privacy.default_private_links', false);

    $html = file_get_contents(PluginManager::$PLUGINS_PATH . '/shaarli2twitter/edit_link.html');
    $html = sprintf(
        $html,
        $private ? '' : 'checked="checked"',
        $conf->get('plugins.TWITTER_TWEET_FORMAT', TWEET_DEFAULT_FORMAT)
    );

    $data['edit_link_plugin'][] = $html;

    return $data;
}

/**
 * Use TwitterAPIExchange to publish the tweet.
 *
 * @param ConfigManager $conf
 * @param string        $tweet
 *
 * @return string JSON response string.
 */
function s2t_tweet($conf, $tweet)
{
    require_once 'TwitterApi/TwitterAPIExchange.php';

    $endpoint = 'https://api.twitter.com/1.1/statuses/update.json';
    $postfields = [
        'status' => $tweet,
    ];
    $settings = [
        'consumer_key'              => $conf->get('plugins.TWITTER_API_KEY'),
        'consumer_secret'           => $conf->get('plugins.TWITTER_API_SECRET'),
        'oauth_access_token'        => $conf->get('plugins.TWITTER_ACCESS_TOKEN'),
        'oauth_access_token_secret' => $conf->get('plugins.TWITTER_ACCESS_TOKEN_SECRET'),
    ];
    $twitter = new \TwitterAPIExchange($settings);

    return $twitter->buildOauth($endpoint, 'POST')
        ->setPostfields($postfields)
        ->performRequest();
}

/**
 * This function will put link data in format placeholders, without overreaching 280 char.
 * Placeholders have priorities, and will be replace until the limit is reached:
 *   1. URL
 *   2. Title
 *   3. Tags
 *   4. Description
 *
 * @param array  $link    Link data.
 * @param string $format  Tweet format with placeholders.
 * @param bool   $hideUrl Hide URL if it's a note and the tweet is too long.
 *
 * @return string Message to tweet.
 */
function s2t_format_tweet($link, $format, $hideUrl)
{
    // Tweets are limited to 280 chars, we need to prioritize what will be displayed
    $priorities = TWEET_ALLOWED_PLACEHOLDERS;

    // Hide URL when sharing a note (microblog mode)
    if ($hideUrl == 'yes' && s2t_is_link_note($link)) {
        unset($priorities[array_search('url', $priorities)]);
        unset($priorities[array_search('permalink', $priorities)]);
        $priorities[] = 'url';
        $priorities[] = 'permalink';
    }

    // We remove URL from description, title and tags.
    // It breaks the length, and often creates an unreadable tweet, with broken links.
    $link = s2t_strip_url_from_link($link);

    $tweet = $format;
    foreach ($priorities as $priority) {
        if (s2t_get_current_length($tweet) >= TWEET_LENGTH) {
            return s2t_remove_remaining_placeholders($tweet);
        }

        $tweet = s2t_replace_placeholder($tweet, $priority, $link[$priority]);
    }

    return trim($tweet);
}

/**
 * Replace a single placeholder in format.
 *
 * @param string       $tweet       Current tweet still containing placeholders.
 * @param string       $placeholder Placeholder to replace.
 * @param array|string $value       Value to replace placeholder (can be an array for tags).
 *
 * @return string $tweet with $placeholder replaced by $value.
 */
function s2t_replace_placeholder($tweet, $placeholder, $value)
{
    if (is_array($value)) {
        return s2t_replace_placeholder_array($tweet, $placeholder, $value);
    }

    $current = s2t_get_current_length($tweet);
    // Tweets URL have a fixed size due to t.co
    $valueLength = ($placeholder != 'url' && $placeholder != 'permalink') ? strlen($value) : TWEET_URL_LENGTH;
    if ($current + $valueLength > TWEET_LENGTH) {
        if ($placeholder != 'url' && $placeholder != 'permalink' && TWEET_LENGTH - $current > 3) {
            $value = mb_strcut($value, 0, TWEET_LENGTH - $current - 3) . '…';
        } else {
            $value = '';
        }
    }

    return str_replace('${' . $placeholder . '}', $value, $tweet);
}

/**
 * Replace a single placeholder with an array value.
 * Use for tags.
 *
 * @param string $tweet       Current tweet still containing placeholders.
 * @param string $placeholder Placeholder to replace.
 * @param array  $value       Values to replace placeholder (will be separated by a space).
 *
 * @return string $tweet with $placeholder replace by the list of $value.
 */
function s2t_replace_placeholder_array($tweet, $placeholder, $value)
{
    $items = '';
    for ($i = 0; $i < count($value); $i++) {
        $current = s2t_get_current_length($tweet);
        $space = $i == 0 ? '' : ' ';
        if ($current + strlen($items) + strlen($value[$i] . $space) > TWEET_LENGTH) {
            break;
        }
        $items .= $space . $value[$i];
    }

    return str_replace('${' . $placeholder . '}', $items, $tweet);
}

/**
 * Get the current length of the tweet without any placeholder.
 *
 * @param string $tweet Current state of the tweet (with or without placeholders left).
 *
 * @return int Tweet length.
 */
function s2t_get_current_length($tweet)
{
    return strlen(s2t_remove_remaining_placeholders(s2t_replace_url_by_tco($tweet)));
}

/**
 * Remove remaining placeholders from the tweet.
 *
 * @param string $tweet Current string for the tweet.
 *
 * @return string $tweet without any placeholder.
 */
function s2t_remove_remaining_placeholders($tweet)
{
    return preg_replace('#\${\w+}#', '', $tweet);
}

/**
 * Replace all URL by a default string of TWEET_URL_LENGTH characters.
 *
 * @param string $tweet Current string for the tweet.
 *
 * @return string $tweet without any URL.
 */
function s2t_replace_url_by_tco($tweet)
{
    $regex = '!https?://\S+[[:alnum:]]/?!si';

    return preg_replace($regex, str_repeat('#', TWEET_URL_LENGTH), $tweet);
}

/**
 * Make sure that all config keys has been set.
 *
 * @param ConfigManager $conf instance.
 *
 * @return bool true if the config is valid, false otherwise.
 */
function s2t_is_config_valid($conf)
{
    $mandatory = [
        'TWITTER_API_KEY',
        'TWITTER_API_SECRET',
        'TWITTER_ACCESS_TOKEN',
        'TWITTER_ACCESS_TOKEN_SECRET',
    ];
    foreach ($mandatory as $value) {
        $setting = $conf->get('plugins.' . $value);
        if (empty($setting)) {
            return false;
        }
    }

    return true;
}

/**
 * Determines if the link is a note.
 * From kalvn's shaarli2mastodon - https://github.com/kalvn/shaarli2mastodon
 *
 * @param  array $link The link to check.
 *
 * @return boolean      Whether the link is a note or not.
 */
function s2t_is_link_note($link)
{
    return strpos($link['url'], $link['shorturl']) !== false;
}

/**
 * Modifies a tag to make them real Tweet tags.
 * From kalvn's shaarli2mastodon - https://github.com/kalvn/shaarli2mastodon
 *
 * @param  string $tag The tag to change.
 *
 * @return string      The tag modified to be valid.
 */
function s2t_get_tagify($tag)
{
    // Regex inspired by https://gist.github.com/janogarcia/3946583
    return '#' . preg_replace('/[^0-9_\p{L}]/u', '', $tag);
}

/**
 * Remove links in description, title and tags to prevent
 * length errors, broken links and unreadable tweets.
 *
 * @param array $link A link array with all its fields
 *
 * @return array the link with URL stripped
 */
function s2t_strip_url_from_link($link)
{
    foreach (['description', 'title', 'tags'] as $field) {
        if (is_array($link[$field])) {
            $link[$field] = array_map('s2t_strip_url', $link[$field]);
        } else {
            $link[$field] = s2t_strip_url($link[$field]);
        }
    }
    return $link;
}

function s2t_strip_url($field)
{
    $regex = '!https?://\S+[[:alnum:]]/?!si';
    return preg_replace($regex, '', $field);
}
