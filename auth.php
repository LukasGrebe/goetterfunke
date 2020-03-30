<html>
<head>
<title>Götterfunke CLI oAuth</title>
<meta charset="utf-8"/>
<body>
<h1>Götterfunke</h1>
Enter the following code in the CLI:

<input onClick="this.select();" value="<?php
error_reporting(E_ALL);

echo htmlspecialchars($_GET["code"]);

?>" />