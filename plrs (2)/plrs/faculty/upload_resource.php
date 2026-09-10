<?php
include("../config/db.php");

if(!isset($_SESSION['uid']) || $_SESSION['role']!='faculty'){
    header("Location: ../auth/login.php");
    exit();
}

$faculty = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='{$_SESSION['uid']}'")
);

$upload_success = false;
$error_msg = "";

if(isset($_POST['upload'])){

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $youtube = mysqli_real_escape_string($conn, $_POST['youtube_link']);
    
    $faculty_name = $faculty['name'];
    $faculty_id = $faculty['id'];
    $request_id = isset($_POST['request_id']) ? mysqli_real_escape_string($conn, $_POST['request_id']) : "NULL";
    $file_path = "";
    
    // Make file upload mandatory
    if(empty($_FILES['pdf_file']['name'])){
        $error_msg = "Please upload a PDF, DOC, or DOCX file.";
    } else {
        $target = "../uploads/";
        $file_path = $target.time()."_".$_FILES['pdf_file']['name'];
        
        if(!is_dir($target)){
            mkdir($target, 0755, true);
        }
        
        move_uploaded_file(
            $_FILES['pdf_file']['tmp_name'],
            $file_path
        );
        
        $type = "pdf";
        $link = (!empty($youtube)) ? $youtube : $file_path;
        
        /* Insert Resource */
        $insert = mysqli_query($conn,"
            INSERT INTO resources
            (faculty_id, faculty_name, request_id, subject, sub_topic, title, resource_type, resource_link, file_path)
            VALUES
            ('$faculty_id', '$faculty_name', $request_id, '$subject', '$topic', '$title', '$type', '$link', '$file_path')
        ");
        
        if($insert){
            $upload_success = true;
        } else {
            $error_msg = "Database error while uploading.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resources | EduSmart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .sidebar-glass {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(226,232,240,0.5);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">
    <div class="flex items-center gap-3 mb-10">
        <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-xl flex items-center justify-center text-white font-bold shadow-lg">
            ES
        </div>
        <span class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-indigo-700 bg-clip-text text-transparent">EduSmart</span>
    </div>

    <nav class="space-y-2 flex-1">
        <a href="dashboard.php"
           class="block px-4 py-3 text-slate-600 hover:bg-indigo-50 rounded-xl transition font-medium hover:text-indigo-600">
            Dashboard
        </a>

        <a href="upload_resource.php"
           class="block px-4 py-3 bg-gradient-to-r from-indigo-50 to-indigo-50/50 text-indigo-600 rounded-xl font-semibold transition border-l-4 border-indigo-600">
            Upload Resources
        </a>

        <a href="resource_feedback.php"
           class="block px-4 py-3 text-slate-600 hover:bg-indigo-50 rounded-xl transition font-medium hover:text-indigo-600">
            Resource & Feedback
        </a>

        <a href="analytics.php"
           class="block px-4 py-3 text-slate-600 hover:bg-indigo-50 rounded-xl transition font-medium hover:text-indigo-600">
            Analytics
        </a>
    </nav>

    <div class="mt-auto bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-lg">
        <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Role</p>
        <p class="font-bold text-sm mt-1">Faculty</p>
    </div>
</aside>

<!-- MAIN -->
<main class="flex-1 lg:ml-64 p-8">

    <header class="flex justify-between items-center mb-12">
        <div>
            <h2 class="text-4xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                Upload Learning Resources
            </h2>
            <p class="text-slate-500 mt-2">Share knowledge with students worldwide</p>
        </div>
        <a href="../auth/logout.php"
           class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-2 rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition shadow-lg hover:shadow-xl">
            Logout
        </a>
    </header>

    <!-- SUCCESS MESSAGE -->
    <?php if($upload_success){ ?>
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-xl p-4 mb-8 shadow-sm animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-green-600 font-bold">✓</span>
            </div>
            <p class="text-green-700 font-semibold">Resource uploaded successfully! It's now available to students.</p>
        </div>
    </div>
    <?php } ?>

    <!-- ERROR MESSAGE -->
    <?php if(!empty($error_msg)){ ?>
    <div class="bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 rounded-xl p-4 mb-8 shadow-sm animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-red-600 font-bold">⚠</span>
            </div>
            <p class="text-red-700 font-semibold"><?php echo htmlspecialchars($error_msg); ?></p>
        </div>
    </div>
    <?php } ?>

    <!-- UPLOAD FORM -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 mb-12 card-hover">
        <div class="mb-8">
            <h3 class="text-2xl font-bold text-slate-800">Add New Resource</h3>
            <p class="text-slate-500 mt-2">Upload videos, documents, or links for your students</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php if(isset($_GET['request_id'])){ ?>
                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($_GET['request_id']); ?>">
            <?php } ?>

            <div class="md:col-span-1">
                <label class="block text-sm font-semibold text-slate-700 mb-3">Resource Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" 
                       placeholder="e.g., Introduction to Algorithms"
                       class="w-full border-2 border-slate-200 p-3 rounded-xl focus:outline-none input-focus"
                       required>
                <p class="text-xs text-slate-500 mt-2">Give your resource a clear, descriptive title</p>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-semibold text-slate-700 mb-3">Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" 
                       placeholder="e.g., Computer Science"
                       class="w-full border-2 border-slate-200 p-3 rounded-xl focus:outline-none input-focus"
                       required>
                <p class="text-xs text-slate-500 mt-2">Which subject is this for?</p>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-semibold text-slate-700 mb-3">Topic <span class="text-red-500">*</span></label>
                <input type="text" name="topic" 
                       placeholder="e.g., Data Structures"
                       class="w-full border-2 border-slate-200 p-3 rounded-xl focus:outline-none input-focus"
                       required>
                <p class="text-xs text-slate-500 mt-2">Specific topic covered</p>
            </div>

            <div class="md:col-span-1">
                <label class="block text-sm font-semibold text-slate-700 mb-3">YouTube Link <span class="text-slate-400 text-xs">(Optional)</span></label>
                <input type="url" name="youtube_link" 
                       placeholder="https://youtube.com/watch?v=..."
                       class="w-full border-2 border-slate-200 p-3 rounded-xl focus:outline-none input-focus">
                <p class="text-xs text-slate-500 mt-2">Paste a YouTube video URL</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-slate-700 mb-3">Upload PDF/Notes <span class="text-red-500">*</span></label>
                <div class="relative border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-indigo-400 hover:bg-indigo-50/30 transition">
                    <input type="file" name="pdf_file" accept=".pdf,.doc,.docx" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('fileName').innerText = this.files[0] ? this.files[0].name : 'Click to upload or drag and drop'">
                    <div class="text-4xl mb-2">📄</div>
                    <p class="text-slate-700 font-semibold" id="fileName">Click to upload or drag and drop</p>
                    <p class="text-xs text-slate-500 mt-1">PDF, DOC, or DOCX (Max 10MB)</p>
                </div>
            </div>

            <div class="md:col-span-2">
                <button type="submit" name="upload"
                        class="w-full btn-primary text-white font-bold py-4 px-6 rounded-xl text-lg">
                    📤 Upload Resource
                </button>
            </div>

        </form>
    </div>

</main>

</body>
</html>