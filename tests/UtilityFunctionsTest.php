<?php

require_once 'shaarli2twitter/shaarli2twitter.php';

class UtilityFunctionsTest extends PHPUnit_Framework_TestCase
{
    public function testReplaceUrlByTcoDefault()
    {
        $tweet = 'bla https://domain.tld:443/test?oui=non#hash #bloup';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH) . ' #bloup';
        $this->assertEquals($expected, s2t_replace_url_by_tco($tweet));

        $tweet = 'bla https://domain.tld:443/test.php?oui=non#hash';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH);
        $this->assertEquals($expected, s2t_replace_url_by_tco($tweet));

        $tweet = 'bla http://domain.tld.';
        $expected = 'bla ' . str_repeat('#', TWEET_URL_LENGTH) . '.';
        $this->assertEquals($expected, s2t_replace_url_by_tco($tweet));
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

    public function testMaximumLength()
    {
        $link = [
            'description' => 'Rem ut sunt eum veritatis ut et voluptatum consectetur. Quod consectetur porro fugiat. '
                            .'Provident dolor praesentium perspiciatis rerum. Et facilis et voluptatem debitis animi '
                            .'totam dolores. Provident ipsum nihil iure. Rem ut sunt eum veritatis ut et voluptatum '
                            .'consectetur. Rem ut sunt eum veritatis ut et voluptatum consectetur. Quod consectetur. '
                            .'Provident dolor praesentium perspiciatis rerum. Et facilis et voluptatem debitis animi '
                            .'totam dolores. Provident ipsum nihil iure. Rem ut sunt eum veritatis ut et voluptatum '
                            .'consectetur.',
            'permalink'   => 'https://links.hoa.ro/?UYepZA',
            'shorturl'    => 'UYepZA',
            'url'         => 'https://twitter.com/flibitijibibo/status/1035618226435235844',
            'tags'        => ['linux', 'games'],
            'title'       => 'Ethan Lee sur Twitter : "#MeetTheDev I make Linux games. Sometimes macOS and Switch and '
                            .'Xbone games too. This has been going on for about 6 years. Usually it\'s just me at home'
                            .'doing all this. If you squint you may recognize one of these:… https://t.co/ojbwNHM7w8"',
        ];
        $tweet = format_tweet($link, '${description} ${permalink}', 'yes');
        $this->assertEquals(TWEET_LENGTH, strlen(s2t_replace_url_by_tco($tweet)));
        $tweet = format_tweet($link, '#Shaarli ${title} — ${description} ${permalink} ${tags}', 'yes');
        $this->assertEquals(TWEET_LENGTH, strlen(s2t_replace_url_by_tco($tweet)));
        $tweet = format_tweet($link, '#Shaarli ${title} — ${description} ${url} ${tags}', 'yes');
        $this->assertEquals(TWEET_LENGTH, strlen(s2t_replace_url_by_tco($tweet)));
    }
}