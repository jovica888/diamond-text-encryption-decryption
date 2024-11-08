<?php
/*
* Diamond Text Encryption Decryption
* Unbreakable encryption system by jovica888
* Licensed under the MIT License
*/
$result = [];
function generatePositions($key, $messageLength, $totalLength) {
    $positions = [];
    $hash = hash('sha256', $key); // Initial hash based on the key
    $requiredPositions = min($messageLength, $totalLength); // Number of positions needed for the message

    // Generate positions for the message using SHA-256
    while (count($positions) < $requiredPositions) {
        // Iterate through the hash in 4-character (16-bit) segments to determine positions
        for ($i = 0; $i < strlen($hash); $i += 4) {
            $position = hexdec(substr($hash, $i, 4)) % $totalLength;

            if (!in_array($position, $positions)) {
                $positions[] = (int) $position;
            } else {
                // Find the next free position if the current one is occupied
                $j = 0;
                while ($j <= $totalLength) {
                    $position = ($position + 1) % $totalLength;
                    if (!in_array($position, $positions)) {
                        $positions[] = (int) $position;
                        break;
                    }
                    $j++;
                }
            }

            // Stop if we have generated enough positions
            if (count($positions) >= $requiredPositions) {
                break;
            }
        }

        // If more positions are needed, generate a new hash based on the current hash
        if (count($positions) < $requiredPositions) {
            $hash = hash('sha256', $key . $hash);
        }
    }

    // Fill in remaining positions in sequence, if we haven't reached totalLength
    for ($i = 0; $i < $totalLength; $i++) {
        if (!in_array($i, $positions)) {
            $positions[] = $i;
        }
        if (count($positions) >= $totalLength) {
            break;
        }
    }

    return $positions;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? '';
    $key = $_POST['key'] ?? '';
    $action = $_POST['action'] ?? '';

    if (strlen($key) < 20 || strlen($key) > 250) {
        $result = 'Key must be between 20 and 250 characters.';
    } elseif (strlen($text) > 1000 && $action === 'encrypt') {
        $result = 'Message cannot exceed 1000 characters.';
    } else {
        $result = array_fill(0, 10000, ''); // Create an array of 10,000 elements for the result

        if ($action === 'encrypt') {
            $x = 0;
            $text = base64_encode($text);
            $text = $text . "{*[END]*}"; // Mark the end of the encoded message
            $messageLength = strlen($text);
            $positions = generatePositions($key, 1400, 10000);

            foreach ($positions as $position) {
                if (isset($text[$x])) {
                    $result[$position] = $text[$x];
                    $x++;
                } else {
                    // Fill empty spaces with random characters
                    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+=-"{}[]|:;<>,.?/~';
                    $charactersLength = strlen($characters);
                    $randomIndex = random_int(0, $charactersLength - 1);
                    $result[$position] = $characters[$randomIndex];
                }
            }

        } elseif ($action === 'decrypt') {
            // Reconstruct the original message using the same positions
            $originalMessage = '';
            $positions = generatePositions($key, 1400, 10000);

            foreach ($positions as $position) {
                if ($text[$position] !== '') {
                    $originalMessage .= $text[$position];
                } else {
                    break; // Stop when encountering an empty position
                }
            }
            
            $originalMessage = explode('{*[END]*}', $originalMessage);
            $originalMessage = $originalMessage[0];
            $result = base64_decode($originalMessage);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encryption and Decryption</title>
</head>
<body>
    <h1>Encryption and Decryption</h1>
    <form method="POST">
        <label for="key">Key:</label><br>
        <input type="text" id="key" name="key" required><br><br>
        
        <label for="text">Message:</label><br>
        <textarea id="text" name="text" rows="10" cols="100" required></textarea><br><br>
        
        <label>Select action:</label><br>
        <input type="radio" id="encrypt" name="action" value="encrypt" required>
        <label for="encrypt">Encrypt</label><br>
        <input type="radio" id="decrypt" name="action" value="decrypt" required>
        <label for="decrypt">Decrypt</label><br><br>
        
        <input type="submit" value="Process"><br><br>
    </form>
    <div>
        <textarea id="result" name="result" rows="10" cols="100" required><?php if (is_array($result)) { foreach ($result as $char) { echo htmlspecialchars($char); } } else { echo htmlspecialchars($result); } ?></textarea>
    </div>
</body>
</html>
