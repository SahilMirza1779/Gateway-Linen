<?php
// GatewayLinenadmin-main/includes/mailer.php

function sendWelcomeEmail($to, $fullName, $plainPassword)
{
    $senderEmail = "sahilmirza01779@gmail.com";
    $appPassword = "xumb xpgu rrbd aimt";
    $subject = "Welcome to Gateway Linen!";

    $messageBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                .container { background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; border-top: 4px solid #031D44; }
                h2 { color: #031D44; }
                p { color: #555555; line-height: 1.6; }
                .credentials { background-color: #FAFAFA; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Welcome to Gateway Linen!</h2>
                <p>Hello <b>{$fullName}</b>,</p>
                <p>Thank you for registering an account with Gateway Linen. We are excited to have you.</p>
                <div class='credentials'>
                    <p><b>Your Login Credentials:</b></p>
                    <p><b>Email:</b> {$to}</p>
                    <p><b>Password:</b> {$plainPassword}</p>
                </div>
                <p>You can now sign in to your dashboard anytime.</p>
                <p>Best regards,<br><b>Gateway Linen Team</b></p>
            </div>
        </body>
        </html>
    ";

    $smtp_server = "ssl://smtp.gmail.com";
    $smtp_port = 465;
    $logFile = __DIR__ . '/mail_debug.log';

    // SSL Bypass for local WAMP testing
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("{$smtp_server}:{$smtp_port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Socket Failed: $errstr\n", FILE_APPEND);
        return false;
    }

    // Helper function to read multi-line SMTP responses perfectly
    $readResponse = function ($sock) {
        $res = '';
        while ($line = @fgets($sock, 515)) {
            $res .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $res;
    };

    $readResponse($socket); // Server Greeting

    @fwrite($socket, "EHLO smtp.gmail.com\r\n");
    $readResponse($socket); // Read ALL lines of EHLO

    @fwrite($socket, "AUTH LOGIN\r\n");
    $readResponse($socket);

    @fwrite($socket, base64_encode($senderEmail) . "\r\n");
    $readResponse($socket);

    @fwrite($socket, base64_encode($appPassword) . "\r\n");
    $auth_response = $readResponse($socket);

    if (strpos($auth_response, '235') === false) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Auth Failed: $auth_response\n", FILE_APPEND);
        @fclose($socket);
        return false;
    }

    @fwrite($socket, "MAIL FROM: <{$senderEmail}>\r\n");
    $readResponse($socket);

    @fwrite($socket, "RCPT TO: <{$to}>\r\n");
    $rcpt_response = $readResponse($socket);

    if (strpos($rcpt_response, '250') === false) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - RCPT Failed: $rcpt_response\n", FILE_APPEND);
        @fclose($socket);
        return false;
    }

    @fwrite($socket, "DATA\r\n");
    $readResponse($socket);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Gateway Linen Support <{$senderEmail}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: {$subject}\r\n\r\n";

    @fwrite($socket, $headers . $messageBody . "\r\n.\r\n");
    $data_response = $readResponse($socket);

    @fwrite($socket, "QUIT\r\n");
    @fclose($socket);

    if (strpos($data_response, '250') !== false) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - SUCCESS: Mail sent to $to\n", FILE_APPEND);
        return true;
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - DATA Failed: $data_response\n", FILE_APPEND);
        return false;
    }
}

// GatewayLinenadmin-main/includes/mailer.php (Existing code ke niche add karein)

function sendOTPEmail($to, $fullName, $otp)
{
    $senderEmail = "sahilmirza01779@gmail.com";
    $appPassword = "xumb xpgu rrbd aimt";
    $subject = "Your Password Reset OTP";

    $messageBody = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
            <div style='background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; border-top: 4px solid #031D44;'>
                <h2 style='color: #031D44;'>Password Reset Request</h2>
                <p>Hello <b>{$fullName}</b>,</p>
                <p>We received a request to reset your Gateway Linen password. Your 6-digit OTP is:</p>
                <div style='background-color: #FAFAFA; padding: 15px; border-radius: 8px; border: 1px solid #e0e0e0; margin: 20px 0; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold; color: #031D44;'>
                    {$otp}
                </div>
                <p>This OTP is valid for 10 minutes. If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br><b>Gateway Linen Team</b></p>
            </div>
        </body>
        </html>
    ";

    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
    $socket = @stream_socket_client("ssl://smtp.gmail.com:465", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return false;

    $readResponse = function ($sock) {
        $res = '';
        while ($line = @fgets($sock, 515)) {
            $res .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $res;
    };

    $readResponse($socket);
    @fwrite($socket, "EHLO smtp.gmail.com\r\n");
    $readResponse($socket);
    @fwrite($socket, "AUTH LOGIN\r\n");
    $readResponse($socket);
    @fwrite($socket, base64_encode($senderEmail) . "\r\n");
    $readResponse($socket);
    @fwrite($socket, base64_encode($appPassword) . "\r\n");
    $auth = $readResponse($socket);
    if (strpos($auth, '235') === false) {
        @fclose($socket);
        return false;
    }

    @fwrite($socket, "MAIL FROM: <{$senderEmail}>\r\n");
    $readResponse($socket);
    @fwrite($socket, "RCPT TO: <{$to}>\r\n");
    $rcpt = $readResponse($socket);
    if (strpos($rcpt, '250') === false) {
        @fclose($socket);
        return false;
    }

    @fwrite($socket, "DATA\r\n");
    $readResponse($socket);
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: Gateway Linen <{$senderEmail}>\r\nTo: <{$to}>\r\nSubject: {$subject}\r\n\r\n";
    @fwrite($socket, $headers . $messageBody . "\r\n.\r\n");
    $data_res = $readResponse($socket);
    @fwrite($socket, "QUIT\r\n");
    @fclose($socket);
    return strpos($data_res, '250') !== false;
}

function sendPasswordChangedEmail($to, $fullName)
{
    $senderEmail = "sahilmirza01779@gmail.com";
    $appPassword = "xumb xpgu rrbd aimt";
    $subject = "Password Changed Successfully";

    $messageBody = "
        <html>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
            <div style='background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; border-top: 4px solid #008000;'>
                <h2 style='color: #008000;'>Password Updated</h2>
                <p>Hello <b>{$fullName}</b>,</p>
                <p>Your Gateway Linen account password has been changed successfully.</p>
                <p>If you did not make this change, please contact support immediately.</p>
                <p>Best regards,<br><b>Gateway Linen Team</b></p>
            </div>
        </body>
        </html>
    ";

    $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
    $socket = @stream_socket_client("ssl://smtp.gmail.com:465", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return false;
    $readResponse = function ($sock) {
        $res = '';
        while ($line = @fgets($sock, 515)) {
            $res .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $res;
    };
    $readResponse($socket);
    @fwrite($socket, "EHLO smtp.gmail.com\r\n");
    $readResponse($socket);
    @fwrite($socket, "AUTH LOGIN\r\n");
    $readResponse($socket);
    @fwrite($socket, base64_encode($senderEmail) . "\r\n");
    $readResponse($socket);
    @fwrite($socket, base64_encode($appPassword) . "\r\n");
    $auth = $readResponse($socket);
    if (strpos($auth, '235') === false) {
        @fclose($socket);
        return false;
    }
    @fwrite($socket, "MAIL FROM: <{$senderEmail}>\r\n");
    $readResponse($socket);
    @fwrite($socket, "RCPT TO: <{$to}>\r\n");
    $rcpt = $readResponse($socket);
    if (strpos($rcpt, '250') === false) {
        @fclose($socket);
        return false;
    }
    @fwrite($socket, "DATA\r\n");
    $readResponse($socket);
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: Gateway Linen <{$senderEmail}>\r\nTo: <{$to}>\r\nSubject: {$subject}\r\n\r\n";
    @fwrite($socket, $headers . $messageBody . "\r\n.\r\n");
    $readResponse($socket);
    @fwrite($socket, "QUIT\r\n");
    @fclose($socket);
    return true;
}
