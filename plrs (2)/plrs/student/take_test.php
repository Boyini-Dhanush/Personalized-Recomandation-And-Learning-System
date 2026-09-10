<?php
include("../config/db.php");

if(!isset($_SESSION['uid'])){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* USER INFO */
$user = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'")
);

/* FETCH AVAILABLE PDF RESOURCES */
// We need to fetch resources that are PDFs.
// Actually, the user says "student can slect the uploaded topic by the facultyy".
$pdfs = mysqli_query($conn,"
    SELECT id, title, subject, sub_topic, file_path 
    FROM resources 
    WHERE file_path LIKE '%.pdf' OR resource_type='pdf'
    ORDER BY id DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Take Test | EduSmart</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
.sidebar-glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-right: 1px solid #e5e7eb; }
</style>
</head>
<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">
<div class="flex items-center gap-3 mb-10">
<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">ES</div>
<span class="text-xl font-bold text-slate-800">EduSmart</span>
</div>

<nav class="space-y-2 flex-1">
<a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">Dashboard</a>
<a href="resources.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">Learning Resources</a>
<a href="search_resource.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">Search Resources</a>
<a href="analytics.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">Analytics</a>
<a href="take_test.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold">Take Test</a>
</nav>

<div class="mt-auto p-4 bg-slate-900 rounded-2xl text-white">
<p class="text-xs text-slate-400 mb-1">Current Role</p>
<p class="font-bold text-sm">Student</p>
</div>
</aside>

<!-- MAIN -->
<main class="flex-1 lg:ml-64 p-8">

<header class="flex justify-between items-center mb-10">
<div>
<h2 class="text-3xl font-bold text-slate-800">Take a Test</h2>
<p class="text-slate-500 mt-1">Generate dynamic tests from uploaded resources using AI.</p>
</div>
<a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600">Logout</a>
</header>

<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 w-full max-w-2xl mb-8">
    <h3 class="text-xl font-bold mb-4 text-slate-800">Test Configuration</h3>
    
    <div class="space-y-4">
        <div>
            <label class="block font-semibold text-sm mb-1 text-slate-700">Select Uploaded Topic (PDF)</label>
            <select id="fileSelect" class="w-full border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Choose a PDF Resource --</option>
                <?php while($pdf = mysqli_fetch_assoc($pdfs)): ?>
                    <option value="<?php echo htmlspecialchars($pdf['file_path']); ?>">
                        <?php echo htmlspecialchars($pdf['subject'] . ' - ' . $pdf['sub_topic'] . ' (' . $pdf['title'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block font-semibold text-sm mb-1 text-slate-700">Test Type</label>
                <select id="typeSelect" class="w-full border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="mcq">Multiple Choice (MCQ)</option>
                    <option value="blanks">Fill in the Blanks</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-sm mb-1 text-slate-700">Number of Questions</label>
                <input type="number" id="numSelect" value="5" min="1" max="20" class="w-full border p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <button id="generateBtn" onclick="generateTest()" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition flex items-center justify-center gap-2">
            Generate Test with AI 🤖
        </button>
    </div>
</div>

<div id="loadingArea" class="hidden my-8 text-center">
    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mb-2"></div>
    <p class="text-indigo-600 font-medium">Reading PDF and generating questions...</p>
</div>

<div id="testArea" class="hidden bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
    <h3 class="text-2xl font-bold mb-6 text-slate-800">Your Test</h3>
    <div id="questionsContainer" class="space-y-8"></div>
    
    <div class="mt-8 pt-6 border-t border-slate-200 flex justify-between items-center">
        <button onclick="submitTest()" class="bg-green-600 text-white font-bold px-8 py-3 rounded-xl hover:bg-green-700 transition shadow-sm">
            Submit Answers
        </button>
        <div id="scoreDisplay" class="hidden text-2xl font-bold text-slate-800"></div>
    </div>
</div>

</main>

<script>
let generatedTestData = [];
let currentTestType = 'mcq';

function generateTest() {
    const filePath = document.getElementById('fileSelect').value;
    const testType = document.getElementById('typeSelect').value;
    const num = document.getElementById('numSelect').value;

    if(!filePath) {
        alert("Please select a PDF resource first.");
        return;
    }

    document.getElementById('generateBtn').disabled = true;
    document.getElementById('loadingArea').classList.remove('hidden');
    document.getElementById('testArea').classList.add('hidden');
    document.getElementById('scoreDisplay').classList.add('hidden');
    document.getElementById('questionsContainer').innerHTML = '';

    fetch('../api/generate_test.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_path: filePath, test_type: testType, num: parseInt(num) })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('generateBtn').disabled = false;
        document.getElementById('loadingArea').classList.add('hidden');

        if(data.status === 'success') {
            generatedTestData = data.data;
            currentTestType = testType;
            renderTest();
        } else {
            alert("Error: " + (data.message || "Could not generate test"));
        }
    })
    .catch(err => {
        document.getElementById('generateBtn').disabled = false;
        document.getElementById('loadingArea').classList.add('hidden');
        alert("Network Error. Please try again.");
    });
}

function renderTest() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';
    
    generatedTestData.forEach((item, index) => {
        const qDiv = document.createElement('div');
        qDiv.className = 'p-6 bg-slate-50 rounded-xl border border-slate-200';
        qDiv.id = `qbox_${index}`;
        
        // Ensure item isn't somehow null
        if(!item || !item.question) return;

        let html = `<p class="font-bold text-lg mb-4 text-slate-800"><span class="text-indigo-600">Q${index+1}.</span> ${item.question}</p>`;
        
        if(currentTestType === 'mcq' && Array.isArray(item.options)) {
            html += `<div class="space-y-3">`;
            item.options.forEach((opt, optIndex) => {
                html += `
                <label class="flex items-start gap-3 p-3 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-indigo-400 transition">
                    <input type="radio" name="answer_${index}" value="${opt.replace(/"/g, '&quot;')}" class="mt-1">
                    <span class="text-slate-700">${opt}</span>
                </label>
                `;
            });
            html += `</div>`;
        } else {
            html += `
            <input type="text" id="answer_${index}" class="w-full border-2 border-slate-200 p-3 rounded-lg focus:outline-none focus:border-indigo-500" placeholder="Type your answer here...">
            `;
        }
        
        html += `<div id="feedback_${index}" class="mt-4 hidden font-medium"></div>`;
        
        qDiv.innerHTML = html;
        container.appendChild(qDiv);
    });

    document.getElementById('testArea').classList.remove('hidden');
}

function submitTest() {
    if(generatedTestData.length === 0) return;
    
    let score = 0;
    
    generatedTestData.forEach((item, index) => {
        let studentAnswer = "";
        if(currentTestType === 'mcq') {
            const selected = document.querySelector(`input[name="answer_${index}"]:checked`);
            if(selected) studentAnswer = selected.value;
        } else {
            const input = document.getElementById(`answer_${index}`);
            if(input) studentAnswer = input.value.trim();
        }
        
        const feedbackEl = document.getElementById(`feedback_${index}`);
        const correctAnswer = (item.answer || "").trim();
        feedbackEl.classList.remove('hidden');
        
        // Basic check for string equality (case insensitive for blanks)
        const isCorrect = (studentAnswer.toLowerCase() === correctAnswer.toLowerCase());
        
        if(isCorrect) {
            score++;
            feedbackEl.innerHTML = `✅ Correct!`;
            feedbackEl.className = 'mt-4 text-green-600 font-bold';
            document.getElementById(`qbox_${index}`).classList.replace('border-slate-200', 'border-green-300');
            document.getElementById(`qbox_${index}`).classList.replace('bg-slate-50', 'bg-green-50');
        } else {
            feedbackEl.innerHTML = `❌ Incorrect. Correct answer: <span class="text-slate-800">${correctAnswer}</span>`;
            feedbackEl.className = 'mt-4 text-red-600 font-medium';
            document.getElementById(`qbox_${index}`).classList.replace('border-slate-200', 'border-red-300');
            document.getElementById(`qbox_${index}`).classList.replace('bg-slate-50', 'bg-red-50');
        }
    });
    
    const scoreDiv = document.getElementById('scoreDisplay');
    scoreDiv.innerHTML = `🏆 Score: <span class="text-indigo-600">${score} / ${generatedTestData.length}</span>`;
    scoreDiv.classList.remove('hidden');
    
    // Save score to DB
    const filePath = document.getElementById('fileSelect').value;
    fetch('save_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ score: score, total: generatedTestData.length, file_path: filePath })
    });
    
    // Scroll to score
    scoreDiv.scrollIntoView({behavior: 'smooth'});
}
</script>
</body>
</html>
