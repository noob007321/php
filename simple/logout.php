<?php
/* =========================================================================
   logout.php  —  forget the user, then go back to the login page
   ========================================================================= */

session_start();     // open the session...
session_destroy();   // ...and throw it away

header('Location: login.php');
exit;
