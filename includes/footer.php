    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container footer-grid">
                <div class="footer-col">
                    <div class="footer-brand">
                        <i class="fas fa-cross"></i>
                        <h3>Holy Trinity Parish</h3>
                    </div>
                    <p>A vibrant Catholic community dedicated to worship, fellowship, and service. Join us as we grow together in faith, hope, and love.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/holy-trinity/pages/about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="/holy-trinity/pages/events.php"><i class="fas fa-chevron-right"></i> Events</a></li>
                        <li><a href="/holy-trinity/pages/sermons.php"><i class="fas fa-chevron-right"></i> Sermons</a></li>
                        <li><a href="/holy-trinity/appointments/book.php"><i class="fas fa-chevron-right"></i> Book Appointment</a></li>
                        <li><a href="/holy-trinity/donations/donate.php"><i class="fas fa-chevron-right"></i> Donate</a></li>
                        <li><a href="/holy-trinity/pages/contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Mass Schedule</h4>
                    <ul class="mass-times">
                        <li><strong>Sunday:</strong> 7:00 AM, 9:00 AM, 11:00 AM, 5:00 PM</li>
                        <li><strong>Weekdays:</strong> 7:00 AM (Mon-Sat)</li>
                        <li><strong>Saturday Vigil:</strong> 5:00 PM</li>
                        <li><strong>Confessions:</strong> Sat 9:00 AM</li>
                        <li><strong>Adoration:</strong> Thu 6:00 PM</li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact Us</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> Holy Trinity Parish, Kampala, Uganda</li>
                        <li><i class="fas fa-phone"></i> +256-XXX-XXXXXX</li>
                        <li><i class="fas fa-envelope"></i> info@holytrinityparish.org</li>
                        <li><i class="fas fa-clock"></i> Office: Mon-Fri 8AM - 5PM</li>
                    </ul>
                    <h4 style="margin-top:1rem;">Newsletter</h4>
                    <form class="newsletter-form" action="/holy-trinity/api/newsletter.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?= date('Y') ?> Holy Trinity Parish. All rights reserved.</p>
                <p>Built with <i class="fas fa-heart" style="color:#e74c3c;"></i> for the Glory of God</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="/holy-trinity/assets/js/main.js"></script>
    <?php if (isset($extraJS)): ?>
        <script src="<?= $extraJS ?>"></script>
    <?php endif; ?>
</body>
</html>
