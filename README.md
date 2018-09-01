# Shaarli2Twitter plugin

This plugin uses the Twitter API to automatically tweet public links published on 
[Shaarli](https://github.com/shaarli/Shaarli/).

Everytime a new link is shaared, use the "Tweet" checkbox in the form 
to post it on Twitter.

> Note: private links and link edits can't be tweeted.

## Requirements

  - PHP 5.6
  - PHP cURL extension
  - PHP mbstring extension
  - Shaarli >= v0.8.1 (still valid in v0.10.2)

## Installation

Download the latest [release](https://github.com/ArthurHoaro/shaarli2twitter/releases),
and put the folder `shaarli2twitter` under your `plugins/` directory.

Then you can enable the plugin in the plugin administration page `http://shaarli.tld/?do=pluginadmin`.
 
> Note: the foldername **must** be `shaarli2twitter` to work.

Example in command lines:

```bash
wget <release URL>.tar.gz
tar xfz <archive>.tar.gz
mv <archive>/shaarli2twitter /path/to/shaarli/plugins
```

## Configuration
 
For this plugin to work, you need to register your Shaarli as a Twitter application in your account,
and retrieve 4 keys used to authenticate API calls.

You must set this keys in the plugin administration page

### Step 1: Create an application

While authenticated to your Twitter account, reach this page: https://apps.twitter.com/app/

And Create a new app: name/description are not important, but you may need to put a valid website.  
Leave "Callback URL" blank.

### Step 2: Generate an access token

In your freshly new app page, go to the tab called "Keys and Access Tokens".

Then click on "Create my access token" at the bottom.

### Step 3: Plugin configuration

You now have everything required to set up shaarli2twitter plugin.

![](https://cloud.githubusercontent.com/assets/1962678/20008438/ddfa0326-a2a0-11e6-87a7-44319da34d1d.png)

## Settings

### TWITTER_TWEET_FORMAT

This setting shows the format of tweets. You can use placeholders which will be filled 
until the 280 chars limit is reached. Values may be truncated if the limit is reached.

Available placeholders, in order of priority:

  * `${url}`: Shaare URL (will be automatically replaced as `t.co` links).
  * `${permalink}`: Shaare permalink (will be automatically replaced as `t.co` links).
  * `${title}`: Shaare title.
  * `${tags}`: List of shaare tags displayed as hashtags (`#tag1 #tag2`...).
  * `${description}`: Shaare description.   
 
Default format: `#Shaarli: ${title} ${url} ${tags}`

Which will render, for example as:

    #Shaarli: Wikipedia, the free encyclopedia https://en.wikipedia.org/wiki/Main_Page #crowdsourcing #knowledge

### TWITTER_HIDE_URL

Hide ${url} and/or ${permalink} when sharing a note to long to hold in a tweet.

Values: `yes` to hide URL or `no` to keep them. Default value is `no`.

## License

MIT License, see LICENSE.md.
