call ../config.bat

for %%f in (test.*.php) do %php% -q "%%f" > "output\%%f.html"

"%wget%" "%testUri%/Debug/test.firefox.php" -O output\test.firefox.php.html

IF NOT "%diff%"=="" ( start "" %diff% output ref )
