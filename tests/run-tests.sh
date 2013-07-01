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

# Default runner arguments
jobsNum=20
phpIni="$dir/php-unix.ini"

# Command line arguments processing
for i in `seq 1 $#`; do
	if [ "$1" = "-j" ]; then
		shift
		if [ -z "$1" ]; then
			echo "Missing argument for -j option." >&2
			exit 2
		fi
		jobsNum="$1"

	elif [ "$1" = "-c" ]; then
		shift
		if [ -z "$1" ]; then
			echo "Missing argument for -c option." >&2
			exit 2
		fi
		phpIni="$1"

	else
		set -- "$@" "$1"
	fi
	shift
done

# Run tests with script's arguments, doubled -c option intentionally
php -c "$phpIni" "$runnerScript" -j "$jobsNum" -c "$phpIni" "$@"
error=$?

# Print *.actual content if tests failed
if [ "${VERBOSE-false}" != "false" -a $error -ne 0 ]; then
	for i in $(find . -name \*.actual); do echo "--- $i"; cat $i; echo; echo; done
	exit $error
fi
