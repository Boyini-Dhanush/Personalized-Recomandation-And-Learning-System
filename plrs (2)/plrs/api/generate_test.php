<?php
header("Content-Type: application/json");
include("../config/db.php");

// Security Check: Only logged-in users can access the AI features
if (!isset($_SESSION['uid'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

$api_key = "AIzaSyB1x12Z0fOCDKJBNSQlMZXYpQ-I3Wqivj0";

$data = json_decode(file_get_contents("php://input"), true);

$file_path = $data['file_path'] ?? '';
$test_type = $data['test_type'] ?? 'mcq';
$num       = (int)($data['num'] ?? 5);

if ($num < 1 || $num > 20) {
    $num = 5;
}

if (empty($file_path) || !file_exists($file_path)) {
    echo json_encode(["status" => "error", "message" => "File not found or not specified."]);
    exit();
}

// ---------- PDF TEXT EXTRACTION ----------
$pdf_text = '';

// Autoload smalot/pdfparser installed via Composer
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    try {
        $parser   = new \Smalot\PdfParser\Parser();
        $pdf      = $parser->parseFile($file_path);
        $pdf_text = $pdf->getText();

        // Trim to ~15000 chars to stay within Gemini token limits without overly truncating
        if (strlen($pdf_text) > 15000) {
            $pdf_text = substr($pdf_text, 0, 15000) . "\n\n[Content truncated for length...]";
        }
    } catch (\Exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to extract text from PDF."]);
        exit();
    }
} else {
    echo json_encode(["status" => "error", "message" => "PDF parser library not found."]);
    exit();
}

if (empty(trim($pdf_text))) {
    echo json_encode(["status" => "error", "message" => "No text could be extracted from the PDF. It might be empty or a scanned image."]);
    exit();
}

// ---------- BUILD PROMPT ----------
if ($test_type === 'mcq') {
    $format = '[{"question": "Question text?", "options": ["A", "B", "C", "D"], "answer": "The exact string of the correct option"}]';
} else {
    $format = '[{"question": "This is a statement with a ______ in it.", "answer": "word for the blank"}]';
}

$prompt = "You are an educational AI tutor. Generate a test based strictly on the following PDF content.\n";
$prompt .= "Test Type: " . ($test_type === 'mcq' ? "Multiple Choice Questions" : "Fill in the Blanks") . "\n";
$prompt .= "Number of Questions: " . $num . "\n";
$prompt .= "Output format MUST be a pure JSON array matching this exact structure: " . $format . "\n";
$prompt .= "Ensure questions are factual and derived ONLY from the provided PDF content.\n";
$prompt .= "Do NOT wrap the output in ```json tags. Output ONLY the raw, valid JSON array of objects without introductory text.\n\n";
$prompt .= "PDF Content:\n" . $pdf_text;

// ---------- GEMINI API CALL ----------
$url      = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $api_key;
$postData = ["contents" => [["parts" => [["text" => $prompt]]]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    echo json_encode(["status" => "error", "message" => "Curl Error: " . $error_msg]);
    exit();
}

curl_close($ch);

$resData = json_decode($response, true);

if ($httpCode !== 200) {
    if (isset($resData['error'])) {
        echo json_encode(["status" => "error", "message" => "Gemini API Error: " . $resData['error']['message']]);
    } else {
        echo json_encode(["status" => "error", "message" => "API returned HTTP " . $httpCode, "raw" => $response]);
    }
} else {
    // Extract the text part
    if (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
        $generated_text = trim($resData['candidates'][0]['content']['parts'][0]['text']);
        
        // Cleanup markdown if present
        if (strpos($generated_text, '```json') !== false || strpos($generated_text, '```') !== false) {
            $generated_text = preg_replace('/```json\s*/i', '', $generated_text);
            $generated_text = preg_replace('/```\s*/', '', $generated_text);
            $generated_text = trim($generated_text);
        }
        
        // Test if it parses back into json
        $parsed = json_decode($generated_text, true);
        if ($parsed === null) {
            // It might still have trailing/leading non-json characters, try to substring it
            $start = strpos($generated_text, '[');
            $end = strrpos($generated_text, ']');
            if($start !== false && $end !== false && $end > $start) {
                $generated_text = substr($generated_text, $start, $end - $start + 1);
                $parsed = json_decode($generated_text, true);
            }
        }
        
        if ($parsed === null) {
            echo json_encode(["status" => "error", "message" => "AI did not return valid JSON formats.", "raw" => $generated_text]);
        } else {
            echo json_encode(["status" => "success", "data" => $parsed]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Unexpected API response structure."]);
    }
}
?>
