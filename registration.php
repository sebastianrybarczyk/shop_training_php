<?php
/**
 * Created by PhpStorm.
 * User: sebastian
 * Date: 11.04.18
 * Time: 11:32
 */
session_status();

if (isset($_POST['email']) && isset($_POST['nick'])) {
    $everything_OK = true;

    // nickname validation
    $nick = $_POST['nick'];

    if (strlen($nick) < 3 || strlen($nick) > 20) {

        $_SESSION['e_nick'] = 'Nickname has to have from 3 to 20 signs!';

    }

    // letter and digits in nickname
    if (ctype_alnum($nick) == false) {
        $everything_OK = false;
        $_SESSION['e_nick'] = "Nickname can contain only letter and signs (without polish signs)";
    }

    // email validation
    $email = $_POST['email'];
    $emailB = filter_var($email, FILTER_SANITIZE_EMAIL); // this will ONLY delete forbidden signs, but pass email address

    if ((filter_var($emailB, FILTER_SANITIZE_EMAIL) == false) || ($email != $emailB)) { // this line checks validity of email address

        $everything_OK = false;
        $_SESSION['e_email'] = "Write valid email";

    }

    // pass validation
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (strlen($pass1) < 8 || (strlen($pass1) > 20)) {
        $everything_OK = false;
        $_SESSION['e_pass'] = "Password must have from 8 to 20 signs";

    }

    if ($pass1 != $pass2) {
        $everything_OK = false;
        $_SESSION['e_pass'] = "Given passwords are not identical";
    }

    $pass_hashed = password_hash($pass1, PASSWORD_DEFAULT); // second argument mean that compliler picks the strongest available hashing algorythm.
    // It is possible to choose PASSWORD_BCRYPT, but better to trust PHP designers.
    // IMPORTANT - cell for pass in database must have at least 255 signs!

    // echo $haslo_hash;
    // exit():

    // terms of use checkbox
    if (!isset($_POST['terms'])) {
        $everything_OK = false;
        $_SESSION['e_terms'] = "Accept terms of use";
    }

    // Captcha

    $secret_code = ""; // must contains individual secret code from goole recaptcha page

    $check = file_get_contents('https://google.com/recaptcha/api/siteverify?secret=' . $secret_code . '&response=' . $_POST['g-recaptcha-response']);

    // json datat parsing
    $response = json_decode($check);

    if ($response->success == false) {
        $everything_OK = false;
        $_SESSION['e_bot'] = "Confirm that you are not a robot";
    }

    // Remember entered data

    $_SESSION['fr_nick'] = $nick;
    $_SESSION['fr_email'] = $email;
    $_SESSION['fr_pass1'] = $pass1;
    $_SESSION['fr_pass2'] = $pass2;
    if (isset($_POST['terms'])) $_SESSION['fr_terms'] = true;

    require_once "connection.php";

    mysqli_report(MYSQLI_REPORT_STRICT);

    try {
        $connection = new mysqli($host, $db_user, $db_password, $db_name);

        if ($connection->connect_errno != 0) {

            throw new Exception(mysqli_connect_error());

        } else {
            // email exists?
            $result = $connection->query("SELECT id FROM users WHERE email='$email'");

            if (!$result) throw new Exception($connection->error);

            $how_many_mails = $result->num_rows;

            if ($how_many_mails > 0) {
                $everything_OK = false;
                $_SESSION['e_email'] = "Enteres email already exists in database";
            }

            // nickname exists?
            $result = $connection->query("SELECT id FROM users WHERE user='$nick'");

            if (!$result) throw new Exception($connection->error);

            $how_many_nicks = $result->num_rows;

            if ($how_many_nicks > 0) {
                $everything_OK = false;
                $_SESSION['e_nick'] = "Entered nickname is already in use, choose another";
            }

            if ($everything_OK == true) {
                // all test validated, adding user to database

                if ($connection->query("INSERT INTO users VALUES (NULL, '$nick', '$email', '$pass_hashed', now())")) { // adding date of making account

                    $_SESSION['successregistration'] = true;
                    header('Location:welcome.php');

                } else {

                    throw new Exception($connection->error);

                }
            }

            $connection->close();

        }

    } catch (Exception $e) {
        echo '<span style="color: red; margin-top: 10px;"> Server error, sorry for inconvinience</span>';
        echo '<br/>Dev info:' . ' ' . $e;
    }

}

?>


<!DOCTYPE HTML>
<html lang="pl">

<head>

    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>Shop registration - new account</title>
    <script src='https://www.google.com/recaptcha/api.js'></script>

    <link rel="stylesheet" href="../../css/style.css" type="text/css"/>
    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
    <![endif]-->


    <style>

    </style>
</head>

<!--No "action" for form that is why I make service in this file-->
<body>

<div class="container">

    <form method="post">

        N<br/> <input type="text" placeholder="name" value="<?php

        if (isset($_SESSION['fr_nick'])) {
            echo $_SESSION['fr_nick'];
            unset($_SESSION['fr_nick']);
        }

        ?>" name="nick"/><br/>

        <?php

        if (isset($_SESSION['e_nick'])) {
            echo '<div class="error"' . $_SESSION['e_nick'] . '</div>';
            unset($_SESSION['e_nick']); // this must exist to correct if user made mistake
        }

        ?>


        <br/> <input type="email" placeholder="email" value="<?php

        if (isset($_SESSION['fr_email'])) {
            echo $_SESSION['fr_email'];
            unset($_SESSION['fr_email']);
        }

        ?>" name="email"/><br/>

        <?php

        if (isset($_SESSION['e_email'])) {
            echo '<div class="error"' . $_SESSION['e_email'] . '</div>';
            unset($_SESSION['e_email']); // this must exist to correct if user made mistake
        }

        ?>

        <br/> <input type="password" placeholder="password" value="<?php

        if (isset($_SESSION['fr_pass1'])) {
            echo $_SESSION['fr_pass1'];
            unset($_SESSION['fr_pass1']);
        }

        ?>" name="pass1"/><br/>

        <?php

        if (isset($_SESSION['e_pass'])) {
            echo '<div class="error"' . $_SESSION['e_pass'] .'</div>';
            unset($_SESSION['e_pass']); // this must exist to correct if user made mistake
        }

        ?>

        <br/> <input type="password" placeholder="confirm password" value="<?php

        if (isset($_SESSION['fr_pass2'])) {
            echo $_SESSION['fr_pass2'];
            unset($_SESSION['fr_pass2']);
        }

        ?>" name="pass2"/><br/>


        <label>

            <input type="checkbox" name="terms" <?php

            if (isset($_SESSION['fr_terms'])) {

                echo "checked";
                unset($_SESSION['fr_terms']);
            }

            ?> />
            <p>I accept terms of use</p>
            <!-- Label for possibility of clicking whole text not only checkbox to mark it-->

        </label>

        <?php

        if (isset($_SESSION['e_terms'])) {

            echo '<div class="error">' . $_SESSION['e_terms'] . '</div>';
        }

        ?>

        <div class="g-recaptcha" data-sitekey=""></div> <!-- must conatains individual data-sitekey from google recapthca page -->

        <?php

        if (isset($_SESSION['e_bot'])) {

            echo '<div class="error">' . $_SESSION['e_bot'] . '</div>';
            unset($_SESSION['e_bot']);
        }

        ?>

        <br/><input type="submit" value="Register"/>


    </form>

</div>

</body>

</html>
