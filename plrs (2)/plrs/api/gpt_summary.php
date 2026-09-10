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

$type      = $data['type']      ?? 'summary';
$text      = $data['text']      ?? '';
$file_path = $data['file_path'] ?? '';

// ---------- PDF TEXT EXTRACTION ----------
$pdf_text = '';

if (!empty($file_path) && file_exists($file_path)) {
    // Autoload smalot/pdfparser installed via Composer
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        try {
            $parser   = new \Smalot\PdfParser\Parser();
            $pdf      = $parser->parseFile($file_path);
            $pdf_text = $pdf->getText();

            // Trim to ~6000 chars to stay within Gemini token limits
            if (strlen($pdf_text) > 6000) {
                $pdf_text = substr($pdf_text, 0, 6000) . "\n\n[Content truncated for length...]";
            }
        } catch (\Exception $e) {
            // If PDF parsing fails, fall back to title-based summary
            $pdf_text = '';
        }
    }
}

// Build the prompt: prefer PDF content over title
if (!empty($pdf_text)) {
    if ($type === 'summary') {
        $prompt = "Act as an educational tutor. The following is the content of a PDF learning resource. Summarize it in simple, clear bullet points for a student:\n\n" . $pdf_text;
    } elseif ($type === 'ask') {
        $prompt = "You are an educational tutor. A student is asking a question about the following PDF learning resource.\n\nPDF Content:\n" . $pdf_text . "\n\nStudent Question:\n" . $text . "\n\nPlease answer the student's question based on the PDF content above.";
    } else {
        $prompt = "Act as an educational tutor. The following is the content of a PDF learning resource. Explain the key concepts in detail so a student can understand them:\n\n" . $pdf_text;
    }
} else {
    // Fallback: use the resource title/metadata
    if ($type === 'summary') {
        $prompt = "Act as an educational tutor. Summarize the following educational topic in simple, easy-to-understand words for a student. Use 3-4 bullet points if possible: " . $text;
    } elseif ($type === 'ask') {
        $prompt = "You are an educational tutor. A student is asking: " . $text . "\n\nPlease provide a clear, educational answer.";
    } else {
        $prompt = "Act as an educational tutor. Explain the following topic in detail for a student, breaking down complex concepts: " . $text;
    }
}

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
    echo $response;
}
?>