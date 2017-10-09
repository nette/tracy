[Tracy](https://tracy.nette.org) - PHP debugger
==============================================

[![Downloads this Month](https://img.shields.io/packagist/dm/tracy/tracy.svg)](https://packagist.org/packages/tracy/tracy)
[![Build Status](https://travis-ci.org/nette/tracy.svg?branch=master)](https://travis-ci.org/nette/tracy)
[![Build Status Windows](https://ci.appveyor.com/api/projects/status/github/nette/tracy?branch=master&svg=true)](https://ci.appveyor.com/project/dg/tracy/branch/master)
[![Latest Stable Version](https://poser.pugx.org/tracy/tracy/v/stable)](https://github.com/nette/tracy/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/tracy/blob/master/license.md)
[![Join the chat at https://gitter.im/nette/tracy](https://badges.gitter.im/nette/tracy.svg)](https://gitter.im/nette/tracy)


Introduction
------------

Tracy library is a useful helper for everyday PHP programmers. It helps you to:

- quickly detect and correct errors
- log errors
- dump variables
- measure execution time of scripts/queries
- see memory consumption


PHP is a perfect language for making hardly detectable errors because it gives a great flexibility to programmers. Tracy\Debugger is more valuable because of that. It is a ultimate tool among the diagnostic ones.
If you are meeting Tracy the first time, believe me, your life starts to be divided one before the Tracy and the one with her.
Welcome to the good part!

Documentation can be found on the [website](https://tracy.nette.org).

If you like Tracy, **[please make a donation now](https://nette.org/make-donation?to=tracy)**. Thank you!


Installation
------------

The recommended way to is via Composer:

```
composer require tracy/tracy
```

Alternatively, you can download the whole package or [tracy.phar](https://github.com/nette/tester/releases) file.

| Tracy | PHP | compatible with browsers
|-----------|---------------|----------
| Tracy 2.6 | PHP 7.1 – 7.3 | Chrome 49+, Firefox 45+, MS Edge 14+, Safari 10+ and iOS Safari 10.2+
| Tracy 2.5 | PHP 5.4.4 – 7.3 | Chrome 49+, Firefox 45+, MS Edge 12+, Safari 10+ and iOS Safari 10.2+
| Tracy 2.4 | PHP 5.4.4 – 7.2 | Chrome 29+, Firefox 28+, IE 11+ (except AJAX), MS Edge 12+, Safari 9+ and iOS Safari 9.2+


Usage
-----

Activating Tracy is easy. Simply add these two lines of code, preferably just after library loading (like `require 'vendor/autoload.php'`) and before any output is sent to browser:

```php
use Tracy\Debugger;

Debugger::enable();
```

The first thing you will notice on the website is a Debugger Bar.

(If you do not see anything, it means that Tracy is running in production mode. For security reasons, Tracy is visible only on localhost.
You may force Tracy to run in development mode by passing the `Debugger::DEVELOPMENT` as the first parameter of `enable()` method.)


Debugger Bar
------------

The Debugger Bar is a floating panel. It is displayed in the bottom right corner of a page. You can move it using the mouse. It will remember its position after the page reloading.

[![Debugger-Bar](https://nette.github.io/tracy/images/tracy-bar.png)](https://nette.github.io/tracy/tracy-debug-bar.html)

You can add other useful panels into the Debugger Bar. You can find interesing ones in [Addons](https://addons.nette.org) or you can create your own.

Implementation of custom panel is easy, just implement interface `Tracy\IBarPanel` with two methods `getTab` and `getPanel`, both returning HTML content to be displayed.
Afterward, registering via `Debugger::getBar()->addPanel(new CustomPanel());` is everything you will need to do.


Visualization of errors and exceptions
--------------------------------------

Surely, you know how PHP reports errors: there is something like this in the page source code:

```pre
<b>Parse error</b>:  syntax error, unexpected '}' in <b>HomepagePresenter.php</b> on line <b>15</b>
```

or uncaught exception:

```pre
<b>Fatal error</b>:  Uncaught Nette\MemberAccessException: Call to undefined method Nette\Application\UI\Form::addTest()? in /sandbox/vendor/nette/utils/src/Utils/ObjectMixin.php:100
Stack trace:
#0 /sandbox/vendor/nette/utils/src/Utils/Object.php(75): Nette\Utils\ObjectMixin::call(Object(Nette\Application\UI\Form), 'addTest', Array)
#1 /sandbox/app/forms/SignFormFactory.php(32): Nette\Object-&gt;__call('addTest', Array)
#2 /sandbox/app/presenters/SignPresenter.php(21): App\Forms\SignFormFactory-&gt;create()
#3 /sandbox/vendor/nette/component-model/src/ComponentModel/Container.php(181): App\Presenters\SignPresenter-&gt;createComponentSignInForm('signInForm')
#4 /sandbox/vendor/nette/component-model/src/ComponentModel/Container.php(139): Nette\ComponentModel\Container-&gt;createComponent('signInForm')
#5 /sandbox/temp/cache/latte/15206b353f351f6bfca2c36cc.php(17): Nette\ComponentModel\Co in <b>/sandbox/vendor/nette/utils/src/Utils/ObjectMixin.php</b> on line <b>100</b><br />
```

It is not so easy to navigate through this output. If you enable Tracy, both errors and exceptions are displayed in a completely different form:

[![Uncaught exception rendered by Tracy](https://nette.github.io/tracy/images/tracy-exception.png)](https://nette.github.io/tracy/tracy-exception.html)

The error message literally screams. You can see a part of the source code with the highlighted line where the error occurred. A message clearly explains an error. The entire site is [interactive, try it](https://nette.github.io/tracy/tracy-exception.html).

And you know what? Fatal errors are captured and displayed in the same way. No need to install any extension (click for live example):

[![Fatal error rendered by Tracy](https://nette.github.io/tracy/images/tracy-error.png)](https://nette.github.io/tracy/tracy-error.html)

Errors like a typo in a variable name or an attempt to open a nonexistent file generate reports of E_NOTICE or E_WARNING level. These can be easily overlooked and/or can be completely hidden in a web page graphic layout. Let Tracy manage them:

[![Notice rendered by Tracy](https://nette.github.io/tracy/images/tracy-notice2.png)](https://nette.github.io/tracy/tracy-debug-bar.html)

Or they may be displayed like errors:

```php
Debugger::$strictMode = true;
```

[![Notice rendered by Tracy](https://nette.github.io/tracy/images/tracy-notice.png)](https://nette.github.io/tracy/tracy-notice.html)


Content Security Policy
-----------------------

If your site uses Content Security Policy, you'll need to add `'nonce-<value>'` to `script-src` and eventually the same nonce to `style-src` for Tracy to work properly. Some 3rd plugins may require additional directives.

Configuration example for [Nette Framework](https://nette.org):

```neon
http:
	csp:
		script-src: nonce
		style-src: nonce
```


Faster loading
--------------

The basic integration is straightforward, however if you have slow blocking scripts in web page, they can slow the Tracy loading.
The solution is to place `<?php Tracy\Debugger::renderLoader() ?>` into your template before
any scripts:

```html
<!DOCTYPE html>
<html>
<head>
	<title>...<title>
	<?php Tracy\Debugger::renderLoader() ?>
	<link rel="stylesheet" href="assets/style.css">
	<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
</head>
```


AJAX and redirected requests
----------------------------

Tracy is able to show Debug bar and Bluescreens for AJAX and redirected requests. You just have to start session before Tracy:

```php
session_start();
Debugger::enable();
```

In case you use non-standard session handler, you can start Tracy immediately (in order to handle any errors), then initialize your session handler
and then inform Tracy that session is ready to use via `dispatch()`:

```php
Debugger::enable();

// initialize session handler
session_start();

Debugger::dispatch();
```


Production mode and error logging
---------------------------------

As you can see, Tracy is quite eloquent. It is appreciated in a development environment, but on a production server it would cause a disaster. Any debugging information cannot be listed there. Therefore Tracy has an environment autodetection and logging functionality. Instead of showing herself, Tracy stores information into a log file and shows the visitor a user-comprehensible server error message:

[![Server Error 500](https://nette.github.io/tracy/images/tracy-error2.png)](https://nette.github.io/tracy/tracy-production.html)

Production output mode suppresses all debugging information which is sent out via `dump()` or `Debugger::fireLog()`, and of course all error messages generated by PHP. So, even if you forget `dump($obj)` in the source code, you do not have to worry about it on your production server. Nothing will be seen.

The output mode is set by the first parameter of `Debugger::enable()`. You can specify either a constant `Debugger::PRODUCTION` or `Debugger::DEVELOPMENT`.

If it is not specified, the default value `Debugger::DETECT` is used. In this case the system detects a server by IP address. The production mode is chosen if an application is accessed via public IP address. A local IP address leads to development mode. It is not necessary to set the mode in most cases. The mode is correctly recognized when you are launching the application on your local server or in production.

In the production mode, Tracy automatically captures all errors and exceptions into a text log. Unless you specify otherwise, it will be stored in log/error.log. This error logging is extremely useful. Imagine, that all users of your application are actually betatesters. They are doing cutting-edge work for free when hunting bugs and you would be silly if you threw away their valuable reports to a recycle bin unnoticed.

If you need to log your own messages or caught exceptions, use the method `log()`:

```php
Debugger::log('Unexpected error'); // text message

try {
	criticalOperation();
} catch (Exception $e) {
	Debugger::log($e); // log exception
	// or
	Debugger::log($e, Debugger::ERROR) // also sends an email notification
}
```

A directory for errors logging can be set by the second parameter of the enable() method:

```php
Debugger::enable(Debugger::DETECT, __DIR__ . '/mylog');
```

If you want Tracy to log PHP errors like `E_NOTICE` or `E_WARNING` with detailed information (HTML report), set `Debugger::$logSeverity`:

```php
Debugger::$logSeverity = E_NOTICE | E_WARNING;
```

For a real professional the error log is a crucial source of information and he or she wants to be notified about any new error immediately. Tracy helps him. She is capable of sending an email for every new error record. The variable $email identifies where to send these e-mails:

```php
Debugger::$email = 'admin@example.com';
```

To protect your e-mail box from flood, Tracy sends **only one message** and creates a file `email-sent`. When a developer receives the e-mail notification, he checks the log, corrects his application and deletes the `email-sent` monitoring file. This activates the e-mail sending again.


Variable dumping
-----------------

Every debugging developer is a good friend with the function `var_dump`, which lists all contents of any variable in detail. Unfortunately, its output is without HTML formatting and outputs the dump into a single line of HTML code, not to mention context escaping. It is necessary to replace the `var_dump` by a handier function. That is just what `dump()` is.

```php
$arr = array(10, 20.2, true, null, 'hello');

dump($arr);
// or Tracy\Debugger::dump($arr);
```

generates the output:

![dump](https://nette.github.io/tracy/images/tracy-dump.png)

You can also change the nesting depth by `Debugger::$maxDepth` and displayed strings length by `Debugger::$maxLength`. Naturally, lower values accelerate Tracy rendering.

```php
Debugger::$maxDepth = 2; // default: 3
Debugger::$maxLength = 50; // default: 150
```

The `dump()` function can display other useful information. `Tracy\Dumper::LOCATION_SOURCE` adds tooltip with path to the file, where the function was called. `Tracy\Dumper::LOCATION_LINK` adds a link to the file. `Tracy\Dumper::LOCATION_CLASS` adds a tooltip to every dumped object containing path to the file, in which the object's class is defined. All these constants can be set in `Debugger::$showLocation` variable before calling the `dump()`. You can set multiple values at once using the `|` operator.

```php
Debugger::$showLocation = Tracy\Dumper::LOCATION_SOURCE; // Shows path to where the dump() was called
Debugger::$showLocation = Tracy\Dumper::LOCATION_CLASS | Tracy\Dumper::LOCATION_LINK; // Shows both paths to the classes and link to where the dump() was called
Debugger::$showLocation = false; // Hides additional location information
Debugger::$showLocation = true; // Shows all additional location information
```

Very handy alternative to `dump()` is `dumpe()` (ie. dump and exit) and `bdump()`. This allows us to dump variables in Debugger Bar. This is useful, because dumps don't mess up the output and we can also add a title to the dump.

```php
bdump([2, 4, 6, 8], 'even numbers up to ten');
bdump([1, 3, 5, 7, 9], 'odd numbers up to ten');
```

![bar dump](https://nette.github.io/tracy/images/tracy-bardump.png)


Timing
------

Another useful tool is the debugger stopwatch with a precision of microseconds:

```php
Debugger::timer();

// sweet dreams my cherrie
sleep(2);

$elapsed = Debugger::timer();
// $elapsed = 2
```

Multiple measurements at once can be achieved by an optional parameter.

```php
Debugger::timer('page-generating');
// some code

Debugger::timer('rss-generating');
// some code

$rssElapsed = Debugger::timer('rss-generating');
$pageElapsed = Debugger::timer('page-generating');
```

```php
Debugger::timer(); // runs the timer

... // some time consuming operation

echo Debugger::timer(); // elapsed time in seconds
```


FireLogger
----------

You cannot always send debugging information to the browser window. This applies to AJAX requests, or generating XML files to output. In such cases, you can send the messages by a separate channel into FireLogger. Error, Notice and Warning levels are sent to FireLogger window automatically. It is also possible to log suppressed exceptions in running application when attention to them is important.

How to do it?

Firefox:
- install extension [Firebug](http://getfirebug.com/) and [FireLogger](https://addons.mozilla.org/cs/firefox/addon/firelogger/)
- turn on Firebug (using F12 key), enable tabs Net and Logger (stay on Logger)

Chrome:
- install extension [FireLogger for Chrome](https://chrome.google.com/webstore/detail/firelogger-for-chrome/hmagilfopmdjkeomnjpchokglfdfjfeh)
- turn on Chrome DevTools (using Ctrl-Shift-I key) and open Console

Navigate to [demo page](https://examples.nette.org/tracy/) and you will see messages sent from PHP.

Because Tracy\Debugger communicates with FireLogger via HTTP headers, you must call the logging function before the PHP script sends anything to output. It is also possible to enable output buffering and delay the output.

```php
use Tracy\Debugger;

Debugger::fireLog('Hello World'); // send string into FireLogger console

Debugger::fireLog($_SERVER); // or even arrays and objects

Debugger::fireLog(new Exception('Test Exception')); // or exceptions
```

The result looks like this:

![FireLogger](https://nette.github.io/tracy/images/tracy-firelogger.png)

Ports
-----------------------------
This is list of unofficial ports to another frameworks and CMS than Nette:
- [Drupal 7](http://drupal.org/project/traced)
- Laravel framework: [recca0120/laravel-tracy](https://github.com/recca0120/laravel-tracy), [whipsterCZ/laravel-tracy](https://github.com/whipsterCZ/laravel-tracy)
- [OpenCart](https://github.com/BurdaPraha/oc_tracy)
- [ProcessWire CMS/CMF](https://github.com/adrianbj/TracyDebugger)
- [Slim Framework](https://github.com/runcmf/runtracy)
- Symfony framework: [kutny/tracy-bundle](https://github.com/kutny/tracy-bundle), [VasekPurchart/Tracy-Blue-Screen-Bundle](https://github.com/VasekPurchart/Tracy-Blue-Screen-Bundle)
- [Wordpress](https://github.com/ktstudio/WP-Tracy)

... feel free to be famous, create a port for your favourite platform!
