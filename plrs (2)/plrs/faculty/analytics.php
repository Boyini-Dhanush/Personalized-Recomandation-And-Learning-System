<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'faculty'){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* Get user info */
$user = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'")
);

/* =====================
   ANALYTICS FUNCTIONS
===================== */

function getFacultyAnalytics($conn, $faculty_name) {
    /* Get all resources uploaded by this faculty */
    $resources = mysqli_query($conn, "SELECT * FROM resources WHERE faculty_name='$faculty_name'");
    
    $analytics = [
        'total_resources' => 0,
        'resources_by_subject' => [],
        'resource_types' => [
            'youtube' => 0,
            'pdf' => 0
        ]
    ];
    
    $analytics['total_resources'] = mysqli_num_rows($resources);
    
    /* Resources by subject */
    $resources = mysqli_query($conn, "
        SELECT subject, COUNT(*) as count FROM resources 
        WHERE faculty_name='$faculty_name' 
        GROUP BY subject
    ");
    
    while($row = mysqli_fetch_assoc($resources)) {
        $analytics['resources_by_subject'][$row['subject']] = $row['count'];
    }
    
    /* Resource types count */
    $resource_types = mysqli_query($conn, "
        SELECT resource_type, COUNT(*) as count FROM resources 
        WHERE faculty_name='$faculty_name' 
        GROUP BY resource_type
    ");
    
    while($row = mysqli_fetch_assoc($resource_types)) {
        $analytics['resource_types'][$row['resource_type']] = $row['count'];
    }
    
    return $analytics;
}

function getUploadedResources($conn, $faculty_name) {
    /* Get all uploaded resources for this faculty */
    $result = mysqli_query($conn, "
        SELECT * FROM resources 
        WHERE faculty_name='$faculty_name'
        ORDER BY id DESC
    ");
    
    $resources = [];
    while($row = mysqli_fetch_assoc($result)) {
        $resources[] = $row;
    }
    
    return $resources;
}

/* Get all analytics data */
$faculty_analytics = getFacultyAnalytics($conn, $user['name']);
$uploaded_resources = getUploadedResources($conn, $user['name']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Analytics - Faculty</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    body {
        background: #f8fafc;
    }
</style>

</head>

<body class="min-h-screen flex">

<!-- Sidebar -->
<aside class="w-64 bg-white border-r border-slate-200 sticky top-0 h-screen flex flex-col p-6">

    <div class="flex items-center px-2 py-3 gap-3 font-bold text-xl mb-8">
        <div class="bg-indigo-600 text-white p-2 rounded-lg text-sm">E</div>
        EduSmart
    </div>

    <nav class="flex-1 space-y-2">

        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Dashboard
        </a>

        <a href="upload_resource.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Upload Resources
        </a>

        <a href="resource_feedback.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium transition">
            Resource & Feedback
        </a>

        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
            Analytics
        </a>

    </nav>

    <div class="p-4 bg-slate-900 rounded-2xl text-white">
        <p class="text-xs text-slate-400 mb-1">Current Role</p>
        <p class="font-bold text-sm">Faculty</p>
    </div>

</aside>

<!-- Main Content -->
<main class="flex-1 p-8">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-4xl font-bold text-slate-800">Analytics Dashboard</h1>
        <a href="dashboard.php" class="text-indigo-600 hover:underline font-medium">← Back</a>
    </div>
    <p class="text-slate-500 mb-8">Your resource management and student impact overview</p>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">Resources Uploaded</p>
            <p class="text-4xl font-bold text-indigo-600"><?php echo $faculty_analytics['total_resources']; ?></p>
            <p class="text-xs text-slate-400 mt-2">learning materials</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">YouTube Videos</p>
            <p class="text-4xl font-bold text-red-600"><?php echo isset($faculty_analytics['resource_types']['youtube']) ? $faculty_analytics['resource_types']['youtube'] : 0; ?></p>
            <p class="text-xs text-slate-400 mt-2">video resources</p>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition">
            <p class="text-slate-600 text-sm font-medium mb-2">PDF Notes</p>
            <p class="text-4xl font-bold text-green-600"><?php echo isset($faculty_analytics['resource_types']['pdf']) ? $faculty_analytics['resource_types']['pdf'] : 0; ?></p>
            <p class="text-xs text-slate-400 mt-2">document resources</p>
        </div>

        <div class="bg-indigo-50 rounded-2xl p-6 shadow-sm border border-indigo-200 hover:shadow-md transition">
            <p class="text-indigo-600 text-sm font-medium mb-2">Subjects Covered</p>
            <p class="text-4xl font-bold text-indigo-700"><?php echo count($faculty_analytics['resources_by_subject']); ?></p>
            <p class="text-xs text-indigo-500 mt-2">different subjects</p>
        </div>

    </div>

    <!-- Resources by Subject -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-slate-200">
        <h2 class="text-2xl font-bold mb-6 text-slate-800">Resources by Subject</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <div class="space-y-3">
                    <?php if(count($faculty_analytics['resources_by_subject']) > 0): ?>
                        <?php foreach($faculty_analytics['resources_by_subject'] as $subject => $count): ?>
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                                <span class="text-slate-700 font-medium"><?php echo htmlspecialchars($subject); ?></span>
                                <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-bold text-sm"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-slate-400 text-center py-6 italic">No resources uploaded yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- All Uploaded Resources -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-200">
        <h2 class="text-2xl font-bold mb-6 text-slate-800">All Uploaded Resources</h2>
        
        <?php if(count($uploaded_resources) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 sticky top-0 border-b border-slate-200">
                        <tr>
                            <th class="p-4 text-left font-semibold text-slate-700">Title</th>
                            <th class="p-4 text-left font-semibold text-slate-700">Subject</th>
                            <th class="p-4 text-left font-semibold text-slate-700">Topic</th>
                            <th class="p-4 text-center font-semibold text-slate-700">Type</th>
                            <th class="p-4 text-center font-semibold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($uploaded_resources as $resource): ?>
                            <tr class="border-b hover:bg-slate-50 transition-colors">
                                <td class="p-4 text-slate-800 font-medium"><?php echo htmlspecialchars($resource['title']); ?></td>
                                <td class="p-4 text-slate-700"><?php echo htmlspecialchars($resource['subject']); ?></td>
                                <td class="p-4 text-slate-700"><?php echo htmlspecialchars($resource['sub_topic']); ?></td>
                                <td class="p-4 text-center">
                                    <?php if($resource['resource_type'] == 'youtube'): ?>
                                        <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">YOUTUBE</span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">PDF</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($resource['resource_type'] == 'youtube'): ?>
                                        <a href="<?php echo htmlspecialchars($resource['resource_link']); ?>" 
                                           target="_blank"
                                           class="text-red-600 hover:text-red-800 font-semibold text-sm">View</a>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" 
                                           target="_blank"
                                           class="text-green-600 hover:text-green-800 font-semibold text-sm">Download</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-8 text-center">
                <p class="text-slate-600 font-medium text-lg">No resources uploaded yet</p>
                <p class="text-slate-500 mt-2">Click "Upload Resources" to get started</p>
                <a href="upload_resource.php" class="inline-block mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Upload Your First Resource
                </a>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>

</html>
