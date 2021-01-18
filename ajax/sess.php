<?php
session_start();
if (isset($_SESSION['tess_uid'])) // Auth::SESS_UID
	echo 1;
else if (count($_SESSION) > 0)
	echo -1;
else
	echo 0;