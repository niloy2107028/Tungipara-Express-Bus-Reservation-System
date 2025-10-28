<!-- Admin Quick Navigation Cards -->
<?php
// Detect current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="row g-3 mb-4">
    <div class="col-md-2 col-sm-4 col-6">
        <a href="<?php echo SITE_URL; ?>/admin/master_schedules.php" class="text-decoration-none">
            <div class="card text-center h-100 hover-shadow <?php echo $current_page == 'master_schedules.php' ? 'active-card' : ''; ?>">
                <div class="card-body p-3">
                    <p class="mb-0 mt-2 small fw-bold">Master Schedules</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <a href="<?php echo SITE_URL; ?>/admin/schedules.php" class="text-decoration-none">
            <div class="card text-center h-100 hover-shadow <?php echo $current_page == 'schedules.php' ? 'active-card' : ''; ?>">
                <div class="card-body p-3">
                    <p class="mb-0 mt-2 small fw-bold">Weekly Schedules</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <a href="<?php echo SITE_URL; ?>/admin/buses.php" class="text-decoration-none">
            <div class="card text-center h-100 hover-shadow <?php echo $current_page == 'buses.php' ? 'active-card' : ''; ?>">
                <div class="card-body p-3">
                    <p class="mb-0 mt-2 small fw-bold">Buses</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <a href="<?php echo SITE_URL; ?>/admin/routes.php" class="text-decoration-none">
            <div class="card text-center h-100 hover-shadow <?php echo $current_page == 'routes.php' ? 'active-card' : ''; ?>">
                <div class="card-body p-3">
                    <p class="mb-0 mt-2 small fw-bold">Routes</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <a href="<?php echo SITE_URL; ?>/admin/bookings.php" class="text-decoration-none">
            <div class="card text-center h-100 hover-shadow <?php echo $current_page == 'bookings.php' ? 'active-card' : ''; ?>">
                <div class="card-body p-3">
                    <p class="mb-0 mt-2 small fw-bold">Bookings</p>
                </div>
            </div>
        </a>
    </div>
</div>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }

    .hover-shadow:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .active-card {
        border: 2px solid #198754;
    }

    .text-purple {
        color: #6f42c1;
    }
</style>