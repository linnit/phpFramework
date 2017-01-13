<?php

$password = "IQ8vtUL1ckhqndfN";

if ( password_verify('IQ8vtUL1ckhqndfN', '2y$10$.N84l8Y8z63qyG3KxTX3wuaJHdr3AgSYRl2nUPgyjpMStZRFAnDQG') ) {
	echo "Good!";
} else {
	echo "Bad!";
}

function register($password = null, $repeatpassword = null) {
	if (is_null($password)) {
		echo "There is null password";
	} else {
		echo "We are good";
	}
}

register($password, $password);


