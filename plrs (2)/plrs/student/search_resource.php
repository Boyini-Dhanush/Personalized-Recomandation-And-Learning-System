<?php
include("../config/db.php");

if (!isset($_SESSION['uid'])) {
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'")
);

/* Get all available subjects for filtering */
$subjects = mysqli_query($conn, "
    SELECT DISTINCT subject FROM resources ORDER BY subject
");

/* Get search results if submitted */
$search_results = null;
if (isset($_GET['subject']) && !empty(trim($_GET['subject']))) {
    $subject = mysqli_real_escape_string($conn, trim($_GET['subject']));

    $query = "
        SELECT * FROM resources
        WHERE LOWER(subject) LIKE LOWER('%$subject%')
    ";

    /* Topic optional */
    if (isset($_GET['topic']) && !empty(trim($_GET['topic']))) {
        $topic = mysqli_real_escape_string($conn, trim($_GET['topic']));
        $query .= " AND LOWER(sub_topic) LIKE LOWER('%$topic%')";
    }

    $query .= " ORDER BY id DESC";

    $search_results = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Learning Resources</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body {
font-family:'Plus Jakarta Sans',sans-serif;
background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.sidebar-glass{
background:rgba(255,255,255,0.95);
backdrop-filter:blur(10px);
border-right:1px solid rgba(226,232,240,0.5);
}

.card-hover{
transition: all 0.3s ease;
}
.card-hover:hover{
transform: translateY(-2px);
box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
}

.input-focus{
transition: all 0.3s ease;
}
.input-focus:focus{
border-color: #4f46e5;
box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}
</style>
</head>

<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">

<div class="flex items-center gap-3 mb-10">
<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">
ES
</div>

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>
</div>

<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Dashboard
</a>


<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Learning Resources
</a>

<a href="search_resource.php"
class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold">
Search Resources
</a>

<a href="analytics.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Analytics
</a>

<a href="take_test.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Take Test
</a>

</nav>

<div class="mt-auto p-4 bg-slate-900 rounded-2xl text-white">
<p class="text-xs text-slate-400 mb-1">
Current Role
</p>

<p class="font-bold text-sm">
Student
</p>
</div>

</aside>


<!-- MAIN -->
<main class="flex-1 lg:ml-64 p-8">

<header class="flex justify-between items-center mb-12">
<div>
<h2 class="text-4xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
Welcome, <?php echo htmlspecialchars($user['name']); ?>
</h2>

<p class="text-slate-500 mt-2 text-lg">
Search learning materials uploaded by faculty
</p>
</div>

<a href="../auth/logout.php"
class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-2 rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition shadow-lg hover:shadow-xl">
Logout
</a>

</header>


<h3 class="text-2xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent mb-8">
Search Learning Resources
</h3>


<!-- SEARCH FORM -->

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-8 card-hover">

<form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-5">

<div>
<label class="block text-sm font-semibold text-slate-700 mb-3">Subject</label>

<input type="text" name="subject"
placeholder="e.g., Mathematics, Science"
value="<?php echo isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : ''; ?>"
class="w-full border-2 border-slate-200 p-3 rounded-xl input-focus text-slate-700 placeholder-slate-400"
required>
</div>

<div>
<label class="block text-sm font-semibold text-slate-700 mb-3">Topic</label>

<input type="text" name="topic"
placeholder="e.g., Algebra, Photosynthesis"
value="<?php echo isset($_GET['topic']) ? htmlspecialchars($_GET['topic']) : ''; ?>"
class="w-full border-2 border-slate-200 p-3 rounded-xl input-focus text-slate-700 placeholder-slate-400">
</div>

<div class="md:col-span-2">
<button type="submit"
class="w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white font-semibold py-3 rounded-xl hover:from-indigo-700 hover:to-indigo-800 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
🔍 Search Resources
</button>
</div>

</form>

</div>


<!-- SEARCH RESULTS -->

<?php if (isset($_GET['subject'])) { ?>

<?php if ($search_results && mysqli_num_rows($search_results) > 0) { ?>

<div class="grid grid-cols-1 gap-6">

<?php while ($row = mysqli_fetch_assoc($search_results)) { ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 card-hover">

<div class="flex justify-between items-start mb-4">

<div class="flex-1">

<h4 class="text-xl font-bold text-slate-800 mb-2">
<?php echo htmlspecialchars($row['title']); ?>
</h4>

<p class="text-slate-600 text-sm mb-2">
<span class="font-semibold text-slate-700">Subject:</span>
<?php echo htmlspecialchars($row['subject']); ?> •

<span class="font-semibold text-slate-700">Topic:</span>
<?php echo htmlspecialchars($row['sub_topic']); ?>
</p>

<p class="text-slate-500 text-sm">
<span class="font-semibold text-slate-700">By:</span>
<span class="text-indigo-600 font-medium">
<?php echo htmlspecialchars($row['faculty_name']); ?>
</span>
</p>

</div>

<span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-xs font-bold uppercase">
<?php echo strtoupper($row['resource_type']); ?>
</span>

</div>

<div class="flex gap-3 mt-5 pt-4 border-t border-slate-100">

<?php if ($row['resource_type'] == "youtube") { ?>

<a href="<?php echo htmlspecialchars($row['resource_link']); ?>"
target="_blank"
class="bg-red-500 text-white px-5 py-2 rounded-lg font-semibold">
▶ Watch Video
</a>

<?php
            }
            else if ($row['resource_type'] == "pdf") { ?>

<a href="<?php echo htmlspecialchars($row['file_path']); ?>"
target="_blank"
class="bg-green-500 text-white px-5 py-2 rounded-lg font-semibold">
⬇ Download PDF
</a>

    <?php
            }?>

    <button onclick="askAI('summary', 'Title: <?php echo addslashes($row['title']); ?>, Subject: <?php echo addslashes($row['subject']); ?>, Topic: <?php echo addslashes($row['sub_topic']); ?>', '<?php echo $row['id']; ?>', '<?php echo addslashes($row['file_path'] ?? ''); ?>')"
            class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
        ✨ Summarize
    </button>

    <button onclick="toggleAskBox('<?php echo $row['id']; ?>')"
            class="bg-violet-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-violet-700 transition">
        💬 Ask AI
    </button>

</div>

<!-- ASK AI INPUT BOX -->
<div id="ask_box_<?php echo $row['id']; ?>" class="hidden mt-4">
    <div class="flex gap-2">
        <input type="text"
               id="ask_input_<?php echo $row['id']; ?>"
               placeholder="Ask anything about this resource..."
               class="flex-1 border-2 border-violet-200 rounded-xl px-4 py-2 text-slate-700 focus:outline-none focus:border-violet-500 transition"
               onkeydown="if(event.key==='Enter') submitAsk('<?php echo $row['id']; ?>', '<?php echo addslashes($row['file_path'] ?? ''); ?>')"
        />
        <button onclick="submitAsk('<?php echo $row['id']; ?>', '<?php echo addslashes($row['file_path'] ?? ''); ?>')"
                class="bg-violet-600 text-white px-5 py-2 rounded-xl font-semibold hover:bg-violet-700 transition">
            Send
        </button>
    </div>
</div>

<!-- AI RESULT CONTAINER -->
<div id="ai_result_<?php echo $row['id']; ?>"></div>

</div>

<?php
        }?>

</div>

<?php
    }
    else { ?>

<div class="bg-amber-100 border border-amber-300 rounded-2xl p-8 text-center">
<p class="text-amber-900 font-bold text-lg">No Resources Found</p>
<p class="text-amber-800 mt-2">Try searching with different subject or topic</p>
</div>

<?php
    }?>

<?php
}?>

</main>

<!-- SUMMARY MODAL -->
<div id="summaryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-8 shadow-2xl transform transition-all">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-slate-800">Resource Summary</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewbox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="summaryContent" class="text-slate-600 leading-relaxed min-h-[100px] flex items-center justify-center">
            <!-- Summary will be injected here -->
            <div id="loader" class="hidden">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
            </div>
        </div>
        <div class="mt-8 flex justify-end">
            <button onclick="closeModal()" class="bg-slate-100 text-slate-700 px-6 py-2 rounded-xl font-semibold hover:bg-slate-200 transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
async function askAI(type, text, id, file_path = '') {
    const resultDiv = document.getElementById("ai_result_" + id);
    resultDiv.innerHTML = "<div class='bg-indigo-50 border p-4 rounded mt-3 flex items-center gap-3'><div class='animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600'></div><strong>AI is thinking...</strong></div>";

    try {
        let response = await fetch("../api/gpt_summary.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                text: text,
                type: type,
                file_path: file_path
            })
        });

        let data = await response.json();

        if (data && data.status === "error") {
             resultDiv.innerHTML = `<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded mt-3'><strong>Error:</strong> ${data.message}</div>`;
        } else if (data && data.candidates && data.candidates[0] && data.candidates[0].content) {
            let answer = data.candidates[0].content.parts[0].text;
            // Simple markdown-ish to HTML conversion for bullet points
            let formattedAnswer = answer.replace(/\n/g, '<br>').replace(/\* /g, '• ');
            
            resultDiv.innerHTML =
                "<div class='bg-indigo-50 border p-4 rounded mt-3'>" +
                "<div class='flex items-center gap-2 mb-2'>" +
                "<span class='text-indigo-600 font-bold'>AI Response</span>" +
                "</div>" +
                "<div class='text-slate-700 leading-relaxed'>" + formattedAnswer + "</div>" +
                "</div>";
        } else {
            throw new Error("Invalid response structure from API");
        }
    } catch (error) {
        resultDiv.innerHTML = "<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded mt-3'><strong>Error:</strong> Could not get AI response. Please check your API key or connection.</div>";
    }
}

