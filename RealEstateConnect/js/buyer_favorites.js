/**
 * Real Estate Listing System
 * Buyer Favorites JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Buyer favorites JS loaded');
    
    // Handle remove favorite buttons
    const removeForms = document.querySelectorAll('.remove-favorite-form');
    
    if (removeForms.length > 0) {
        console.log('Found', removeForms.length, 'remove forms');
        
        removeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (confirm('Are you sure you want to remove this property from your favorites?')) {
                    const propertyId = this.querySelector('input[name="property_id"]').value;
                    const propertyCard = this.closest('.property-card').parentNode;
                    
                    console.log('Removing property ID:', propertyId);
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('property_id', propertyId);
                    formData.append('action', 'remove');
                    
                    // Log the data being sent
                    console.log('Sending to API:', { property_id: propertyId, action: 'remove' });
                    
                    // Send request to server using our direct implementation
                    fetch('../api/favorites_fix.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Favorite response:', data);
                        
                        if (data.success) {
                            // Fade out and remove the property card
                            propertyCard.style.opacity = '0';
                            propertyCard.style.transition = 'opacity 0.5s';
                            
                            setTimeout(() => {
                                propertyCard.remove();
                                
                                // Check if there are no more properties
                                const remainingCards = document.querySelectorAll('.property-card');
                                if (remainingCards.length === 0) {
                                    const cardBody = document.querySelector('.card-body');
                                    cardBody.innerHTML = `
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> You haven't added any properties to your favorites yet. Browse the <a href="../search.php" class="alert-link">property listings</a> to find properties you like.
                                        </div>
                                    `;
                                }
                            }, 500);
                        } else {
                            alert(data.message || 'Failed to remove property from favorites. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            });
        });
    } else {
        console.log('No remove forms found on page');
    }
});