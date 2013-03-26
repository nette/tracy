#!/bin/sh

# Path to this script's directory
dir=$(cd `dirname $0` && pwd)

# Path to test runner script
runnerScript="$dir/../vendor/nette/tester/Tester/tester.php"
if [ ! -f "$runnerScript" ]; then
	echo "Nette Tester is missing. You can install it using Composer:" >&2
	echo "php composer.phar update --dev." >&2
	exit 2
fi

# Path to php.ini if passed as argument option
phpIni=
while getopts ":c:" opt; do
	case $opt in
	c)	phpIni="$OPTARG"
		;;

	:)	echo "Missing argument for -$OPTARG option" >&2
		exit 2
		;;
	esac
done

# Runs tests with script's arguments, add default php.ini if not specified
# Doubled -c option intentionally
if [ -n "$phpIni" ]; then
	php -c "$phpIni" "$runnerScript" -j 20 "$@"
else
	php -c "$dir/php.ini-unix" "$runnerScript" -j 20 -c "$dir/php.ini-unix" "$@"
fi
error=$?

# Print *.actual content if tests failed
if [ "${VERBOSE-false}" != "false" -a $error -ne 0 ]; then
	for i in $(find . -name \*.actual); do echo "--- $i"; cat $i; echo; echo; done
	exit $error
fi
