<?php

require_once '../includes/session.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: loginUser.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Halaman Utama</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f5f1eb;
            font-family: Arial, Helvetica, sans-serif;
        }

        .message {
            text-align: center;
        }

        .message h1 {
            font-family: Georgia, serif;
            font-size: 38px;
            color: #63391f;
            margin-bottom: 12px;
        }

        .message p {
            color: #888;
            font-size: 16px;
        }
    </style>
</head>

<body>

    <div class="message">
        <h1>Halaman Ini Masih Dalam Proses</h1>
        <p>
            Halaman utama sedang dalam tahap pengembangan.
        </p>
    </div>

</body>

</html>