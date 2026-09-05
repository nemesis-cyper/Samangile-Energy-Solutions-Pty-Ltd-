/**
 * Samangile Energy Solutions - Frontend JavaScript
 * Handles chat functionality, form submissions, and interactions
 */

// ===========================
// Chat Functionality
// ===========================

const chatInput = document.getElementById('chat-input');
const chatBtn = document.getElementById('chat-btn');
const chatContainer = document.getElementById('chat-container');

if (chatBtn) {
    chatBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

/**
 * Send a message to the chat backend
 */
function sendMessage() {
    const message = chatInput.value.trim();
    
    if (!message) {
        alert('Please enter a message');
        return;
    }

    // Display user message
    addMessageToChat(message, 'user');
    chatInput.value = '';

    // Send to Netlify serverless function
    fetch('/.netlify/functions/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            message: message,
            sessionId: getSessionId()
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.reply) {
            addMessageToChat(data.reply, 'bot');
        } else {
            addMessageToChat('Sorry, I could not process your message. Please try again.', 'bot');
            console.error('Chat error:', data.message);
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        addMessageToChat('Connection error. Please try again later.', 'bot');
    });
}

/**
 * Add a message to the chat display
 */
function addMessageToChat(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender}-message`;
    messageDiv.innerHTML = `<p>${escapeHtml(text)}</p>`;
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

/**
 * Get or create a session ID
 */
function getSessionId() {
    let sessionId = localStorage.getItem('samangile_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('samangile_session_id', sessionId);
    }
    return sessionId;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// ===========================
// Quote Request Form
// ===========================

const quoteForm = document.getElementById('quote-form');
const quoteResponse = document.getElementById('quote-response');

if (quoteForm) {
    quoteForm.addEventListener('submit', submitQuoteRequest);
}

/**
 * Submit a quote request form
 */
function submitQuoteRequest(e) {
    e.preventDefault();

    const formData = new FormData(quoteForm);
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        service: formData.get('service'),
        amount: parseFloat(formData.get('amount')) || 0,
        message: formData.get('message')
    };

    // Show loading state
    const submitBtn = quoteForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Submitting...';
    submitBtn.disabled = true;

    fetch('Quote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showQuoteResponse('success', data.message || 'Quote request submitted successfully! Our team will contact you shortly.');
            quoteForm.reset();
        } else {
            showQuoteResponse('error', data.message || 'Failed to submit quote request. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error submitting quote:', error);
        showQuoteResponse('error', 'Connection error. Please try again later.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Display quote response message
 */
function showQuoteResponse(type, message) {
    quoteResponse.textContent = message;
    quoteResponse.className = type;
    quoteResponse.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        quoteResponse.style.display = 'none';
    }, 5000);
}

// ===========================
// Smooth Scroll Links
// ===========================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && document.querySelector(href)) {
            e.preventDefault();
            const target = document.querySelector(href);
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ===========================
// Navbar Active Link
// ===========================

window.addEventListener('scroll', () => {
    let current = '';
    
    document.querySelectorAll('section').forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (scrollY >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });

    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href').slice(1) === current) {
            link.classList.add('active');
        }
    });
});

// ===========================
// Initialize on Page Load
// ===========================

document.addEventListener('DOMContentLoaded', () => {
    console.log('Samangile Energy Solutions - Website Loaded');
    initializeSessionId();
});

/**
 * Initialize session tracking
 */
function initializeSessionId() {
    const sessionId = getSessionId();
    console.log('Session ID:', sessionId);
}

// ===========================
// Utility Functions
// ===========================

/**
 * Format currency for South African Rand
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'ZAR'
    }).format(amount);
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Debounce function for performance
 */
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

// ===========================
// Page Analytics (Optional)
// ===========================

/**
 * Track page view
 */
function trackPageView() {
    console.log('Page viewed at:', new Date().toLocaleString());
}

// Call on load
trackPageView();