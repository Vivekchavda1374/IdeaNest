function toggleProjectType() {
    const projectType = document.getElementById('projectType').value;
    const softwareOptions = document.getElementById('softwareOptions');
    const hardwareOptions = document.getElementById('hardwareOptions');

    if (projectType === 'software') {
        softwareOptions.classList.remove('hidden');
        hardwareOptions.classList.add('hidden');
    } else if (projectType === 'hardware') {
        hardwareOptions.classList.remove('hidden');
        softwareOptions.classList.add('hidden');
    } else {
        softwareOptions.classList.add('hidden');
        hardwareOptions.classList.add('hidden');
    }
    
    // Clear reviewers when project type changes
    document.getElementById('reviewersSection').style.display = 'none';
}

// Load matching reviewers based on classification
function loadMatchingReviewers(classification) {
    if (!classification) {
        document.getElementById('reviewersSection').style.display = 'none';
        return;
    }
    
    fetch(`get_matching_reviewers.php?classification=${encodeURIComponent(classification)}`)
        .then(response => response.json())
        .then(data => {
            const reviewersList = document.getElementById('reviewersList');
            const reviewersSection = document.getElementById('reviewersSection');
            
            if (data.reviewers && data.reviewers.length > 0) {
                reviewersList.innerHTML = '';
                
                data.reviewers.forEach(reviewer => {
                    const reviewerCard = `
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">
                                        <i class="fas fa-user-tie me-2 text-primary"></i>
                                        ${escapeHtml(reviewer.name)}
                                    </h6>
                                    <p class="card-text mb-1">
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            ${escapeHtml(reviewer.email)}
                                        </small>
                                    </p>
                                    <p class="card-text mb-0">
                                        <small class="text-muted">
                                            <i class="fas fa-tags me-1"></i>
                                            <strong>Domains:</strong> ${escapeHtml(reviewer.domains)}
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                    reviewersList.innerHTML += reviewerCard;
                });
                
                reviewersSection.style.display = 'block';
            } else {
                reviewersSection.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading reviewers:', error);
            document.getElementById('reviewersSection').style.display = 'none';
        });
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleProjectType();
    
    // Add event listeners for classification changes
    const softwareClassification = document.querySelector('select[name="software_classification"]');
    const hardwareClassification = document.querySelector('select[name="hardware_classification"]');
    
    if (softwareClassification) {
        softwareClassification.addEventListener('change', function() {
            loadMatchingReviewers(this.value);
        });
    }
    
    if (hardwareClassification) {
        hardwareClassification.addEventListener('change', function() {
            loadMatchingReviewers(this.value);
        });
    }
});
