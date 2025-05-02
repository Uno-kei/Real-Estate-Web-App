<?php
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if user is logged in and has seller role
checkPermission(['seller']);

// Get seller data
$sellerId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$seller = fetchOne($sql, "i", [$sellerId]);

// Get properties for this seller
$sql = "SELECT p.*, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC";
$properties = fetchAll($sql, "i", [$sellerId]);

// Process property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_property') {
    $propertyId = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    
    if ($propertyId > 0) {
        // Verify the property belongs to this seller
        $sql = "SELECT id FROM properties WHERE id = ? AND seller_id = ?";
        $property = fetchOne($sql, "ii", [$propertyId, $sellerId]);
        
        if ($property) {
            // Delete property images
            $sql = "DELETE FROM property_images WHERE property_id = ?";
            updateData($sql, "i", [$propertyId]);
            
            // Delete property
            $sql = "DELETE FROM properties WHERE id = ?";
            $result = updateData($sql, "i", [$propertyId]);
            
            if ($result) {
                header("Location: properties.php?success=1");
                exit;
            }
        }
    }
    
    header("Location: properties.php?error=1");
    exit;
}

include '../inc/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Seller Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="properties.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i> My Properties
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Inquiries
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php 
                        $unreadCount = getUnreadMessagesCount($sellerId);
                        if ($unreadCount > 0): 
                        ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">My Properties</h5>
                    <a href="add_property.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> Add New Property
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i> Property deleted successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> An error occurred. Please try again.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($properties)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> You haven't listed any properties yet. Click "Add New Property" to get started.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($properties as $property): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-img-top" style="height: 200px; background-image: url('<?= $property['primary_image'] ?? '../img/property-placeholder.jpg' ?>'); background-size: cover; background-position: center;"></div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= $property['title'] ?></h5>
                                            <p class="card-text text-primary fw-bold"><?= formatCurrency($property['price']) ?></p>
                                            <p class="card-text">
                                                <i class="fas fa-map-marker-alt"></i> <?= $property['address'] ?>, <?= $property['city'] ?>, <?= $property['state'] ?>
                                            </p>
                                            <div class="d-flex mb-3">
                                                <div class="me-3"><i class="fas fa-bed me-1"></i> <?= $property['bedrooms'] ?> bd</div>
                                                <div class="me-3"><i class="fas fa-bath me-1"></i> <?= $property['bathrooms'] ?> ba</div>
                                                <div><i class="fas fa-ruler-combined me-1"></i> <?= $property['area'] ?> sqft</div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mt-3">
                                                <a href="../property_details.php?id=<?= $property['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this property?');">
                                                    <input type="hidden" name="action" value="delete_property">
                                                    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <?php
                                            // Get the status label
                                            $statusClass = '';
                                            $statusText = ucfirst($property['status']);
                                            
                                            switch ($property['status']) {
                                                case 'active':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'sold':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>