function toggleAskBox(id) {
    const box = document.getElementById('ask_box_' + id);
    box.classList.toggle('hidden');
    if (!box.classList.contains('hidden')) {
        document.getElementById('ask_input_' + id).focus();
    }
}

async function submitAsk(id, file_path) {
    const input  = document.getElementById('ask_input_' + id);
    const question = input.value.trim();
    if (!question) return;

    const resultDiv = document.getElementById('ai_result_' + id);
    resultDiv.innerHTML = "<div class='bg-violet-50 border p-4 rounded mt-3 flex items-center gap-3'><div class='animate-spin rounded-full h-4 w-4 border-b-2 border-violet-600'></div><strong>AI is thinking...</strong></div>";
    input.value = '';

    try {
        let response = await fetch('../api/gpt_summary.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text: question, type: 'ask', file_path: file_path })
        });

        let data = await response.json();

        if (data && data.status === 'error') {
            resultDiv.innerHTML = `<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded mt-3'><strong>Error:</strong> ${data.message}</div>`;
        } else if (data && data.candidates && data.candidates[0] && data.candidates[0].content) {
            let answer = data.candidates[0].content.parts[0].text;
            let formattedAnswer = answer.replace(/\n/g, '<br>').replace(/\* /g, '• ');
            resultDiv.innerHTML =
                "<div class='bg-violet-50 border border-violet-200 p-4 rounded mt-3'>" +
                "<div class='flex items-center gap-2 mb-2'>" +
                "<span class='text-violet-700 font-bold'>💬 AI Answer</span>" +
                "</div>" +
                "<div class='text-slate-700 leading-relaxed'>" + formattedAnswer + "</div>" +
                "</div>";
        } else {
            throw new Error('Invalid response');
        }
    } catch (error) {
        resultDiv.innerHTML = "<div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded mt-3'><strong>Error:</strong> Could not get AI response.</div>";
    }
}
</script>

</body>
</html>