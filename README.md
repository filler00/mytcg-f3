# Fat-Free MyTCG
**MyTCG-f3** was created to provide TCG owners and players a more stable and secure alternative to the original MyTCG script. It's open source, built with PHP on top of the Fat-Free Framework, and retains many of the features of its predecessor while optimizing and improving them.

Features introduced in MyTCG-f3 include:

* Built on [Fat-Free Framework](http://fatfreeframework.com/home) (F3)
* [MVC-style structure](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)
* Parameterized queries & password_hash() for increased security
* SwiftMailer integration. Simplifies the use of SMTP, HTML emails, and automatic multipart support for improved email deliverability
* Semantic URLs
* Permanent logs are default
* Delegate admin access to multiple users
* Streamlined plugin system
* Supports Bootstrap styles out of the box (or you can just remove the included styles and templates to rock your own)
* Many things have been re-organized and a few things removed to make things as clean and simple as possible!

## Demo
http://code.filler00.com/mytcg-f3/

## Download
Download the latest stable release from GitHub: https://github.com/tooblue/mytcg-f3/releases

Or if you know what you're doing, you can download the very latest version from the master branch: https://github.com/tooblue/mytcg-f3/archive/master.zip

## Configuration
Once you've downloaded MyTCG-f3 and placed the files in your TCG project's directory, the first thing that you should do is change the default settings in your configuration file. Locate the `config.ini.tmp` file (it should in the project's root directory) and rename it to `config.ini`. This is your TCG's global configuration file which contains your database and administrator credentials, email settings, and other global variables that you can use to customize your TCG.

## Documentation & Support
Detailed documentation is available on the [MyTCG-f3 GitHub Wiki](https://github.com/tooblue/mytcg-f3/wiki). Get support or report issues via the [GitHub Issue Tracker](https://github.com/tooblue/mytcg-f3/issues) or the [MyTCG-f3 Support Forums](https://forum.filler00.com/conversations/mytcg-f3).