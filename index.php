<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>True Beauty Concept — Dermatology Clinic in Tuaran</title>
    <!-- Fraunces = a warm, slightly wonky serif for personality. Work Sans = a clean, human body font. -->
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500;1,9..144,600&family=Work+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ---------- Design tokens ----------
           Warm, skin-toned palette instead of the cold navy/teal of the
           original — the idea is "healthy glow", not "clinical night sky". */
        :root {
            --sand: #FBF1E7;        /* page background */
            --sand-deep: #F3DECF;   /* card / section backgrounds */
            --clay: #B5583E;        /* primary accent (buttons, links) */
            --clay-deep: #8A4030;   /* hover states, dark accent panels */
            --ink: #2E211D;         /* main text */
            --ink-soft: #6B5850;    /* secondary text */
            --sage: #6F8F72;        /* botanical secondary accent */
            --gold: #E3A857;        /* glow highlight */
            --white: #FFFDFB;
            --radius: 18px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Work Sans', sans-serif;
            color: var(--ink);
            background: var(--sand);
            line-height: 1.65;
            overflow-x: hidden;
        }

        h1, h2, h3 {
            font-family: 'Fraunces', serif;
            font-weight: 600;
            line-height: 1.15;
        }

        a {
            color: inherit;
        }

        /* Visible focus ring for keyboard users — accessibility is part of
           a design actually caring about people, not just looking nice. */
        a:focus-visible,
        button:focus-visible {
            outline: 2px solid var(--clay);
            outline-offset: 3px;
        }

        img {
            max-width: 100%;
            display: block;
        }

        /* ================= Navbar ================= */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: transparent;
            z-index: 1000;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(251, 241, 231, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(46, 33, 29, 0.06);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Fraunces', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--ink);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .logo i {
            color: var(--clay);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2.2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--ink);
            font-weight: 500;
            font-size: 0.98rem;
            position: relative;
            padding-bottom: 4px;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--clay);
            transition: width 0.25s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .profile-dropdown {
            position: relative;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--clay);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.05rem;
        }

        .dropdown-menu {
            position: absolute;
            top: 55px;
            right: 0;
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 10px 35px rgba(46, 33, 29, 0.15);
            min-width: 210px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.25s ease;
        }

        /* :focus-within added so keyboard users can reach the menu too,
           not just people hovering with a mouse. */
        .profile-dropdown:hover .dropdown-menu,
        .profile-dropdown:focus-within .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 0.9rem 1.3rem;
            color: var(--ink);
            text-decoration: none;
            font-size: 0.92rem;
            transition: background 0.2s ease;
        }

        .dropdown-menu a:first-child { border-radius: 14px 14px 0 0; }
        .dropdown-menu a:last-child { border-radius: 0 0 14px 14px; }

        .dropdown-menu a:hover {
            background: var(--sand-deep);
        }

        .dropdown-menu a i {
            margin-right: 10px;
            width: 18px;
            color: var(--clay);
        }

        .menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            background: none;
            border: none;
        }

        .menu-toggle span {
            width: 24px;
            height: 2px;
            background: var(--ink);
            border-radius: 2px;
        }

        /* ================= Hero ================= */
        .hero {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* The "signature" element: two soft, blurred glows that breathe
           slowly, like light on skin — a quiet stand-in for the original's
           busy twinkling starfield. One deliberate motion instead of many. */
        .glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            pointer-events: none;
            animation: breathe 7s ease-in-out infinite;
        }

        .glow-gold {
            width: 480px;
            height: 480px;
            background: var(--gold);
            opacity: 0.35;
            top: -120px;
            right: -100px;
        }

        .glow-clay {
            width: 380px;
            height: 380px;
            background: var(--clay);
            opacity: 0.2;
            bottom: -140px;
            left: -80px;
            animation-delay: 2.5s;
        }

        @keyframes breathe {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.15); opacity: 0.45; }
        }

        @media (prefers-reduced-motion: reduce) {
            .glow { animation: none; }
        }

        .hero-content {
            max-width: 780px;
            padding: 4rem 2rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--clay-deep);
            background: rgba(181, 88, 62, 0.1);
            padding: 0.4rem 1rem;
            border-radius: 50px;
            margin-bottom: 1.5rem;
        }

        .hero h1 {
            font-size: 3.2rem;
            color: var(--ink);
            margin-bottom: 1.4rem;
        }

        .hero h1 .highlight {
            position: relative;
            font-style: italic;
            color: var(--clay-deep);
        }

        /* A hand-drawn-looking underline (not a perfect straight rule) —
           a small imperfection that reads as human rather than templated. */
        .hero h1 .highlight svg {
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 100%;
            height: 12px;
        }

        .hero p {
            font-size: 1.15rem;
            color: var(--ink-soft);
            margin-bottom: 2.3rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1.2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.95rem 2.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.98rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--clay);
            color: var(--white);
            box-shadow: 0 10px 25px rgba(181, 88, 62, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(181, 88, 62, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--ink);
            border: 1.5px solid var(--ink);
        }

        .btn-secondary:hover {
            background: var(--ink);
            color: var(--white);
        }

        .btn-light {
            background: var(--white);
            color: var(--clay-deep);
        }

        .btn-light:hover {
            transform: translateY(-3px);
        }

        /* ================= Approach section ================= */
        .approach {
            padding: 6rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-heading {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 3.5rem;
        }

        .section-heading h2 {
            font-size: 2.2rem;
            margin-bottom: 0.8rem;
        }

        .section-heading p {
            color: var(--ink-soft);
        }

        .approach-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .approach-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 2.2rem 1.8rem;
            border: 1px solid rgba(46, 33, 29, 0.06);
        }

        .approach-card i {
            font-size: 1.5rem;
            color: var(--clay);
            background: var(--sand-deep);
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
        }

        .approach-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.6rem;
        }

        .approach-card p {
            color: var(--ink-soft);
            font-size: 0.97rem;
        }

        /* ================= Philosophy ================= */
        .philosophy {
            background: var(--sand-deep);
            padding: 5.5rem 2rem;
        }

        .philosophy-inner {
            max-width: 720px;
            margin: 0 auto;
            text-align: center;
        }

        .philosophy blockquote {
            font-family: 'Fraunces', serif;
            font-style: italic;
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 1.5;
            color: var(--ink);
            margin-bottom: 1.5rem;
        }

        .philosophy cite {
            font-style: normal;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--clay-deep);
            letter-spacing: 0.02em;
        }

        /* ================= CTA banner ================= */
        .cta-banner {
            background: var(--clay-deep);
            color: var(--white);
            padding: 4.5rem 2rem;
            text-align: center;
        }

        .cta-banner h2 {
            font-size: 2rem;
            margin-bottom: 0.6rem;
        }

        .cta-banner p {
            color: rgba(255, 253, 251, 0.85);
            margin-bottom: 2rem;
        }

        /* ================= Footer ================= */
        .footer {
            background: var(--ink);
            color: rgba(255, 253, 251, 0.85);
            padding: 4rem 2rem 2rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.4rem;
            color: var(--gold);
        }

        .footer-section p,
        .footer-section a {
            color: rgba(255, 253, 251, 0.75);
            text-decoration: none;
            display: block;
            margin-bottom: 0.8rem;
            transition: color 0.2s ease;
        }

        .footer-section a:hover {
            color: var(--gold);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .contact-item i {
            color: var(--gold);
            font-size: 1.1rem;
            margin-top: 3px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.3rem;
        }

        .social-links a {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
        }

        .social-links a:hover {
            background: var(--gold);
            color: var(--ink);
        }

        .map-container {
            width: 100%;
            height: 260px;
            border-radius: var(--radius);
            overflow: hidden;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 253, 251, 0.5);
            font-size: 0.9rem;
        }

        /* ================= Responsive ================= */
        @media (max-width: 900px) {
            .approach-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .menu-toggle { display: flex; }

            .nav-links {
                position: absolute;
                top: 74px;
                left: 0;
                width: 100%;
                background: var(--sand);
                flex-direction: column;
                align-items: flex-start;
                padding: 1.5rem 2rem;
                box-shadow: 0 10px 30px rgba(46, 33, 29, 0.1);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .nav-links.active {
                transform: translateX(0);
            }

            .hero h1 { font-size: 2.3rem; }
            .hero p { font-size: 1.05rem; }
            .cta-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .footer-content { grid-template-columns: 1fr; gap: 2rem; }
        }
    </style>
</head>
<body>

    <!-- ================= Navbar ================= -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-leaf"></i>
                <span>True Beauty Concept</span>
            </a>

            <button class="menu-toggle" onclick="toggleMenu()" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>

            <ul class="nav-links" id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="services_public.php">Services</a></li>
                <li><a href="feedback.php">Feedback</a></li>
                <li><a href="#contact">Contact</a></li>

                <li class="profile-dropdown">
                    <div class="profile-icon" tabindex="0">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="index_patient.php"><i class="fas fa-user-circle"></i>Patient Login</a>
                        <a href="registration.php"><i class="fas fa-user-plus"></i>Patient Registration</a>
                        <a href="index_staff.php"><i class="fas fa-user-md"></i>Staff Login</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- ================= Hero ================= -->
    <section class="hero">
        <div class="glow glow-gold"></div>
        <div class="glow glow-clay"></div>

        <div class="hero-content">
            <span class="eyebrow">Dermatology Clinic · Tuaran, Sabah</span>
            <h1>
                Skin care that starts with
                <span class="highlight">
                    really seeing
                    <svg viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M2 8 Q 50 2, 100 7 T 198 5" stroke="#E3A857" stroke-width="4" fill="none" stroke-linecap="round"/>
                    </svg>
                </span>
                you.
            </h1>
            <p>True Beauty Concept is a dermatology clinic built around one idea: treatments should match your skin and your story, not get squeezed into a fifteen-minute slot.</p>

            <div class="cta-buttons">
                <a href="registration.php" class="btn btn-primary">
                    <span>Book a Consultation</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="services_public.php" class="btn btn-secondary">
                    <span>See Our Services</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ================= Approach ================= -->
    <section class="approach">
        <div class="section-heading">
            <h2>How we work with you</h2>
            <p>No two skins are the same, so no two visits should feel identical either.</p>
        </div>

        <div class="approach-grid">
            <div class="approach-card">
                <i class="fas fa-comments"></i>
                <h3>We ask before we treat</h3>
                <p>Every visit opens with a real conversation about your skin history and routine, before any treatment is suggested.</p>
            </div>
            <div class="approach-card">
                <i class="fas fa-hourglass-half"></i>
                <h3>We don't rush the room</h3>
                <p>Consultations are paced around your questions, not a fixed slot on the clock.</p>
            </div>
            <div class="approach-card">
                <i class="fas fa-calendar-check"></i>
                <h3>We check in afterwards</h3>
                <p>Follow-up matters to us. We want to know how your skin responds, not just how the appointment went.</p>
            </div>
        </div>
    </section>

    <!-- ================= Philosophy ================= -->
    <section class="philosophy">
        <div class="philosophy-inner">
            <blockquote>"Skin says a lot before a patient does — stress, sleep, the weather it's lived through. Treating it well starts with listening to all of that, not just what shows on the surface."</blockquote>
            <cite>— The team at True Beauty Concept</cite>
        </div>
    </section>

    <!-- ================= CTA banner ================= -->
    <section class="cta-banner">
        <h2>Ready to see your skin differently?</h2>
        <p>Book a consultation, or call us at +60 13-838 1233.</p>
        <a href="registration.php" class="btn btn-light">Book a Consultation</a>
    </section>

    <!-- ================= Footer ================= -->
    <footer class="footer" id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>We're a dermatology clinic in Tuaran focused on unhurried, personalised skin care — not a one-size-fits-all menu of treatments.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/sheyreen.ling" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/truebeautyconcept_kk/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@truebeautyconcept" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <div class="footer-section">
                <h3>Contact Us</h3>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>Menggatal Plaza, Lot 5 Block A<br>Taman Permai Miles 8, Jalan Tuaran<br>88450, Tuaran</p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <p>+60 13-838 1233</p>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <p>ariefakbar0101@gmail.com</p>
                </div>
            </div>

            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="services_public.php">Our Services</a>
                <a href="feedback.php">Feedback</a>
                <a href="index_patient.php">Patient Portal</a>
                <a href="index_staff.php">Staff Portal</a>
            </div>

            <div class="footer-section">
                <h3>Our Location</h3>
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.8091904628172!2d116.14592247498878!3d6.020912993964486!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x323b6c63575a2a17%3A0xb8ba6577e556d272!2sTrue%20Beauty%20Concept%20Official!5e0!3m2!1sen!2sid!4v1762695500090!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <span id="year"></span> True Beauty Concept. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Add a background to the navbar once the page is scrolled a bit,
        // so the logo stays readable over the hero.
        window.addEventListener('scroll', function () {
            const navbar = document.getElementById('navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Open/close the mobile nav menu.
        function toggleMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }

        // Close the mobile menu if someone taps outside the navbar.
        document.addEventListener('click', function (event) {
            const navLinks = document.getElementById('navLinks');
            const navbar = document.querySelector('.navbar');
            if (!navbar.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        });

        // Fill in the current year automatically, so the footer never
        // goes stale.
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>