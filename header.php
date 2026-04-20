<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>salon system</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <link rel="stylesheet" href="/eproject/assets/css/style.css">
    
    <?php if(isset($_SESSION['id'])): ?>
    <script>
    // Real-time session validation polling
    setInterval(function() {
        fetch('/eproject/includes/check_session_status.php')
            .then(response => response.json())
            .then(data => {
                if (!data.valid) {
                    window.location.href = '/eproject/index.php';
                }
            })
            .catch(err => console.error('Session check failed', err));
    }, 10000); // Poll every 10 seconds
    </script>
    <?php endif; ?>

    <script>
    // Global Premium SweetAlert2 Theme Configuration
    const PremiumSwal = Swal.mixin({
        position: 'center',
        background: '#0f0f0f',
        color: '#ffffff',
        confirmButtonColor: '#D4AF37',
        cancelButtonColor: '#333',
        backdrop: 'rgba(0,0,0,0.85)',
        customClass: {
            popup: 'glass-card border-gold shadow-lg',
            title: 'text-gold fw-bold mb-3',
            content: 'text-light',
            confirmButton: 'px-4 py-2 rounded-pill',
            cancelButton: 'px-4 py-2 rounded-pill'
        }
    });
    
    // Set global Swal to our Premium version
    window.Swal = PremiumSwal;
    </script>
</head>

<body>

    