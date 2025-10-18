<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethical eLearning Platform | Whistleblower Training & Corporate Compliance Courses</title>
    <meta name="description" content="Comprehensive eLearning platform for ethics training, whistleblower protection programs, corporate compliance education, anti-corruption courses, and workplace integrity development.">
    <meta name="keywords" content="elearning, ethics training, whistleblower protection, corporate compliance, regulatory compliance, anti-corruption, code of conduct, workplace ethics, fraud prevention, risk management, corporate governance, business ethics, compliance training, data protection, GDPR, SOX compliance, FCPA, UK Bribery Act, speak-up culture, anonymous reporting, retaliation prevention, ethical decision making, integrity training, ESG compliance, sustainability ethics">
    <meta name="author" content="Ethical eLearning Platform">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#1e40af">
    <meta property="og:title" content="Ethical eLearning Platform | Compliance Training & Whistleblower Protection">
    <meta property="og:description" content="Comprehensive ethics training and whistleblower protection programs for organizations committed to integrity and compliance.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ethical-elearning.example.com">
    <meta property="og:image" content="https://ethical-elearning.example.com/images/og-image.jpg">
    <link rel="canonical" href="https://ethical-elearning.example.com">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#1e3a8a',
                        accent: '#3b82f6',
                        danger: '#dc2626',
                        success: '#16a34a'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'pulse-slow': 'pulse 3s infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
        }
        
        .dropdown:hover .dropdown-menu { 
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }
        
        .hero-bg {
            background-image: linear-gradient(rgba(0,0,0,0.7),rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .compliance-bg {
            background-image: linear-gradient(rgba(30,64,175,0.9),rgba(30,64,175,0.9)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0; 
            left: 0; 
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 50;
            display: none;
            transition: all 0.3s ease;
        }
        
        .bottom-nav.active { display: flex; }
        
        .nav-item {
            flex: 1;
            text-align: center;
            padding: 12px 0;
            color: #6b7280;
            transition: all 0.2s ease;
        }
        
        .nav-item.active { 
            color: #1e40af;
            transform: translateY(-2px);
        }
        
        .nav-item svg {
            display: block;
            margin: 0 auto 4px;
            width: 24px;
            height: 24px;
            transition: all 0.2s ease;
        }
        
        .nav-item.active svg {
            transform: scale(1.1);
        }
        
        .nav-item span { 
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .content-wrap { 
            padding-bottom: 70px;
            opacity: 0;
            animation: fadeIn 0.5s ease-in-out forwards;
        }
        
        /* Chatbot styles */
        .chatbot-container {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .chatbot-container.open {
            transform: translateY(0);
            opacity: 1;
        }
        
        .chatbot-header {
            background: #1e40af;
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chatbot-messages {
            height: 350px;
            overflow-y: auto;
            padding: 16px;
            background: #f9fafb;
        }
        
        .message {
            margin-bottom: 12px;
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 12px;
            animation: slideUp 0.2s ease-out;
        }
        
        .bot-message {
            background: white;
            border: 1px solid #e5e7eb;
            align-self: flex-start;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .user-message {
            background: #1e40af;
            color: white;
            align-self: flex-end;
            margin-left: auto;
        }
        
        .chatbot-input {
            display: flex;
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            background: white;
        }
        
        .chatbot-input input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            outline: none;
            transition: all 0.2s ease;
        }
        
        .chatbot-input input:focus {
            border-color: #1e40af;
        }
        
        .chatbot-input button {
            margin-left: 8px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .chatbot-input button:hover {
            background: #1e3a8a;
        }
        
        .chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: #1e40af;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
            z-index: 90;
            transition: all 0.3s ease;
        }
        
        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
        }
        
        .chatbot-toggle i {
            font-size: 24px;
        }
        
        .typing-indicator {
            display: flex;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            width: fit-content;
            margin-bottom: 12px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
        
        @media (min-width: 640px) {
            .bottom-nav { display: none !important; }
            .content-wrap { padding-bottom: 0; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="font-sans antialiased">
    <!-- Desktop Navigation -->
    <nav class="bg-white shadow-lg hidden sm:block sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-8">
                    <div class="flex-shrink-0">
                        <span class="text-primary text-xl font-bold">Ethical<span class="text-accent">eLearn</span></span>
                    </div>
                    <div class="hidden sm:flex sm:space-x-8">
                        <a href="#home" class="border-primary text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition duration-300">Home</a>
                        <div class="relative dropdown">
                            <button class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium transition duration-300">
                                Compliance Training
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div class="absolute z-10 left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 dropdown-menu hidden">
                                <div class="py-1">
                                    <a href="#anti-corruption" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Anti-Corruption & Bribery</a>
                                    <a href="#data-protection" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Data Protection & Privacy</a>
                                    <a href="#harassment" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Workplace Harassment Prevention</a>
                                    <a href="#aml" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Anti-Money Laundering</a>
                                    <a href="#competition-law" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Competition Law Compliance</a>
                                </div>
                            </div>
                        </div>
                        <div class="relative dropdown">
                            <button class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium transition duration-300">
                                Whistleblower Resources
                                <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div class="absolute z-10 left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 dropdown-menu hidden">
                                <div class="py-1">
                                    <a href="#whistleblower-rights" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Whistleblower Protection Laws</a>
                                    <a href="#reporting-channels" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Anonymous Reporting Channels</a>
                                    <a href="#retaliation" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Retaliation Prevention</a>
                                    <a href="#case-studies" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-200">Case Studies & Best Practices</a>
                                </div>
                            </div>
                        </div>
                        <a href="#organizations" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium transition duration-300">For Organizations</a>
                        <a href="#about" class="text-gray-500 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium transition duration-300">About Us</a>
                    </div>
                </div>
                <div class="hidden sm:flex sm:items-center space-x-4">
                    <a href="login.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition duration-300">Login</a>
                    <a href="#demo" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition duration-300 transform hover:scale-105">Request Demo</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation (Bottom) -->
    <div class="bottom-nav" id="bottomNav">
        <a href="#home" class="nav-item active" data-target="home">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span>Home</span>
        </a>
        <a href="#courses" class="nav-item" data-target="courses">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
            </svg>
            <span>Courses</span>
        </a>
        <a href="#demo" class="nav-item" data-target="demo">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            <span>Demo</span>
        </a>
        <a href="login.php" class="nav-item" data-target="login">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            <span>Login</span>
        </a>
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrap">
        <!-- Hero Section -->
        <section id="home" class="hero-bg relative overflow-hidden">
            <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8 animate__animated animate__fadeIn">
                <div class="text-center">
                    <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl md:text-6xl animate__animated animate__fadeInDown">
                        <span class="block">Ethical Compliance Training</span>
                        <span class="block text-accent">and Whistleblower Protection</span>
                    </h1>
                    <p class="mt-3 max-w-md mx-auto text-base text-gray-300 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl animate__animated animate__fadeIn animate__delay-1s">
                        Award-winning eLearning solutions for corporate compliance, anti-corruption training, and whistleblower protection programs that meet global regulatory standards.
                    </p>
                    <div class="mt-10 sm:flex sm:justify-center space-x-3 animate__animated animate__fadeIn animate__delay-1s">
                        <div class="rounded-md shadow">
                            <a href="#courses" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-accent hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition duration-300 transform hover:scale-105">
                                Explore Courses
                            </a>
                        </div>
                        <div class="rounded-md shadow">
                            <a href="#organizations" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-success hover:bg-green-700 md:py-4 md:text-lg md:px-10 transition duration-300 transform hover:scale-105">
                                For Organizations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust Indicators -->
        <div class="bg-gray-50 py-8 animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Trusted by compliance professionals at</p>
                    <div class="mt-6 grid grid-cols-2 gap-8 md:grid-cols-6">
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=Fortune+500" alt="Fortune 500">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=Global+Banks" alt="Global Banks">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=Healthcare" alt="Healthcare">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=Tech+Giants" alt="Tech Giants">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=Government" alt="Government">
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <img class="h-12" src="https://via.placeholder.com/120x48?text=NGOs" alt="NGOs">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Features -->
        <section id="courses" class="py-12 bg-white animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-accent font-semibold tracking-wide uppercase">Compliance Solutions</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        Comprehensive Ethics & Compliance Training
                    </p>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                        Our platform delivers engaging, up-to-date training that meets global regulatory requirements and fosters ethical workplace cultures.
                    </p>
                </div>

                <div class="mt-10">
                    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Feature 1 -->
                        <div id="whistleblower-training" class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Whistleblower Protection</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Comprehensive training on whistleblower rights, anonymous reporting channels, and retaliation prevention aligned with EU Whistleblower Directive and global standards.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#whistleblower-rights" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Feature 2 -->
                        <div id="anti-corruption" class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Anti-Corruption & Bribery</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    FCPA, UK Bribery Act, and global anti-corruption compliance training with real-world scenarios and risk assessment tools.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div id="data-protection" class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Data Protection & Privacy</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    GDPR, CCPA, and global data privacy compliance training with practical guidance on data handling and breach reporting.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Feature 4 -->
                        <div id="harassment" class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Workplace Harassment Prevention</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Interactive training on identifying, preventing, and reporting workplace harassment, discrimination, and microaggressions.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Feature 5 -->
                        <div class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Code of Conduct Training</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Customizable code of conduct training that aligns with your organization's values and ethical business practices.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Feature 6 -->
                        <div id="aml" class="feature-card transition duration-500 ease-in-out rounded-lg bg-white p-6 shadow-md border border-gray-100">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-5">
                                <h3 class="text-lg font-medium text-gray-900">Anti-Money Laundering</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    AML/CFT compliance training covering customer due diligence, suspicious activity reporting, and regulatory requirements.
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                    Learn more <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Compliance Standards Section -->
        <div class="compliance-bg py-16 text-white animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-accent font-semibold tracking-wide uppercase">Global Compliance</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight sm:text-4xl">
                        Meeting International Regulatory Standards
                    </p>
                    <p class="mt-4 max-w-2xl text-xl lg:mx-auto">
                        Our training programs are designed to meet the requirements of key global compliance frameworks and regulations.
                    </p>
                </div>

                <div class="mt-10">
                    <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                        <div class="col-span-1 flex flex-col items-center p-4 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20 transition duration-300">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-white text-primary mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium">EU Whistleblower Directive</h3>
                        </div>
                        
                        <div class="col-span-1 flex flex-col items-center p-4 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20 transition duration-300">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-white text-primary mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium">GDPR</h3>
                        </div>
                        
                        <div class="col-span-1 flex flex-col items-center p-4 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20 transition duration-300">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-white text-primary mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium">FCPA</h3>
                        </div>
                        
                        <div class="col-span-1 flex flex-col items-center p-4 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20 transition duration-300">
                            <div class="flex items-center justify-center h-12 w-12 rounded-md bg-white text-primary mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium">SOX</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Whistleblower Resources -->
        <section id="whistleblower-rights" class="py-16 bg-gray-50 animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-accent font-semibold tracking-wide uppercase">Whistleblower Protection</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        Resources for Whistleblowers
                    </p>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                        Essential information and guidance for individuals considering reporting misconduct.
                    </p>
                </div>

                <div class="mt-10">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Resource 1 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Know Your Rights</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Understand legal protections for whistleblowers under various jurisdictions.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Resource 2 -->
                        <div id="reporting-channels" class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Reporting Options</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Explore internal and external reporting channels and their differences.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Resource 3 -->
                        <div id="retaliation" class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Retaliation Prevention</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Recognize signs of retaliation and learn how to protect yourself.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Resource 4 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Documenting Evidence</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Best practices for securely documenting and preserving evidence of misconduct.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Resource 5 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Support Networks</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Connect with organizations that provide legal and emotional support to whistleblowers.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Resource 6 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg transition duration-300 hover:shadow-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-primary rounded-md p-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-5 w-0 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">Anonymous Reporting</h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            How to make secure, anonymous reports while protecting your identity.
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="#" class="text-sm font-medium text-accent hover:text-secondary transition duration-200">
                                        Learn more <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- For Organizations -->
        <section id="organizations" class="py-16 bg-white animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-2 lg:gap-8">
                    <div class="animate__animated animate__fadeInLeft">
                        <h2 class="text-base text-accent font-semibold tracking-wide uppercase">For Organizations</h2>
                        <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                            Building Ethical Workplace Cultures
                        </p>
                        <p class="mt-4 text-lg text-gray-500">
                            Our comprehensive solutions help organizations establish robust compliance programs, effective whistleblower protection systems, and ethical workplace cultures.
                        </p>
                        
                        <div class="mt-8 space-y-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary rounded-md p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-base text-gray-700">
                                        <strong>Customizable Training Programs</strong> tailored to your industry and specific compliance needs
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary rounded-md p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-base text-gray-700">
                                        <strong>Whistleblower Protection Systems</strong> including secure reporting channels and case management
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary rounded-md p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-base text-gray-700">
                                        <strong>Compliance Analytics Dashboard</strong> to track training completion and identify risk areas
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary rounded-md p-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-base text-gray-700">
                                        <strong>Policy Development Support</strong> for codes of conduct and whistleblower protection policies
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="demo" class="mt-12 lg:mt-0 animate__animated animate__fadeInRight">
                        <div class="bg-gray-50 rounded-lg shadow-lg overflow-hidden transition duration-300 hover:shadow-xl">
                            <div class="px-6 py-8 sm:p-10">
                                <h3 class="text-2xl font-bold text-gray-900">Request a Demo</h3>
                                <p class="mt-2 text-gray-600">
                                    See how our platform can help your organization meet compliance requirements and foster an ethical workplace culture.
                                </p>
                                
                                <form class="mt-8 space-y-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                        <input type="text" id="name" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary transition duration-200">
                                    </div>
                                    
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" id="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary transition duration-200">
                                    </div>
                                    
                                    <div>
                                        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                                        <input type="text" id="company" name="company" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary transition duration-200">
                                    </div>
                                    
                                    <div>
                                        <label for="employees" class="block text-sm font-medium text-gray-700">Number of Employees</label>
                                        <select id="employees" name="employees" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary transition duration-200">
                                            <option>1-50</option>
                                            <option>51-200</option>
                                            <option>201-500</option>
                                            <option>501-1000</option>
                                            <option>1000+</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition duration-300 transform hover:scale-105">
                                            Request Demo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <div class="bg-gray-50 py-16 animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-accent font-semibold tracking-wide uppercase">Trusted Worldwide</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        What Our Clients Say
                    </p>
                </div>

                <div class="mt-10">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Testimonial 1 -->
                        <div class="bg-white p-6 rounded-lg shadow transition duration-300 hover:shadow-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">Sarah Johnson</h3>
                                    <p class="text-gray-500">Chief Compliance Officer</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-600">
                                    "The whistleblower training program helped us establish a speak-up culture while meeting EU Whistleblower Directive requirements. Our reporting rates increased by 40% in the first quarter."
                                </p>
                            </div>
                            <div class="mt-4 flex items-center">
                                <div class="flex-shrink-0 text-yellow-400">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div class="ml-1 text-gray-500">5.0</div>
                            </div>
                        </div>

                        <!-- Testimonial 2 -->
                        <div class="bg-white p-6 rounded-lg shadow transition duration-300 hover:shadow-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">Michael Chen</h3>
                                    <p class="text-gray-500">Head of Legal</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-600">
                                    "The anti-corruption training modules saved us countless hours of development time. The content is comprehensive, engaging, and updated with the latest FCPA guidance."
                                </p>
                            </div>
                            <div class="mt-4 flex items-center">
                                <div class="flex-shrink-0 text-yellow-400">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div class="ml-1 text-gray-500">4.8</div>
                            </div>
                        </div>

                        <!-- Testimonial 3 -->
                        <div class="bg-white p-6 rounded-lg shadow transition duration-300 hover:shadow-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-medium text-gray-900">David Müller</h3>
                                    <p class="text-gray-500">Data Protection Officer</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-gray-600">
                                    "Our GDPR compliance training completion rates improved from 65% to 98% after switching to Ethical eLearn. The mobile-friendly format made all the difference for our field staff."
                                </p>
                            </div>
                            <div class="mt-4 flex items-center">
                                <div class="flex-shrink-0 text-yellow-400">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div class="ml-1 text-gray-500">4.9</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="bg-primary animate__animated animate__fadeIn">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    <span class="block">Ready to strengthen your compliance program?</span>
                    <span class="block text-accent">Start your free trial today.</span>
                </h2>
                <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
                    <div class="inline-flex rounded-md shadow">
                        <a href="#" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 transition duration-300 transform hover:scale-105">
                            Get started
                        </a>
                    </div>
                    <div class="ml-3 inline-flex rounded-md shadow">
                        <a href="#demo" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-secondary transition duration-300 transform hover:scale-105">
                            Contact sales
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer id="about" class="bg-gray-800 animate__animated animate__fadeIn">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Training</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#whistleblower-training" class="text-base text-gray-300 hover:text-white transition duration-200">Whistleblower Protection</a></li>
                        <li><a href="#anti-corruption" class="text-base text-gray-300 hover:text-white transition duration-200">Anti-Corruption</a></li>
                        <li><a href="#data-protection" class="text-base text-gray-300 hover:text-white transition duration-200">Data Privacy</a></li>
                        <li><a href="#harassment" class="text-base text-gray-300 hover:text-white transition duration-200">Workplace Harassment</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Resources</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Compliance Blog</a></li>
                        <li><a href="#case-studies" class="text-base text-gray-300 hover:text-white transition duration-200">Case Studies</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">White Papers</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Webinars</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Company</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#about" class="text-base text-gray-300 hover:text-white transition duration-200">About Us</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Leadership</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Careers</a></li>
                        <li><a href="#demo" class="text-base text-gray-300 hover:text-white transition duration-200">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase">Legal</h3>
                    <ul class="mt-4 space-y-4">
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Privacy Policy</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Terms of Service</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">Cookie Policy</a></li>
                        <li><a href="#" class="text-base text-gray-300 hover:text-white transition duration-200">GDPR Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 border-t border-gray-700 pt-8 md:flex md:items-center md:justify-between">
                <div class="flex space-x-6 md:order-2">
                    <a href="#" class="text-gray-400 hover:text-gray-300 transition duration-200">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    
                    <a href="#" class="text-gray-400 hover:text-gray-300 transition duration-200">
                        <span class="sr-only">LinkedIn</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    
                    <a href="#" class="text-gray-400 hover:text-gray-300 transition duration-200">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                </div>
                <p class="mt-8 text-base text-gray-400 md:mt-0 md:order-1">
                    &copy; 2023 Ethical eLearning Platform. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Whistleblower Hotline (Fixed at bottom right) -->
 

    <!-- Chatbot Toggle Button -->
    <div class="chatbot-toggle animate__animated animate__fadeInUp animate__delay-1s" id="chatbotToggle">
        <i class="fas fa-comment-dots"></i>
    </div>

    <!-- Chatbot Container -->
    <div class="chatbot-container" id="chatbotContainer">
        <div class="chatbot-header">
            <h3 class="text-lg font-semibold">Ethics Compliance Assistant</h3>
            <button id="closeChatbot" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="message bot-message">
                Hello! I'm your Ethics Compliance Assistant. How can I help you today?
            </div>
            <div class="message bot-message">
                You can ask me about:
                <ul class="list-disc pl-5 mt-2">
                    <li>Whistleblower protections</li>
                    <li>Compliance training options</li>
                    <li>Reporting procedures</li>
                    <li>Ethical workplace practices</li>
                </ul>
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Type your question..." class="flex-1">
            <button id="sendMessage" class="bg-primary hover:bg-secondary">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        // Show/hide bottom nav based on screen size
        function updateBottomNav() {
            const bottomNav = document.getElementById('bottomNav');
            bottomNav.classList.toggle('active', window.innerWidth < 640);
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    // Update active state for mobile nav
                    if(window.innerWidth < 640) {
                        document.querySelectorAll('.nav-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        document.querySelector(`.nav-item[href="${targetId}"]`).classList.add('active');
                    }
                    
                    // Scroll to target
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Chatbot functionality
        const chatbotToggle = document.getElementById('chatbotToggle');
        const chatbotContainer = document.getElementById('chatbotContainer');
        const closeChatbot = document.getElementById('closeChatbot');
        const chatbotMessages = document.getElementById('chatbotMessages');
        const chatbotInput = document.getElementById('chatbotInput');
        const sendMessage = document.getElementById('sendMessage');

        chatbotToggle.addEventListener('click', () => {
            chatbotContainer.classList.toggle('open');
        });

        closeChatbot.addEventListener('click', () => {
            chatbotContainer.classList.remove('open');
        });

        function addBotMessage(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message bot-message';
            messageDiv.textContent = message;
            chatbotMessages.appendChild(messageDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        function addUserMessage(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message user-message';
            messageDiv.textContent = message;
            chatbotMessages.appendChild(messageDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }

        function showTypingIndicator() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            chatbotMessages.appendChild(typingDiv);
            chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            return typingDiv;
        }

        function removeTypingIndicator(indicator) {
            indicator.remove();
        }

        function getBotResponse(userMessage) {
            const lowerMessage = userMessage.toLowerCase();
            
            if(lowerMessage.includes('whistleblower') || lowerMessage.includes('report')) {
                return "Our whistleblower protection program ensures anonymous reporting channels and protection against retaliation. You can report concerns through our secure portal or hotline.";
            } else if(lowerMessage.includes('training') || lowerMessage.includes('course')) {
                return "We offer compliance training in anti-corruption, data privacy, workplace harassment prevention, and more. All courses are available online with completion certificates.";
            } else if(lowerMessage.includes('policy') || lowerMessage.includes('code of conduct')) {
                return "Our Code of Conduct outlines expected behaviors and ethical standards for all employees. It covers topics like conflicts of interest, gifts, and proper use of company resources.";
            } else if(lowerMessage.includes('anonymous') || lowerMessage.includes('confidential')) {
                return "Yes, all reports can be made anonymously through our third-party managed hotline. Your identity will be protected if you choose to disclose it.";
            } else if(lowerMessage.includes('legal') || lowerMessage.includes('law')) {
                return "Our programs comply with all relevant regulations including the EU Whistleblower Directive, FCPA, UK Bribery Act, GDPR, and other global compliance standards.";
            } else {
                const responses = [
                    "I'm not sure I understand. Could you rephrase your question?",
                    "I can help with questions about whistleblower protections, compliance training, and ethical workplace practices.",
                    "For more specific questions, you might want to contact our compliance team directly.",
                    "That's an interesting question. Let me connect you with more detailed resources."
                ];
                return responses[Math.floor(Math.random() * responses.length)];
            }
        }

        function handleUserMessage() {
            const message = chatbotInput.value.trim();
            if(message === '') return;
            
            addUserMessage(message);
            chatbotInput.value = '';
            
            const typingIndicator = showTypingIndicator();
            
            setTimeout(() => {
                removeTypingIndicator(typingIndicator);
                const response = getBotResponse(message);
                addBotMessage(response);
            }, 1500);
        }

        sendMessage.addEventListener('click', handleUserMessage);
        
        chatbotInput.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') {
                handleUserMessage();
            }
        });

        // Initialize and add resize listener
        document.addEventListener('DOMContentLoaded', () => {
            updateBottomNav();
            window.addEventListener('resize', updateBottomNav);

            // Animate elements when they come into view
            const animateOnScroll = () => {
                const elements = document.querySelectorAll('.animate__animated');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.2;
                    
                    if(elementPosition < screenPosition) {
                        const animation = element.getAttribute('data-animation');
                        element.classList.add(animation);
                    }
                });
            };
            
            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll(); // Run once on load
        });
    </script>
</body>
</html>