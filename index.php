<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'connecting_fIle/config.php'?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Connect | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .sidebar {
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation Bar -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                      
                    </div>
                    <div class="hidden md:ml-6 md:flex md:space-x-8">

                    </div>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex md:items-center space-x-4">
                       <button class="bg-white text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-ticket-alt mr-1"></i> IT Ticket
                        </button>
                      <button 
    onclick="window.location.href='auth/login.php';"
    class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium flex items-center">
    <i class="fas fa-sign-in-alt mr-2"></i> Login
</button>

                    </div>
                    <div class="md:hidden flex items-center">
                        <button id="mobileMenuToggle" class="text-gray-500 hover:text-gray-700 p-2">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
       <div
  id="mobileMenu"
  class="md:hidden hidden transition-transform duration-300 ease-in-out transform translate-y-full"
>
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="flex items-center px-4 space-x-3">
                        <button class="bg-white text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-life-ring mr-1"></i> Help
                        </button>
                        <button class="bg-white text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-ticket-alt mr-1"></i> IT Ticket
                        </button>
                        <button class="bg-indigo-600 text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome to LMS</h1>
            <p class="text-gray-600 mt-2">Your one-stop portal for all HR services and employee resources</p>
        </div>

        <!-- CEO Message Section -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8 card-hover">
            <div class="md:flex">
                <div class="md:flex-shrink-0 md:w-1/4 bg-gradient-to-r from-indigo-500 to-purple-600 p-6 flex items-center justify-center">
                    <div class="text-center text-white">
                       
                        <h3 class="mt-4 text-xl font-bold">Test</h3>
                        <p class="text-indigo-100">Name of the CEO </p>
                    </div>
                </div>
                <div class="p-8 md:w-3/4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-gray-900">Message from Our CEO</h2>
                        <span class="text-sm text-gray-500">Oct 1, 2025</span>
                    </div>
                    <div class="prose max-w-none text-gray-700">
                        <p class="mb-4">Team, I'm excited to share our vision for the upcoming quarter. Our focus will be on employee growth, innovation, and strengthening our company culture.</p>
                        <p class="mb-4">We're launching several new initiatives including a mentorship program, expanded learning resources, and wellness workshops. Your feedback has been invaluable in shaping these programs.</p>
                        <p>I'm continually inspired by your dedication and the incredible work you do every day. Together, we'll achieve new heights of success.</p>
                    </div>
                    <div class="mt-6 flex items-center">
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Celebrations Section -->
     <!-- Celebrations & Recognitions -->
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Celebrations & Recognitions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Birthdays -->
        <div class="bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl shadow-md text-white overflow-hidden card-hover">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-birthday-cake text-2xl mr-3"></i>
                    <h3 class="text-xl font-bold">Birthdays</h3>
                </div>
                <div class="space-y-4">
                <?php
                $currentMonth = date('m');
                $nextMonth = date('m', strtotime('+1 month'));
                $sql = "
                    SELECT name, date_of_birth 
                    FROM users 
                    WHERE date_of_birth IS NOT NULL
                    AND (MONTH(date_of_birth) = $currentMonth OR MONTH(date_of_birth) = $nextMonth)
                    ORDER BY 
                        MONTH(date_of_birth) = $currentMonth DESC, 
                        MONTH(date_of_birth), 
                        DAY(date_of_birth)
                ";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $name = htmlspecialchars($row['name']);
                        $date = htmlspecialchars(date('d M', strtotime($row['date_of_birth'])));
                        $img = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=F472B6&color=fff&size=128";
                        echo '<div class="flex items-center">';
                        echo '<img class="h-12 w-12 rounded-full object-cover mr-3 border-2 border-white" src="' . $img . '" alt="'.$name.'">';
                        echo '<div>';
                        echo '<p class="font-medium">'.$name.'</p>';
                        echo '<p class="text-sm opacity-90">'.$date.'</p>';
                        echo '</div></div>';
                    }
                } else {
                    echo '<div class="font-medium text-white">No birthdays this or next month.</div>';
                }
                ?>
                </div>
            </div>
        </div>

      
    </div>
