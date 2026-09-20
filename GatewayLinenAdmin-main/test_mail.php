<?php
// GatewayLinenadmin-main/test_mail.php

require_once __DIR__ . '/includes/mailer.php';

// Apna khud ka email yahan daal kar test karo
$testEmail = "sahilmirza01779@gmail.com";
$testName = "Sahil Mirza";
$testPass = "123456";

echo "<h2>Testing Gmail SMTP Socket Mailer...</h2>";

$result = sendWelcomeEmail($testEmail, $testName, $testPass);

if ($result) {
    echo "<p style='color: green; font-weight: bold;'>Success! Mail function executed and socket connected successfully. Please check your inbox (and spam folder).</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Failed! Socket connection to Gmail SMTP was blocked or timed out by the local WAMP environment.</p>";
}
