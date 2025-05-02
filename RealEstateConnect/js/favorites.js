/**
 * Real Estate Listing System
 * Favorites JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Handle favorite button clicks
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    if (favoriteButtons.length > 0) {
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Get property ID from data attribute
                const propertyId = this.getAttribute('data-property-id');
                if (!propertyId) {
                    console.error('No property ID found on favorite button');
                    return;
                }
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Create form data
                const formData = new FormData();
                formData.append('property_id', propertyId);
                formData.append('action', 'toggle');
                
                // Send request to server
                fetch('api/favorite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Favorite toggled:', data);
                    
                    // Reset button state
                    this.disabled = false;
                    
                    if (data.success) {
                        if (data.action === 'added') {
                            // Update UI for favorited state
                            this.classList.remove('btn-outline-danger');
                            this.classList.add('btn-danger', 'favorited');
                            this.innerHTML = '<i class="fas fa-heart"></i> Remove from Favorites';
                            alert('Property added to favorites!');
                        } else if (data.action === 'removed') {
                            // Update UI for unfavorited state
                            this.classList.remove('btn-danger', 'favorited');
                            this.classList.add('btn-outline-danger');
                            this.innerHTML = '<i class="far fa-heart"></i> Add to Favorites';
                            alert('Property removed from favorites.');
                        }
                    } else {
                        // Error handling
                        this.innerHTML = originalText;
                        
                        if (data.redirect) {
                            if (confirm('You need to log in first. Go to login page?')) {
                                window.location.href = data.redirect;
                            }
                        } else {
                            alert(data.message || 'An error occurred while toggling favorite status');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error toggling favorite:', error);
                    this.disabled = false;
                    this.innerHTML = originalText;
                    alert('An error occurred. Please try again later.');
                });
            });
        });
    }
});