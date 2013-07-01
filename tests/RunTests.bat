@ECHO OFF

IF NOT EXIST "%~dp0..\vendor\nette\tester" (
	ECHO Nette Tester is missing. You can install it using Composer:
	ECHO php composer.phar update --dev
	EXIT /B 2
)

php.exe -n "%~dp0..\vendor\nette\tester\Tester\tester.php" -p php-cgi.exe -c "%~dp0php-win.ini" -j 20 -log "%~dp0test.log" %*

rmdir "%~dp0/tmp" /S /Q
