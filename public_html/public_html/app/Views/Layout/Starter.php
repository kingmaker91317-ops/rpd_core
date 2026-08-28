<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="current-user" content="<?= session('unames') ?>">
    <meta name="timezone" content="UTC">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">

    <!-- Custom CSS -->
    <?= link_tag('assets/css/natacode.css') ?>
    
    <!-- Additional CSS -->
    <?= $this->renderSection('css') ?>

    <title><?= BASE_NAME ?> - <?= isset($title) ? $title : 'Panel' ?></title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script>
</head>

<body class="app-shell d-flex flex-column min-vh-100">
    <!-- Start menu -->
    <?= $this->include('Layout/Header') ?>
    <!-- End of menu -->
    
    <main class="flex-grow-1">
        <div class="container page-shell app-reveal" id="content">
            <!-- Start content -->
            <?= $this->renderSection('content') ?>
            <!-- End of content -->
        </div>
    </main>

    <footer class="app-footer bg-light border-top py-3 text-muted mt-auto" style="width: 100%;">
        <div class="container d-flex justify-content-center align-items-center">
            <small class="text-muted">&copy; <?= date('Y') ?> - <?= BASE_NAME ?></small>
        </div>
    </footer>

    <!-- Core JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.1.0/sweetalert2.all.min.js" integrity="sha512-0UUEaq/z58JSHpPgPv8bvdhHFRswZzxJUT9y+Kld5janc9EWgGEVGfWV1hXvIvAJ8MmsR5d4XV9lsuA90xXqUQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

    <!-- Custom JavaScript -->
    <?= script_tag('assets/js/natacode.js') ?>

    <script>
    // Global AJAX setup
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    // Adjust bottom padding for content
    function adjustBottomPadding() {
        const footerHeight = document.querySelector('footer').clientHeight;
        document.getElementById('content').style.paddingBottom = footerHeight + 'px';
    }

    window.addEventListener('load', adjustBottomPadding);
    window.addEventListener('resize', adjustBottomPadding);
    </script>

    <!-- Additional JavaScript -->
    <?= $this->renderSection('js') ?>

</body>

</html>
