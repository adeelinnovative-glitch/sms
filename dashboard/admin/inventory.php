<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

if(isset($_GET['delete'])) {
    $iid = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM inventory WHERE item_id = $iid");
    header("Location: inventory.php?d_success=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $_POST['p_name'];
    $qty = $_POST['p_qty'];
    $price = $_POST['p_price'];
    $min = $_POST['p_min'];
    $supplier = $_POST['p_supplier'];
    $st = mysqli_prepare($con, "INSERT INTO inventory (product_name, quantity, price, min_stock_level, supplier_info) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($st, "sidis", $name, $qty, $price, $min, $supplier);
    $psuccess = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $item_id = intval($_POST['item_id']);
    $name = $_POST['p_name'];
    $qty = $_POST['p_qty'];
    $price = $_POST['p_price'];
    $min = $_POST['p_min'];
    $supplier = $_POST['p_supplier'];
    $st = mysqli_prepare($con, "UPDATE inventory SET product_name = ?, quantity = ?, price = ?, min_stock_level = ?, supplier_info = ? WHERE item_id = ?");
    mysqli_stmt_bind_param($st, "sidisi", $name, $qty, $price, $min, $supplier, $item_id);
    $usuccess = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_po'])) {
    $iid = intval($_POST['item_id']);
    mysqli_query($con, "UPDATE inventory SET po_status = 'sent', last_po_date = CURDATE() WHERE item_id = $iid");
    $usuccess = true; // Use this to trigger the info alert
}

// Fetch Inventory Stats
$statsQ = mysqli_query($con, "SELECT COUNT(*) as total_items, SUM(quantity * price) as total_value, SUM(CASE WHEN quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock FROM inventory");
$stats = mysqli_fetch_assoc($statsQ);
$total_items = $stats['total_items'] ?? 0;
$total_value = $stats['total_value'] ?? 0;
$low_stock = $stats['low_stock'] ?? 0;

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3><i class="fas fa-boxes text-gold me-2"></i>Inventory Management</h3>
                <p class="text-muted mb-0">Track salon supplies, view stock alerts, and manage product orders.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.15), rgba(0, 123, 255, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-box-open fs-3 mb-2 d-block text-info" style="opacity: 0.8;"></i>Total Products</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $total_items; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(139, 0, 0, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-exclamation-triangle fs-3 mb-2 d-block text-danger" style="opacity: 0.8;"></i>Low Stock Alerts</h6>
                    <h2 class="mb-0 text-white fw-bold"><?php echo $low_stock; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card text-center p-4 h-100 d-flex flex-column justify-content-center scale-hover shadow-lg" style="background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(32, 201, 151, 0.05)); border: 1px solid rgba(255,255,255,0.08);">
                    <h6 class="text-light mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-dollar-sign fs-3 mb-2 d-block text-success" style="opacity: 0.8;"></i>Total Value</h6>
                    <h2 class="mb-0 text-white fw-bold">$<?php echo number_format($total_value, 2); ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card mb-4 p-3 shadow-lg">
            <h5 class="text-gold mb-2"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
            <?php if(isset($psuccess) && $psuccess) echo '<div class="alert alert-success py-2 mb-2"><i class="fas fa-check-circle me-2"></i>Product Added Successfully!</div>'; ?>
            <?php if(isset($usuccess) && $usuccess && !isset($_POST['confirm_po'])) echo '<div class="alert alert-info py-2 mb-2"><i class="fas fa-check-circle me-2"></i>Product Updated Successfully!</div>'; ?>
            <?php if(isset($usuccess) && $usuccess && isset($_POST['confirm_po'])) echo '<div class="alert alert-gold border-gold py-2 mb-2" style="background: rgba(212, 175, 55, 0.2); color: #fff;"><i class="fas fa-paper-plane me-2"></i>Purchase Order Sent Successfully!</div>'; ?>
            <?php if(isset($_GET['delete_success'])) echo '<div class="alert alert-danger py-2 mb-2"><i class="fas fa-trash-alt me-2"></i>Product Deleted Successfully!</div>'; ?>
            <form action="" method="post" class="row g-4 align-items-end mt-0">
                <!-- Row 1 -->
                <div class="col-md-6">
                    <label class="form-label text-light fw-bold mb-2">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="p_name" class="form-control py-2" placeholder="e.g. L'Oreal Professional Shampoo" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-light fw-bold mb-2">Supplier Info <span class="text-danger">*</span></label>
                    <input type="text" name="p_supplier" class="form-control py-2" placeholder="Vendor Name / Distributor Details" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                
                <!-- Row 2 -->
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-2">Current Stock Qty <span class="text-danger">*</span></label>
                    <input type="number" name="p_qty" class="form-control" placeholder="0" style="height: 45px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-warning fw-bold mb-2">Min Alert Level <span class="text-danger">*</span></label>
                    <input type="number" name="p_min" class="form-control" placeholder="0" style="height: 45px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-light fw-bold mb-2">Price Per Unit ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="p_price" class="form-control" placeholder="0.00" style="height: 45px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #fff;" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_product" class="btn-gold w-100 fw-bold shadow-sm" style="height: 45px; font-size: 1.05rem; padding: 0;"><i class="fas fa-plus-circle me-2"></i> Add Product</button>
                </div>
            </form>
        </div>

        <div class="glass-card mb-5 p-4 shadow-lg">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="text-gold mb-0"><i class="fas fa-table me-2"></i>Current Stock Registry</h5>
                <div class="search-box" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="inventorySearch" class="form-control bg-dark text-light border-secondary shadow-none" placeholder="Search products..." onkeyup="filterInventory()">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0 align-middle" style="background: transparent;" id="inventoryTable">
                    <thead>
                        <tr>
                            <th class="text-gold border-bottom border-light pb-3">Product Name</th>
                            <th class="text-gold border-bottom border-light pb-3">Stock Level</th>
                            <th class="text-gold border-bottom border-light pb-3">Status</th>
                            <th class="text-gold border-bottom border-light pb-3">Price</th>
                            <th class="text-gold border-bottom border-light pb-3">Supplier Info</th>
                            <th class="text-gold border-bottom border-light pb-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($con, "SELECT * FROM inventory ORDER BY product_name ASC");
                        while($row = mysqli_fetch_assoc($res)) {
                            $isLow = $row['quantity'] <= $row['min_stock_level'];
                            $isPOSent = $row['po_status'] == 'sent' && $row['last_po_date'] == date('Y-m-d');
                            
                            $badge = !$isLow ? '<span class="badge rounded-pill bg-success px-3 py-2 shadow-sm" style="background: linear-gradient(135deg, #198754, #20c997) !important;"><i class="fas fa-check-circle me-1"></i> In Stock</span>' : '<span class="badge rounded-pill bg-danger px-3 py-2 shadow-sm" style="background: linear-gradient(135deg, #dc3545, #8b0000) !important;"><i class="fas fa-exclamation-triangle me-1"></i> Low Stock</span>';
                            
                            if ($isPOSent) {
                                $badge .= ' <div class="mt-2 text-gold small"><i class="fas fa-paper-plane me-1"></i>PO Sent: '.$row['last_po_date'].'</div>';
                            }
                            
                            // Visual bar
                            $percent = min(100, max(0, ($row['quantity'] / max(1, $row['min_stock_level'] * 3)) * 100));
                            $barColor = $isLow ? 'bg-danger' : 'bg-success';
                            $stockCell = "
                                <div class='d-flex align-items-center mb-1'>
                                    <span class='fw-bold fs-6 me-2'>{$row['quantity']}</span> <span class='text-muted small'>/ Min {$row['min_stock_level']}</span>
                                </div>
                                <div class='progress' style='height: 4px; background-color: rgba(255,255,255,0.1);'>
                                    <div class='progress-bar {$barColor}' role='progressbar' style='width: {$percent}%;'></div>
                                </div>
                            ";

                            $poBtn = $isLow ? ($isPOSent ? "<a href='print_po.php?id={$row['item_id']}' target='_blank' class='btn btn-sm btn-outline-info rounded-pill px-3 py-1 scale-hover border border-info'><i class='fas fa-print me-1'></i>Print PO</a>" : "<button onclick='showPODraft(\"{$row['item_id']}\", \"" . addslashes($row['product_name']) . "\", \"" . addslashes($row['supplier_info']) . "\")' class='btn btn-sm btn-outline-warning rounded-pill px-3 py-1 scale-hover border border-warning'><i class='fas fa-file-invoice me-1'></i>Generate PO</button>") : "<span class='text-muted'>--</span>";
                            
                            $safeName = htmlspecialchars(addslashes($row['product_name']));
                            $safeSupplier = htmlspecialchars(addslashes($row['supplier_info']));
                            
                            echo "<tr>
                                <td><div class='fw-bold text-light'>{$row['product_name']}</div></td>
                                <td style='min-width: 130px;'>{$stockCell}</td>
                                <td>{$badge}</td>
                                <td><span class='text-light'>$" . number_format($row['price'], 2) . "</span></td>
                                <td><span class='text-muted'><i class='fas fa-truck text-gold me-1'></i> {$row['supplier_info']}</span></td>
                                <td>
                                    <div class='d-flex justify-content-center align-items-center gap-2'>
                                        {$poBtn}
                                        <button onclick='editProduct({$row['item_id']}, \"{$safeName}\", {$row['quantity']}, {$row['min_stock_level']}, {$row['price']}, \"{$safeSupplier}\")' class='btn btn-sm btn-outline-light rounded-pill px-3 scale-hover' title='Edit'><i class='fas fa-pen me-1'></i> Edit</button>
                                        <a href='?delete={$row['item_id']}' class='btn btn-sm btn-outline-danger rounded-pill px-3 scale-hover' title='Delete' onclick='return confirm(\"Are you sure you want to delete {$safeName}?\")'><i class='fas fa-trash-alt me-1'></i> Delete</a>
                                    </div>
                                </td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterInventory() {
    var input, filter, table, tr, tdName, tdSupplier, i, txtValueName, txtValueSupplier;
    input = document.getElementById("inventorySearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("inventoryTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tdName = tr[i].getElementsByTagName("td")[0];
        tdSupplier = tr[i].getElementsByTagName("td")[4];
        if (tdName || tdSupplier) {
            txtValueName = tdName.textContent || tdName.innerText;
            txtValueSupplier = tdSupplier.textContent || tdSupplier.innerText;
            if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueSupplier.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

function showPODraft(id, product, supplier) {
    const poNumber = "PO-" + Math.floor(1000 + Math.random() * 9000);
    const date = new Date().toLocaleDateString();
    
    Swal.fire({
        title: '<span class="text-gold">Purchase Order Draft</span>',
        html: `
            <div class="text-start p-3" style="background: #1a1a1a; border: 1px solid #daa520; color: #fff; font-family: monospace;">
                <div class="d-flex justify-content-between mb-3 border-bottom border-secondary pb-2">
                    <span><strong>${poNumber}</strong></span>
                    <span>Date: ${date}</span>
                </div>
                <p class="mb-1 text-gold"><strong>SUPPLIER:</strong></p>
                <p class="mb-3">${supplier}</p>
                
                <p class="mb-1 text-gold"><strong>ITEM DESCRIPTION:</strong></p>
                <div class="d-flex justify-content-between">
                    <span>${product} (Restock)</span>
                    <span>Qty: 50 units</span>
                </div>
            </div>
            <form id="poConfirmForm" method="post">
                <input type="hidden" name="confirm_po" value="1">
                <input type="hidden" name="item_id" value="${id}">
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirm & Send Order',
        cancelButtonText: 'Discard',
        confirmButtonColor: '#daa520',
        cancelButtonColor: '#333',
        background: '#111',
        preConfirm: () => {
            document.getElementById('poConfirmForm').submit();
        }
    });
}

function editProduct(id, name, qty, min, price, supplier) {
    Swal.fire({
        title: '<span class="text-gold">Edit Inventory Item</span>',
        html: `
            <form id="editForm" method="post" class="text-start p-3">
                <input type="hidden" name="update_product" value="1">
                <input type="hidden" name="item_id" value="${id}">
                <div class="mb-3">
                    <label class="text-gold small mb-1">Product Name</label>
                    <input type="text" name="p_name" class="form-control" value="${name}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small mb-1">Quantity</label>
                        <input type="number" name="p_qty" class="form-control" value="${qty}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-gold small mb-1">Min Stock</label>
                        <input type="number" name="p_min" class="form-control" value="${min}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="text-gold small mb-1">Price per Unit</label>
                    <input type="number" step="0.01" name="p_price" class="form-control" value="${price}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
                <div class="mb-3">
                    <label class="text-gold small mb-1">Supplier Info</label>
                    <input type="text" name="p_supplier" class="form-control" value="${supplier}" required style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Changes',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#daa520',
        cancelButtonColor: '#333',
        background: '#111',
        preConfirm: () => {
            document.getElementById('editForm').submit();
        }
    });
}
</script>

<?php include_once("../../footer.php"); ?>
