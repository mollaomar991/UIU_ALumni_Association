// UIU Alumni Connect - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-mdb-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new mdb.Tooltip(tooltip);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new mdb.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Like Post Function
function likePost(postId) {
    fetch(`${window.location.origin}/AlumniAccociation/api/like_post.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeBtn = document.querySelector(`[data-post-id="${postId}"]`);
            const likeCount = likeBtn.querySelector('.like-count');
            likeCount.textContent = data.likes;
            likeBtn.classList.toggle('active');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Delete Post Function
function deletePost(postId) {
    if (confirm('Are you sure you want to delete this post?')) {
        fetch(`${window.location.origin}/AlumniAccociation/api/delete_post.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete post');
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Image Preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Search Alumni
function searchAlumni(query) {
    if (query.length < 2) {
        return;
    }
    
    fetch(`${window.location.origin}/AlumniAccociation/api/search_alumni.php?q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data);
    })
    .catch(error => console.error('Error:', error));
}

// Load More Posts (Infinite Scroll)
let isLoading = false;
let currentPage = 1;

function loadMorePosts() {
    if (isLoading) return;
    
    isLoading = true;
    currentPage++;
    
    fetch(`${window.location.origin}/AlumniAccociation/api/get_posts.php?page=${currentPage}`)
    .then(response => response.json())
    .then(data => {
        if (data.posts && data.posts.length > 0) {
            appendPosts(data.posts);
        }
        isLoading = false;
    })
    .catch(error => {
        console.error('Error:', error);
        isLoading = false;
    });
}

// Infinite Scroll Implementation
window.addEventListener('scroll', () => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        loadMorePosts();
    }
});

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Format Time Ago
function timeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
            return interval + ' ' + unit + (interval === 1 ? '' : 's') + ' ago';
        }
    }
    
    return 'Just now';
}

// Debounce Function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Show Toast Notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3 fade show`;
    toast.style.zIndex = '9999';
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Toggle Password Visibility
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    const icon = iconElement.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Confirm Action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}
