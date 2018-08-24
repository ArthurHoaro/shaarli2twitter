<?php

require_once 'shaarli2twitter/shaarli2twitter.php';

class UtilityFunctionsTest extends PHPUnit_Framework_TestCase
{
    public function testReplaceUrlByTcoDefault()
    {
        $tweet = 'bla https://domain.tld:443/test?oui=non#hash #bloup';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH) . ' #bloup';
        $this->assertEquals($expected, replace_url_by_tco($tweet));

        $tweet = 'bla https://domain.tld:443/test.php?oui=non#hash';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH);
        $this->assertEquals($expected, replace_url_by_tco($tweet));

        $tweet = 'bla http://domain.tld.';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH) . '.';
        $this->assertEquals($expected, replace_url_by_tco($tweet));
    }

    public function testFormatWithoutURL()
    {
        $link = [
            'description' => 'Rem ut sunt eum veritatis ut et voluptatum consectetur. Quod consectetur porro fugiat. '
                            .'Provident dolor praesentium perspiciatis rerum. Et facilis et voluptatem debitis animi '
                            .'totam dolores. Provident ipsum nihil iure. Rem ut sunt eum veritatis ut et voluptatum '
                            .'consectetur.',
            'permalink'   => 'http://abc.def',
            'shorturl'    => 'kek',
            'url'         => '?kek',
            'tags'        => '',
            'title'       => '',
        ];
        $this->assertEquals($link['description'], format_tweet($link, '${description} ${permalink}', 'yes'));
    }
}