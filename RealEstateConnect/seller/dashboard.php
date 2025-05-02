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

// Get property count
$sql = "SELECT COUNT(*) as count FROM properties WHERE seller_id = ?";
$propertiesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get active inquiries count
$sql = "SELECT COUNT(*) as count FROM inquiries i 
        JOIN properties p ON i.property_id = p.id 
        WHERE p.seller_id = ? AND i.status = 'pending'";
$pendingInquiriesCount = fetchOne($sql, "i", [$sellerId])['count'];

// Get unread messages count
$unreadCount = getUnreadMessagesCount($sellerId);

// Get recent properties
$sql = "SELECT p.*, pt.name as property_type, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        JOIN property_types pt ON p.property_type_id = pt.id 
        WHERE p.seller_id = ? 
        ORDER BY p.created_at DESC LIMIT 5";
$recentProperties = fetchAll($sql, "i", [$sellerId]);

// Get recent inquiries
$sql = "SELECT i.*, p.title as property_title, p.price, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image,
        u.full_name as buyer_name, u.email as buyer_email
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        JOIN users u ON i.buyer_id = u.id
        WHERE p.seller_id = ?
        ORDER BY i.created_at DESC LIMIT 5";
$recentInquiries = fetchAll($sql, "i", [$sellerId]);

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="add_property.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i> Add Property
                    </a>
                    <a href="inquiries.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope me-2"></i> Inquiries
                        <?php if ($pendingInquiriesCount > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-1"><?= $pendingInquiriesCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="messages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Messages
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Welcome Section -->
            <div class="dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="dashboard-title">Welcome, <?= $seller['full_name'] ?></h2>
                        <p class="dashboard-subtitle">Manage your property listings and inquiries</p>
                    </div>
                    <div>
                        <a href="add_property.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Add New Property
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="dashboard-stats mb-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $propertiesCount ?></h3>
                        <p>Properties Listed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $pendingInquiriesCount ?></h3>
                        <p>Pending Inquiries</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $unreadCount ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= formatDateTime($seller['created_at'], 'd M Y') ?></h3>
                        <p>Member Since</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Inquiries -->
            <div class="dashboard-content mb-4">
                <div class="dashboard-section-title">
                    <h3>Recent Inquiries</h3>
                    <?php if (count($recentInquiries) > 0): ?>
                        <a href="inquiries.php" class="btn btn-sm btn-outline-primary">View All</a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($recentInquiries)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't received any inquiries yet. List more properties to attract buyer interest.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Buyer</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInquiries as $inquiry): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $inquiry['property_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa' ?>'); background-size: cover; background-position: center;"></div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $inquiry['property_title'] ?></h6>
                                                    <small class="text-muted"><?= formatCurrency($inquiry['price']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?= $inquiry['buyer_name'] ?><br>
                                            <small class="text-muted"><?= $inquiry['buyer_email'] ?></small>
                                        </td>
                                        <td><?= formatInquiryStatus($inquiry['status']) ?></td>
                                        <td><?= formatDateTime($inquiry['created_at']) ?></td>
                                        <td>
                                            <a href="inquiries.php?id=<?= $inquiry['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Inquiry">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($inquiry['status'] === 'pending'): ?>
                                                <a href="javascript:void(0);" onclick="approveInquiry(<?= $inquiry['id'] ?>)" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="rejectInquiry(<?= $inquiry['id'] ?>)" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- My Properties -->
            <div class="dashboard-content">
                <div class="dashboard-section-title">
                    <h3>My Properties</h3>
                    <?php if (count($recentProperties) > 0): ?>
                        <a href="add_property.php" class="btn btn-sm btn-outline-primary">Add New</a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($recentProperties)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> You haven't listed any properties yet. 
                        <a href="add_property.php" class="alert-link">Add your first property</a> to start attracting buyers.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Property</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Listed Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProperties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0" style="width: 50px; height: 50px; background-image: url('<?= $property['primary_image'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa' ?>'); background-size: cover; background-position: center;"></div>
                                                <div class="ms-3">
                                                    <h6 class="mb-0"><?= $property['title'] ?></h6>
                                                    <small class="text-muted"><?= $property['city'] ?>, <?= $property['state'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= formatCurrency($property['price']) ?></td>
                                        <td>
                                            <span class="badge <?= $property['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                                <?= ucfirst($property['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDateTime($property['created_at'], 'd M Y') ?></td>
                                        <td>
                                            <a href="../property_details.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_property.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($recentProperties) == 5 && $propertiesCount > 5): ?>
                        <div class="text-center mt-3">
                            <a href="#" class="btn btn-outline-primary">View All Properties</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm" method="POST" action="../api/users.php">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $seller['full_name'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= $seller['email'] ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= $seller['phone'] ?>" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
                
                <hr>
                
                <form id="passwordForm" method="POST" action="../api/users.php">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-outline-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
<script>
// Direct JavaScript functions for inquiry handling
function approveInquiry(inquiryId) {
    if (confirm('Are you sure you want to approve this inquiry?')) {
        // Show processing message
        alert('Processing your request... Please wait.');
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('inquiry_id', inquiryId);
        formData.append('status', 'approved');
        
        // Make the AJAX request
        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Inquiry approved successfully!');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to approve inquiry. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again. Error: ' + error);
        });
    }
}

function rejectInquiry(inquiryId) {
    if (confirm('Are you sure you want to reject this inquiry?')) {
        // Show processing message
        alert('Processing your request... Please wait.');
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('inquiry_id', inquiryId);
        formData.append('status', 'rejected');
        
        // Make the AJAX request
        fetch('../api/inquiries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Inquiry rejected successfully!');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to reject inquiry. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again. Error: ' + error);
        });
    }
}
</script>

<?php include '../inc/footer.php'; ?>
