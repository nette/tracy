@ECHO OFF

SET testRunner="%~dp0..\vendor\nette\tester\Tester\tester.php"

IF NOT EXIST %testRunner% (
	ECHO Nette Tester is missing. You can install it using Composer:
	ECHO php composer.phar update --dev
	EXIT /B 2
)

SET phpIni="%~dp0php.ini-win"

php.exe -c %phpIni% %testRunner% -p php-cgi.exe -c %phpIni% -j 20 -log "%~dp0test.log" %*

rmdir "%~dp0/tmp" /S /Q
