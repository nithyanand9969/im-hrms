<script>
  function toggleModal() {
    const modal = document.getElementById("leaveModal");
    modal.classList.toggle("hidden");

    // Redirect to user-dashboard.php after closing the modal
    if (modal.classList.contains("hidden")) {
      window.location.href = "user-dashboard.php";
    }
  }

  // Show modal if URL has modal=1
  window.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get("modal") === "1") {
      toggleModal();
    }
  });

  function toggleLeaveType(isHalfDay) {
    const halfDayType = document.getElementById('half-day-type');
    const toDateContainer = document.getElementById('to-date-container');
    const toDateInput = document.getElementById('to_date');

    if (isHalfDay) {
      halfDayType.classList.remove('hidden');
      toDateContainer.classList.add('hidden');
      toDateInput.value = '';
      toDateInput.removeAttribute('required');
    } else {
      halfDayType.classList.add('hidden');
      toDateContainer.classList.remove('hidden');
      toDateInput.setAttribute('required', 'required');
    }
  }
    </main>
  </div>
  
  <script>
    // Activate current page in sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active-nav');
            }
        });
    });
  </script>
      <script>
        function toggleLeaveType(isHalfDay) {
            document.getElementById('half-day-type').classList.toggle('hidden', !isHalfDay);
            document.getElementById('to-date-container').classList.toggle('hidden', isHalfDay);
            
            // Set default half day type if showing
            if (isHalfDay) {
                document.querySelector('select[name="half_day_type"]').value = "First Half";
            }
        }

        function toggleModal() {
            const modal = document.getElementById('leaveModal');
            modal.classList.toggle('hidden');
        }

        // Set today as default date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('from_date').value = today;
            document.getElementById('to_date').value = today;
            
            // If modal=1 is in URL, show modal on load
            const params = new URLSearchParams(window.location.search);
            if (params.get('modal') === '1') {
                toggleModal();
            }
        });
        
        $('.nav-item').click(function() {
    $('.nav-item').removeClass('active');
    $(this).addClass('active');
    const page = $(this).data('page');
    $('#main-content > section').addClass('hidden');
    $(`#${page}`).removeClass('hidden');
});
    </script>
</body>
</html>
</script>
