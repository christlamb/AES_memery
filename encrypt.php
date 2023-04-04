<!DOCTYPE html>
<html>
<head>
    <title>Encryption/Decryption</title>
</head>
<body>
    <h1>Encryption/Decryption</h1>

    <?php
    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 获取表单参数
        $key = $_POST['key'];
        $plaintext = $_POST['plaintext'];
        $action = $_POST['action'];

        // 连接数据库
        $mysqli = new mysqli("localhost", "root", "root", "mydb");

        // 根据操作执行加密或解密
        if ($action === 'encrypt') {
            // 加密并插入数据到MySQL中
            $encrypted_text = encrypt($plaintext, $key);
            $sql = "INSERT INTO `encrypted_data` (`encrypted_text`) VALUES ('$encrypted_text')";
            $mysqli->query($sql);
            echo "<p>Encrypted text: $encrypted_text</p>";
        } elseif ($action === 'decrypt-all') {
            // 读取加密的数据并解密
            $sql = "SELECT * FROM `encrypted_data`";
            $result = $mysqli->query($sql);

            while ($row = $result->fetch_assoc()) {
                $encrypted_text = $row['encrypted_text'];
                $decrypted_text = decrypt($encrypted_text, $key);
                echo "<p>Encrypted Text: $encrypted_text<br>Decrypted Text: $decrypted_text</p>";
            }
        }
    }

    function encrypt($plaintext, $key) {
        $cipher = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        $ciphertext = base64_encode($iv.$hmac.$ciphertext_raw);
        return $ciphertext;
    }

    function decrypt($ciphertext, $key) {
        $cipher = 'aes-256-cbc';
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len=32);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        if (!hash_equals($hmac, $calcmac)) {
            throw new Exception('Authentication failed');
        }
        $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        return $plaintext;
    }
    ?>

    <!-- 加密表单 -->
    <h2>Encrypt</h2>
    <form method="POST">
        <label for="key">Key:</label>
        <input type="text" name="key" id="key" required><br>
        <label for="plaintext">Plaintext:</label>
        <textarea name="plaintext" id="plaintext" required></textarea><br>
        <input type="hidden" name="action" value="encrypt">
        <input type="submit" value="Encrypt">
    </form>

    <!-- 解密表单 -->
    <h2>Decrypt All</h2>
    <form method="POST">
        <label for="key">Key:</label>
        <input type="text" name="key" id="key" required><br>
        <input type="hidden" name="action" value="decrypt-all">
        <input type="submit" value="Decrypt All">
    </form>
</body>
</html>
