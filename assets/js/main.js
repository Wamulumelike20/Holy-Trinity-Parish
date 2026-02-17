/**
 * Holy Trinity Parish - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // Mobile Navigation Toggle
    // ==========================================
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            this.classList.toggle('active');
        });

        // Close menu on link click
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
            });
        });

        // Close menu on outside click
        document.addEventListener('click', function(e) {
            if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                navMenu.classList.remove('open');
                navToggle.classList.remove('active');
            }
        });
    }

    // Mobile dropdown toggle
    document.querySelectorAll('.nav-dropdown > a').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                this.parentElement.classList.toggle('open');
            }
        });
    });

    // ==========================================
    // Sticky Navigation on Scroll
    // ==========================================
    const mainNav = document.getElementById('mainNav');
    if (mainNav) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                mainNav.classList.add('scrolled');
            } else {
                mainNav.classList.remove('scrolled');
            }
        });
    }

    // ==========================================
    // Back to Top Button
    // ==========================================
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 400) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ==========================================
    // Flash Message Auto-dismiss
    // ==========================================
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-20px)';
            setTimeout(() => msg.remove(), 500);
        }, 5000);
    });

    // ==========================================
    // Tab System
    // ==========================================
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabGroup = this.closest('.tabs');
            const target = this.dataset.tab;
            const parent = this.closest('.card') || this.closest('.section') || document;

            // Update active tab button
            tabGroup.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Update active tab content
            parent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            const targetEl = parent.querySelector(`#${target}`);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // ==========================================
    // Modal System
    // ==========================================
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => {
                m.classList.remove('active');
            });
            document.body.style.overflow = '';
        }
    });

    // ==========================================
    // Donation Amount Selection
    // ==========================================
    document.querySelectorAll('.amount-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.amount-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const amountInput = document.getElementById('donationAmount');
            if (amountInput) {
                amountInput.value = this.dataset.amount;
            }
        });
    });

    // ==========================================
    // Form Validation
    // ==========================================
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Clear previous errors
            this.querySelectorAll('.form-error').forEach(err => err.remove());
            this.querySelectorAll('.form-control.error').forEach(el => el.classList.remove('error'));

            // Required fields
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, 'This field is required');
                }
            });

            // Email validation
            this.querySelectorAll('[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, 'Please enter a valid email address');
                }
            });

            // Phone validation
            this.querySelectorAll('[data-validate-phone]').forEach(field => {
                if (field.value && !isValidPhone(field.value)) {
                    isValid = false;
                    showFieldError(field, 'Please enter a valid phone number');
                }
            });

            // Password match
            const password = this.querySelector('[name="password"]');
            const confirmPassword = this.querySelector('[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                showFieldError(confirmPassword, 'Passwords do not match');
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('.form-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });

    function showFieldError(field, message) {
        field.classList.add('error');
        field.style.borderColor = '#ef4444';
        const error = document.createElement('div');
        error.className = 'form-error';
        error.textContent = message;
        field.parentNode.appendChild(error);
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidPhone(phone) {
        return /^[\+]?[\d\s\-\(\)]{7,20}$/.test(phone);
    }

    // ==========================================
    // Sidebar Toggle (Dashboard)
    // ==========================================
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // ==========================================
    // Appointment Calendar
    // ==========================================
    window.AppointmentCalendar = {
        currentDate: new Date(),

        init: function(containerId) {
            this.container = document.getElementById(containerId);
            if (!this.container) return;
            this.render();
        },

        render: function() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();

            const monthNames = ['January','February','March','April','May','June',
                              'July','August','September','October','November','December'];

            let html = `
                <div class="calendar-nav d-flex justify-between align-center mb-2">
                    <button class="btn btn-sm btn-outline" onclick="AppointmentCalendar.prev()">
                        <i class="fas fa-chevron-left"></i> Prev
                    </button>
                    <h3 style="margin:0">${monthNames[month]} ${year}</h3>
                    <button class="btn btn-sm btn-outline" onclick="AppointmentCalendar.next()">
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="calendar-grid">
            `;

            const dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            dayNames.forEach(d => {
                html += `<div class="calendar-header-cell">${d}</div>`;
            });

            // Previous month days
            const prevMonthDays = new Date(year, month, 0).getDate();
            for (let i = firstDay - 1; i >= 0; i--) {
                html += `<div class="calendar-cell other-month"><span class="day-number">${prevMonthDays - i}</span></div>`;
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                const isPast = new Date(year, month, day) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const classes = ['calendar-cell'];
                if (isToday) classes.push('today');

                html += `<div class="${classes.join(' ')}" ${!isPast ? `onclick="AppointmentCalendar.selectDate(${year}, ${month}, ${day})" style="cursor:pointer"` : ''}>
                    <span class="day-number">${day}</span>
                </div>`;
            }

            // Next month days
            const totalCells = firstDay + daysInMonth;
            const remaining = 7 - (totalCells % 7);
            if (remaining < 7) {
                for (let i = 1; i <= remaining; i++) {
                    html += `<div class="calendar-cell other-month"><span class="day-number">${i}</span></div>`;
                }
            }

            html += '</div>';
            this.container.innerHTML = html;
        },

        prev: function() {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.render();
        },

        next: function() {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.render();
        },

        selectDate: function(year, month, day) {
            const dateInput = document.getElementById('appointmentDate');
            if (dateInput) {
                const m = String(month + 1).padStart(2, '0');
                const d = String(day).padStart(2, '0');
                dateInput.value = `${year}-${m}-${d}`;

                // Highlight selected
                this.container.querySelectorAll('.calendar-cell').forEach(c => c.style.background = '');
                event.currentTarget.style.background = 'rgba(212,168,67,0.2)';

                // Load available times
                if (typeof loadAvailableTimes === 'function') {
                    loadAvailableTimes(`${year}-${m}-${d}`);
                }
            }
        }
    };

    // ==========================================
    // Smooth Scroll for Anchor Links
    // ==========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ==========================================
    // Animate on Scroll
    // ==========================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.quick-action-card, .clergy-card, .event-card, .stat-card, .schedule-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // ==========================================
    // File Upload Preview
    // ==========================================
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.parentElement.querySelector('.file-preview');
            if (preview && this.files[0]) {
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                preview.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
            }
        });
    });

    // ==========================================
    // Print Function
    // ==========================================
    window.printContent = function(elementId) {
        const content = document.getElementById(elementId);
        if (!content) return;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html><head><title>Print - Holy Trinity Parish</title>
            <style>
                body { font-family: 'Inter', sans-serif; padding: 2rem; color: #333; }
                h1, h2, h3 { font-family: 'Cinzel', serif; color: #1a3a5c; }
                table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
                th, td { padding: 0.5rem; border: 1px solid #ddd; text-align: left; }
                th { background: #1a3a5c; color: white; }
                .badge { padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem; }
            </style></head><body>
            <h2 style="text-align:center;">Holy Trinity Parish</h2>
            ${content.innerHTML}
            </body></html>
        `);
        printWindow.document.close();
        printWindow.print();
    };

    // ==========================================
    // Confirm Delete
    // ==========================================
    window.confirmDelete = function(message, url) {
        if (confirm(message || 'Are you sure you want to delete this item?')) {
            window.location.href = url;
        }
    };

    // ==========================================
    // AJAX Helper
    // ==========================================
    window.ajaxRequest = function(url, method, data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch(e) {
                    callback(e, xhr.responseText);
                }
            }
        };
        if (data && typeof data === 'object') {
            const params = Object.keys(data).map(k => 
                encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
            ).join('&');
            xhr.send(params);
        } else {
            xhr.send(data);
        }
    };

});
