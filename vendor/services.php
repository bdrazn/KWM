<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$db = new Database();
$auth = new Auth($db);

if (!$auth->isLoggedIn() || $_SESSION['user_type'] !== 'vendor') {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = intval($_POST['duration']);

    $stmt = $db->getConnection()->prepare('
        INSERT INTO services (vendor_id, title, description, price, duration)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $title, SQLITE3_TEXT);
    $stmt->bindValue(3, $description, SQLITE3_TEXT);
    $stmt->bindValue(4, $price, SQLITE3_FLOAT);
    $stmt->bindValue(5, $duration, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Service added successfully!'
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Failed to add service. Please try again.'
        ];
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

include '../includes/header.php';
?>

<div class="container py-4" style="max-width: 1200px;">
    <div class="row">
        <!-- Service Creation Form -->
        <div class="col-md-4">
            <div class="card mb-3 animate-fade-in">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Add New Service
                    </h5>
                    <form method="POST" id="serviceForm" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-tag text-muted me-2"></i>
                                Service Title
                            </label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-folder text-muted me-2"></i>
                                Category
                            </label>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>Select a category</option>
                                <option value="tutoring">Tutoring</option>
                                <option value="design">Design Services</option>
                                <option value="programming">Programming</option>
                                <option value="writing">Writing & Editing</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-align-left text-muted me-2"></i>
                                Description
                            </label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-dollar-sign text-muted me-2"></i>
                                Price
                            </label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-clock text-muted me-2"></i>
                                Duration (minutes)
                            </label>
                            <select name="duration" class="form-select" required>
                                <option value="30">30 minutes</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                            </select>
                        </div>
                        <button type="submit" name="add_service" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            Add Service
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Services List -->
        <div class="col-md-8">
            <div class="card animate-fade-in">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-briefcase text-primary me-2"></i>
                            My Services
                        </h5>
                        <div class="input-group w-50">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchServices" placeholder="Search services...">
                        </div>
                    </div>
                    
                    <!-- Update the example service card to include category -->
                    <!-- Replace the static services list with this -->
                    <div id="servicesList">
                        <?php
                        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                        $perPage = 3; // Number of services per page
                        $offset = ($page - 1) * $perPage;
                        
                        // Get total count for pagination
                        $countStmt = $db->getConnection()->prepare('SELECT COUNT(*) as total FROM services WHERE vendor_id = ?');
                        $countStmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
                        $totalServices = $countStmt->execute()->fetchArray(SQLITE3_ASSOC)['total'];
                        $totalPages = ceil($totalServices / $perPage);
                        
                        // Update the services query to include pagination
                        $stmt = $db->getConnection()->prepare('
                            SELECT * FROM services 
                            WHERE vendor_id = ? 
                            ORDER BY created_at DESC
                            LIMIT ? OFFSET ?
                        ');
                        $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
                        $stmt->bindValue(2, $perPage, SQLITE3_INTEGER);
                        $stmt->bindValue(3, $offset, SQLITE3_INTEGER);
                        $result = $stmt->execute();
                        
                        $hasServices = false;
                        while ($service = $result->fetchArray(SQLITE3_ASSOC)) {
                            $hasServices = true;
                            ?>
                            <div class="card service-card mb-3" data-id="<?php echo $service['id']; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title"><?php echo htmlspecialchars($service['title']); ?></h5>
                                            <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars($service['category']); ?></span>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($service['description']); ?></p>
                                            <div class="d-flex align-items-center text-muted">
                                                <i class="fas fa-clock me-2"></i>
                                                <span><?php echo $service['duration']; ?> minutes</span>
                                                <i class="fas fa-dollar-sign ms-3 me-2"></i>
                                                <span>$<?php echo number_format($service['price'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="service-action-buttons">
                                         
                                            <button class="btn btn-danger btn-sm" onclick="deleteService(<?php echo $service['id']; ?>)">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        
                        if (!$hasServices) {
                            echo '<div class="text-center text-muted p-4">No services added yet</div>';
                        }
                        ?>

                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Services pagination">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>" <?php echo $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Previous</a>
                                    </li>
                                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>" <?php echo $page >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Update the JavaScript to include refresh function -->
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Search functionality remains the same...
                    
                        // Updated form submission handling
                        const form = document.getElementById('serviceForm');
                        const submitButton = form.querySelector('button[type="submit"]');
                        const originalButtonHtml = submitButton.innerHTML;
                    
                        form.addEventListener('submit', async function(e) {
                            e.preventDefault();
                            
                            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
                            submitButton.disabled = true;
                            
                            try {
                                const formData = new FormData(this);
                                formData.append('add_service', '1'); // Add this to match PHP check
                                
                                const response = await fetch('add_service.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                
                                const result = await response.json();
                                
                                if (result.success) {
                                    toastr.success(result.message);
                                    form.reset();
                                    // TODO: Add code to refresh services list
                                } else {
                                    toastr.error(result.message || 'Failed to add service');
                                }
                            } catch (error) {
                                toastr.error('An unexpected error occurred: ' + error.message);
                            } finally {
                                submitButton.innerHTML = originalButtonHtml;
                                submitButton.disabled = false;
                            }
                        });
                    });
                    </script>
                    <script>
                    // Add search functionality
                    const searchInput = document.getElementById('searchServices');
                    const servicesList = document.getElementById('servicesList');
                    const serviceCards = servicesList.getElementsByClassName('service-card');
                    
                    // Update the search event listener
                    searchInput.addEventListener('input', function(e) {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        let visibleCount = 0;
                        
                        Array.from(serviceCards).forEach(card => {
                            const title = card.querySelector('.card-title').textContent.toLowerCase();
                            const description = card.querySelector('p.text-muted').textContent.toLowerCase();
                            const category = card.querySelector('.badge').textContent.toLowerCase();
                            
                            const matches = title.includes(searchTerm) || 
                                           description.includes(searchTerm) || 
                                           category.includes(searchTerm);
                            
                            card.style.display = matches ? '' : 'none';
                            if (matches) visibleCount++;
                        });
                    
                        // Show/hide pagination based on search results
                        const paginationNav = document.querySelector('nav[aria-label="Services pagination"]');
                        if (paginationNav) {
                            paginationNav.style.display = searchTerm ? 'none' : '';
                        }
                    
                        // Show/hide no results message
                        const noResults = servicesList.querySelector('.no-results');
                        if (visibleCount === 0) {
                            if (!noResults) {
                                const message = document.createElement('div');
                                message.className = 'no-results text-center text-muted p-4';
                                message.textContent = 'No services found matching your search';
                                servicesList.appendChild(message);
                            }
                        } else if (noResults) {
                            noResults.remove();
                        }
                    });
                    
                    
                    // Add these functions for edit and delete (to be implemented later)
                    function editService(id) {
                        // TODO: Implement edit functionality
                        console.log('Edit service:', id);
                    }
                    
                    function deleteService(id) {
                        if (confirm('Are you sure you want to delete this service?')) {
                            fetch('delete_service.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `id=${id}`
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    const serviceCard = document.querySelector(`.service-card[data-id="${id}"]`);
                                    if (serviceCard) {
                                        serviceCard.remove();
                                        // Check if there are any services left
                                        const remainingServices = document.getElementsByClassName('service-card');
                                        if (remainingServices.length === 0) {
                                            document.getElementById('servicesList').innerHTML = 
                                                '<div class="text-center text-muted p-4">No services added yet</div>';
                                        }
                                    }
                                } else {
                                    toastr.error(result.message || 'Failed to delete service');
                                }
                            })
                            .catch(error => {
                                toastr.error('An unexpected error occurred');
                                console.error('Error:', error);
                            });
                        }
                    }
                    </script>
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Replace all script tags with this single one before the closing body tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission handling
    const form = document.getElementById('serviceForm');
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton.innerHTML;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
        submitButton.disabled = true;
        
        try {
            const formData = new FormData(this);
            formData.append('add_service', '1');
            
            const response = await fetch('add_service.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                toastr.success(result.message);
                form.reset();
                location.reload(); // Refresh the page to show new service
            } else {
                toastr.error(result.message || 'Failed to add service');
            }
        } catch (error) {
            toastr.error('An unexpected error occurred: ' + error.message);
        } finally {
            submitButton.innerHTML = originalButtonHtml;
            submitButton.disabled = false;
        }
    });

    // Search functionality
    const searchInput = document.getElementById('searchServices');
    const servicesList = document.getElementById('servicesList');
    const serviceCards = servicesList.getElementsByClassName('service-card');

    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase().trim();
        
        Array.from(serviceCards).forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const description = card.querySelector('p.text-muted').textContent.toLowerCase();
            const category = card.querySelector('.badge').textContent.toLowerCase();
            
            const matches = title.includes(searchTerm) || 
                           description.includes(searchTerm) || 
                           category.includes(searchTerm);
            
            card.style.display = matches ? '' : 'none';
        });

        // Show/hide no results message
        const noResults = servicesList.querySelector('.no-results');
        const visibleCards = Array.from(serviceCards).filter(card => card.style.display !== 'none');
        
        if (visibleCards.length === 0) {
            if (!noResults) {
                const message = document.createElement('div');
                message.className = 'no-results text-center text-muted p-4';
                message.textContent = 'No services found matching your search';
                servicesList.appendChild(message);
            }
        } else if (noResults) {
            noResults.remove();
        }
    });
});

// Service management functions
function editService(id) {
    // TODO: Implement edit functionality
    console.log('Edit service:', id);
}

function deleteService(id) {
    if (confirm('Are you sure you want to delete this service?')) {
        // TODO: Implement delete functionality
        console.log('Delete service:', id);
    }
}
</script>

<script src="/assets/js/toast.js"></script>

<?php include '../includes/footer.php'; ?>