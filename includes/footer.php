    </main>

    <!-- Back to Top Button -->
    <div class="back-to-top-btn" id="backToTopBtn" onclick="scrollToTop()">
        <i class="fa-solid fa-chevron-up"></i>
    </div>

    <!-- Mega Footer Section -->
    <footer class="footer-custom mt-5 py-5" style="background-color: var(--blue-brand) !important; border-top: 4px solid var(--orange-brand); font-size: 0.85rem; color: #cbd5e1;">
        <div class="container">
            <div class="row g-4 mb-4">
                <!-- Brand Profile -->
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="logo-crest me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #103178 0%, #1c4b9c 100%); border-radius: 12px; border: 2px solid #F59E0B; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35); position: relative;">
                            <i class="fa-solid fa-shield-halved" style="font-size: 1.45rem; color: #F59E0B;"></i>
                            <i class="fa-solid fa-crown" style="position: absolute; font-size: 0.75rem; color: #ffffff; top: 13px;"></i>
                            <span style="position: absolute; top: -3px; right: -3px; width: 12px; height: 12px; background: linear-gradient(135deg, #ffd700 0%, #f59e0b 100%); border: 1.5px solid #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-diamond" style="font-size: 6px; color: #103178;"></i>
                            </span>
                        </div>
                        <div class="lh-1">
                            <div style="font-family: 'Playfair Display', serif; font-weight: 900; font-size: 1.9rem; background: linear-gradient(135deg, #ffd700 0%, #f39c12 50%, #ffd700 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing: 1.5px; display: flex; align-items: center; filter: drop-shadow(0px 1px 2px rgba(0,0,0,0.3));">
                                <?php echo htmlspecialchars(strtoupper(get_setting('system_name', 'JARVIS'))); ?>
                                <span style="color: var(--orange-brand); -webkit-text-fill-color: var(--orange-brand); font-weight: 300; font-size: 1.4rem; margin-left: 2px; margin-top: -6px;">✦</span>
                            </div>
                            <span style="display:block; font-size:0.52rem; letter-spacing:4px; color:var(--orange-brand); font-weight:700; text-transform:uppercase; margin-top:2px;"><?php echo htmlspecialchars(get_setting('brand_tagline', 'building aspirations')); ?></span>
                        </div>
                    </div>
                    <p class="text-white-50 small" style="font-size: 0.78rem;">
                        Building premium residential landmarks, industrial parks, and high-end workspaces across India's largest cities since 2004.
                    </p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="https://www.facebook.com/casagrandbuilders/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://twitter.com/casagrandtweets" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-brands fa-twitter"></i></a>
                        <a href="https://www.linkedin.com/company/casagrand-builder-pvt-ltd/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="https://www.instagram.com/casagrandbuilders/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-brands fa-instagram"></i></a>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-uppercase mb-3 text-white">Quick Links</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="<?php echo $base_path; ?>index.php" class="text-decoration-none text-white-50 hover-gold">Residential Listings</a></li>
                        <li><a href="<?php echo $base_path; ?>commercial.php" class="text-decoration-none text-white-50 hover-gold">Commercial Suites</a></li>
                    </ul>
                </div>

                <!-- Resident Portal -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-uppercase mb-3 text-white">Resident Portal</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="<?php echo $base_path; ?>resident/login.php" class="text-decoration-none text-white-50 hover-gold"><i class="fa-solid fa-right-to-bracket me-1 text-warning"></i> Resident Login</a></li>
                    </ul>
                </div>

                <!-- Newsletter Subscription -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold text-uppercase mb-3 text-white">Newsletter</h6>
                    <p class="text-white-50 small" style="font-size: 0.75rem;">Subscribe to our newsletter for launch alerts.</p>
                    <form onsubmit="handleNewsletter(event)">
                        <div class="input-group">
                            <input type="email" id="newsEmail" class="form-control form-control-custom form-control-sm" placeholder="Enter email" required style="font-size:0.72rem;">
                            <button type="submit" class="btn btn-primary-custom btn-sm text-white" style="background-color:var(--orange-brand); border-color:var(--orange-brand);">Go</button>
                        </div>
                    </form>
                    <div id="newsletterSuccess" class="alert alert-success p-2 mt-2" style="display:none; font-size:0.7rem;"><i class="fa-solid fa-circle-check me-1"></i> Subscribed successfully!</div>
                </div>

                <!-- Map & Location Details -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-uppercase mb-3 text-white">Headquarters</h6>
                    <div class="rounded overflow-hidden shadow-sm mb-2" style="height: 100px;">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3886.851694767228!2d80.20786967590807!3d13.045091713289069!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a5266e7b165213b%3A0xe54e604fca350916!2sCasagrand%20Builder%20Private%20Limited!5e0!3m2!1sen!2sin!4v1710000000000!5m2!1sen!2sin" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    <p class="text-white-50 small mb-0" style="font-size: 0.75rem;"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars(get_setting('contact_address', 'NPL Devi, 111 OMR Rd, Chennai')); ?></p>
                </div>
            </div>

            <hr class="border-secondary border-opacity-20 my-4">

            <div class="row align-items-center text-white-50">
                <div class="col-md-6 text-md-start mb-3 mb-md-0">
                    <p class="mb-0 small">&copy; <?php echo date('Y'); ?> Jarvis Real Estate Builder. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="me-3 text-decoration-none text-white-50 hover-gold small">Terms of Service</a>
                    <a href="#" class="me-3 text-decoration-none text-white-50 hover-gold small">Privacy Policy</a>
                    <a href="#" class="text-decoration-none text-white-50 hover-gold small">Customer Support</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- VIP Site Visit Scheduling Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, var(--blue-brand) 0%, #0d2354 100%);">
                    <h5 class="modal-title fw-bold" id="inquiryModalLabel"><i class="fa-solid fa-calendar-days text-warning me-2"></i>Book a VIP Site Visit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <p class="text-muted small mb-4">Please submit your details below to schedule an accompanied property walk-through with our sales representatives.</p>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="schedule_visit">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                                <input type="text" name="visit_name" class="form-control form-control-custom border-start-0" placeholder="e.g. John Doe" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                <input type="tel" name="visit_phone" class="form-control form-control-custom border-start-0" placeholder="e.g. +91 98765 43210" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                                <input type="email" name="visit_email" class="form-control form-control-custom border-start-0" placeholder="e.g. john@example.com" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Preferred Date</label>
                                <input type="date" name="visit_date" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">Preferred Time</label>
                                <input type="time" name="visit_time" class="form-control form-control-custom" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2.5" style="border: none; background-color: var(--orange-brand); border-radius: 4px;">
                            <i class="fa-solid fa-paper-plane me-1"></i> Schedule Visit
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Mega Footer Interactive Features Script -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const heroCarousel = document.getElementById('heroCarousel');
        if (heroCarousel) {
            new bootstrap.Carousel(heroCarousel, {
                interval: 2000,
                ride: 'carousel',
                wrap: true
            });
        }
    });

    // Back to Top widget routines
    window.onscroll = function() {
        const btn = document.getElementById("backToTopBtn");
        if (btn) {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                btn.style.display = "flex";
            } else {
                btn.style.display = "none";
            }
        }
    };

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }

    // Newsletter subscription simulations
    function handleNewsletter(e) {
        e.preventDefault();
        const input = document.getElementById("newsEmail");
        const success = document.getElementById("newsletterSuccess");
        if (input && success) {
            success.style.display = "block";
            input.value = "";
            setTimeout(() => {
                success.style.display = "none";
            }, 4000);
        }
    }
    // Voice search simulation & Web Speech API routines
    let recognition;
    let voiceSearchTimeout;

    function startVoiceRecognition() {
        const status = document.getElementById('voiceStatus');
        const transcriptDiv = document.getElementById('voiceTranscript');
        status.innerHTML = 'Listening...';
        status.classList.remove('text-danger', 'text-success');
        transcriptDiv.innerHTML = '"..."';
        transcriptDiv.classList.remove('text-success');

        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            status.innerHTML = 'Speech Recognition Not Supported';
            status.classList.add('text-danger');
            transcriptDiv.innerHTML = 'Your browser does not support voice search. Please try Google Chrome.';
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-IN';

        recognition.onerror = function(event) {
            console.error('Voice recognition error:', event.error);
            status.innerHTML = 'Error occurred';
            status.classList.add('text-danger');
            transcriptDiv.innerHTML = 'Could not understand your voice. Please try again.';
        };

        recognition.onresult = function(event) {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            const activeTranscript = finalTranscript || interimTranscript;
            transcriptDiv.innerHTML = `"${activeTranscript}"`;

            if (finalTranscript) {
                status.innerHTML = 'Speech Recognized!';
                status.classList.add('text-success');
                transcriptDiv.classList.add('text-success');
                recognition.stop();
                
                clearTimeout(voiceSearchTimeout);
                voiceSearchTimeout = setTimeout(() => {
                    const query = encodeURIComponent(finalTranscript.trim().replace(/[.]/g, ''));
                    window.location.href = `<?php echo $base_path; ?>index.php?search_name=${query}`;
                }, 1200);
            }
        };

        recognition.start();
    }

    function stopVoiceRecognition() {
        if (recognition) {
            recognition.abort();
        }
        clearTimeout(voiceSearchTimeout);
    }
    </script>

    <!-- Search Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, var(--blue-brand) 0%, #0d2354 100%);">
                    <h5 class="modal-title fw-bold" id="searchModalLabel"><i class="fa-solid fa-magnifying-glass text-warning me-2"></i>Search Apartment Listings</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <form action="<?php echo $base_path; ?>index.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Enter Apartment or Block Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-building text-muted"></i></span>
                                <input type="text" name="search_name" class="form-control form-control-custom border-start-0" placeholder="e.g. Casagrand, Woodside, Elan, Zenith..." required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning text-dark fw-bold w-100 py-2.5" style="border: none; background-color: var(--orange-brand); border-radius: 4px;">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Search Properties
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Voice Search Modal -->
    <div class="modal fade" id="voiceSearchModal" tabindex="-1" aria-labelledby="voiceSearchModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg text-center" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header text-white border-0 py-3" style="background: linear-gradient(135deg, var(--blue-brand) 0%, #0d2354 100%);">
                    <h5 class="modal-title fw-bold w-100 text-center" id="voiceSearchModalLabel"><i class="fa-solid fa-microphone text-warning me-2"></i>Jarvis Voice Assistant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopVoiceRecognition()"></button>
                </div>
                <div class="modal-body p-5 bg-light">
                    <!-- Pulsating Microphone Circle -->
                    <div class="voice-pulse-container mb-4">
                        <div class="voice-pulse-ring ring-1"></div>
                        <div class="voice-pulse-ring ring-2"></div>
                        <div class="voice-mic-icon d-flex align-items-center justify-content-center bg-warning text-dark mx-auto" style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="fa-solid fa-microphone fa-2x animate-bounce-slow"></i>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mb-2 text-dark" id="voiceStatus">Listening...</h5>
                    <p class="text-muted small mb-4">Try saying: "Woodside", "Elan", "Zenith", or "Chennai"</p>
                    
                    <!-- Speech Transcript Output -->
                    <div class="p-3 border rounded bg-white shadow-sm mb-4 min-height-80 d-flex align-items-center justify-content-center">
                        <p class="mb-0 fw-semibold text-primary fs-5" id="voiceTranscript">"..."</p>
                    </div>
                    
                    <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" onclick="stopVoiceRecognition()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