</div>


      
<div class="mb-8">
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Holiday List</h2>
    <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium">View All News</a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
   <?php
$today = date('Y-m-d');
$result = $conn->query("SELECT holiday_date, holiday_name, day_name FROM holiday ORDER BY holiday_date ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $holidayDate = htmlspecialchars($row['holiday_date']);
        $holidayName = htmlspecialchars($row['holiday_name']);
        $dayName = htmlspecialchars($row['day_name']);

        $isPast = ($holidayDate < $today);

        echo '<div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center card-hover transition-all duration-300 hover:shadow-lg">';
        if ($isPast) {
            // Past Holiday
            echo '<span class="text-xl font-bold text-gray-400 mb-1 text-center line-through">' . $holidayName . '</span>';
            echo '<span class="text-base text-gray-400 font-semibold mb-2 line-through">' . $holidayDate . '</span>';
            echo '<span class="text-sm text-gray-300 font-medium line-through">' . $dayName . '</span>';
        } else {
            // Today or Upcoming Holiday
            echo '<span class="text-xl font-bold text-green-700 mb-1 text-center">' . $holidayName . '</span>';
            echo '<span class="text-base text-green-800 font-semibold mb-2">' . $holidayDate . '</span>';
            echo '<span class="text-sm text-green-600 font-medium">' . $dayName . '</span>';
        }
        echo '</div>';
    }
} else {
    echo '<div class="col-span-3 text-center text-red-600">No holidays found.</div>';
}
?>

  </div>
</div>


            </div>
        </div>
        
    </div>
    
    

    <!-- Footer -->
 <footer class="bg-gray-800 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">HR Connect</h3>
                <p class="text-gray-400 text-sm">Your one-stop portal for all HR needs, resources, and employee services.</p>
            </div>
            <div>
                <h4 class="font-medium mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white transition">Careers</a></li>
                    <li><a href="#" class="hover:text-white transition">Help Desk</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium mb-4">Contact Us</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li class="flex items-center">
                        <i class="fas fa-envelope mr-2"></i>
                        <span>hr@Demo.com</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-phone mr-2"></i>
                        <span>+919969802679</span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-building mr-2"></i>
                        <span>Centrum IT Park</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-sm text-gray-400 text-center">
            <p>&copy; 2025 LMS Portal. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Version Watermark -->
<div class="fixed bottom-2 right-4 pointer-events-none z-50 select-none"
     style="opacity:0.16; font-size:2rem; font-weight:bold; letter-spacing:0.17em; color:#fff;">
  V1.2
</div>


<style>
  /* Optional: customize transition if needed */
</style>

<script>
  const mobileMenu = document.getElementById("mobileMenu");
  const menuToggle = document.getElementById("mobileMenuToggle");

  function toggleMenu() {
    if (mobileMenu.classList.contains("translate-y-full")) {
      // When closed, remove the translate to slide up
      mobileMenu.classList.remove("translate-y-full", "opacity-100");
      mobileMenu.classList.add("hidden");
    } else {
      // When open, remove hidden, slide in from bottom
      mobileMenu.classList.remove("hidden");
      // Trigger reflow to ensure transition
      void mobileMenu.offsetWidth;
      mobileMenu.classList.add("translate-y-full");
    }
  }

  document
    .getElementById("mobileMenuToggle")
    .addEventListener("click", function () {
      toggleMenu();
    });

  // Close when clicking outside
  document.addEventListener("click", function (event) {
    if (window.innerWidth <= 768) {
      if (
        !mobileMenu.contains(event.target) &&
        !menuToggle.contains(event.target) &&
        !mobileMenu.classList.contains("hidden")
      ) {
        mobileMenu.classList.remove("translate-y-full");
        mobileMenu.classList.add("hidden");
      }
    }
  });
</script>

</body>
</html>