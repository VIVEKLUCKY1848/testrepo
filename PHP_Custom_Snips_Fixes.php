<?php
# Fix the encoding issues encountered during the use of abroad's languages start
$enc = mb_detect_encoding($value, "UTF-8,ISO-8859-1");
echo ($enc != "UTF-8") ? iconv($enc, "UTF-8", $value) : $value;
# Fix the encoding issues encountered during the use of abroad's languages finish....
