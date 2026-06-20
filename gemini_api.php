<?php
// Koneksi Database
$conn = mysqli_connect("localhost", "root", "", "bps_tolitoli");

$apiKey = "AIzaSyCE5uLP425CBmqo-021X2y7_pTVbXui5T8"; // API Key Anda
$userInput = $_POST['message'] ?? '';

if (!empty($userInput)) {
    // Instruksi sistem agar AI fokus pada KBLI 2025
    $instruction = "Anda adalah asisten BPS Tolitoli. Jawablah pertanyaan mengenai kode ekonomi menggunakan referensi KBLI 2025 (Peraturan BPS No. 7 Tahun 2025). Berikan jawaban dalam struktur 5 digit jika memungkinkan.";

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    
    $data = [
        "contents" => [["parts" => [["text" => $instruction . "\n\nPertanyaan: " . $userInput]]]]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? "Maaf, sistem sedang sibuk.";

    // Simpan ke Database
    $stmt = $conn->prepare("INSERT INTO chat_kbli (user_msg, ai_msg) VALUES (?, ?)");
    $stmt->bind_param("ss", $userInput, $aiResponse);
    $stmt->execute();

    echo $aiResponse;
}
?>