<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'student'){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* Get user info */
$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'")
);

/* Initial data for SSR */
function getResourceAnalytics($conn, $uid) {
    $result = mysqli_query($conn, "
        SELECT sr.id, sr.subject, sr.weak_topic, sr.created_at, f.id as feedback_id
        FROM student_requests sr
        LEFT JOIN feedback f ON sr.id = f.request_id
        WHERE sr.user_id = '$uid'
        ORDER BY sr.created_at DESC
    ");
    $analytics = ['total_requests' => 0, 'subjects_requested' => [], 'topics_requested' => [], 'total_feedback_given' => 0, 'pending_resources' => 0];
    while($row = mysqli_fetch_assoc($result)) {
        $analytics['total_requests']++;
        $subject = $row['subject'];
        $analytics['subjects_requested'][$subject] = ($analytics['subjects_requested'][$subject] ?? 0) + 1;
        $topic = $row['weak_topic'];
        $analytics['topics_requested'][$topic] = ($analytics['topics_requested'][$topic] ?? 0) + 1;
        if($row['feedback_id']) $analytics['total_feedback_given']++;
        else $analytics['pending_resources']++;
    }
    return $analytics;
}

function getTestAnalytics($conn, $uid) {
    // Check if table exists
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'test_results'");
    if(mysqli_num_rows($check) == 0) {
        return ['tests_taken' => 0, 'avg_score' => 0];
    }
    
    $q = mysqli_query($conn, "SELECT COUNT(*) as tests_taken, AVG(score/total*100) as avg_score FROM test_results WHERE user_id='$uid'");
    $r = mysqli_fetch_assoc($q);
    return [
        'tests_taken' => (int)$r['tests_taken'],
        'avg_score' => round((float)$r['avg_score'], 1)
    ];
}

$resources = getResourceAnalytics($conn, $uid);
$tests = getTestAnalytics($conn, $uid);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Analytics - EduSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #f8fafc;
        }

        .sidebar-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(226,232,240,0.8);
        }

        .premium-shadow { box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); }
        .premium-shadow-hover:hover { box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.1); transform: translateY(-2px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>

<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full z-50">
    <div class="flex items-center gap-3 mb-10 px-2">
        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
            📘
        </div>
        <span class="text-xl font-bold text-slate-800">
            EduSmart
        </span>
    </div>

    <nav class="space-y-2 overflow-y-auto flex-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
            Dashboard
        </a>
        <a href="resources.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
            Learning Resources
        </a>
        <a href="search_resource.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
            Search Resources
        </a>
        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
            Analytics
        </a>
        <a href="take_test.php" class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
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

<main class="flex-1 lg:ml-64 p-8">
    <!-- HEADER -->
    <header class="flex justify-between items-center mb-10">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">
                Welcome, <?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'Student'; ?> 👋
            </h2>
            <p class="text-slate-500 mt-1">
                Your personalized learning journey
            </p>
        </div>
        <div class="flex items-center gap-4">
            <div id="statusIndicator" class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-600 rounded-full text-sm font-bold border border-green-100">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                Data Synced
            </div>
            <button onclick="updateAll()" class="p-2 bg-white rounded-full border border-slate-200 text-slate-400 hover:text-indigo-600 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </button>
            <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition">
                Logout
            </a>
        </div>
    </header>

    <div class="mb-10 text-center lg:text-left">
        <span class="text-indigo-600 font-bold text-sm uppercase tracking-widest mb-2 block">Personal Progress</span>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">My Activity Hub</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-500 text-sm font-medium mb-2">Total Requests</p>
            <h3 id="stat-total-requests" class="text-4xl font-bold text-slate-800"><?= $resources['total_requests'] ?></h3>
            <p class="text-xs text-slate-400 mt-2">Personal learning requests</p>
        </div>
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-500 text-sm font-medium mb-2">Pending Resources</p>
            <h3 id="stat-pending" class="text-4xl font-bold text-slate-800"><?= $resources['pending_resources'] ?></h3>
            <p class="text-xs text-slate-400 mt-2">Awaiting faculty upload</p>
        </div>
        <div class="bg-indigo-50 p-6 rounded-3xl shadow-sm border border-indigo-100 hover:shadow-md transition">
            <p class="text-indigo-600 text-sm font-medium mb-2">Feedback Given</p>
            <h3 id="stat-feedback" class="text-4xl font-bold text-indigo-700"><?= $resources['total_feedback_given'] ?></h3>
            <p class="text-xs text-indigo-500 mt-2">Responses contributed</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
            <h3 class="text-xl font-extrabold text-slate-900 mb-6">My Subject Focus</h3>
            <div id="subjectDist" class="space-y-6">
                <?php if(empty($resources['subjects_requested'])): ?>
                    <p class="text-slate-400 italic">No data available yet</p>
                <?php else: ?>
                    <?php foreach($resources['subjects_requested'] as $subj => $count): 
                         $perc = ($count / $resources['total_requests']) * 100; ?>
                        <div>
                            <div class="flex justify-between text-sm font-bold mb-2">
                                <span class="text-slate-600"><?= htmlspecialchars($subj) ?></span>
                                <span class="text-indigo-600"><?= round($perc) ?>%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: <?= $perc ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
            <h3 class="text-xl font-extrabold text-slate-900 mb-6">Topic Breakdown</h3>
            <div id="topicDist" class="space-y-4">
                 <?php if(empty($resources['topics_requested'])): ?>
                    <p class="text-slate-400 italic">No data available yet</p>
                <?php else: ?>
                    <?php 
                    arsort($resources['topics_requested']);
                    $top_topics = array_slice($resources['topics_requested'], 0, 5);
                    foreach($top_topics as $topic => $count): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="font-bold text-slate-700"><?= htmlspecialchars($topic) ?></span>
                            <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-black"><?= $count ?> Requests</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-extrabold text-slate-900">AI Test Performance</h3>
            <span class="bg-indigo-50 text-indigo-600 px-4 py-1.5 rounded-full text-sm font-bold border border-indigo-100">Evaluations</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-center p-6 bg-slate-50 border border-slate-100 rounded-3xl">
                <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-2xl mr-6 text-indigo-500">
                    📝
                </div>
                <div>
                    <p class="text-slate-500 font-medium text-sm mb-1">Total Tests Taken</p>
                    <h4 id="stat-tests-taken" class="text-3xl font-black text-slate-800"><?= $tests['tests_taken'] ?></h4>
                </div>
            </div>
            
            <div class="flex items-center p-6 bg-slate-50 border border-slate-100 rounded-3xl">
                <div class="w-16 h-16 rounded-2xl bg-white shadow-sm flex items-center justify-center text-2xl mr-6 text-green-500">
                    🎯
                </div>
                <div>
                    <p class="text-slate-500 font-medium text-sm mb-1">Average Score</p>
                    <h4 id="stat-avg-score" class="text-3xl font-black text-slate-800"><?= $tests['avg_score'] ?>%</h4>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    async function updateAll() {
        const status = document.getElementById('statusIndicator');
        try {
            const res = await fetch('get_analytics.php');
            const data = await res.json();
            
            // Stats
            document.getElementById('stat-total-requests').innerText = data.resource.total_requests;
            document.getElementById('stat-pending').innerText = data.resource.pending_resources;
            document.getElementById('stat-feedback').innerText = data.resource.total_feedback_given;
            
            // Subject Distribution
            const subDist = document.getElementById('subjectDist');
            let subHtml = '';
            for(const [subj, count] of Object.entries(data.resource.subjects_requested)) {
                const perc = (count / data.resource.total_requests) * 100;
                subHtml += `
                    <div>
                        <div class="flex justify-between text-sm font-bold mb-2">
                            <span class="text-slate-600">${subj}</span>
                            <span class="text-indigo-600">${Math.round(perc)}%</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full" style="width: ${perc}%"></div>
                        </div>
                    </div>
                `;
            }
            if(!subHtml) subHtml = '<p class="text-slate-400 italic">No data available yet</p>';
            subDist.innerHTML = subHtml;

            // Topic Breakdown
            const topicDist = document.getElementById('topicDist');
            let topHtml = '';
            const topics = Object.entries(data.resource.topics_requested).sort((a,b) => b[1] - a[1]).slice(0, 5);
            for(const [topic, count] of topics) {
                topHtml += `
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="font-bold text-slate-700">${topic}</span>
                        <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-xs font-black">${count} Requests</span>
                    </div>
                `;
            }
            if(!topHtml) topHtml = '<p class="text-slate-400 italic">No data available yet</p>';
            topicDist.innerHTML = topHtml;
            
            // Test Stats
            if(data.tests) {
                document.getElementById('stat-tests-taken').innerText = data.tests.tests_taken;
                document.getElementById('stat-avg-score').innerText = data.tests.avg_score + '%';
            }
            
            status.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Data Synced';
            status.className = 'flex items-center gap-2 px-4 py-2 bg-green-50 text-green-600 rounded-full text-sm font-bold border border-green-100';

        } catch(e) {
            console.error(e);
            status.innerHTML = '<span class="w-2 h-2 bg-red-500 rounded-full"></span> Error fetching data';
            status.className = 'flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 rounded-full text-sm font-bold border border-red-100';
        }
    }

    // Polling every 10 seconds
    setInterval(updateAll, 10000);
</script>

</body>
</html>
