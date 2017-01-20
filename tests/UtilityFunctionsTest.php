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
}