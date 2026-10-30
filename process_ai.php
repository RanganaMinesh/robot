<?php
header('Content-Type: application/json');

// Get the POST payload from the Javascript fetch
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(["error" => "No data received"]);
    exit;
}

$prompt = $input['prompt'];
$imageBase64 = $input['image'];

// Your Gemini API Key
$apiKey = 'YOUR_GEMINI_API_KEY_HERE';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

// System instructions telling the AI how to behave
$systemPrompt = "You are the AI brain of a mobile robot. 
You will receive a picture from your camera and a voice command from the user.
Analyze the image and the command, then decide how to move.
You MUST reply ONLY in strict JSON format with no markdown formatting.
Format:
{
  \"speech_reply\": \"A short conversational response to the user\",
  \"robot_command\": \"F\" (Forward), \"B\" (Backward), \"L\" (Left), \"R\" (Right), or \"S\" (Stop),
  \"duration_ms\": time in milliseconds to execute the move (e.g., 1000 for 1 second)
}";

// Construct the payload for Gemini Multimodal API
$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $systemPrompt . "\n\nUser Command: " . $prompt],
                [
                    "inline_data" => [
                        "mime_type" => "image/jpeg",
                        "data" => $imageBase64
                    ]
                ]
            ]
        ]
    ]
];

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Execute and parse response
$response = curl_exec($ch);
curl_close($ch);

$resObj = json_decode($response, true);

// Extract the text response from Gemini
if (isset($resObj['candidates'][0]['content']['parts'][0]['text'])) {
    $aiText = $resObj['candidates'][0]['content']['parts'][0]['text'];
    
    // Clean up any potential markdown code blocks Gemini might accidentally add
    $aiText = str_replace(['```json', '```'], '', $aiText);
    $aiText = trim($aiText);

    // Output the JSON directly to the frontend
    echo $aiText;
} else {
    // Fallback if AI fails to respond correctly
    echo json_encode([
        "speech_reply" => "I'm having trouble thinking right now.",
        "robot_command" => "S",
        "duration_ms" => 0
    ]);
}
?>
