<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Staff Portal' ?> — True Beauty Concept</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/staff_layout.css">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-layout">
        <?php require_once 'includes/staff_sidebar.php'; ?>

        <div class="main-content">
            <header class="masthead">
                <div class="masthead-inner">
                    <h2 class="page-title"><?= $page_title ?? 'Dashboard' ?></h2>
                    <div class="page-meta"><?= date('l, F j, Y') ?></div>
                </div>
            </header>

            <main class="content-wrapper">
